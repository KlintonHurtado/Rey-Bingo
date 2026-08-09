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
use App\Models\TempCartonsModel;
use App\Models\AwardsModel;
use App\Models\SingsModel;
use App\Models\GameRoomsModel;
use App\Models\BoardsModel;
use App\Libraries\ExcelExport;
use CodeIgniter\Controller;

class Users extends Controller {
    public function __construct() {
        helper(['form', 'url', 'cookie', 'text', 'bingo']);
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
            helper('wallet');
            $data['userData'] = wallet_service()->normalizeUser($data['userData']);
        } else {
            $data['userData'] = null;
            $data['isUpdate'] = false;
        }
        
        return view('users/modalUser', $data);
    }

    public function stores()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        helper('bingo');

        $modelUsers = new UsersModel();
        $modelContacts = new ContactsModel();

        $user = $modelUsers->find(session()->get('id'));
        $imagePath = ! empty($user['image'])
            ? site_url('uploads/users/' . $user['image'])
            : site_url('assets/img/avatar.jpg');

        $stores = $modelUsers
            ->where('group', bingo_group_store())
            ->where('deleted', 0)
            ->orderBy('id', 'DESC')
            ->findAll();
        $stores = bingo_enrich_stores_with_operator($stores);

        $data = [
            'page' => [
                'title' => translate('point of sale management'),
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('stores/index', [
                'contacts' => $modelContacts->findAll(),
                'user' => $user,
                'imagePath' => $imagePath,
                'stores' => $stores,
            ]),
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        }

        return view('layout/index', $data);
    }

    public function addStore($storeId = null)
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        helper('bingo');

        $model = new UsersModel();
        $data = ['isUpdate' => false, 'storeData' => null];

        if ($storeId) {
            $store = $model->where('id', $storeId)->where('group', bingo_group_store())->first();
            if (! $store) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException(translate('store not found'));
            }
            $data['storeData'] = $store;
            $data['isUpdate'] = true;
        }

        $data['operators'] = bingo_list_operators(false);

        return view('stores/modalStore', $data);
    }

    public function storeSubmit()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        helper('bingo');

        $model = new UsersModel();
        $storeId = (int) $this->request->getPost('store-id');
        $action = $this->request->getPost('store-action');

        $validationRules = [
            'firstname' => [
                'label' => translate('first name'),
                'rules' => 'required|min_length[2]',
            ],
            'lastname' => [
                'label' => translate('last name'),
                'rules' => 'required|min_length[2]',
            ],
            'email' => [
                'label' => translate('email'),
                'rules' => 'required|valid_email|is_unique[users.email,id,' . $storeId . ']',
            ],
            'business_name' => [
                'label' => translate('business name'),
                'rules' => 'required|min_length[2]|max_length[255]',
            ],
            'store_commission_rate' => [
                'label' => translate('store commission rate'),
                'rules' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
            ],
        ];

        if ($action === 'add') {
            $validationRules['password'] = [
                'label' => translate('password'),
                'rules' => 'required|min_length[6]',
            ];
        } elseif ($this->request->getPost('password')) {
            $validationRules['password'] = [
                'label' => translate('password'),
                'rules' => 'min_length[6]',
            ];
        }

        if (! $this->validate($validationRules)) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors(),
            ]);
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        $storeCommissionRate = bingo_parse_store_commission_rate_post($this->request->getPost('store_commission_rate'));
        $ggrCommissionRate = bingo_parse_store_commission_rate_post($this->request->getPost('ggr_commission_rate'));
        $prizeCommissionRate = bingo_parse_store_commission_rate_post($this->request->getPost('store_prize_commission_rate'));
        $data = [
            'firstname' => trim((string) $this->request->getPost('firstname')),
            'lastname' => trim((string) $this->request->getPost('lastname')),
            'business_name' => trim((string) $this->request->getPost('business_name')),
            'email' => $email,
            'username' => bingo_generate_store_username($email, $model, $storeId ?: null),
            'group' => bingo_group_store(),
            'status' => 1,
            'sounds' => 0,
            'narration' => 1,
            'autodial' => 1,
            'roulette' => 1,
            'wallet' => 0,
            'document' => '',
            'phone' => '',
            'bank' => '',
            'account' => '',
            'image' => '',
            'verified_email' => 1,
            'verification_token' => '',
            'restore_code' => '',
            'restore_token' => '',
            'is_reseller' => 0,
            'store_commission_rate' => $storeCommissionRate,
            'ggr_commission_rate' => $ggrCommissionRate,
            'store_prize_commission_rate' => $prizeCommissionRate,
            'kyc_status' => 'verified',
        ];

        if ($action === 'update' && $storeId) {
            $existing = $model->where('id', $storeId)->where('group', bingo_group_store())->first();
            if (! $existing) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => translate('store not found'),
                ]);
            }

            $updateData = [
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'business_name' => $data['business_name'],
                'email' => $email,
                'store_commission_rate' => $storeCommissionRate,
                'ggr_commission_rate' => $ggrCommissionRate,
                'store_prize_commission_rate' => $prizeCommissionRate,
            ];

            if ($email !== strtolower(trim((string) $existing['email']))) {
                $updateData['username'] = bingo_generate_store_username($email, $model, $storeId);
            }

            if ($this->request->getPost('password')) {
                $updateData['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
            }

            if ($model->update($storeId, $updateData)) {
                bingo_assign_store_operator($storeId, (int) $this->request->getPost('operator_id') ?: null);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => translate('store updated successfully'),
                ]);
            }
        } else {
            $lastUser = $model->orderBy('id', 'DESC')->first();
            $nextId = $lastUser ? ((int) $lastUser['id'] + 1) : 1;
            $data['code'] = 'BGC-T' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
            $data['document'] = 'ST-' . str_pad((string) $nextId, 8, '0', STR_PAD_LEFT);
            $data['phone'] = '9' . str_pad((string) $nextId, 10, '0', STR_PAD_LEFT);
            $data['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
            $data['referred_code'] = strtoupper(substr(md5(uniqid()), 0, 8));

            if ($model->insert($data)) {
                $newStoreId = (int) $model->getInsertID();
                bingo_assign_store_operator($newStoreId, (int) $this->request->getPost('operator_id') ?: null);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => translate('store added successfully'),
                ]);
            }
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => translate('error processing request'),
        ]);
    }

    public function storeDeactivate()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        $storeId = (int) $this->request->getPost('store_id');
        $status = (int) $this->request->getPost('status');

        if ($storeId < 1) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('user id required'),
            ]);
        }

        $model = new UsersModel();
        $store = $model->where('id', $storeId)->where('group', bingo_group_store())->where('deleted', 0)->first();

        if (! $store) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('store not found'),
            ]);
        }

        $newStatus = $status === 1 ? 1 : 2;

        if ($model->update($storeId, ['status' => $newStatus])) {
            return $this->response->setJSON([
                'success' => true,
                'message' => $newStatus === 1
                    ? translate('store activated successfully')
                    : translate('store deactivated successfully'),
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'error' => translate('error updating user status'),
        ]);
    }

    public function storeDelete()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        $storeId = (int) $this->request->getPost('store_id');
        if ($storeId < 1) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('user id required'),
            ]);
        }

        $model = new UsersModel();
        $store = $model->where('id', $storeId)->where('group', bingo_group_store())->where('deleted', 0)->first();

        if (! $store) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('store not found'),
            ]);
        }

        if ($model->update($storeId, ['deleted' => 1, 'status' => 2])) {
            return $this->response->setJSON([
                'success' => true,
                'message' => translate('store deleted successfully'),
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'error' => translate('error deleting user'),
        ]);
    }

    public function storesListGet()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        $modelUsers = new UsersModel();
        $stores = $modelUsers
            ->where('group', bingo_group_store())
            ->where('deleted', 0)
            ->orderBy('id', 'DESC')
            ->findAll();

        return view('stores/list', ['stores' => bingo_enrich_stores_with_operator($stores)]);
    }

    public function operators()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        helper('bingo');

        $modelUsers = new UsersModel();
        $modelContacts = new ContactsModel();

        $user = $modelUsers->find(session()->get('id'));
        $imagePath = ! empty($user['image'])
            ? site_url('uploads/users/' . $user['image'])
            : site_url('assets/img/avatar.jpg');

        $operators = $modelUsers
            ->where('group', bingo_group_operator())
            ->where('deleted', 0)
            ->orderBy('id', 'DESC')
            ->findAll();

        foreach ($operators as &$operator) {
            $operator['stores_count'] = bingo_operator_store_count((int) $operator['id']);
        }

        $data = [
            'page' => [
                'title' => translate('operator management'),
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('operators/index', [
                'contacts' => $modelContacts->findAll(),
                'user' => $user,
                'imagePath' => $imagePath,
                'operators' => $operators,
            ]),
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        }

        return view('layout/index', $data);
    }

    public function addOperator($operatorId = null)
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        helper('bingo');

        $model = new UsersModel();
        $data = ['isUpdate' => false, 'operatorData' => null, 'assignedStoreIds' => []];

        if ($operatorId) {
            $operator = $model->where('id', $operatorId)->where('group', bingo_group_operator())->first();
            if (! $operator) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException(translate('operator not found'));
            }

            $data['operatorData'] = $operator;
            $data['isUpdate'] = true;
            $assigned = $model
                ->select('id')
                ->where('group', bingo_group_store())
                ->where('operator_id', (int) $operatorId)
                ->where('deleted', 0)
                ->findAll();
            $data['assignedStoreIds'] = array_map(static fn ($row) => (int) $row['id'], $assigned);
        }

        $data['stores'] = $model
            ->where('group', bingo_group_store())
            ->where('deleted', 0)
            ->orderBy('business_name', 'ASC')
            ->findAll();

        return view('operators/modalOperator', $data);
    }

    public function operatorSubmit()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        helper('bingo');

        $model = new UsersModel();
        $operatorId = (int) $this->request->getPost('operator-id');
        $action = $this->request->getPost('operator-action');
        $storeIds = $this->request->getPost('store_ids');
        $storeIds = is_array($storeIds) ? $storeIds : [];

        $validationRules = [
            'firstname' => [
                'label' => translate('first name'),
                'rules' => 'required|min_length[2]',
            ],
            'lastname' => [
                'label' => translate('last name'),
                'rules' => 'required|min_length[2]',
            ],
            'email' => [
                'label' => translate('email'),
                'rules' => 'required|valid_email|is_unique[users.email,id,' . $operatorId . ']',
            ],
        ];

        if ($action === 'add') {
            $validationRules['password'] = [
                'label' => translate('password'),
                'rules' => 'required|min_length[6]',
            ];
        } elseif ($this->request->getPost('password')) {
            $validationRules['password'] = [
                'label' => translate('password'),
                'rules' => 'min_length[6]',
            ];
        }

        if (! $this->validate($validationRules)) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors(),
            ]);
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        $operatorCommissionRate = bingo_parse_store_commission_rate_post($this->request->getPost('operator_commission_rate'));
        $operatorRechargeRate = bingo_parse_store_commission_rate_post($this->request->getPost('operator_recharge_rate'));
        $operatorWithdrawRate = bingo_parse_store_commission_rate_post($this->request->getPost('operator_withdraw_rate'));
        $data = [
            'firstname' => trim((string) $this->request->getPost('firstname')),
            'lastname' => trim((string) $this->request->getPost('lastname')),
            'email' => $email,
            'username' => bingo_generate_operator_username($email, $model, $operatorId ?: null),
            'group' => bingo_group_operator(),
            'status' => 1,
            'sounds' => 0,
            'narration' => 1,
            'autodial' => 1,
            'roulette' => 1,
            'wallet' => 0,
            'document' => '',
            'phone' => '',
            'bank' => '',
            'account' => '',
            'image' => '',
            'verified_email' => 1,
            'verification_token' => '',
            'restore_code' => '',
            'restore_token' => '',
            'is_reseller' => 0,
            'operator_commission_rate' => $operatorCommissionRate,
            'store_commission_rate' => $operatorRechargeRate,
            'store_prize_commission_rate' => $operatorWithdrawRate,
            'ggr_commission_rate' => null,
            'kyc_status' => 'verified',
        ];

        if ($action === 'update' && $operatorId) {
            $existing = $model->where('id', $operatorId)->where('group', bingo_group_operator())->first();
            if (! $existing) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => translate('operator not found'),
                ]);
            }

            $updateData = [
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'email' => $email,
                'operator_commission_rate' => $operatorCommissionRate,
                'store_commission_rate' => $operatorRechargeRate,
                'store_prize_commission_rate' => $operatorWithdrawRate,
                'ggr_commission_rate' => null,
            ];

            if ($email !== strtolower(trim((string) $existing['email']))) {
                $updateData['username'] = bingo_generate_operator_username($email, $model, $operatorId);
            }

            if ($this->request->getPost('password')) {
                $updateData['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
            }

            if ($model->update($operatorId, $updateData)) {
                bingo_sync_operator_stores($operatorId, $storeIds);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => translate('operator updated successfully'),
                ]);
            }
        } else {
            $lastUser = $model->orderBy('id', 'DESC')->first();
            $nextId = $lastUser ? ((int) $lastUser['id'] + 1) : 1;
            $data['code'] = 'BGC-O' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
            $data['document'] = 'OP-' . str_pad((string) $nextId, 8, '0', STR_PAD_LEFT);
            $data['phone'] = '8' . str_pad((string) $nextId, 10, '0', STR_PAD_LEFT);
            $data['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
            $data['referred_code'] = strtoupper(substr(md5(uniqid('operator', true)), 0, 8));

            if ($model->insert($data)) {
                $newOperatorId = (int) $model->getInsertID();
                bingo_sync_operator_stores($newOperatorId, $storeIds);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => translate('operator added successfully'),
                ]);
            }
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => translate('error processing request'),
        ]);
    }

    public function operatorDeactivate()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        $operatorId = (int) $this->request->getPost('operator_id');
        $status = (int) $this->request->getPost('status');

        if ($operatorId < 1) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('user id required'),
            ]);
        }

        $model = new UsersModel();
        $operator = $model->where('id', $operatorId)->where('group', bingo_group_operator())->where('deleted', 0)->first();

        if (! $operator) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('operator not found'),
            ]);
        }

        $newStatus = $status === 1 ? 1 : 2;

        if ($model->update($operatorId, ['status' => $newStatus])) {
            return $this->response->setJSON([
                'success' => true,
                'message' => $newStatus === 1
                    ? translate('operator activated successfully')
                    : translate('operator deactivated successfully'),
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'error' => translate('error updating user status'),
        ]);
    }

    public function operatorDelete()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        helper('bingo');

        $operatorId = (int) $this->request->getPost('operator_id');
        if ($operatorId < 1) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('user id required'),
            ]);
        }

        $model = new UsersModel();
        $operator = $model->where('id', $operatorId)->where('group', bingo_group_operator())->where('deleted', 0)->first();

        if (! $operator) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('operator not found'),
            ]);
        }

        if ($model->update($operatorId, ['deleted' => 1, 'status' => 2])) {
            bingo_sync_operator_stores($operatorId, []);

            return $this->response->setJSON([
                'success' => true,
                'message' => translate('operator deleted successfully'),
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'error' => translate('error deleting user'),
        ]);
    }

    public function operatorsListGet()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        helper('bingo');

        $modelUsers = new UsersModel();
        $operators = $modelUsers
            ->where('group', bingo_group_operator())
            ->where('deleted', 0)
            ->orderBy('id', 'DESC')
            ->findAll();

        foreach ($operators as &$operator) {
            $operator['stores_count'] = bingo_operator_store_count((int) $operator['id']);
        }

        return view('operators/list', ['operators' => $operators]);
    }

    public function lowBalancePlayers()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        helper(['bingo', 'wallet']);

        $modelUsers = new UsersModel();
        $modelContacts = new ContactsModel();

        $user = $modelUsers->find(session()->get('id'));
        $imagePath = ! empty($user['image'])
            ? site_url('uploads/users/' . $user['image'])
            : site_url('assets/img/avatar.jpg');

        $payload = bingo_fetch_low_balance_players();

        $data = [
            'page' => [
                'title' => translate('low balance players'),
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('users/low_balance_players/index', [
                'contacts' => $modelContacts->findAll(),
                'user' => $user,
                'imagePath' => $imagePath,
                'players' => $payload['players'],
                'threshold' => $payload['threshold'],
            ]),
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        }

        return view('layout/index', $data);
    }

    public function lowBalancePlayersListGet()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        helper(['bingo', 'wallet']);

        if (bingo_low_balance_auto_enabled()) {
            bingo_process_low_balance_auto_roulette_batch();
        }

        $payload = bingo_fetch_low_balance_players();

        return view('users/low_balance_players/list', [
            'players' => $payload['players'],
            'threshold' => $payload['threshold'],
        ]);
    }

    public function exportLowBalancePlayers()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        helper(['bingo', 'wallet']);

        $payload = bingo_fetch_low_balance_players();
        $players = $payload['players'] ?? [];
        $threshold = (float) ($payload['threshold'] ?? 0);
        $currency = (string) systemGet('currency');

        $headers = [
            'ID',
            'Codigo',
            'Nombre',
            'Usuario',
            'Email',
            'Telefono',
            'Documento',
            'Saldo total',
            'Saldo recarga',
            'Saldo retiro',
            'Saldo bono',
            'Ruleta disponible',
            'Ultimo otorgamiento ruleta',
            'Origen otorgamiento',
            'Umbral',
        ];

        $rows = [];
        foreach ($players as $player) {
            $latestGrant = $player['latest_grant'] ?? null;
            $grantAt = '';
            $grantSource = '';
            if (is_array($latestGrant)) {
                $grantAt = (string) ($latestGrant['created_at'] ?? '');
                $grantSource = (($latestGrant['source'] ?? '') === 'auto')
                    ? translate('automatic')
                    : translate('manual');
            }

            $rows[] = [
                $player['id'] ?? '',
                $player['code'] ?? '',
                trim(($player['firstname'] ?? '') . ' ' . ($player['lastname'] ?? '')),
                $player['username'] ?? '',
                $player['email'] ?? '',
                $player['phone'] ?? '',
                $player['document'] ?? '',
                number_format((float) ($player['wallet_total'] ?? 0), 2, '.', ''),
                number_format((float) ($player['wallet_recharge_display'] ?? $player['wallet_recharge'] ?? 0), 2, '.', ''),
                number_format((float) ($player['wallet_withdraw_display'] ?? $player['wallet_withdraw'] ?? 0), 2, '.', ''),
                number_format((float) ($player['wallet_bonus'] ?? 0), 2, '.', ''),
                ((int) ($player['roulette'] ?? 1) === 0) ? translate('active') : translate('inactive'),
                $grantAt,
                $grantSource,
                number_format($threshold, 2, '.', '') . ' ' . $currency,
            ];
        }

        $filename = 'jugadores-poco-saldo-' . date('Ymd-His') . '.xls';

        return (new ExcelExport())->downloadResponse($headers, $rows, $filename, [
            'sheet_name' => 'Poco saldo',
        ]);
    }

    public function lowBalanceHistoryListGet()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        helper(['bingo', 'wallet']);

        return view('users/low_balance_players/history_modal', [
            'history' => bingo_fetch_low_balance_roulette_history(),
        ]);
    }

    public function grantPlayerRoulette()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => translate('unauthorized')]);
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => translate('unauthorized')]);
        }

        $userId = (int) $this->request->getPost('user_id');
        if ($userId <= 0) {
            return $this->response->setJSON(['success' => false, 'error' => translate('user not found')]);
        }

        $modelUsers = new UsersModel();
        $player = $modelUsers
            ->where('id', $userId)
            ->where('group', bingo_group_player())
            ->where('deleted', 0)
            ->first();

        if (! $player) {
            return $this->response->setJSON(['success' => false, 'error' => translate('user not found')]);
        }

        if ((int) ($player['roulette'] ?? 1) === 0) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('player already has roulette'),
            ]);
        }

        if (! bingo_user_kyc_verified($player)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'El jugador debe tener KYC verificado para recibir el Ruletazo.',
            ]);
        }

        helper('bingo');

        if (! bingo_grant_player_roulette($userId, translate('roulette granted notification'), true)) {
            return $this->response->setJSON(['success' => false, 'error' => translate('user not found')]);
        }

        bingo_log_low_balance_roulette_grant($userId, 'manual', (int) session()->get('id'));

        return $this->response->setJSON([
            'success' => true,
            'message' => translate('roulette granted successfully'),
            'pending_count' => bingo_low_balance_roulette_pending_count(),
        ]);
    }

    public function grantBonusGet($userId = null)
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        helper('wallet');

        $modelUsers = new UsersModel();
        $selectedUserId = (int) ($userId ?? $this->request->getGet('user_id') ?? 0);

        $players = $modelUsers
            ->select('id, code, firstname, lastname, username, email, wallet_bonus')
            ->where('group', bingo_group_player())
            ->where('deleted', 0)
            ->where('status', 1)
            ->orderBy('firstname', 'ASC')
            ->findAll();

        $selectedUser = null;
        if ($selectedUserId > 0) {
            foreach ($players as $player) {
                if ((int) $player['id'] === $selectedUserId) {
                    $selectedUser = wallet_service()->normalizeUser($player);
                    break;
                }
            }
            if (! $selectedUser) {
                $found = $modelUsers
                    ->where('id', $selectedUserId)
                    ->where('group', bingo_group_player())
                    ->where('deleted', 0)
                    ->first();
                if ($found) {
                    $selectedUser = wallet_service()->normalizeUser($found);
                }
            }
        }

        return view('users/grant_bonus', [
            'players' => $players,
            'selectedUserId' => $selectedUserId,
            'selectedUser' => $selectedUser,
        ]);
    }

    public function grantPlayerBonus()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error' => translate('unauthorized'),
            ]);
        }

        helper('wallet');

        $userId = (int) $this->request->getPost('user_id');
        $amount = (float) $this->request->getPost('amount');
        $note = trim((string) $this->request->getPost('note'));
        if (mb_strlen($note) > 200) {
            $note = mb_substr($note, 0, 200);
        }

        $result = wallet_grant_admin_bonus(
            $userId,
            $amount,
            (int) session()->get('id'),
            $note
        );

        if (! ($result['success'] ?? false)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $result['message'] ?? translate('invalid bonus amount'),
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => $result['message'],
            'amount' => $result['amount'] ?? $amount,
            'wallet_bonus' => $result['wallet_bonus'] ?? null,
            'user_id' => $userId,
        ]);
    }

    public function updatePlayerWallets()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error' => translate('unauthorized'),
            ]);
        }

        helper('wallet');

        $userId = (int) $this->request->getPost('user_id');
        $bonus = (float) $this->request->getPost('wallet_bonus');
        $recharge = (float) $this->request->getPost('wallet_recharge');
        $withdraw = (float) $this->request->getPost('wallet_withdraw');

        if ($userId <= 0) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('user not found'),
            ]);
        }

        if ($bonus < 0 || $recharge < 0 || $withdraw < 0) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('invalid wallet amounts'),
            ]);
        }

        $modelUsers = new UsersModel();
        $player = $modelUsers
            ->where('id', $userId)
            ->where('deleted', 0)
            ->first();

        if (! $player) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('user not found'),
            ]);
        }

        wallet_set_balances($userId, $bonus, $recharge, $withdraw);
        $updated = wallet_service()->normalizeUser($modelUsers->find($userId) ?: $player);

        return $this->response->setJSON([
            'success' => true,
            'message' => translate('wallets updated successfully'),
            'wallets' => [
                'bonus' => round((float) ($updated['wallet_bonus'] ?? 0), 2),
                'recharge' => round((float) ($updated['wallet_recharge'] ?? 0), 2),
                'withdraw' => round((float) ($updated['wallet_withdraw'] ?? 0), 2),
                'total' => round(wallet_total($updated), 2),
            ],
            'user_id' => $userId,
        ]);
    }

    public function lowBalancePendingCountGet()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => translate('unauthorized')]);
        }

        helper('bingo');

        return $this->response->setJSON([
            'success' => true,
            'count' => bingo_low_balance_roulette_pending_count(),
        ]);
    }

    public function lowBalanceSettingsSubmit()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => translate('unauthorized')]);
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => translate('unauthorized')]);
        }

        $threshold = $this->request->getPost('lowBalanceThreshold');
        $autoRoulette = $this->request->getPost('lowBalanceAutoRoulette');

        if ($threshold === null || $threshold === '' || ! is_numeric($threshold) || (float) $threshold < 0) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('low balance threshold invalid'),
            ]);
        }

        if (! in_array((string) $autoRoulette, ['0', '1'], true)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('invalid value'),
            ]);
        }

        helper('bingo');
        bingo_ensure_system_settings_schema();

        try {
            $modelSystem = new \App\Models\SystemModel();
            $modelSystem->updateValue('lowBalanceThreshold', number_format((float) $threshold, 2, '.', ''));
            $modelSystem->updateValue('lowBalanceAutoRoulette', (string) $autoRoulette);

            $autoProcessed = 0;
            if ((int) $autoRoulette === 1) {
                $autoProcessed = bingo_process_low_balance_auto_roulette_batch();
            }

            $message = translate('low balance settings saved');
            if ($autoProcessed > 0) {
                $message .= ' (' . $autoProcessed . ' ' . translate('players') . ')';
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
                'threshold' => bingo_low_balance_threshold(),
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Error: ' . $e->getMessage(),
            ]);
        }
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
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error' => translate('unauthorized'),
            ]);
        }

        $model = new UsersModel();

        $userId = (int) $this->request->getPost('user_id');
        // 0 = ban, 1 = unban (castear: POST "0" no debe tratarse como vacío)
        $status = ((int) $this->request->getPost('status') === 1) ? 1 : 0;
        
        if ($userId < 1) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('user id required')
            ]);
        }

        if ($userId === (int) session()->get('id')) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('you cannot ban yourself'),
            ]);
        }
        
        $user = $model->find($userId);
        
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('user not found')
            ]);
        }

        // No banear administradores
        if ((int) ($user['group'] ?? -1) === 1 && $status === 0) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('cannot ban admin users'),
            ]);
        }

        $payload = [
            'status' => $status,
        ];
        // Al banear, invalidar "recordarme" para impedir reingreso automático
        if ($status === 0) {
            $payload['remember_token'] = null;
        }
        
        if (! $model->update($userId, $payload)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('error updating user status')
            ]);
        }

        $fresh = $model->find($userId);
        if (! $fresh || (int) ($fresh['status'] ?? -1) !== $status) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('error updating user status')
            ]);
        }

        // Cerrar sesiones activas del usuario baneado (si el driver es DB)
        if ($status === 0 && function_exists('bingo_destroy_user_sessions')) {
            bingo_destroy_user_sessions($userId);
        }
            
        $message = $status === 0 ? translate('user banned successfully') : translate('user unbanned successfully');
        return $this->response->setJSON([
            'success' => true,
            'message' => $message,
            'status' => $status,
        ]);
    }

    public function getUserDetails($userId) {
        if (! session()->get('logged_in')) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error' => translate('unauthorized access'),
            ]);
        }

        bingo_ensure_users_schema();
        helper(['bingo', 'wallet']);

        $model = new UsersModel();
        $userId = (int) $userId;
        $user = $model->find($userId);
        
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('user not found')
            ]);
        }

        $sessionGroup = (int) session()->get('group');
        $sessionUserId = (int) session()->get('id');

        $isAllowed = false;
        if ($sessionGroup === 1) {
            $isAllowed = true;
        } elseif ($sessionGroup === 3) {
            if ($userId === $sessionUserId) {
                $isAllowed = true;
            } elseif ((int) ($user['group'] ?? 0) === bingo_group_store() && (int) ($user['operator_id'] ?? 0) === $sessionUserId) {
                $isAllowed = true;
            }
        }

        if (!$isAllowed) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error' => translate('unauthorized access'),
            ]);
        }
        $modelCartons = new CartonsModel();
        $modelDeposits = new DepositsModel();
        $modelRetires = new RetiresModel();
        $modelRoulettes = new RoulettesModel();
        $modelPayments = new PaymentsModel();
        $modelLogs = new \App\Models\LogsModel();
        $modelGames = new GamesModel();
        $modelSings = new SingsModel();
        $modelPurchaseLogs = new \App\Models\CartonPurchaseLogsModel();

        $user = $model->find($userId);
        
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('user not found')
            ]);
        }

        $user = wallet_service()->normalizeUser($user);
        unset($user['password']);

        $totalDeposits = (float) (($modelDeposits
            ->where('user', $userId)
            ->where('status', 2)
            ->selectSum('amount')
            ->get()
            ->getRow()
            ->amount) ?? 0);

        $totalRetires = (float) (($modelRetires
            ->where('user', $userId)
            ->where('status', 2)
            ->selectSum('amount')
            ->get()
            ->getRow()
            ->amount) ?? 0);

        $grantedCartons = (int) (($modelRoulettes
            ->where('user', $userId)
            ->selectSum('cartons')
            ->get()
            ->getRow()
            ->cartons) ?? 0);

        $rouletteAmount = (float) (($modelRoulettes
            ->where('user', $userId)
            ->where('status', 1)
            ->selectSum('amount')
            ->get()
            ->getRow()
            ->amount) ?? 0);

        $bonusReleased = (float) (($modelPurchaseLogs
            ->where('user_id', $userId)
            ->selectSum('from_bonus')
            ->get()
            ->getRow()
            ->from_bonus) ?? 0);

        $rouletteReleased = (float) (($modelPurchaseLogs
            ->where('user_id', $userId)
            ->where('source', 'roulette')
            ->selectSum('amount')
            ->get()
            ->getRow()
            ->amount) ?? 0);

        if ($rouletteReleased <= 0) {
            $rouletteReleased = $rouletteAmount;
        }

        $totalPrizes = (float) (($modelPayments
            ->where('user', $userId)
            ->where('type', 'award')
            ->where('status', 2)
            ->selectSum('amount')
            ->get()
            ->getRow()
            ->amount) ?? 0);

        $stats = [
            'total_cartons' => $modelCartons->where('user', $userId)->countAllResults(),
            'total_deposits' => round($totalDeposits, 2),
            'total_retires' => round($totalRetires, 2),
            'total_roulettes' => round($rouletteAmount, 2),
            'granted_cartons' => $grantedCartons,
            'pending_cartons' => bingo_count_pending_roulette_cartons((int) $userId),
            'wallet_total' => wallet_total($user),
            'wallet_recharge' => (float) ($user['wallet_recharge'] ?? 0),
            'wallet_withdraw' => (float) ($user['wallet_withdraw'] ?? 0),
            'wallet_bonus' => (float) ($user['wallet_bonus'] ?? 0),
            'bonus_released' => round($bonusReleased, 2),
            'roulette_released' => round($rouletteReleased, 2),
            'total_prizes' => round($totalPrizes, 2),
            'last_activity' => $this->getLastActivity($userId),
        ];

        $deposits = $modelDeposits->where('user', $userId)->orderBy('date', 'DESC')->findAll(100);
        $retires = $modelRetires->where('user', $userId)->orderBy('created_at', 'DESC')->findAll(100);

        $prizes = $modelPayments
            ->where('user', $userId)
            ->where('type', 'award')
            ->orderBy('created_at', 'DESC')
            ->findAll(100);

        $prizeRows = [];
        foreach ($prizes as $prize) {
            $sing = $modelSings->find((int) ($prize['type_id'] ?? 0));
            $game = $sing ? $modelGames->find((int) ($sing['game'] ?? 0)) : null;
            $prizeRows[] = [
                'id' => $prize['id'],
                'amount' => (float) $prize['amount'],
                'status' => (int) $prize['status'],
                'created_at' => $prize['created_at'],
                'game' => $game['description'] ?? ('#' . ($sing['game'] ?? '-')),
                'modality' => $sing['modality'] ?? '-',
                'carton' => $sing['carton'] ?? '-',
            ];
        }

        $purchaseRows = bingo_build_user_carton_purchase_report((int) $userId, 500);
        $movements = bingo_build_user_movements_ledger((int) $userId, 1500);

        $roulettes = $modelRoulettes->where('user', $userId)->orderBy('created_at', 'DESC')->findAll(80);
        $loginLogs = $modelLogs
            ->where('id_user', $userId)
            ->whereIn('action', ['login', 'signup', 'google_signup'])
            ->orderBy('created_at', 'DESC')
            ->findAll(30);

        $lastLog = $loginLogs[0] ?? null;
        $ip = (string) ($user['last_ip'] ?? ($lastLog['ip_address'] ?? ''));
        $mac = (string) ($user['last_mac'] ?? '');
        $docExpiry = bingo_document_expiry_status($user);
        $kycStatus = (string) ($user['kyc_status'] ?? 'pending');

        $html = view('users/user_details_admin', [
            'user' => $user,
            'stats' => $stats,
            'deposits' => $deposits,
            'retires' => $retires,
            'prizes' => $prizeRows,
            'purchases' => $purchaseRows,
            'movements' => $movements,
            'roulettes' => $roulettes,
            'loginLogs' => $loginLogs,
            'ip' => $ip,
            'mac' => $mac,
            'docExpiry' => $docExpiry,
            'kycStatus' => $kycStatus,
            'currency' => systemGet('currency'),
        ]);

        return $this->response->setJSON([
            'success' => true,
            'html' => $html,
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    public function exportRiskAnalysis($userId)
    {
        if (! session()->get('logged_in') || (int) session()->get('group') !== 1) {
            return redirect()->to('/signin');
        }

        bingo_ensure_users_schema();
        helper('wallet');

        $model = new UsersModel();
        $user = $model->find((int) $userId);
        if (! $user) {
            return redirect()->back()->with('error', translate('user not found'));
        }

        $user = wallet_service()->normalizeUser($user);
        $details = $this->buildRiskExportRows((int) $userId, $user);

        $filename = 'riesgo-usuario-' . ($user['code'] ?? $userId) . '-' . date('Ymd-His') . '.xls';

        return (new ExcelExport())->downloadResponse(
            ['Seccion', 'Campo', 'Valor', 'Detalle'],
            $details,
            $filename,
            ['sheet_name' => 'Riesgo']
        );
    }

    public function exportUserMovements($userId)
    {
        if (! session()->get('logged_in') || (int) session()->get('group') !== 1) {
            return redirect()->to('/signin');
        }

        bingo_ensure_users_schema();
        helper(['bingo', 'wallet']);

        $model = new UsersModel();
        $user = $model->find((int) $userId);
        if (! $user) {
            return redirect()->back()->with('error', translate('user not found'));
        }

        $movements = bingo_build_user_movements_ledger((int) $userId, 5000);
        $export = bingo_user_movements_export_rows($movements);
        $code = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($user['code'] ?? $userId)) ?: (string) $userId;
        $filename = 'movimientos-usuario-' . $code . '-' . date('Ymd-His') . '.xls';

        return (new ExcelExport())->downloadResponse(
            $export['headers'],
            $export['rows'],
            $filename,
            [
                'sheet_name' => 'Movimientos',
                'numeric_columns' => [3, 9, 11, 12, 13],
            ]
        );
    }

    /**
     * @return list<list<mixed>>
     */
    private function buildRiskExportRows(int $userId, array $user): array
    {
        $modelDeposits = new DepositsModel();
        $modelRetires = new RetiresModel();
        $modelPayments = new PaymentsModel();
        $modelLogs = new \App\Models\LogsModel();
        $modelRoulettes = new RoulettesModel();
        $modelPurchaseLogs = new \App\Models\CartonPurchaseLogsModel();
        $modelCartons = new CartonsModel();

        $rows = [];
        $add = static function (string $section, string $field, $value, string $detail = '') use (&$rows) {
            $rows[] = [$section, $field, $value, $detail];
        };

        $docExpiry = bingo_document_expiry_status($user);
        $add('Perfil', 'ID', $userId);
        $add('Perfil', 'Codigo', $user['code'] ?? '');
        $add('Perfil', 'Nombre', trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')));
        $add('Perfil', 'Usuario', $user['username'] ?? '');
        $add('Perfil', 'Email', $user['email'] ?? '');
        $add('Perfil', 'Telefono', $user['phone'] ?? '');
        $add('Perfil', 'Documento', $user['document'] ?? '');
        $add('Perfil', 'Vence documento', $docExpiry['expires_at'] ?? '', $docExpiry['label'] ?? '');
        $add('Perfil', 'KYC', $user['kyc_status'] ?? 'pending');
        $add('Perfil', 'IP', $user['last_ip'] ?? '');
        $add('Perfil', 'MAC', $user['last_mac'] ?: translate('mac not available web'));
        $add('Saldos', 'Total', wallet_total($user));
        $add('Saldos', 'Recarga', (float) ($user['wallet_recharge'] ?? 0));
        $add('Saldos', 'Retiro', (float) ($user['wallet_withdraw'] ?? 0));
        $add('Saldos', 'Bono', (float) ($user['wallet_bonus'] ?? 0));

        $deposits = $modelDeposits->where('user', $userId)->orderBy('date', 'DESC')->findAll(200);
        foreach ($deposits as $d) {
            $add('Depositos', '#' . $d['id'], (float) $d['amount'], 'status=' . $d['status'] . ' ref=' . ($d['reference'] ?? '') . ' date=' . ($d['date'] ?? ''));
        }

        $retires = $modelRetires->where('user', $userId)->orderBy('created_at', 'DESC')->findAll(200);
        foreach ($retires as $r) {
            $add('Retiros', '#' . $r['id'], (float) $r['amount'], 'status=' . $r['status'] . ' bank=' . ($r['bank'] ?? '') . ' date=' . ($r['created_at'] ?? ''));
        }

        $awards = $modelPayments->where('user', $userId)->where('type', 'award')->orderBy('created_at', 'DESC')->findAll(200);
        foreach ($awards as $a) {
            $add('Premios', '#' . $a['id'], (float) $a['amount'], 'date=' . ($a['created_at'] ?? ''));
        }

        $purchases = bingo_build_user_carton_purchase_report((int) $userId, 500);
        foreach ($purchases as $p) {
            $add(
                'Compras cartones',
                (string) ($p['serial'] ?? $p['id'] ?? ''),
                (float) ($p['amount'] ?? 0),
                'partida=' . ($p['game'] ?? '')
                . ' origen=' . ($p['source'] ?? '')
                . ' bono=' . ($p['from_bonus'] ?? 0)
                . ' recarga=' . ($p['from_recharge'] ?? 0)
                . ' retiro=' . ($p['from_withdraw'] ?? 0)
                . ' resultado=' . ($p['result_label'] ?? '')
                . ' premio=' . ($p['prize_amount'] ?? 0)
                . ' acredito=' . ($p['credit_label'] ?? '')
            );
        }

        $roulettes = $modelRoulettes->where('user', $userId)->orderBy('created_at', 'DESC')->findAll(200);
        foreach ($roulettes as $roulette) {
            $add('Ruleta', '#' . $roulette['id'], (float) $roulette['amount'], 'cartones=' . $roulette['cartons'] . ' status=' . $roulette['status']);
        }

        $logs = $modelLogs->where('id_user', $userId)->orderBy('created_at', 'DESC')->findAll(100);
        foreach ($logs as $log) {
            $add('Accesos', $log['action'] ?? '', $log['ip_address'] ?? '', ($log['country'] ?? '') . ' | ' . ($log['created_at'] ?? ''));
        }

        $ips = [];
        foreach ($logs as $log) {
            $ip = trim((string) ($log['ip_address'] ?? ''));
            if ($ip !== '') {
                $ips[$ip] = ($ips[$ip] ?? 0) + 1;
            }
        }
        foreach ($ips as $ip => $count) {
            $add('Riesgo', 'IP distinta', $ip, 'veces=' . $count);
        }

        $sameDoc = [];
        if (! empty($user['document'])) {
            $sameDoc = (new UsersModel())
                ->where('document', $user['document'])
                ->where('id !=', $userId)
                ->where('deleted', 0)
                ->findAll(20);
        }
        foreach ($sameDoc as $dup) {
            $add('Riesgo', 'Documento duplicado', $dup['code'] ?? $dup['id'], ($dup['firstname'] ?? '') . ' ' . ($dup['lastname'] ?? ''));
        }

        $add('Resumen', 'Total cartones', $modelCartons->where('user', $userId)->countAllResults());
        $add('Resumen', 'Depositos aprobados', (float) (($modelDeposits->where('user', $userId)->where('status', 2)->selectSum('amount')->get()->getRow()->amount) ?? 0));
        $add('Resumen', 'Retiros aprobados', (float) (($modelRetires->where('user', $userId)->where('status', 2)->selectSum('amount')->get()->getRow()->amount) ?? 0));

        return $rows;
    }

    public function revokeKyc()
    {
        if (! session()->get('logged_in') || (int) session()->get('group') !== 1) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => translate('unauthorized access'),
            ]);
        }

        $userId = (int) $this->request->getPost('user_id');
        $observations = trim((string) $this->request->getPost('kyc_observations'));
        if ($userId <= 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('user not found'),
            ]);
        }

        bingo_ensure_users_schema();
        $model = new UsersModel();
        $user = $model->find($userId);
        if (! $user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('user not found'),
            ]);
        }

        $note = $observations !== ''
            ? $observations
            : translate('kyc revoked by admin');

        $model->update($userId, [
            'kyc_status' => 'pending',
            'kyc_front' => null,
            'kyc_back' => null,
            'kyc_selfie' => null,
            'kyc_observations' => $note,
        ]);

        if ((int) ($user['roulette'] ?? 1) === 0) {
            $alreadyClaimed = (new \App\Models\RoulettesModel())
                ->where('user', $userId)
                ->countAllResults() > 0;
            if (! $alreadyClaimed) {
                $model->update($userId, ['roulette' => 1]);
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => translate('kyc verification removed'),
        ]);
    }

    public function saveDocumentExpiry()
    {
        if (! session()->get('logged_in') || (int) session()->get('group') !== 1) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => translate('unauthorized access'),
            ]);
        }

        $userId = (int) $this->request->getPost('user_id');
        $expires = trim((string) $this->request->getPost('document_expires_at'));
        if ($userId <= 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('user not found'),
            ]);
        }

        bingo_ensure_users_schema();
        $value = $expires !== '' ? date('Y-m-d', strtotime($expires)) : null;
        (new UsersModel())->update($userId, [
            'document_expires_at' => $value,
        ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => translate('document expiry updated'),
            'document_expires_at' => $value,
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
        $stats['store_users'] = $this->modelUsers->where('group', 2)->where('deleted', 0)->countAllResults();
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
            'document_expires_at' => ($exp = trim((string) $this->request->getPost('document_expires_at'))) !== ''
                ? date('Y-m-d', strtotime($exp))
                : null,
            'bank' => $this->request->getPost('bank') ?? '',
            'account' => $this->request->getPost('account') ?? '',
            'account_type' => bingo_normalize_account_type($this->request->getPost('account_type')),
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

        helper('wallet');
        $walletBonus = max(0, round((float) ($this->request->getPost('wallet_bonus') ?? 0), 2));
        $walletRecharge = max(0, round((float) ($this->request->getPost('wallet_recharge') ?? 0), 2));
        $walletWithdraw = max(0, round((float) ($this->request->getPost('wallet_withdraw') ?? 0), 2));
        // Compatibilidad: si solo llega "wallet" legacy y no los 3 campos
        if (
            $this->request->getPost('wallet_bonus') === null
            && $this->request->getPost('wallet_recharge') === null
            && $this->request->getPost('wallet_withdraw') === null
        ) {
            $walletRecharge = max(0, round((float) ($this->request->getPost('wallet') ?? 0), 2));
        }
        $data['wallet_bonus'] = $walletBonus;
        $data['wallet_recharge'] = $walletRecharge;
        $data['wallet_withdraw'] = $walletWithdraw;
        $data['wallet'] = round($walletBonus + $walletRecharge + $walletWithdraw, 2);
        
        if ($action === 'add') {
            // Generar código único
            $lastUser = $model->orderBy('id', 'DESC')->first();
            $nextId = $lastUser ? $lastUser['id'] + 1 : 1;
            $group = (int) ($data['group'] ?? 0);
            $codePrefix = $group === bingo_group_store() ? 'BGC-T' : 'BGC-A';
            $data['code'] = $codePrefix . str_pad($nextId, 5, '0', STR_PAD_LEFT);
            
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
                @mkdir($uploadPath, 0755, true); 
            }

            if (is_dir($uploadPath) && $imageData !== false) {
                $written = @file_put_contents($uploadPath . $fileName, $imageData);
                if ($written !== false && is_file($uploadPath . $fileName)) {
                    $data['image'] = $fileName;
                } else {
                    log_message('error', 'Profile: no se pudo guardar imagen en ' . $uploadPath . $fileName);
                }
            }
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

    public function exportUsersModal()
    {
        if (! session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        return view('users/export_modal', [
            'exportFields' => $this->userExportFieldMap(),
        ]);
    }

    public function searchUsersForExport()
    {
        if (! session()->get('logged_in') || session()->get('group') != 1) {
            return $this->response->setJSON(['success' => false, 'users' => []]);
        }

        $query = trim((string) ($this->request->getGet('q') ?? ''));
        if (mb_strlen($query) < 2) {
            return $this->response->setJSON(['success' => true, 'users' => []]);
        }

        $model = new UsersModel();
        $users = $model->builder()
            ->where('deleted', 0)
            ->groupStart()
                ->like('firstname', $query)
                ->orLike('lastname', $query)
                ->orLike('username', $query)
                ->orLike('email', $query)
                ->orLike('phone', $query)
                ->orLike('document', $query)
                ->orLike('code', $query)
                ->orLike('business_name', $query)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->limit(15)
            ->get()
            ->getResultArray();

        $payload = array_map(function (array $user): array {
            $name = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
            $groupLabel = $this->exportUserGroupLabel((int) ($user['group'] ?? 0));

            return [
                'id' => (int) $user['id'],
                'label' => trim($name) . ' (@' . ($user['username'] ?? '') . ') · ' . $groupLabel,
            ];
        }, $users);

        return $this->response->setJSON([
            'success' => true,
            'users' => $payload,
        ]);
    }

    public function exportRequiredFields()
    {
        if (! session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        helper('wallet');

        $fieldMap = $this->userExportFieldMap();
        $selectedFields = $this->resolveSelectedExportFields($fieldMap);
        $users = $this->resolveUsersForExport();

        if ($users === []) {
            return $this->response
                ->setStatusCode(400)
                ->setBody('No hay usuarios para exportar con los criterios seleccionados.');
        }

        $headers = [];
        $numericColumns = [];
        $integerColumns = [];

        foreach ($selectedFields as $index => $fieldKey) {
            $headers[] = $fieldMap[$fieldKey]['label'];
            $type = $fieldMap[$fieldKey]['type'] ?? '';
            if ($type === 'money') {
                $numericColumns[] = $index;
            }
            if ($type === 'integer') {
                $integerColumns[] = $index;
            }
        }

        $rows = [];
        foreach ($users as $user) {
            $row = [];
            foreach ($selectedFields as $fieldKey) {
                $row[] = $this->resolveUserExportValue($user, $fieldKey);
            }
            $rows[] = $row;
        }

        $filename = 'usuarios-export-' . date('Ymd-His') . '.xls';

        return (new ExcelExport())->downloadResponse($headers, $rows, $filename, [
            'sheet_name' => 'Usuarios',
            'numeric_columns' => $numericColumns,
            'integer_columns' => $integerColumns,
        ]);
    }

    /**
     * @return array<string, array{label: string, type?: string}>
     */
    private function userExportFieldMap(): array
    {
        return [
            'id' => ['label' => 'ID', 'type' => 'integer'],
            'code' => ['label' => 'Codigo'],
            'group' => ['label' => 'Grupo'],
            'firstname' => ['label' => 'Nombres'],
            'lastname' => ['label' => 'Apellidos'],
            'business_name' => ['label' => 'Nombre negocio'],
            'username' => ['label' => 'Usuario'],
            'document' => ['label' => 'Documento'],
            'phone' => ['label' => 'Telefono'],
            'email' => ['label' => 'Email'],
            'address_line' => ['label' => 'Direccion'],
            'city' => ['label' => 'Ciudad'],
            'state' => ['label' => 'Estado'],
            'is_reseller' => ['label' => translate('point of sale')],
            'bank' => ['label' => 'Banco'],
            'account' => ['label' => 'Cuenta'],
            'wallet_total' => ['label' => 'Saldo total', 'type' => 'money'],
            'wallet_recharge' => ['label' => 'Saldo recarga', 'type' => 'money'],
            'wallet_withdraw' => ['label' => 'Saldo retiro', 'type' => 'money'],
            'wallet_bonus' => ['label' => 'Saldo bono', 'type' => 'money'],
            'kyc_status' => ['label' => 'KYC'],
            'status' => ['label' => 'Estado cuenta'],
            'created_at' => ['label' => 'Fecha registro'],
        ];
    }

    /**
     * @param array<string, array{label: string, type?: string}> $fieldMap
     * @return list<string>
     */
    private function resolveSelectedExportFields(array $fieldMap): array
    {
        $requested = $this->request->getPost('fields') ?? $this->request->getGet('fields');
        $selected = [];

        if (is_string($requested) && $requested !== '') {
            $requested = explode(',', $requested);
        }

        if (is_array($requested)) {
            foreach ($requested as $fieldKey) {
                $fieldKey = trim((string) $fieldKey);
                if ($fieldKey !== '' && isset($fieldMap[$fieldKey])) {
                    $selected[] = $fieldKey;
                }
            }
        }

        if ($selected === []) {
            return array_keys($fieldMap);
        }

        return $selected;
    }

    private function resolveUsersForExport(): array
    {
        $scope = (string) ($this->request->getPost('export_scope') ?? $this->request->getGet('export_scope') ?? 'filtered');
        $search = trim((string) ($this->request->getPost('search') ?? $this->request->getGet('search') ?? ''));
        $status = (string) ($this->request->getPost('status') ?? $this->request->getGet('status') ?? 'all');
        $groupParam = $this->request->getPost('group') ?? $this->request->getGet('group');
        $group = $groupParam === null ? '0' : (string) $groupParam;

        if ($scope === 'selected') {
            $userIds = $this->request->getPost('user_ids') ?? $this->request->getGet('user_ids');
            if (! is_array($userIds)) {
                $userIds = $userIds ? [(int) $userIds] : [];
            }

            $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
            if ($userIds === []) {
                return [];
            }

            $model = new UsersModel();

            return $model->builder()
                ->where('deleted', 0)
                ->whereIn('id', $userIds)
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray();
        }

        if ($scope === 'all') {
            return $this->fetchUsersForExport('', 'all', 'all');
        }

        return $this->fetchUsersForExport($search, $status, $group);
    }

    private function resolveUserExportValue(array $user, string $fieldKey): mixed
    {
        $user = wallet_service()->normalizeUser($user);

        return match ($fieldKey) {
            'id' => (int) ($user['id'] ?? 0),
            'code' => $user['code'] ?? '',
            'group' => $this->exportUserGroupLabel((int) ($user['group'] ?? 0)),
            'firstname' => $user['firstname'] ?? '',
            'lastname' => $user['lastname'] ?? '',
            'business_name' => $user['business_name'] ?? '',
            'username' => $user['username'] ?? '',
            'document' => $user['document'] ?? '',
            'phone' => $user['phone'] ?? '',
            'email' => $user['email'] ?? '',
            'address_line' => $user['address_line'] ?? '',
            'city' => $user['city'] ?? '',
            'state' => $user['state'] ?? '',
            'is_reseller' => ! empty($user['is_reseller']) ? 'Si' : 'No',
            'bank' => $user['bank'] ?? '',
            'account' => $user['account'] ?? '',
            'wallet_total' => (float) wallet_total($user),
            'wallet_recharge' => (float) ($user['wallet_recharge'] ?? 0),
            'wallet_withdraw' => (float) ($user['wallet_withdraw'] ?? 0),
            'wallet_bonus' => (float) ($user['wallet_bonus'] ?? 0),
            'kyc_status' => $user['kyc_status'] ?? 'pending',
            'status' => $this->exportUserStatusLabel((int) ($user['status'] ?? 0)),
            'created_at' => ! empty($user['created_at']) ? date('d/m/Y H:i', strtotime($user['created_at'])) : '',
            default => '',
        };
    }

    private function fetchUsersForExport(string $search, string $status, string $group): array
    {
        $model = new UsersModel();
        $builder = $model->builder();

        if ($search !== '') {
            $builder->groupStart()
                ->like('firstname', $search)
                ->orLike('lastname', $search)
                ->orLike('username', $search)
                ->orLike('email', $search)
                ->orLike('phone', $search)
                ->orLike('document', $search)
                ->orLike('business_name', $search)
                ->groupEnd();
        }

        if ($status !== 'all') {
            $builder->where('status', (int) $status);
        }

        if ($group !== 'all') {
            $builder->where('group', (int) $group);
        }

        $builder->where('deleted', 0);

        return $builder->orderBy('id', 'DESC')->get()->getResultArray();
    }

    private function exportUserGroupLabel(int $group): string
    {
        return match ($group) {
            1 => 'Admin',
            2 => translate('point of sale'),
            3 => translate('operator'),
            default => 'Jugador',
        };
    }

    private function exportUserStatusLabel(int $status): string
    {
        return match ($status) {
            1 => 'Activo',
            2 => 'Desactivado',
            default => 'Baneado',
        };
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
            // Misma lógica que Playings::play: salas pendientes (2) y listas (1).
            // Las partidas automáticas se crean con status=2; si solo se poll'ean status=1,
            // las cards aparecen al recargar y desaparecen al primer updateGames().
            $games = $modelGames->whereIn('status', [1, 2])->orderBy('date', 'ASC')->orderBy('time', 'ASC')->findAll();

            $formattedGames = [];
            foreach ($games as $index => $game) {
                $room = $modelGameRooms->where('id', $game['room'])->where('status', 1)->first();
                if (!$room) {
                    continue;
                }

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
                    // Incluir jugadores con cartones en temp_cartons (lobby LIVE) para mostrar conteo real
                    $players = bingo_count_game_players((int) $game['id']);
                    $SingsCount = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();
                    $AwardsCount = $modelAwards->where('game', $game['id'])->where('status', 1)->countAllResults();

                    // Incluir cartones en temp_cartons para mostrar conteo real
                    $cartons = bingo_count_game_cartons((int) $game['id']);
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
