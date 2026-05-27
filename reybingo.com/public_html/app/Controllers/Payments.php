<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\PaymentsModel;
use App\Models\DepositsModel;
use App\Models\RetiresModel;
use App\Models\TransfersModel;
use App\Models\ReferralsModel;
use App\Models\BanksModel;
use App\Models\SingsModel;
use App\Models\AwardsModel;
use App\Models\NotificationsModel;
use App\Models\GamesModel;
use App\Models\CartonsModel;
use App\Models\ModalitiesModel;
use CodeIgniter\Controller;

class Payments extends Controller {
    public function __construct() {
        helper(['form', 'url', 'cookie', 'text', 'wallet']);
        session();
    }

    public function show() {
        if (!session()->get('id')) {
            return $this->response->setStatusCode(401)->setBody('Usuario no autenticado');
        }

        $modelUsers = new UsersModel();
        $user = $modelUsers->find(session()->get('id'));
        
        if (!$user) {
            return $this->response->setStatusCode(404)->setBody('Usuario no encontrado');
        }
        
        $data = site_url() . '' . $user['code'];

        require_once APPPATH . 'Libraries/phpqrcode/qrlib.php';
        
        ob_start();
        \QRcode::png($data, null, QR_ECLEVEL_M, 6, 2);
        $png = ob_get_clean();
        
        return $this->response->setContentType('image/png')->setBody($png);
    }

    public function index() {
        
        $modelGames = new GamesModel();
        
        $game = $modelGames->find(session()->get('game_id'));
    
        if ($game) {
            return redirect()->to('/board');
        }

        $data = [
            'page' => [
                'title' => translate('payments')
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('games/index') 
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }

    public function paymentsGet() {
        $modelUsers = new UsersModel();

        // Obtener parámetros de filtrado
        $filters = $this->getFilters();
        
        // Obtener usuario actual
        $userRow = $modelUsers->find(session()->get('id'));
        if (!$userRow) {
            return $this->response->setStatusCode(401)->setBody(translate('user not found'));
        }
        $data['user'] = wallet_service()->normalizeUser($userRow);
        $data['filters'] = $filters;
        
        // Cargar usuarios para el filtro (solo para admin)
        if (session()->get('group') == 1) {
            $data['users'] = $modelUsers->select('id, code, firstname, lastname')->where('group', 0)->orderBy('firstname', 'ASC')->findAll();
        }

        // Obtener todas las transacciones
        $allTransactions = $this->getAllTransactions();
        
        // Aplicar filtros
        $filteredTransactions = $this->applyFilters($allTransactions, $filters);
        
        // Calcular estadísticas
        $data['statistics'] = $this->calculateStatistics($filteredTransactions);
        $data['adminKpis'] = $this->getAdminKpis();
        
        // Paginación
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $perPage;
        
        $data['payments'] = array_slice($filteredTransactions, $offset, $perPage);
        $data['pagination'] = $this->createPagination($filteredTransactions, $page, $perPage);
        
        return view('users/payments', $data);
    }

    public function paymentsAjax() {
        try {
            $filters = $this->getFilters();
            $allTransactions = $this->getAllTransactions();
            $filteredTransactions = $this->applyFilters($allTransactions, $filters);
            
            $perPage = $filters['per_page'] ?? 15;
            $page = $filters['page'] ?? 1;
            $offset = ($page - 1) * $perPage;
            
            $payments = array_slice($filteredTransactions, $offset, $perPage);
            $statistics = $this->calculateStatistics($filteredTransactions);
            $pagination = $this->createPagination($filteredTransactions, $page, $perPage);

            return $this->response->setJSON([
                'success' => true,
                'payments' => $payments,
                'statistics' => $statistics,
                'pagination' => $pagination,
                'adminKpis' => $this->getAdminKpis(),
                'total' => count($filteredTransactions)
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en paymentsAjax: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Error processing request'
            ]);
        }
    }

    private function getFilters() {
        return [
            'search' => $this->request->getGet('search') ?? '',
            'type' => $this->request->getGet('type') ?? 'all',
            'status' => $this->request->getGet('status') ?? 'all',
            'user_id' => $this->request->getGet('user_id') ?? 'all',
            'date_from' => $this->request->getGet('date_from') ?? '',
            'date_to' => $this->request->getGet('date_to') ?? '',
            'page' => (int)($this->request->getGet('page') ?? 1),
            'per_page' => (int)($this->request->getGet('per_page') ?? 15)
        ];
    }

    private function getAllTransactions() {
        $modelUsers = new UsersModel();
        $modelPayments = new PaymentsModel();
        $modelDeposits = new DepositsModel();
        $modelRetires = new RetiresModel();
        $modelTransfers = new TransfersModel();

        $allTransactions = [];
        
        try {
            // Obtener pagos
            if (session()->get('group') == 1) {
                $payments = $modelPayments->findAll();
            } else {
                $payments = $modelPayments->where('user', session()->get('id'))->findAll();
            }

            foreach ($payments as $payment) {
                $user = $modelUsers->find($payment['user']);
                if ($payment['type'] == 'award') {
                    $typePayment = translate('per award paid');
                } else if ($payment['type'] == 'referred') {
                    $typePayment = translate('per referred player');
                }
                $transaction = [
                    'id' => $payment['id'],
                    'type' => 'payment',
                    'type_Tra' => translate('payment'),
                    'user_id' => $payment['user'],
                    'user_name' => $user ? $user['firstname'] . ' ' . $user['lastname'] : 'N/A',
                    'user_code' => $user ? $user['code'] : 'N/A',
                    'bank' => $this->formatBankInfo('payment', $user, $typePayment),
                    'reference' => str_pad($payment['id'], 4, '0', STR_PAD_LEFT),
                    'amount' => $payment['amount'],
                    'date' => $payment['created_at'],
                    'date_formatted' => date('d/m/Y', strtotime($payment['created_at'])),
                    'status' => $payment['status'],
                    'status_raw' => $payment['status'],
                    'status_formatted' => $this->formatStatusPayment($payment['status']),
                    'created_at' => date('d/m/Y', strtotime($payment['created_at']))
                ];
                $allTransactions[] = $transaction;
            }

            // Obtener depósitos
            if (session()->get('group') == 1) {
                $deposits = $modelDeposits->findAll();
            } else {
                $deposits = $modelDeposits->where('user', session()->get('id'))->findAll();
            }

            foreach ($deposits as $deposit) {
                $user = $modelUsers->find($deposit['user']);
                $transaction = [
                    'id' => $deposit['id'],
                    'type' => 'deposit',
                    'type_Tra' => translate('deposit'),
                    'user_id' => $deposit['user'],
                    'user_name' => $user ? $user['firstname'] . ' ' . $user['lastname'] : 'N/A',
                    'user_code' => $user ? $user['code'] : 'N/A',
                    'bank' => $this->formatBankInfo('deposit', $user, $deposit['bank']),
                    'reference' => $deposit['reference'],
                    'amount' => $deposit['amount'],
                    'date' => $deposit['date'],
                    'date_formatted' => date('d/m/Y', strtotime($deposit['date'])),
                    'status' => $deposit['status'],
                    'status_raw' => $deposit['status'],
                    'status_formatted' => $this->formatStatusDeposit($deposit['status']),
                    'created_at' => date('d/m/Y', strtotime($deposit['created_at'])) ?? $deposit['date']
                ];
                $allTransactions[] = $transaction;
            }

            // Obtener retiros
            if (session()->get('group') == 1) {
                $retires = $modelRetires->findAll();
            } else {
                $retires = $modelRetires->where('user', session()->get('id'))->findAll();
            }

            foreach ($retires as $retire) {
                $user = $modelUsers->find($retire['user']);
                $transaction = [
                    'id' => $retire['id'],
                    'type' => 'retire',
                    'type_Tra' => translate('retire'),
                    'user_id' => $retire['user'],
                    'user_name' => $user ? $user['firstname'] . ' ' . $user['lastname'] : 'N/A',
                    'user_code' => $user ? $user['code'] : 'N/A',
                    'bank' => $this->formatBankInfo('retire', $user, $retire['bank']),
                    'reference' => str_pad($retire['id'], 4, '0', STR_PAD_LEFT),
                    'amount' => $retire['amount'],
                    'date' => $retire['created_at'],
                    'date_formatted' => date('d/m/Y', strtotime($retire['created_at'])),
                    'status' => $retire['status'],
                    'status_raw' => $retire['status'],
                    'status_formatted' => $this->formatStatusRetire($retire['status']),
                    'created_at' => date('d/m/Y', strtotime($retire['created_at']))
                ];
                $allTransactions[] = $transaction;
            }

            // Obtener transferencias
            if (session()->get('group') == 1) {
                $transfers = $modelTransfers->findAll();
            } else {
                $transfers = $modelTransfers->groupStart()->where('user', session()->get('id'))->orWhere('from', session()->get('id'))->groupEnd()->findAll();
            }

            foreach ($transfers as $transfer) {
                $userFrom = $modelUsers->find($transfer['from']);
                $userTo = $modelUsers->find($transfer['user']);
                
                $transaction = [
                    'id' => $transfer['id'],
                    'type' => 'transfer',
                    'type_Tra' => translate('transfer'),
                    'user_id' => $transfer['user'],
                    'user_name' => $userFrom ? $userFrom['firstname'] . ' ' . $userFrom['lastname'] : 'N/A',
                    'user_code' => $userFrom ? $userFrom['code'] : 'N/A',
                    'bank' => $this->formatBankInfo('transfer', $userFrom, null, $userTo),
                    'reference' => str_pad($transfer['id'], 4, '0', STR_PAD_LEFT),
                    'amount' => $transfer['amount'],
                    'date' => $transfer['created_at'],
                    'date_formatted' => date('d/m/Y', strtotime($transfer['created_at'])),
                    'status' => 1,
                    'status_raw' => 1,
                    'status_formatted' => $this->formatStatusTransfer(1),
                    'created_at' => date('d/m/Y', strtotime($transfer['created_at']))
                ];
                $allTransactions[] = $transaction;
            }

            // Ordenar por fecha descendente
            usort($allTransactions, function ($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

        } catch (\Exception $e) {
            log_message('error', 'Error obteniendo transacciones: ' . $e->getMessage());
        }

        return $allTransactions;
    }

    private function applyFilters($transactions, $filters) {
        $filtered = $transactions;

        try {
            // Filtro de búsqueda
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $filtered = array_filter($filtered, function ($transaction) use ($search) {
                    return strpos(strtolower($transaction['reference']), $search) !== false || strpos(strtolower($transaction['user_name']), $search) !== false || strpos(strtolower($transaction['user_code']), $search) !== false || strpos(strtolower(strip_tags($transaction['bank'])), $search) !== false;
                });
            }

            // Filtro por tipo
            if ($filters['type'] !== 'all') {
                $filtered = array_filter($filtered, function ($transaction) use ($filters) {
                    return $transaction['type'] === $filters['type'];
                });
            }

            // Filtro por estado
            if ($filters['status'] !== 'all') {
                $filtered = array_filter($filtered, function ($transaction) use ($filters) {
                    return $transaction['status_raw'] == $filters['status'];
                });
            }

            // Filtro por usuario (solo para admin)
            if (session()->get('group') == 1 && $filters['user_id'] !== 'all') {
                $filtered = array_filter($filtered, function ($transaction) use ($filters) {
                    return $transaction['user_id'] == $filters['user_id'];
                });
            }

            // Filtro por fecha desde
            if (!empty($filters['date_from'])) {
                $dateFrom = strtotime($filters['date_from']);
                $filtered = array_filter($filtered, function ($transaction) use ($dateFrom) {
                    return strtotime($transaction['date']) >= $dateFrom;
                });
            }

            // Filtro por fecha hasta
            if (!empty($filters['date_to'])) {
                $dateTo = strtotime($filters['date_to'] . ' 23:59:59');
                $filtered = array_filter($filtered, function ($transaction) use ($dateTo) {
                    return strtotime($transaction['date']) <= $dateTo;
                });
            }

        } catch (\Exception $e) {
            log_message('error', 'Error aplicando filtros: ' . $e->getMessage());
        }

        return array_values($filtered);
    }

    private function calculateStatistics($transactions) {
        $stats = [
            'total_transactions' => count($transactions),
            'total_amount' => 0,
            'deposits' => ['count' => 0, 'amount' => 0],
            'retires' => ['count' => 0, 'amount' => 0],
            'transfers' => ['count' => 0, 'amount' => 0],
            'payments' => ['count' => 0, 'amount' => 0],
            'pending' => ['count' => 0, 'amount' => 0],
            'approved' => ['count' => 0, 'amount' => 0],
            'rejected' => ['count' => 0, 'amount' => 0]
        ];

        try {
            foreach ($transactions as $transaction) {
                $amount = floatval($transaction['amount']);
                $stats['total_amount'] += $amount;

                // Por tipo
                if (isset($stats[$transaction['type'] . 's'])) {
                    $stats[$transaction['type'] . 's']['count']++;
                    $stats[$transaction['type'] . 's']['amount'] += $amount;
                } elseif ($transaction['type'] === 'payment') {
                    $stats['payments']['count']++;
                    $stats['payments']['amount'] += $amount;
                }

                // Por estado
                switch ($transaction['status_raw']) {
                    case 1:
                        $stats['pending']['count']++;
                        $stats['pending']['amount'] += $amount;
                        break;
                    case 2:
                        $stats['approved']['count']++;
                        $stats['approved']['amount'] += $amount;
                        break;
                    case 0:
                        $stats['rejected']['count']++;
                        $stats['rejected']['amount'] += $amount;
                        break;
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Error calculando estadísticas: ' . $e->getMessage());
        }

        return $stats;
    }

    private function createPagination($transactions, $currentPage, $perPage) {
        $total = count($transactions);
        $totalPages = ceil($total / $perPage);

        return [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_previous' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'previous_page' => $currentPage > 1 ? $currentPage - 1 : null,
            'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null
        ];
    }

    private function getAdminKpis(): array
    {
        if (session()->get('group') != 1) {
            return [
                'manual_credits' => 0,
                'user_spend' => 0,
                'total_prizes' => 0,
            ];
        }

        $modelDeposits = new DepositsModel();
        $modelPayments = new PaymentsModel();
        $modelAwards = new AwardsModel();

        return [
            'manual_credits' => round((float) ($modelDeposits->selectSum('amount')->where('status', 2)->get()->getRow()->amount ?? 0), 2),
            'user_spend' => round((float) ($modelPayments->selectSum('amount')->where('status', 2)->get()->getRow()->amount ?? 0), 2),
            'total_prizes' => round((float) ($modelAwards->selectSum('amount')->where('status', 1)->get()->getRow()->amount ?? 0), 2),
        ];
    }

    private function formatBankInfo($type, $user, $bank = null, $userTo = null) {
        if (session()->get('group') == 1) {
            switch ($type) {
                case 'payment':
                    return translate('payment to wallet') . '<br><small class="text-muted">' . $bank . '</small>';
                    //return translate('payment to wallet') . '<br><small class="text-muted">' . ($user ? $user['code'] . ' - ' . $user['firstname'] . ' ' . $user['lastname'] : 'N/A') . '</small>';
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

    public function updateStatus() {
        // Verificar que sea una petición AJAX
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'error' => 'Invalid request']);
        }

        // Verificar autorización (solo admin)
        if (session()->get('group') != 1) {
            return $this->response->setJSON(['success' => false, 'error' => 'Unauthorized']);
        }

        try {
            $type = $this->request->getPost('type');
            $id = $this->request->getPost('id');
            $status = $this->request->getPost('status');

            // Validar datos
            if (!$type || !$id || !$status) {
                return $this->response->setJSON(['success' => false, 'error' => 'Invalid data']);
            }

            // Actualizar según el tipo
            switch ($type) {
                case 'payment':
                    $modelPayments->update($id, ['status' => $status]);
                    break;
                case 'deposit':
                    $modelDeposits->update($id, ['status' => $status]);
                    break;
                case 'retire':
                    $modelRetires->update($id, ['status' => $status]);
                    break;
                default:
                    return $this->response->setJSON(['success' => false, 'error' => 'Invalid type']);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => translate('status updated successfully')
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error actualizando estado: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Error updating status'
            ]);
        }
    }

    public function requestGet($type = null, $id = null) {
        $modelUsers   = new UsersModel();
        $modelPayments = new PaymentsModel();
        $modelDeposits = new DepositsModel();
        $modelRetires = new RetiresModel();
        $modelTransfers = new TransfersModel();
        $data         = [];

        if ($type === 'payment') {
            $data['payment'] = $modelPayments->where('id', $id)->first();

            if ($data['payment']) {
                $data['user'] = $modelUsers->find($data['payment']['user']);
            }

            $data['status'] = $this->formatStatusPayment($data['payment']['status']);

            $data['type'] = $type;

            return view('users/requestPayment', $data);
        } else if ($type === 'deposit') {
            $data['deposit'] = $modelDeposits->where('id', $id)->first();

            if ($data['deposit']) {
                $data['user'] = $modelUsers->find($data['deposit']['user']);
            }

            $data['status'] = $this->formatStatusDeposit($data['deposit']['status']);

            $data['type'] = $type;

            return view('users/requestDeposit', $data);
        } else if ($type === 'retire') {
            $data['retire'] = $modelRetires->where('id', $id)->first();

            if ($data['retire']) {
                $data['user'] = $modelUsers->find($data['retire']['user']);
            }

            $data['status'] = $this->formatStatusRetire($data['retire']['status']);

            $data['type'] = $type;

            return view('users/requestRetire', $data);
        } else if ($type === 'transfer') {
            $data['transfer'] = $modelTransfers->where('id', $id)->first();
            
            if ($data['transfer']) {
                $data['userFrom'] = $modelUsers->find($data['transfer']['from']);
                $data['userTo'] = $modelUsers->find($data['transfer']['user']);
            }

            $data['status'] = $this->formatStatusTransfer(3);

            $data['type'] = $type;

            return view('users/requestTransfer', $data);
        }
    }

    public function modalVoucher($id = null) {
        $modelDeposits = new DepositsModel();

        $data['deposit'] = $modelDeposits->where('id', $id)->first();

        return view('users/modalVoucher', $data);
    }
  
    public function depositGet() {
        $modelBanks = new BanksModel();

        $data['banks'] = $modelBanks->where('status', 1)->findAll();

        $modelUsers = new UsersModel();

        $data['users'] = $modelUsers->where('status', 1)->findAll();

        $data['user'] = $modelUsers->find(session()->get('id'));

        return view('users/deposit', $data);
    }

    public function depositStepSubmit() {
        $data = [
            'account'   => $this->request->getPost('account'),
            'method'    => $this->request->getPost('method')
        ];

        $errors = [];

        if ($data['account'] == '') {
            $errors['deposit-account'] = translate('bingo bank') . ' ' . strtolower(translate('it is mandatory'));
        }

        if ($data['method'] == '') {
            $errors['deposit-method'] = translate('payment method') . ' ' . strtolower(translate('it is mandatory'));
        }

        if (!empty($errors)) {
            $response = [
                'success' => false,
                'errors' => $errors
            ];
            return $this->response->setJSON($response);
        }

        if ($data['method'] == 'paypal') {
            $paypal = true;
        } else {
            $paypal = false;
        }

        $response = [
            'success' => true,
            'paypal' => $paypal
        ];

        return $this->response->setJSON($response);
    }

    public function depositSubmit() {
        $modelDeposits = new DepositsModel();
        $modelUsers = new UsersModel();
        $modelReferrals = new ReferralsModel();

        $validationRules = [
            'deposit-account' => [
                'label' => translate('bingo bank'),
                'rules' => 'required'
            ],
            'deposit-method' => [
                'label' => translate('payment of method'),  
                'rules' => 'required'
            ],
            'deposit-bank' => [
                'label' => translate('bank of origin'),  
                'rules' => 'required'
            ],
            'deposit-document' => [
                'label' => translate('document'),  
                'rules' => 'required|numeric'
            ],
            'deposit-phone' => [
                'label' => translate('phone'),  
                'rules' => 'required|numeric'
            ],
            'deposit-date' => [
                'label' => translate('date'),  
                'rules' => 'required|valid_date[Y-m-d]'
            ],
            'deposit-amount' => [
                'label' => translate('amount'),  
                'rules' => 'required|numeric|greater_than[0]'
            ],
            'deposit-reference' => [
                'label' => translate('reference'),  
                'rules' => 'required'
            ]
        ];

        if (session()->get('group') == 1 && $this->request->getPost('deposit-user')) {
            $validationRules['deposit-user'] = [
                'label' => translate('user'),
                'rules' => 'required|is_not_unique[users.id]'
            ];
        }
        
        if (!$this->validate($validationRules)) {
            $errors = $this->validator->getErrors();
            $response = [
                'success' => false,
                'errors' => $errors 
            ];
            return $this->response->setJSON($response);
        }

        $isAdmin = session()->get('group') == 1;
        $selectedUser = $this->request->getPost('deposit-user');

        if ($isAdmin && $selectedUser) {
            $depositUserId = $selectedUser;
            $observation = $this->request->getPost('observation');
            $status = 2;
        } else {
            $depositUserId = session()->get('id');
            $observation = '';
            $status = 1;
        }

        $data = [
            'user'      => $depositUserId,
            'account'   => $this->request->getPost('deposit-account'),
            'method'    => $this->request->getPost('deposit-method'),
            'bank'      => $this->request->getPost('deposit-bank'),
            'document'  => $this->request->getPost('deposit-document'),
            'phone'     => $this->request->getPost('deposit-phone'),
            'date'      => $this->request->getPost('deposit-date'),
            'amount'    => $this->request->getPost('deposit-amount'),
            'reference' => $this->request->getPost('deposit-reference'),
            'observation' => $observation,
            'status'    => $status
        ];

        $voucherImage = $this->request->getPost('deposit-voucher');
        if ($voucherImage) {
            // Si llega base64 => nueva imagen
            if (strpos($voucherImage, 'data:image') === 0) {
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $voucherImage));
                $fileName  = uniqid() . '.png';  
                $uploadPath = FCPATH . 'uploads/vouchers/';
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true); 
                }
                file_put_contents($uploadPath . $fileName, $imageData);
                $data['voucher'] = $fileName;
            }
        }

        if (systemGet('activateDeposit') == 1) {
            if ($data['amount'] < systemGet('minimumDeposit')) {
                return $this->response->setJSON([
                    'success' => false,
                    'minMax' => true,
                    'message' => 'El monto mínimo de depósito es ' . systemGet('minimumDeposit') . ' ' . systemGet('currency')
                ]);
            }
            if ($data['amount'] > systemGet('maximumDeposit')) {
                return $this->response->setJSON([
                    'success' => false,
                    'minMax' => true,
                    'message' => 'El monto máximo de depósito es ' . systemGet('maximumDeposit') . ' ' . systemGet('currency')
                ]);
            }
        }

        $modelDeposits->insert($data);
        $depositId = $modelDeposits->insertID();

        $user = $modelUsers->find($depositUserId);
        $currentUserId = session()->get('id');
        $modelNotifications = new NotificationsModel();

        if ($isAdmin && $selectedUser) {
            wallet_credit_recharge($depositUserId, (float) $data['amount']);

            $deposits = $modelDeposits->where('user', $depositUserId)->countAllResults();

            $userReferrer = $modelReferrals->where('id_referrer', $depositUserId)->where('status', 1)->first();

            if ($userReferrer && $deposits == 1) {
                $reward = $data['amount'] * systemGet('rateReferrals');

                $userReferred = $modelUsers->find($userReferrer['id_referred']);
                wallet_credit_withdrawable($userReferrer['id_referred'], (float) $reward);

                $modelReferrals->update($userReferrer['id'], ['amount' => $reward, 'status' => 2]);

                $modelPayments = new PaymentsModel();

                $dataPayment = [
                    'user' => $userReferrer['id_referred'],
                    'type' => 'payment',
                    'type_id' => $userReferrer['id'],
                    'amount' => $reward,
                    'status' => 2
                ];

                $modelPayments->insert($dataPayment);
                $paymentId = $modelPayments->insertID();

                $notificationData = [
                    'user' => $userReferrer['id_referred'],
                    'from' => $currentUserId,
                    'type' => 'payment',
                    'type_id' => $paymentId,
                    'title' => '🥳 PAGO ACREDITADO',
                    'message' => 'Se ha acreditado en su billetera la suma de ' . systemGet('currency') . ' ' . number_format($reward, 2) . ' como recompensa por invitar a un amigo. ¡Sigue invitando y acumula más beneficios!',
                ];

                $modelNotifications->insert($notificationData);
            }

            $notificationData = [
                'user' => $depositUserId,
                'from' => $currentUserId,
                'type' => 'deposit',
                'type_id' => $depositId,
                'title' => '✅ DEPÓSITO ACREDITADO',
                'message' => 'Su depósito por ' . systemGet('currency') . ' ' . number_format($data['amount'], 2) . ' ha sido verificado y acreditado correctamente en su billetera.',
            ];

            $modelNotifications->insert($notificationData);
        } else {
            $admins = $modelUsers->select('id')->where('group', 1)->findAll();

            foreach ($admins as $admin) {
                $notificationData = [
                    'user' => $admin['id'],
                    'from' => $currentUserId,
                    'type' => 'deposit',
                    'type_id' => $depositId,
                    'title' => '📥 NUEVA SOLICITUD DE DEPÓSITO',
                    'message' => $user['firstname'] . ' ' . $user['lastname'] . ' ha realizado un depósito por ' . systemGet('currency') . ' ' . number_format($data['amount'], 2) . ' | Ref: #' . $data['reference'] . ' | Fecha: ' . date('d/m/Y', strtotime($data['date'])) . '.',
                ];

                $modelNotifications->insert($notificationData);
            }
        }

        if ($depositId) {
            $response = [
                'success' => true,
                'newRecharge' => [
                    'id' => $depositId,
                    'type' => 'deposit',
                    'type_Tra' => translate('deposit'),
                    'user_id' => $data['user'],
                    'user_name' => $user ? $user['firstname'] . ' ' . $user['lastname'] : 'N/A',
                    'user_code' => $user ? $user['code'] : 'N/A',
                    'bank' => $this->formatBankInfo('deposit', $user, $data['bank']),
                    'reference' => $data['reference'],
                    'amount' => $data['amount'],
                    'date' => $data['date'],
                    'date_formatted' => date('d/m/Y', strtotime($data['date'])),
                    'status' => $data['status'],
                    'status_raw' => $data['status'],
                    'status_formatted' => $this->formatStatusDeposit($data['status']),
                    'created_at' => date('d/m/Y')
                ]
            ];
        } else {
            $response = [
                'success' => false,
                'error' => translate('error saving payment')
            ];
        }
        
        return $this->response->setJSON($response);
    }

    public function depositPaypalSubmit() {
        $modelDeposits = new DepositsModel();

        $amount = $this->request->getPost('amount');
        $paymentID = $this->request->getPost('paymentID');
        $paymentToken = $this->request->getPost('paymentToken');
        $payerID = $this->request->getPost('payerID');

        $paypal = json_decode(systemGet('paypal'), true);

        /*if ($paypal[0]['mode'] == 'sandbox') {
            $paypalClientID = $paypal[0]['sandbox_client_id'];
            $paypalSecret = $paypal[0]['sandbox_secret_key'];
        } else {
            $paypalClientID = $paypal[0]['production_client_id'];
            $paypalSecret = $paypal[0]['production_secret_key'];
        }*/

        $paypalClientID = systemGet('idPayPal');
        $paypalSecret = systemGet('secretPayPal');

        $payid = $modelDeposits->paypalPayment($paymentID, $paymentToken, $payerID, $paypalClientID, $paypalSecret);

        $total = $modelDeposits->countAll();

        $data = [
            'user'      => session()->get('id'),
            'account'   => $payerID,
            'method'    => 'paypal',
            'bank'      => 'N/A',
            'document'  => 'N/A',
            'phone'     => 'N/A',
            'date'      => date('Y-m-d'),  // Fecha obtenida de PayPal
            'amount'    => $amount,
            'reference' => $paymentID, // ID de pago
            'status'    => 2
        ];

        $model->insert($data);
        $paymentId = $model->insertID();

        $response = [
            'success' => true,
            'newRecharge' => [
                'type'      => translate('paypal'),
                'date'      => date('Y-m-d'),
                'amount'    => $amount,
                'reference' => $paymentID, // ID de pago
                'bank'      => 'N/A',
            ]
        ];

        return $this->response->setJSON($response);
    }

    public function infobankGet($id) {
        $modelBanks = new BanksModel();

        $bank = $modelBanks->find($id);

        if (!$bank) {
            return $this->response->setStatusCode(404)->setJSON(['error' => translate('bank not found')]);
        }

        if (!empty($bank['logo'])) {
            $logo_url = '<img src="'.site_url('uploads/banks/'.$bank['logo']).'" alt="logo banco" class="img-fluid" style="width:50px; height:50px; object-fit:cover;">';
        } else {
            $logo_url = '<i class="fa-duotone fa-solid fa-building-columns fs-1 text-white"></i>';
        }

        return $this->response->setJSON([
            'logo_url' => $logo_url,
            'bank' => $bank['name'],
            'account' => $bank['account'],
            'holder' => $bank['holder'],
            'document' => $bank['document'],
            'phone' => $bank['phone']
        ]);
    }

    public function retireGet() {
        $modelUsers = new UsersModel();

        $data['user'] = $modelUsers->find(session()->get('id'));

        return view('users/retire', $data);
    }

    public function retireSubmit() {
        $modelRetires = new RetiresModel();

        $receiver = $this->request->getPost('retire-receiver');

        $validationRules = [
            'retire-receiver' => [
                'label' => translate('receiver bank'),
                'rules' => 'required'
            ],
            'retire-amount' => [
                'label' => translate('amount'),
                'rules' => 'required|numeric|greater_than[0]'
            ]
        ];

        if ($receiver === "0") {
            $additionalRules = [
                'retire-bank' => [
                    'label' => translate('destiny bank'),
                    'rules' => 'required'
                ],
                'retire-account' => [
                    'label' => translate('account'),
                    'rules' => 'required|numeric'
                ],
                'retire-document' => [
                    'label' => translate('document'),
                    'rules' => 'required|numeric'
                ],
                'retire-phone' => [
                    'label' => translate('phone'),
                    'rules' => 'required|numeric'
                ]
            ];

            $validationRules = array_merge($validationRules, $additionalRules);
        }
    
        if (!$this->validate($validationRules)) {
            $errors = $this->validator->getErrors();
            $response = [
                'success' => false,
                'errors' => $errors 
            ];
            return $this->response->setJSON($response);
        }

        $modelUsers = new UsersModel();

        $user = wallet_service()->normalizeUser($modelUsers->find(session()->get('id')));

        if (! wallet_kyc_allows_withdraw($user)) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => [
                    'retire-amount' => 'Debe completar la verificación KYC antes de retirar. Visite /kyc',
                ],
            ]);
        }

        $withdrawable = wallet_withdrawable($user);
        if ($this->request->getPost('retire-amount') > $withdrawable) {
            $response = [
                'success' => false,
                'errors' => [
                    'retire-amount' => translate('the amount cannot exceed what is available') . ': ' . systemGet('currency') . ' ' . number_format($withdrawable, 2)
                ]
            ];
            return $this->response->setJSON($response);
        }

        $saveAccount = $this->request->getPost('save-account');

        if ($receiver === "0") {
            $data = [
                'user'   => session()->get('id'),
                'bank'      => $this->request->getPost('retire-bank'),
                'account'   => $this->request->getPost('retire-account'),
                'document'  => $this->request->getPost('retire-document'),
                'phone'     => $this->request->getPost('retire-phone'),
                'amount'    => $this->request->getPost('retire-amount'),
                'status' => 1
            ];

            if ($saveAccount) {
                $dataBank = [
                    'bank'     => $data['bank'],
                    'account'  => $data['account'],
                    'document' => $data['document'],
                    'phone'    => $data['phone']
                ];

                $modelUsers->update(session()->get('id'), $dataBank);
            }
        } else {
            $data = [
                'user'   => session()->get('id'),
                'bank'      => $user['bank'],
                'account'   => $user['account'],
                'document'  => $user['document'],
                'phone'     => $user['phone'],
                'amount'    => $this->request->getPost('retire-amount'),
                'status' => 1
            ];
        }

        if (systemGet('activateRetire') == 1) {
            if ($data['amount'] < systemGet('minimumRetire')) {
                return $this->response->setJSON([
                    'success' => false,
                    'minMax' => true,
                    'message' => 'El monto mínimo de retiro es ' . systemGet('minimumRetire') . ' ' . systemGet('currency')
                ]);
            }
            if ($data['amount'] > systemGet('maximumRetire')) {
                return $this->response->setJSON([
                    'success' => false,
                    'minMax' => true,
                    'message' => 'El monto máximo de retiro es ' . systemGet('maximumRetire') . ' ' . systemGet('currency')
                ]);
            }
        }

        $modelRetires->insert($data);
        $retireId = $modelRetires->insertID();

        $retire = $modelRetires->where('id', $retireId)->first();

        $reference = str_pad($retireId, 4, '0', STR_PAD_LEFT);

        $modelNotifications = new NotificationsModel();

        $currentUserId = session()->get('id');

        $admins = $modelUsers->select('id')->where('group', 1)->findAll();

        foreach ($admins as $admin) {
            $notificationData = [
                'user' => $admin['id'],
                'from' => $currentUserId,
                'type' => 'retire',
                'type_id' => $retireId,
                'title' => '📥 NUEVA SOLICITUD DE RETIRO',
                'message' => $user['firstname'] . ' ' . $user['lastname'] . ' ha solicitado un retiro por ' . systemGet('currency') . ' ' . number_format($data['amount'], 2) . ' | Ref: #' . $reference . ' | Fecha: ' . date('d/m/Y'),
            ];

            $modelNotifications->insert($notificationData);
        }

        if ($retireId) {
            $response = [
                'success' => true,
                'newRetire' => [
                    'id' => $retireId,
                    'type' => 'retire',
                    'type_Tra' => translate('retire'),
                    'user_id' => $data['user'],
                    'user_name' => $user ? $user['firstname'] . ' ' . $user['lastname'] : 'N/A',
                    'user_code' => $user ? $user['code'] : 'N/A',
                    'bank' => $this->formatBankInfo('deposit', $user, $data['bank']),
                    'reference' => str_pad($retireId, 4, '0', STR_PAD_LEFT),
                    'amount' => $data['amount'],
                    'date' => date('d/m/Y'),
                    'date_formatted' => date('d/m/Y'),
                    'status' => $data['status'],
                    'status_raw' => $data['status'],
                    'status_formatted' => $this->formatStatusDeposit($data['status']),
                    'created_at' => date('d/m/Y')
                ]
            ];
        } else {
            $response = [
                'success' => false,
                'error' => translate('error creating retire request')
            ];
        }
        
        return $this->response->setJSON($response);
    }

    public function retirebankGet() {
        $modelUsers = new UsersModel();

        $user = $modelUsers->find(session()->get('id'));

        return $this->response->setJSON([
            'bank' => $user['bank'],
            'account' => $user['account'],
            'holder' => $user['firstname'] . ' ' . $user['lastname'],
            'document' => $user['document'],
            'phone' => $user['phone']
        ]);
    }

    public function transferGet() {
        $modelUsers = new UsersModel();

        $data['user'] = $modelUsers->find(session()->get('id'));

        $currentUserId = session()->get('id');

        $data['players'] = $modelUsers->where('group', 0)->where('id !=', $currentUserId)->findAll();

        return view('users/transfer', $data);
    }

    public function transferUserGet($bgc) {
        $modelUsers = new UsersModel();

        $currentUserId = session()->get('id');

        $user = $modelUsers->where('id !=', $currentUserId)->where('code', $bgc)->first();

        $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');

        return $this->response->setJSON([
            'code' => $user['code'],
            'document' => $user['document'],
            'email' => $user['email'],
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname'],
            'image' => $imagePath
        ]);
    }

    public function transferSubmit() {
        $modelUsers = new UsersModel();
        $modelTransfers = new TransfersModel();

        $validationRules = [
            'user' => [
                'label' => translate('bgc player'),
                'rules' => 'required'
            ],
            'amount' => [
                'label' => translate('amount'),
                'rules' => 'required|numeric|greater_than[0]'
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

        $bgc = $this->request->getPost('user');

        $receiver = $modelUsers->where('code', $bgc)->first();
        if (!$receiver) {
            return $this->response->setJSON(['success' => false, 'error' => translate('user not found')]);
        }

        $data = [
            'user' => $receiver['id'],
            'from' => session()->get('id'),
            'amount' => $this->request->getPost('amount'),
            'note' => $this->request->getPost('note'),
            'status' => 2
        ];

        if (systemGet('activateTransfer') == 1) {
            if ($data['amount'] < systemGet('minimumTransfer')) {
                return $this->response->setJSON([
                    'success' => false,
                    'minMax' => true,
                    'message' => 'El monto mínimo de transferencia es ' . systemGet('minimumTransfer') . ' ' . systemGet('currency')
                ]);
            }
            if ($data['amount'] > systemGet('maximumTransfer')) {
                return $this->response->setJSON([
                    'success' => false,
                    'minMax' => true,
                    'message' => 'El monto máximo de transferencia es ' . systemGet('maximumTransfer') . ' ' . systemGet('currency')
                ]);
            }
        }

        $modelTransfers->insert($data);
        $transferId = $modelTransfers->insertID();

        $transfer = $modelTransfers->where('id', $transferId)->first();

        $reference = str_pad($transferId, 4, '0', STR_PAD_LEFT);

        $modelUsers->update($receiver['id'], ['wallet' => $receiver['wallet'] + $transfer['amount']]);

        $modelNotifications = new NotificationsModel();

        $user = $modelUsers->find(session()->get('id'));

        $wallet = $user['wallet'] - $transfer['amount'];

        $modelUsers->update($user['id'], ['wallet' => $wallet]);

        $notificationData = [
            'user' => $receiver['id'],
            'from' => $user['id'],
            'type' => 'transfer',
            'type_id' => $transferId,
            'title' => '✅ TRANSFERENCIA ACREDITADA',
            'message' => $user['firstname'] . ' ' . $user['lastname'] . ' le ha transferido ' . systemGet('currency') . ' ' . number_format($transfer['amount'], 2) . ' | Ref: #' . $reference . ' | Fecha: ' . date('d/m/Y', strtotime($transfer['created_at'])) . '.',
        ];

        $modelNotifications->insert($notificationData);

        $userFrom = $modelUsers->find($user['id']);
        $userTo = $modelUsers->find($receiver['id']);

        $response = [
            'success' => true,
            'wallet' => number_format($wallet, 2),
            'newTransfer' => [
                'id' => $transferId,
                'type' => 'transfer',
                'type_Tra' => translate('transfer'),
                'user_id' => $user['id'],
                'user_name' => $userFrom ? $userFrom['firstname'] . ' ' . $userFrom['lastname'] : 'N/A',
                'user_code' => $userFrom ? $userFrom['code'] : 'N/A',
                'bank' => $this->formatBankInfo('transfer', $userFrom, null, $userTo),
                'reference' => str_pad($transferId, 4, '0', STR_PAD_LEFT),
                'amount' => $transfer['amount'],

                'date' => $transfer['created_at'],
                'date_formatted' => date('d/m/Y', strtotime($transfer['created_at'])),
                'status' => 1,
                'status_raw' => 1,
                'status_formatted' => $this->formatStatusTransfer(1),
                'created_at' => date('d/m/Y', strtotime($transfer['created_at']))
            ]
        ];
    
        return $this->response->setJSON($response);
    }

    public function settingswalletGet() {
        $modelUsers = new UsersModel();

        $data['user'] = $modelUsers->find(session()->get('id'));

        return view('users/settingswallet', $data);
    }

    public function availablewalletGet() {
        $modelUsers = new UsersModel();

        $user = $modelUsers->find(session()->get('id'));

        return $this->response->setJSON([
            'wallet' => $user['wallet']
        ]);
    }

    public function settingswalletSubmit() {
        $modelUsers = new UsersModel();

        $validationRules = [
            'setting-bank' => [
                'label' => translate('bank'),
                'rules' => 'required'
            ],
            'setting-account' => [
                'label' => translate('account'),  
                'rules' => 'required|numeric'
            ],
            'setting-document' => [
                'label' => translate('document'),
                'rules' => 'required|numeric'
            ],
            'setting-phone' => [
                'label' => translate('phone'),
                'rules' => 'required|numeric'
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
            'bank' => $this->request->getPost('setting-bank'),
            'account' => $this->request->getPost('setting-account'),
            'document' => $this->request->getPost('setting-document'),
            'phone' => $this->request->getPost('setting-phone')
        ];

        $modelUsers->update(session()->get('id'), $data);

        $response = [
            'success' => true,
        ];
    
        return $this->response->setJSON($response);
    }

    public function statusSubmit() {
        $modelPayments = new PaymentsModel();
        $modelDeposits = new DepositsModel();
        $modelRetires = new RetiresModel();
        $modelUsers = new UsersModel();
        $modelReferrals = new ReferralsModel();

        $requestData = $this->request->getJSON();
        $type = $requestData->type ?? null;
        $id = $requestData->id ?? null;
        $action = $requestData->action ?? null;
        $observation = $requestData->observation ?? null;

        if (!$type || !$id || !$action) {
            return $this->response->setJSON(['success' => false, 'error' => translate('incomplete data')]);
        }

        if ($type === 'deposit') {
            $deposit = $modelDeposits->find($id);

            if (!$deposit) {
                return $this->response->setJSON(['success' => false, 'error' => translate('deposit not found')]);
            }

            $user = $modelUsers->find($deposit['user']);
            if (!$user) {
                return $this->response->setJSON(['success' => false, 'error' => translate('user not found')]);
            }

            if ($action === 'approve') {
                if ($deposit['status'] === '1' || $deposit['status'] === '0') {
                    wallet_credit_recharge($deposit['user'], (float) $deposit['amount']);

                    $modelDeposits->update($id, ['status' => 2, 'observation' => $observation]);

                    $totalDeposits = $modelDeposits->where('user', $deposit['user'])->where('status', 2)->countAllResults();

                    $userReferrer = $modelReferrals->where('id_referrer', $deposit['user'])->where('status', 1)->first();

                    if ($userReferrer && $totalDeposits == 1) {
                        $reward = $deposit['amount'] * systemGet('rateReferrals');

                        $userReferred = $modelUsers->find($userReferrer['id_referred']);
                        wallet_credit_withdrawable($userReferrer['id_referred'], (float) $reward);

                        $modelReferrals->update($userReferrer['id'], ['amount' => $reward, 'status' => 2]);

                        $modelNotifications = new NotificationsModel();

                        $currentUserId = session()->get('id');

                        $dataPayment = [
                            'user' => $userReferrer['id_referred'],
                            'type' => 'referred',
                            'type_id' => $userReferrer['id'],
                            'amount' => $reward,
                            'status' => 2
                        ];

                        $modelPayments->insert($dataPayment);
                        $paymentId = $modelPayments->insertID();

                        $notificationData = [
                            'user' => $userReferrer['id_referred'],
                            'from' => $currentUserId,
                            'type' => 'payment',
                            'type_id' => $paymentId,
                            'title' => '🥳 PAGO ACREDITADO',
                            'message' => 'Se ha acreditado en su billetera la suma de ' . systemGet('currency') . ' ' . number_format($reward, 2) . ' como recompensa por invitar a un amigo. ¡Sigue invitando y acumula más beneficios!',
                        ];

                        $modelNotifications->insert($notificationData);
                    }
                }

                $modelNotifications = new NotificationsModel();

                $currentUserId = session()->get('id');

                $notificationData = [
                    'user' => $deposit['user'],
                    'from' => $currentUserId,
                    'type' => 'deposit',
                    'type_id' => $deposit['id'],
                    'title' => '✅ DEPÓSITO ACREDITADO',
                    'message' => 'Su depósito por ' . systemGet('currency') . ' ' . number_format($deposit['amount'], 2) . ' ha sido verificado y acreditado correctamente en su billetera.',
                ];

                $modelNotifications->insert($notificationData);
            } elseif ($action === 'refuse') {
                if ($deposit['status'] === '2') {
                    $modelUsers->update($deposit['user'], ['wallet' => $user['wallet'] - $deposit['amount']]);
                }

                $modelDeposits->update($id, ['status' => 0, 'observation' => $observation]);

                $modelNotifications = new NotificationsModel();

                $currentUserId = session()->get('id');

                $notificationData = [
                    'user' => $deposit['user'],
                    'from' => $currentUserId,
                    'type' => 'deposit',
                    'type_id' => $deposit['id'],
                    'title' => '❌ DEPÓSITO RECHAZADO',
                    'message' => 'Su solicitud de depósito a su billetera por un monto de ' . systemGet('currency') . ' ' . number_format($deposit['amount'], 2) . ' no pudo ser verificada y ha sido rechazada. Para más información, por favor contacte con soporte.',
                ];

                $modelNotifications->insert($notificationData);
            }
        } elseif ($type === 'retire') {
            $retire = $modelRetires->find($id);

            if (!$retire) {
                return $this->response->setJSON(['success' => false, 'error' => translate('retire not found')]);
            }

            $user = wallet_service()->normalizeUser($modelUsers->find($retire['user']));
            if (!$user) {
                return $this->response->setJSON(['success' => false, 'error' => translate('user not found')]);
            }

            if ($action === 'approve') {
                if ($retire['status'] === '1' || $retire['status'] === '0') {
                    if (wallet_withdrawable($user) < $retire['amount']) {
                        $modelNotifications = new NotificationsModel();

                        $currentUserId = session()->get('id');

                        $notificationData = [
                            'user' => $retire['user'],
                            'from' => $currentUserId,
                            'type' => 'retire',
                            'type_id' => $retire['id'],
                            'title' => '❌ RETIRO RECHAZADO',
                            'message' => 'Su solicitud de retiro por un monto de ' . systemGet('currency') . ' ' . number_format($retire['amount'], 2) . ' ha sido rechazada. Si desea más información, por favor comuníquese con soporte.',
                        ];

                        $modelNotifications->insert($notificationData);

                        $modelRetires->update($id, ['status' => 0, 'observation' => translate('the amount available in the user wallet is less than the amount to be retire')]);

                        return $this->response->setJSON(['refuse' => true, 'error' => translate('the amount available in the user wallet is less than the amount to be retire')]);
                    }

                    if (! wallet_deduct_withdrawable($retire['user'], (float) $retire['amount'])) {
                        return $this->response->setJSON(['refuse' => true, 'error' => translate('the amount available in the user wallet is less than the amount to be retire')]);
                    }
                }

                $modelRetires->update($id, ['status' => 2, 'observation' => $observation]);

                $modelNotifications = new NotificationsModel();

                $currentUserId = session()->get('id');

                $notificationData = [
                    'user' => $retire['user'],
                    'from' => $currentUserId,
                    'type' => 'retire',
                    'type_id' => $retire['id'],
                    'title' => '📤 RETIRO APROBADO',
                    'message' => 'Se ha debitado de su billetera ' . systemGet('currency') . ' ' . number_format($retire['amount'], 2) . ' correspondientes a su solicitud de retiro y transferido a su cuenta bancaria.',
                ];

                $modelNotifications->insert($notificationData);
            } elseif ($action === 'refuse') {
                if ($retire['status'] === '2') {
                    $modelUsers->update($retire['user'], ['wallet' => $user['wallet'] + $retire['amount']]);
                }

                $modelRetires->update($id, ['status' => 0, 'observation' => $observation]);

                $modelNotifications = new NotificationsModel();

                $currentUserId = session()->get('id');

                $notificationData = [
                    'user' => $retire['user'],
                    'from' => $currentUserId,
                    'type' => 'retire',
                    'type_id' => $retire['id'],
                    'title' => '❌ RETIRO RECHAZADO',
                    'message' => 'Su solicitud de retiro por un monto de ' . systemGet('currency') . ' ' . number_format($retire['amount'], 2) . ' ha sido rechazada. Si desea más información, por favor comuníquese con soporte.',
                ];

                $modelNotifications->insert($notificationData);
            }
        }

        return $this->response->setJSON(['success' => true]);
    }

    public function payawardSubmit() {
        $modelSings = new SingsModel();
        $modelAwards = new AwardsModel();
        $modelUsers = new UsersModel();
        $modelGames = new GamesModel();
        $modelPayments = new PaymentsModel();
        $modelCartons = new CartonsModel();
        $modelModalities = new ModalitiesModel();

        $requestData = $this->request->getJSON();
        $id = $requestData->id ?? null;
        $action = $requestData->action ?? null;

        if (!$id || !$action) {
            return $this->response->setJSON(['success' => false, 'error' => translate('incomplete data')]);
        }

        $sing = $modelSings->find($id);
        if (!$sing) {
            return $this->response->setJSON(['success' => false, 'error' => translate('sing not found')]);
        }

        $game = $modelGames->find($sing['game']);

        if (!$game) {
            return $this->response->setJSON(['success' => false, 'error' => translate('game not found')]);
        }

        $sings = $modelSings->where('game', $sing['game'])->where('modality', $sing['modality'])->findAll();
        
        $singsCount = count($sings);
        if ($singsCount < 1) {
            return $this->response->setJSON(['success' => false, 'error' => translate('no valid winners found')]);
        }

        $award = $modelAwards->where('game', $sing['game'])->where('modality', $sing['modality'])->first();

        if (!$award) {
            return $this->response->setJSON(['success' => false, 'error' => translate('award not found')]);
        }

        $cartons = $modelCartons->where('game', $game['id'])->where('user !=', 0)->countAllResults();
        $accumulated = $cartons * $game['price'];
        $total_award = $accumulated - ($accumulated * systemGet('rateEarnings'));

        if ($game['award'] == 2) {
            $awardPerSing = $award['amount'] / $singsCount;
        } else {
            $awardPerSing = ($total_award * $award['amount'] / 100) / $singsCount;
        }

        $user = $modelUsers->find($sing['user']);
        if (!$user) {
            return $this->response->setJSON(['success' => false, 'error' => translate('user not found')]);
        }

        if ($action === 'pay') {
            if ($sing['status'] === '1') {
                wallet_credit_withdrawable($sing['user'], (float) $awardPerSing);
            }
            $modelSings->update($id, ['status' => 2]);

            $dataPayment = [
                'user' => $sing['user'],
                'type' => 'award',
                'type_id' => $sing['id'],
                'amount' => $awardPerSing,
                'status' => 2
            ];

            $modelPayments->insert($dataPayment);
            $paymentId = $modelPayments->insertID();

            $modelNotifications = new NotificationsModel();

            $currentUserId = session()->get('id');

            $modalitySing = $modelModalities->find($sing['modality']);

            $notificationData = [
                'user' => $user['id'],
                'from' => $currentUserId,
                'game' => $sing['game'],
                'modality' => $sing['modality'],
                'type' => 'payment',
                'type_id' => $paymentId,
                'title' => '💵 PAGO ACREDITADO',
                'message' => 'Se ha acreditado en su billetera la suma de ' . systemGet('currency') . ' ' . number_format($awardPerSing, 2) . ' como pago por el 🏆 premio ganado en la partida "' . $game['description'] . '" modalidad ' . translate($modalitySing['name']) . '.',
            ];

            $modelNotifications->insert($notificationData);
        } elseif ($action === 'earring') {
            if ($sing['status'] === '2') {
                $modelUsers->update($sing['user'], ['wallet' => $user['wallet'] - $awardPerSing]);
            }
            $modelSings->update($id, ['status' => 1]);
        }

        return $this->response->setJSON(['success' => true]);
    }
}