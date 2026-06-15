<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\PaymentsModel;
use App\Models\DepositsModel;
use App\Models\RetiresModel;
use App\Models\TransfersModel;
use App\Models\ContactsModel;
use App\Models\NotificationsModel;
use App\Models\ReferralsModel;
use App\Models\RoulettesModel;
use App\Models\GamesModel;
use App\Models\CartonsModel;
use App\Models\AwardsModel;
use App\Models\SingsModel;
use App\Models\GameRoomsModel;
use App\Models\BoardsModel;
use CodeIgniter\Controller;

class Users extends Controller {
    public function __construct() {
        helper(['form', 'url', 'cookie', 'text']);
        session();
    }

    public function index() {
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        
        if (!session()->get('logged_in')) {
            return redirect()->to('/signin');
        }
        
        $game = $modelGames->getGameByDate(date('Y-m-d'));
    
        if ($game) {
            $cartons = $modelCartons->getCartonsByUser(session()->get('id'), $game['id']);
            
            if (!empty($cartons)) {
                return redirect()->to('/games');
            }
        }

        $data = [
            'page' => [
                'title' => 'Inicio'
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('dashboard/index') 
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }

    public function add($userId = null) {
        $model = new UsersModel();

        $data = [];
        
        if ($userId) {
            $data['userData'] = $model->find($userId);
            $data['isUpdate'] = true;
            
            if (!$data['userData']) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('Usuario no encontrado');
            }
        } else {
            $data['userData'] = null;
            $data['isUpdate'] = false;
        }
        
        return view('users/modalUser', $data);
    }

    public function deleteUser() {
        $model = new UsersModel();

        $userId = $this->request->getPost('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('user id required')
            ]);
        }
        
        $user = $model->find($userId);
        
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('user not found')
            ]);
        }
        
        // Marcar como eliminado en lugar de eliminar físicamente
        if ($model->update($userId, ['deleted' => 1, 'status' => 0])) {
            return $this->response->setJSON([
                'success' => true,
                'message' => translate('user deleted successfully')
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'error' => translate('error deleting user')
        ]);
    }

    public function banUser() {
        $model = new UsersModel();

        $userId = $this->request->getPost('user_id');
        $status = $this->request->getPost('status'); // 0 = ban, 1 = unban
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('user id required')
            ]);
        }
        
        $user = $model->find($userId);
        
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('user not found')
            ]);
        }
        
        if ($model->update($userId, ['status' => $status])) {
            $message = $status == 0 ? translate('user banned successfully') : translate('user unbanned successfully');
            return $this->response->setJSON([
                'success' => true,
                'message' => $message
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'error' => translate('error updating user status')
        ]);
    }

    public function getUserDetails($userId) {
        $model = new UsersModel();
        $modelCartons = new CartonsModel();
        $modelDeposits = new DepositsModel();
        $modelRetires = new RetiresModel();
        $modelRoulettes = new RoulettesModel();

        $user = $model->find($userId);
        
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('user not found')
            ]);
        }
        
        // Estadísticas detalladas del usuario
        $stats = [
            'total_cartons' => $modelCartons->where('user', $userId)->countAllResults(),
            'total_deposits' => $modelDeposits->where('user', $userId)->where('status', 1)->selectSum('amount')->get()->getRow()->amount ?? 0,
            'total_retires' => $modelRetires->where('user', $userId)->where('status', 1)->selectSum('amount')->get()->getRow()->amount ?? 0,
            'total_roulettes' => $modelRoulettes->where('user', $userId)->where('status', 1)->selectSum('amount')->get()->getRow()->amount ?? 0,
            'last_activity' => $this->getLastActivity($userId)
        ];
        
        return $this->response->setJSON([
            'success' => true,
            'user' => $user,
            'stats' => $stats
        ]);
    }

    private function getUsersStats() {
        $stats = [];
        
        // Total de usuarios
        $stats['total_users'] = $this->modelUsers->where('deleted', 0)->countAllResults();
        
        // Usuarios activos
        $stats['active_users'] = $this->modelUsers->where('status', 1)->where('deleted', 0)->countAllResults();
        
        // Usuarios baneados
        $stats['banned_users'] = $this->modelUsers->where('status', 0)->where('deleted', 0)->countAllResults();
        
        // Usuarios por grupo
        $stats['admin_users'] = $this->modelUsers->where('group', 1)->where('deleted', 0)->countAllResults();
        $stats['player_users'] = $this->modelUsers->where('group', 0)->where('deleted', 0)->countAllResults();
        
        // Total en wallets
        $stats['total_wallet'] = $this->modelUsers->where('deleted', 0)->selectSum('wallet')->get()->getRow()->wallet ?? 0;
        
        // Promedio por usuario
        $stats['avg_wallet'] = $stats['total_users'] > 0 ? $stats['total_wallet'] / $stats['total_users'] : 0;
        
        // Usuarios registrados hoy
        $stats['today_users'] = $this->modelUsers->where('DATE(created_at)', date('Y-m-d'))->where('deleted', 0)->countAllResults();
        
        // Usuarios registrados esta semana
        $stats['week_users'] = $this->modelUsers->where('created_at >=', date('Y-m-d', strtotime('-7 days')))->where('deleted', 0)->countAllResults();
        
        // Usuarios registrados este mes
        $stats['month_users'] = $this->modelUsers->where('created_at >=', date('Y-m-d', strtotime('-30 days')))->where('deleted', 0)->countAllResults();
        
        return $stats;
    }

    private function getLastActivity($userId) {
        $modelCartons = new CartonsModel();
        $modelDeposits = new DepositsModel();
        $modelRetires = new RetiresModel();
        $modelRoulettes = new RoulettesModel();

        // Buscar la última actividad del usuario en diferentes tablas
        $lastCarton = $modelCartons->where('user', $userId)->orderBy('created_at', 'DESC')->first();
        $lastDeposit = $modelDeposits->where('user', $userId)->orderBy('date', 'DESC')->first();
        $lastRetire = $modelRetires->where('user', $userId)->orderBy('created_at', 'DESC')->first();
        $lastRoulette = $modelRoulettes->where('user', $userId)->orderBy('created_at', 'DESC')->first();
        
        $activities = [];
        
        if ($lastCarton) $activities[] = $lastCarton['created_at'];
        if ($lastDeposit) $activities[] = $lastDeposit['date'];
        if ($lastRetire) $activities[] = $lastRetire['created_at'];
        if ($lastRoulette) $activities[] = $lastRoulette['created_at'];
        
        return !empty($activities) ? max($activities) : null;
    }

    public function userSubmit() {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        $model = new UsersModel();

        $userId = $this->request->getPost('user-id');
        $action = $this->request->getPost('user-action');

        $validationRules = [
            'firstname' => [
                'label' => translate('first name'),
                'rules' => 'required|min_length[3]'
            ],
            'lastname' => [
                'label' => translate('last name'),
                'rules' => 'required|min_length[3]'
            ],
            'document' => [
                'label' => translate('document'),
                'rules' => 'required|numeric|is_unique[users.document,id,' . $userId . ']'
            ],
            'username' => [
                'label' => translate('username'), 
                'rules' => 'required|min_length[3]|is_unique[users.username,id,' . $userId . ']'
            ],
            'phone' => [
                'label' => translate('phone'),  
                'rules' => 'required|numeric|is_unique[users.phone,id,' . $userId . ']'
            ],
            'email' => [
                'label' => translate('email'), 
                'rules' => 'required|valid_email|is_unique[users.email,id,' . $userId . ']'
            ]
        ];

        if (!$this->validate($validationRules)) {
            $errors = $this->validator->getErrors();
            $response = [
                'success' => false,
                'errors' => $errors
            ];
            return $this->response->setJSON($response);
        }
        
        $data = [
            'firstname' => $this->request->getPost('firstname'),
            'lastname' => $this->request->getPost('lastname'),
            'username' => $this->request->getPost('username'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone') ?? '',
            'document' => $this->request->getPost('document') ?? '',
            'bank' => $this->request->getPost('bank') ?? '',
            'account' => $this->request->getPost('account') ?? '',
            'wallet' => $this->request->getPost('wallet') ?? 0,
            'group' => $this->request->getPost('group'),
            'status' => $this->request->getPost('status'),
            'sounds' => $this->request->getPost('sounds') ?? 1,
            'narration' => $this->request->getPost('narration') ?? 1,
            'autodial' => $this->request->getPost('autodial') ?? 1,
            'roulette' => $this->request->getPost('roulette') ?? 1,
            'address_line' => $this->request->getPost('address_line') ?? '',
            'city' => $this->request->getPost('city') ?? '',
            'state' => $this->request->getPost('state') ?? '',
            'is_reseller' => (int) ($this->request->getPost('is_reseller') ?? 0),
        ];
        
        if ($action === 'add') {
            // Generar código único
            $lastUser = $model->orderBy('id', 'DESC')->first();
            $nextId = $lastUser ? $lastUser['id'] + 1 : 1;
            $data['code'] = 'BGC-A' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
            
            // Hash de la contraseña
            $data['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
            
            // Generar tokens
            $data['referred_code'] = strtoupper(substr(md5(uniqid()), 0, 8));
            $data['verification_token'] = md5(uniqid());
            
            if ($model->insert($data)) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => translate('user added successfully')
                ]);
            }
        } else {
            // Actualizar contraseña solo si se proporciona
            if (!empty($this->request->getPost('password'))) {
                $data['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
            }
            
            if ($model->update($userId, $data)) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => translate('user updated successfully')
                ]);
            }
        }
        
        return $this->response->setJSON([
            'success' => false,
            'message' => translate('error processing request')
        ]);
    }

    public function profile() {
        if (!session()->get('logged_in')) {
            return redirect()->to('/signin');
        }

        $model = new UsersModel();
        $modelContacts = new ContactsModel();

        $contacts = $modelContacts->findAll();

        $user = $model->find(session()->get('id'));

        $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');

        $data = [
            'page' => [
                'title' => translate('my profile')
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('users/profile', ['contacts' => $contacts, 'user' => $user, 'imagePath' => $imagePath])
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }

    public function profileStepSubmit() {
        $model = new UsersModel();

        $validationRules = [
            'firstname' => [
                'label' => translate('first name'),
                'rules' => 'required|min_length[3]'
            ],
            'lastname' => [
                'label' => translate('last name'),
                'rules' => 'required|min_length[3]'
            ],
            'document' => [
                'label' => translate('document'),
                'rules' => 'required|numeric|is_unique[users.document,id,' . session()->get('id') . ']'
            ]
        ];

        if (!$this->validate($validationRules)) {
            $errors = $this->validator->getErrors();
            $response = [
                'success' => false,
                'errors' => $errors
            ];
            return $this->response->setJSON($response);
        }

        $response = [
            'success' => true
        ];

        return $this->response->setJSON($response);
    }

    public function profileSubmit() {
        /*if (defined('IS_DEMO') && IS_DEMO === 1) {
            $response = [
                'success' => false,
                'error' => translate('this action is disabled in DEMO mode.')
            ];
            return $this->response->setJSON($response);
        }

        $userId = session()->get('id');
        if (in_array($userId, [1, 2, 3])) {
            $response = [
                'success' => false,
                'error' => translate('you cannot modify the information of DEMO users.')
            ];
            return $this->response->setJSON($response);
        }*/

        $model = new UsersModel();

        $user = $model->getUserById(session()->get('id'));
    
        $validationRules = [
            'firstname' => [
                'label' => translate('first name'),
                'rules' => 'required|min_length[3]'
            ],
            'lastname' => [
                'label' => translate('last name'),
                'rules' => 'required|min_length[3]'
            ],
            'document' => [
                'label' => translate('document'),
                'rules' => 'required|numeric|is_unique[users.document,id,' . session()->get('id') . ']'
            ],
            'username' => [
                'label' => translate('username'), 
                'rules' => 'required|min_length[3]|is_unique[users.username,id,' . session()->get('id') . ']'
            ],
            'phone' => [
                'label' => translate('phone'),  
                'rules' => 'required|numeric|is_unique[users.phone,id,' . session()->get('id') . ']'
            ],
            'email' => [
                'label' => translate('email'), 
                'rules' => 'required|valid_email|is_unique[users.email,id,' . session()->get('id') . ']'
            ]
        ];
    
        if (!$this->validate($validationRules)) {
            $errors = $this->validator->getErrors();
            $response = [
                'success' => false,
                'errors' => $errors 
            ];
            return $this->response->setJSON($response);
        }
    
        $data = [
            'firstname' => $this->request->getPost('firstname'),
            'lastname' => $this->request->getPost('lastname'),
            'document' => $this->request->getPost('document'),
            'username' => $this->request->getPost('username'),
            'phone' => $this->request->getPost('phone'),
            'email' => $this->request->getPost('email'),
            'address_line' => $this->request->getPost('address_line'),
            'city' => $this->request->getPost('city'),
            'state' => $this->request->getPost('state'),
        ];

        $profileImage = $this->request->getPost('image');

        if ($profileImage) {
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $profileImage));
            $fileName = uniqid() . '.png'; 
            
            $uploadPath = FCPATH . 'uploads/users/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true); 
            }

            file_put_contents($uploadPath . $fileName, $imageData);

            $data['image'] = $fileName; 
        }

        $model->update(session()->get('id'), $data);
        
        $sessionData = [
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'document' => $data['document'],
            'username' => $data['username'],
            'phone' => $data['phone'],
            'email' => $data['email']
        ];
            
        session()->set($sessionData);
        
        $response = [
            'success' => true
        ];
        
        return $this->response->setJSON($response);
    }

    public function password() {
        if (!session()->get('logged_in')) {
            return redirect()->to('/signin');
        }

        $model = new UsersModel();
        $modelContacts = new ContactsModel();

        $contacts = $modelContacts->findAll();

        $user = $model->find(session()->get('id'));

        $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');

        $data = [
            'page' => [
                'title' => translate('my profile')
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('users/password', ['contacts' => $contacts, 'user' => $user, 'imagePath' => $imagePath])
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }

    public function passwordSubmit() {
        /*if (defined('IS_DEMO') && IS_DEMO === 1) {
            $response = [
                'success' => false,
                'error' => translate('this action is disabled in DEMO mode.')
            ];
            return $this->response->setJSON($response);
        }

        $userId = session()->get('id');
        if (in_array($userId, [1, 2, 3])) {
            $response = [
                'success' => false,
                'error' => translate('you cannot modify the information of DEMO users.')
            ];
            return $this->response->setJSON($response);
        }*/

        $model = new UsersModel();

        $user = $model->getUserById(session()->get('id'));
    
        $validationRules = [
            'password_current' => [
                'label' => translate('current password'),
                'rules' => 'required'
            ],
            'password' => [
                'label' => translate('password'),
                'rules' => 'required|min_length[6]'
            ],
            'password_confirm' => [
                'label' => translate('confirm password'),
                'rules' => 'required|matches[password]'
            ]
        ];
    
        if (!$this->validate($validationRules)) {
            $errors = $this->validator->getErrors();
            $response = [
                'success' => false,
                'errors' => $errors
            ];
            return $this->response->setJSON($response);
        }

        $passwordCurrent = $this->request->getPost('password_current');
        
        if (!password_verify($passwordCurrent, $user['password'])) {
            $response = [
                'success' => false,
                'errors' => ['password_current' => translate('the current password is incorrect')]
            ];
            return $this->response->setJSON($response);
        }

        $newPassword = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);

        $model->update(session()->get('id'), ['password' => $newPassword]);
        
        $response = [
            'success' => true,
        ];
        
        return $this->response->setJSON($response);
    }

    public function referralCode() {
        $modelUsers = new UsersModel();
        $user = $modelUsers->find(session()->get('id'));
        
        if (!$user) {
            return $this->response->setJSON(['success' => false, 'error' => translate('user not found')]);
        }
        
        $data = site_url('signup/' . $user['referred_code']);

        require_once APPPATH . 'Libraries/phpqrcode/qrlib.php';
        
        ob_start();
        \QRcode::png($data, null, QR_ECLEVEL_M, 6, 2);
        $png = ob_get_clean();
        
        return $this->response->setContentType('image/png')->setBody($png);
    }

    public function referralsGet() { 
        $model       = new UsersModel();
        $modelGames  = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelAwards = new AwardsModel();

        $data['user'] = $model->find(session()->get('id'));

        $data['lastGame'] = $modelGames->orderBy('created_at', 'DESC')->first();

        if ($data['lastGame']) {
            $cartons = $modelCartons->where('game', $data['lastGame']['id'])->where('user !=', 0)->countAllResults();

            $accumulated = $cartons * $data['lastGame']['price'];
            $data['total'] = $accumulated - ($accumulated * systemGet('rateEarnings'));
        } else {
            $data['total'] = 0;
        }

        return view('users/referrals', $data);
    }

    public function exportRequiredFields()
    {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        $model = new UsersModel();
        $users = $model->where('group', 0)->orderBy('id', 'DESC')->findAll();

        $filename = 'users-required-fields-' . date('Ymd-His') . '.csv';
        $headers = [
            'ID', 'Codigo', 'Nombres', 'Apellidos', 'Usuario',
            'Documento', 'Telefono', 'Email', 'Direccion', 'Ciudad', 'Estado',
            'Punto de venta', 'Saldo total', 'Saldo recarga', 'Saldo retiro', 'Saldo bono', 'KYC'
        ];

        $fh = fopen('php://temp', 'w+');
        fputcsv($fh, $headers);

        foreach ($users as $user) {
            $user = wallet_service()->normalizeUser($user);
            fputcsv($fh, [
                $user['id'] ?? '',
                $user['code'] ?? '',
                $user['firstname'] ?? '',
                $user['lastname'] ?? '',
                $user['username'] ?? '',
                $user['document'] ?? '',
                $user['phone'] ?? '',
                $user['email'] ?? '',
                $user['address_line'] ?? '',
                $user['city'] ?? '',
                $user['state'] ?? '',
                ! empty($user['is_reseller']) ? 'Si' : 'No',
                wallet_total($user),
                $user['wallet_recharge'] ?? 0,
                $user['wallet_withdraw'] ?? 0,
                $user['wallet_bonus'] ?? 0,
                $user['kyc_status'] ?? 'pending',
            ]);
        }

        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }

    public function userNotifications() {
        $model = new UsersModel();
        $modelNotifications = new NotificationsModel();
        $modelGames = new GamesModel();
        $modelBoards = new BoardsModel();
        $modelCartons = new CartonsModel();
        $modelGameRooms = new GameRoomsModel();
        $modelAwards = new AwardsModel();
        $modelSings = new SingsModel();

        $user = $model->find(session()->get('id'));

        $notifications = $modelNotifications->where('user', $user['id'])->where('status', 0)->orderBy('created_at', 'DESC')->findAll();

        foreach ($notifications as &$notification) { 
            if (in_array($notification['type'], ['deposit', 'retire', 'transfer', 'payment', 'referred']) && $notification['type_id'] > 0) {
                $transactionData = $this->getTransactions($notification['type'], $notification['type_id']);
                if ($transactionData) {
                    $notification['transaction'] = $transactionData;
                }
            }
        }

        $response = [
            'notifications' => $notifications
        ];

        if (session()->get('group') == 0) {
            $games = $modelGames->where('status', 1)->findAll();

            $formattedGames = [];
            foreach ($games as $index => $game) { 
                $room = $modelGameRooms->where('id', $game['room'])->where('status', 1)->first();

                $cartons = $modelCartons->where('game', $game['id'])->where('user !=', 0)->countAllResults();
                $accumulated = $cartons * $game['price'];
                $gameAccumulated = $accumulated - ($accumulated * systemGet('rateEarnings'));

                $cartonsUser = $modelCartons->where('game', $game['id'])->where('user', session()->get('id'))->countAllResults();

                $formattedGames[] = [
                    'id'             => $game['id'],
                    'room'           => $room['name'],
                    'cartons'        => $cartonsUser,
                    'description'    => $game['description'],
                    'price'          => $game['price'],
                    'date'           => $game['date'],
                    'date_translate' => translate_date($game['date']),
                    'time'           => $game['time'],
                    'time_translate' => translate_time($game['time']),
                    'accumulated'    => number_format($gameAccumulated, 2),
                    'color'          => $this->getCardColor($index),
                    'label'          => $game['description'] . ' · ' . systemGet('currency') . ' ' . $game['price'] . ' · ' . translate_day($game['date'] . ' ' . $game['time'])
                ];
            }

            $response['games']  = $formattedGames;
            $response['wallet'] = wallet_summary_payload($user);
        }

        if (session()->get('group') == 1) {
            try {
                $games = $modelGames->findAll();
                $gameProgress = [];

                foreach ($games as $game) {
                    $numbers = $modelBoards->where('game', $game['id'])->countAllResults();
                    $players = $modelCartons->where('game', $game['id'])->where('user !=', 0)->select('user')->distinct()->countAllResults();
                    $SingsCount = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();
                    $AwardsCount = $modelAwards->where('game', $game['id'])->where('status', 1)->countAllResults();

                    $cartons = $modelCartons->where('game', $game['id'])->where('user !=', 0)->countAllResults();
                    $accumulated = $cartons * $game['price'];
                    $gameAccumulated = $accumulated - ($accumulated * systemGet('rateEarnings'));
                    
                    $percentage = ($numbers / 75) * 100;

                    if ($numbers == 0) {
                        $status = '<span class="badge bg-info">' . translate('UNSTARTED') . '</span>';
                    } elseif ($numbers >= 75 || $SingsCount >= $AwardsCount) {
                        $status = '<span class="badge bg-success">' . translate('FINISHED') . '</span>';
                    } elseif ($numbers > 0 && $numbers < 75) {
                        $status = '<span class="badge bg-primary">' . translate('INITIATED') . '</span>';
                    }
                    
                    $gameProgress[] = [
                        'game_id'        => $game['id'],
                        'numbers_called' => $numbers,
                        'status'         => $status,
                        'total'          => systemGet('currency') .' ' . number_format($gameAccumulated, 2),
                        'players'        => $players,
                        'total_numbers'  => 75,
                        'percentage'     => round($percentage, 1)
                    ];
                }

                $response['progress'] = $gameProgress;
                
            } catch (Exception $e) {
                // Log del error para debug
                log_message('error', 'Error en progress games: ' . $e->getMessage());
                $response['progress'] = []; // Array vacío en caso de error
            }
        }

        return $this->response->setJSON($response);
    }

    function getCardColor($index) {
        $colors = ['bingo-bg-primary', 'bingo-bg-success', 'bingo-bg-info', 'bingo-bg-warning', 'bingo-bg-danger', 'bingo-bg-secondary', 'bingo-bg-white', 'bingo-bg-dark', 'bingo-bg-orange', 'bingo-bg-purple'];
        return $colors[$index % count($colors)];
    }

    private function getTransactions($type, $typeId) {
        $modelUsers = new UsersModel();
        
        switch ($type) {
            case 'payment':
                $modelPayments = new PaymentsModel();
                $transaction = $modelPayments->find($typeId);
                if ($transaction) {
                    $user = $modelUsers->find($transaction['user']);
                    if ($transaction['type'] == 'award') {
                        $typePayment = translate('per award paid');
                    } else if ($transaction['type'] == 'referred') {
                        $typePayment = translate('per referred player');
                    }
                    return [
                        'id' => $transaction['id'],
                        'type' => 'payment',
                        'type_Tra' => translate('payment'),
                        'user_id' => $transaction['user'],
                        'user_name' => $user ? $user['firstname'] . ' ' . $user['lastname'] : 'N/A',
                        'user_code' => $user ? $user['code'] : 'N/A',
                        'bank' => $this->formatBankInfo('payment', $user, $typePayment),
                        'reference' => str_pad($transaction['id'], 4, '0', STR_PAD_LEFT),
                        'amount' => $transaction['amount'],
                        'date' => $transaction['created_at'],
                        'date_formatted' => date('d/m/Y', strtotime($transaction['created_at'])),
                        'status' => $transaction['status'],
                        'status_raw' => $transaction['status'],
                        'status_formatted' => $this->formatStatusPayment($transaction['status']),
                        'created_at' => date('d/m/Y', strtotime($transaction['created_at']))
                    ];
                }
                break;

            case 'deposit':
                $modelDeposits = new DepositsModel();
                $transaction = $modelDeposits->find($typeId);
                if ($transaction) {
                    $user = $modelUsers->find($transaction['user']);
                    return [
                        'id' => $transaction['id'],
                        'type' => 'deposit',
                        'type_Tra' => translate('deposit'),
                        'user_id' => $transaction['user'],
                        'user_name' => $user ? $user['firstname'] . ' ' . $user['lastname'] : 'N/A',
                        'user_code' => $user ? $user['code'] : 'N/A',
                        'bank' => $this->formatBankInfo('deposit', $user, $transaction['bank']),
                        'reference' => $transaction['reference'],
                        'amount' => $transaction['amount'],
                        'date' => $transaction['date'],
                        'date_formatted' => date('d/m/Y', strtotime($transaction['date'])),
                        'status' => $transaction['status'],
                        'status_raw' => $transaction['status'],
                        'status_formatted' => $this->formatStatusDeposit($transaction['status']),
                        'created_at' => date('d/m/Y', strtotime($transaction['created_at']))
                    ];
                }
                break;

            case 'retire':
                $modelRetires = new RetiresModel();
                $transaction = $modelRetires->find($typeId);
                if ($transaction) {
                    $user = $modelUsers->find($transaction['user']);
                    return [
                        'id' => $transaction['id'],
                        'type' => 'retire',
                        'type_Tra' => translate('retire'),
                        'user_id' => $transaction['user'],
                        'user_name' => $user ? $user['firstname'] . ' ' . $user['lastname'] : 'N/A',
                        'user_code' => $user ? $user['code'] : 'N/A',
                        'bank' => $this->formatBankInfo('retire', $user, $transaction['bank']),
                        'reference' => str_pad($transaction['id'], 4, '0', STR_PAD_LEFT),
                        'amount' => $transaction['amount'],
                        'date' => $transaction['created_at'],
                        'date_formatted' => date('d/m/Y', strtotime($transaction['created_at'])),
                        'status' => $transaction['status'],
                        'status_raw' => $transaction['status'],
                        'status_formatted' => $this->formatStatusRetire($transaction['status']),
                        'created_at' => date('d/m/Y', strtotime($transaction['created_at']))
                    ];
                }
                break;

            case 'transfer':
                $modelTransfers = new TransfersModel();
                $transaction = $modelTransfers->find($typeId);
                if ($transaction) {
                    $userFrom = $modelUsers->find($transaction['from']);
                    $userTo = $modelUsers->find($transaction['user']);
                    return [
                        'id' => $transaction['id'],
                        'type' => 'transfer',
                        'type_Tra' => translate('transfer'),
                        'user_id' => $transaction['user'],
                        'user_name' => $userFrom ? $userFrom['firstname'] . ' ' . $userFrom['lastname'] : 'N/A',
                        'user_code' => $userFrom ? $userFrom['code'] : 'N/A',
                        'bank' => $this->formatBankInfo('transfer', $userFrom, null, $userTo),
                        'reference' => str_pad($transaction['id'], 4, '0', STR_PAD_LEFT),
                        'amount' => $transaction['amount'],
                        'date' => $transaction['created_at'],
                        'date_formatted' => date('d/m/Y', strtotime($transaction['created_at'])),
                        'status' => 1,
                        'status_raw' => 1,
                        'status_formatted' => $this->formatStatusTransfer(1),
                        'created_at' => date('d/m/Y', strtotime($transaction['created_at']))
                    ];
                }
                break;
        }

        return null;
    }

    private function formatBankInfo($type, $user, $bank = null, $userTo = null) {
        if (session()->get('group') == 1) {
            switch ($type) {
                case 'payment':
                    return translate('payment to wallet') . '<br><small class="text-muted">' . ($user ? $user['code'] . ' - ' . $user['firstname'] . ' ' . $user['lastname'] : 'N/A') . '</small>';
                case 'deposit':
                case 'retire':
                    return ($bank ?? 'N/A') . '<br><small class="text-muted">' . ($user ? $user['code'] . ' - ' . $user['firstname'] . ' ' . $user['lastname'] : 'N/A') . '</small>';
                case 'transfer':
                    return '<small class="text-muted">' . translate('from') . ': ' . ($user ? $user['code'] . ' - ' . $user['firstname'] . ' ' . $user['lastname'] : 'N/A') . '<br>' . translate('to') . ': ' . ($userTo ? $userTo['code'] . ' - ' . $userTo['firstname'] . ' ' . $userTo['lastname'] : 'N/A') . '</small>';
            }
        } else {
            switch ($type) {
                case 'payment':
                    return translate('payment to wallet') . '<br><small class="text-muted">' . $bank . '</small>';
                case 'deposit':
                case 'retire':
                    return $bank ?? 'N/A';
                case 'transfer':
                    if ($user && $user['id'] == session()->get('id')) {
                        return translate('to') . ': ' . ($userTo ? $userTo['firstname'] . ' ' . $userTo['lastname'] : 'N/A');
                    } else {
                        return translate('from') . ': ' . ($user ? $user['firstname'] . ' ' . $user['lastname'] : 'N/A');
                    }
            }
        }

        return 'N/A';
    }

    private function formatStatusPayment($status) {
        switch ($status) {
            case 1:
                return '<span class="badge bg-warning"><i class="fa-duotone fa-solid fa-clock"></i> ' . translate('pending') . '</span>';
            case 2:
                return '<span class="badge bg-success"><i class="fa-duotone fa-solid fa-check-double"></i> ' . translate('approved') . '</span>';
            case 0:
                return '<span class="badge bg-danger"><i class="fa-duotone fa-solid fa-xmark"></i> ' . translate('rejected') . '</span>';
            default:
                return '<span class="badge bg-secondary">N/A</span>';
        }
    }

    private function formatStatusDeposit($status) {
        switch ($status) {
            case 1:
                return '<span class="badge bg-warning"><i class="fa-duotone fa-solid fa-clock"></i> ' . translate('pending') . '</span>';
            case 2:
                return '<span class="badge bg-success"><i class="fa-duotone fa-solid fa-check-double"></i> ' . translate('approved') . '</span>';
            case 0:
                return '<span class="badge bg-danger"><i class="fa-duotone fa-solid fa-xmark"></i> ' . translate('rejected') . '</span>';
            default:
                return '<span class="badge bg-secondary">N/A</span>';
        }
    }

    private function formatStatusRetire($status) {
        switch ($status) {
            case 1:
                return '<span class="badge bg-warning"><i class="fa-duotone fa-solid fa-clock"></i> ' . translate('pending') . '</span>';
            case 2:
                return '<span class="badge bg-success"><i class="fa-duotone fa-solid fa-check-double"></i> ' . translate('approved') . '</span>';
            case 0:
                return '<span class="badge bg-danger"><i class="fa-duotone fa-solid fa-xmark"></i> ' . translate('rejected') . '</span>';
            default:
                return '<span class="badge bg-secondary">N/A</span>';
        }
    }

    private function formatStatusTransfer($status) {
        return '<span class="badge bg-success"><i class="fa-duotone fa-solid fa-check-double"></i> ' . translate('approved') . '</span>';
    }

    /**
     * Crear notificación de prueba (solo entorno development).
     */
    public function testNotification() {
        if (ENVIRONMENT !== 'development') {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'No disponible']);
        }

        if (!$this->request->isAJAX() || !session()->get('logged_in')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'No autorizado']);
        }

        $modelNotifications = new NotificationsModel();
        $userId = (int) session()->get('id');

        $id = $modelNotifications->insert([
            'user'    => $userId,
            'type'    => 'info',
            'title'   => 'Notificación de prueba',
            'message' => 'Desliza hacia la derecha para cerrar. Si ves esto, el sistema funciona.',
            'status'  => 0,
        ]);

        return $this->response->setJSON(['ok' => true, 'id' => $id]);
    }

    // Método para marcar notificación como leída
    public function markNotificationRead() {
        $modelNotifications = new NotificationsModel();

        // Verificar si es una petición AJAX
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acceso no autorizado']);
        }

        // Verificar si el usuario está autenticado
        if (!session()->get('logged_in')) {
            return $this->response->setJSON(['error' => 'Usuario no autenticado']);
        }

        $notificationId = $this->request->getJSON()->id ?? null;
        
        if (!$notificationId) {
            return $this->response->setJSON(['error' => 'ID de notificación no proporcionado']);
        }

        // Verificar que la notificación pertenezca al usuario actual
        $notification = $modelNotifications->where('id', $notificationId)->where('user', session()->get('id'))->first();

        if (!$notification) {
            return $this->response->setJSON(['error' => 'Notificación no encontrada']);
        }

        // Marcar como leída
        $modelNotifications->update($notificationId, ['status' => 1]);

        $modelNotifications->where('user', session()->get('id'))->where('status', 0)->set(['status' => 1])->update();

        return $this->response->setJSON(['success' => true]);
    }
}
