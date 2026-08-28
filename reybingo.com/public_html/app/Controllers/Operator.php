<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\ContactsModel;
use App\Models\RetiresModel;
use App\Libraries\ExcelExport;
use CodeIgniter\Controller;

class Operator extends Controller
{
    public function __construct()
    {
        helper(['form', 'url', 'cookie', 'text', 'bingo', 'wallet', 'affiliate_ggr']);
        session();
    }

    private function requireOperator()
    {
        if (! session()->get('logged_in') || ! bingo_is_operator()) {
            return redirect()->to('/signin');
        }

        return null;
    }

    public function index()
    {
        if ($redirect = $this->requireOperator()) {
            return $redirect;
        }

        bingo_set_acting_store_id(0);

        $modelUsers = new UsersModel();
        $modelContacts = new ContactsModel();
        $operatorId = (int) session()->get('id');
        $operator = $modelUsers->find($operatorId);

        $imagePath = ! empty($operator['image'])
            ? site_url('uploads/users/' . $operator['image'])
            : site_url('assets/img/avatar.jpg');

        $stores = $modelUsers
            ->where('group', bingo_group_store())
            ->where('operator_id', $operatorId)
            ->where('deleted', 0)
            ->orderBy('business_name', 'ASC')
            ->findAll();

        if ($operator) {
            bingo_ensure_operator_affiliate_code($operator);
            $operator = $modelUsers->find($operatorId) ?? $operator;
        }

        bingo_clear_operator_signup_session();

        $referredStoresCount = count($stores);

        $operatorCommissions = bingo_fetch_operator_commissions_summary($operatorId, $operator ?? []);
        $defaultDateFrom = date('Y-m-d', strtotime('-30 days'));
        $defaultDateTo = date('Y-m-d');
        $storesCommissions = bingo_fetch_operator_stores_commissions_summary(
            $stores,
            30,
            $operator ?? [],
            $defaultDateFrom,
            $defaultDateTo
        );

        $operatorGgrDashboard = $operatorCommissions['ggr_dashboard'] ?? bingo_fetch_affiliate_ggr_dashboard($operatorId, 'operator', 30);

        $modelRetires = new RetiresModel();
        $retires = $modelRetires
            ->where('user', $operatorId)
            ->orderBy('created_at', 'DESC')
            ->findAll(30);

        foreach ($retires as &$retire) {
            $status = (int) ($retire['status'] ?? 0);
            $retire['status_label'] = match ($status) {
                2 => '<span class="badge bg-success">' . translate('paid') . '</span>',
                0 => '<span class="badge bg-danger">' . translate('rejected') . '</span>',
                default => '<span class="badge bg-warning text-dark">' . translate('pending') . '</span>',
            };
        }
        unset($retire);

        $earningsSummary = bingo_fetch_operator_withdraw_summary($operatorId, $operator);

        $modelBanks = new \App\Models\BanksModel();
        $banks = $modelBanks->where('status', 1)->findAll();

        $modelDeposits = new \App\Models\DepositsModel();
        $operatorDeposits = $modelDeposits
            ->where('user', $operatorId)
            ->orderBy('created_at', 'DESC')
            ->findAll(30);

        $data = [
            'page' => [
                'title' => translate('operator panel'),
            ],
            'validation' => \Config\Services::validation(),
            'contacts' => $modelContacts->findAll(),
            'contentPage' => view('operator/index', [
                'user' => $operator,
                'imagePath' => $imagePath,
                'stores' => $stores,
                'affiliateLink' => bingo_operator_affiliate_link($operator ?? []),
                'referredStoresCount' => $referredStoresCount,
                'ggrDashboard' => $operatorGgrDashboard,
                'ggrRate' => bingo_ggr_commission_rate_for($operator ?? [], 'operator'),
                'operatorCommissions' => $operatorCommissions,
                'storesCommissions' => $storesCommissions,
                'retires' => $retires,
                'earningsSummary' => $earningsSummary,
                'retireEnabled' => (string) (systemGet('activateRetire') ?? '1') === '1',
                'minimumRetire' => (float) (systemGet('minimumRetire') ?? 0),
                'maximumRetire' => (float) (systemGet('maximumRetire') ?? 0),
                'banks' => $banks,
                'operatorDeposits' => $operatorDeposits,
            ]),
        ];

        return view('layout/index', $data);
    }

    public function enterStore($storeId = null)
    {
        if ($redirect = $this->requireOperator()) {
            return $redirect;
        }

        $storeId = (int) $storeId;
        $operatorId = (int) session()->get('id');

        if ($storeId <= 0 || ! bingo_operator_can_access_store($operatorId, $storeId)) {
            return redirect()->to('/operator')->with('error', translate('store not found'));
        }

        bingo_set_acting_store_id($storeId);

        return redirect()->to('/store/funding');
    }

    public function leaveStore()
    {
        if ($redirect = $this->requireOperator()) {
            return $redirect;
        }

        bingo_set_acting_store_id(0);

        return redirect()->to('/operator');
    }

    public function registerAffiliate()
    {
        if ($redirect = $this->requireOperator()) {
            return $redirect;
        }

        $modelUsers = new UsersModel();
        $operatorId = (int) session()->get('id');
        $operator = $modelUsers->find($operatorId);

        if (! $operator) {
            return redirect()->to('/operator');
        }

        bingo_ensure_operator_affiliate_code($operator);
        $operator = $modelUsers->find($operatorId) ?? $operator;
        bingo_set_store_signup_session($operator);

        $modelContacts = new ContactsModel();
        $referrerName = trim(($operator['firstname'] ?? '') . ' ' . ($operator['lastname'] ?? ''));

        return view('layout/index', [
            'page' => [
                'title' => translate('create point of sale account'),
            ],
            'validation' => \Config\Services::validation(),
            'contacts' => $modelContacts->findAll(),
            'contentPage' => view('signup/store_affiliate', [
                'referrerName' => $referrerName,
                'referrerType' => 'operator',
                'storeRegistering' => true,
                'operatorRegistering' => true,
                'backUrl' => site_url('operator'),
                'backLabel' => translate('back to operator panel'),
            ]),
        ]);
    }

    public function affiliateCode()
    {
        if ($redirect = $this->requireOperator()) {
            return $redirect;
        }

        $modelUsers = new UsersModel();
        $operatorId = (int) session()->get('id');
        $operator = $modelUsers->find($operatorId);

        if (! $operator) {
            return $this->response->setStatusCode(404);
        }

        bingo_ensure_operator_affiliate_code($operator);
        $operator = $modelUsers->find($operatorId) ?? $operator;

        $data = bingo_operator_affiliate_link($operator);

        require_once APPPATH . 'Libraries/phpqrcode/qrlib.php';

        ob_start();
        \QRcode::png($data, null, QR_ECLEVEL_M, 6, 2);
        $png = ob_get_clean();

        return $this->response->setContentType('image/png')->setBody($png);
    }

    public function addStore()
    {
        if ($redirect = $this->requireOperator()) {
            return $redirect;
        }

        return view('operator/modal_store');
    }

    public function storeSubmit()
    {
        if (! session()->get('logged_in') || ! bingo_is_operator()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('unauthorized'),
            ]);
        }

        $operatorId = (int) session()->get('id');
        $model = new UsersModel();

        $validationRules = [
            'business_name' => [
                'label' => translate('business name'),
                'rules' => 'required|min_length[2]|max_length[255]',
            ],
            'address_line' => [
                'label' => translate('address'),
                'rules' => 'required|min_length[3]|max_length[255]',
            ],
            'password' => [
                'label' => translate('password'),
                'rules' => 'required|min_length[6]',
            ],
        ];

        if (! $this->validate($validationRules)) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors(),
            ]);
        }

        $businessName = trim((string) $this->request->getPost('business_name'));
        $addressLine = trim((string) $this->request->getPost('address_line'));
        $phoneInput = trim((string) $this->request->getPost('phone'));
        $lastUser = $model->orderBy('id', 'DESC')->first();
        $nextId = $lastUser ? ((int) $lastUser['id'] + 1) : 1;

        do {
            $email = sprintf('pv.%d.%s@reybingo.local', $operatorId, bin2hex(random_bytes(6)));
        } while ($model->where('email', $email)->first());

        $data = [
            'firstname' => $businessName,
            'lastname' => 'PV',
            'business_name' => $businessName,
            'address_line' => $addressLine,
            'email' => $email,
            'username' => bingo_generate_store_username($email, $model),
            'group' => bingo_group_store(),
            'operator_id' => $operatorId,
            'status' => 1,
            'sounds' => 0,
            'narration' => 1,
            'autodial' => 1,
            'roulette' => 1,
            'wallet' => 0,
            'document' => 'ST-' . str_pad((string) $nextId, 8, '0', STR_PAD_LEFT),
            'phone' => $phoneInput !== '' ? $phoneInput : ('9' . str_pad((string) $nextId, 10, '0', STR_PAD_LEFT)),
            'bank' => '',
            'account' => '',
            'image' => '',
            'verified_email' => 1,
            'verification_token' => '',
            'restore_code' => '',
            'restore_token' => '',
            'is_reseller' => 0,
            'kyc_status' => 'verified',
            'code' => 'BGC-T' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'referred_code' => strtoupper(substr(md5(uniqid()), 0, 8)),
        ];

        if (! $model->insert($data)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('error processing request'),
            ]);
        }

        $newStoreId = (int) $model->getInsertID();
        $store = $model->find($newStoreId);
        if ($store) {
            bingo_ensure_store_affiliate_code($store);
        }
        bingo_assign_store_operator($newStoreId, $operatorId);

        return $this->response->setJSON([
            'success' => true,
            'message' => translate('store added successfully'),
            'store_id' => $newStoreId,
        ]);
    }

    public function updateStoreGgrRate()
    {
        if (! session()->get('logged_in') || ! bingo_is_operator()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('unauthorized'),
            ]);
        }

        $operatorId = (int) session()->get('id');
        $storeId = (int) $this->request->getPost('store_id');
        $rateInput = $this->request->getPost('ggr_rate');

        if ($storeId <= 0 || ! bingo_operator_can_access_store($operatorId, $storeId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('store not found'),
            ]);
        }

        $model = new UsersModel();
        $store = $model->find($storeId);
        $operator = $model->find($operatorId);

        if (! $store || ! $operator) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('store not found'),
            ]);
        }

        $validation = bingo_validate_store_ggr_rate($rateInput, $store, $operator);
        if (! $validation['valid']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $validation['message'] ?? translate('invalid request'),
            ]);
        }

        $model->update($storeId, ['ggr_commission_rate' => $validation['rate']]);

        $store = $model->find($storeId) ?? $store;
        $operatorTotal = bingo_operator_commission_rate($operator);
        $storeRate = bingo_store_ggr_commission_rate($store);
        $margin = max(0.0, round($operatorTotal - $storeRate, 4));

        return $this->response->setJSON([
            'success'      => true,
            'message'      => translate('settings updated successfully'),
            'store_rate'   => $storeRate,
            'margin_rate'  => $margin,
            'operator_total' => $operatorTotal,
        ]);
    }

    public function adjustStoreBalance()
    {
        if (! session()->get('logged_in') || ! bingo_is_operator()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('unauthorized'),
            ]);
        }

        helper('wallet');

        $operatorId = (int) session()->get('id');
        $storeId = (int) $this->request->getPost('store_id');
        $action = strtolower(trim((string) $this->request->getPost('action')));
        $amount = round((float) $this->request->getPost('amount'), 2);

        if ($storeId <= 0 || ! bingo_operator_can_access_store($operatorId, $storeId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('store not found'),
            ]);
        }

        if (! in_array($action, ['add', 'remove'], true)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('invalid request'),
            ]);
        }

        if ($amount < 0.01) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('invalid amount'),
            ]);
        }

        $modelUsers = new UsersModel();
        $store = $modelUsers->find($storeId);
        if (! $store || (int) ($store['group'] ?? -1) !== bingo_group_store()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('store not found'),
            ]);
        }

        $operator = $modelUsers->find($operatorId);
        if (! $operator) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('unauthorized'),
            ]);
        }

        $currentStoreBalance = wallet_recharge_balance($store);
        $currentOperatorBalance = wallet_recharge_balance($operator);

        if ($action === 'add') {
            if ($amount > $currentOperatorBalance + 0.00001) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => translate('insufficient operator balance') ?: ('Saldo insuficiente del operador. Saldo disponible: ' . systemGet('currency') . ' ' . number_format($currentOperatorBalance, 2)),
                ]);
            }

            if (! wallet_deduct_recharge($operatorId, $amount)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => translate('insufficient operator balance') ?: 'Saldo insuficiente del operador',
                ]);
            }

            wallet_credit_recharge($storeId, $amount);
            $paymentType = 'operator_store_credit';
            $message = translate('store balance added successfully');
        } else {
            if ($amount > $currentStoreBalance + 0.00001) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => translate('insufficient store balance') ?: 'El monto a retirar excede el saldo disponible del Punto de venta',
                ]);
            }

            if (! wallet_deduct_recharge($storeId, $amount)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => translate('insufficient store balance'),
                ]);
            }

            wallet_credit_recharge($operatorId, $amount);
            $paymentType = 'operator_store_debit';
            $message = translate('store balance removed successfully');
        }

        $modelPayments = new \App\Models\PaymentsModel();
        $modelPayments->insert([
            'user' => $storeId,
            'type' => $paymentType,
            'type_id' => $operatorId,
            'amount' => $amount,
            'status' => 2,
        ]);

        $updatedStore = $modelUsers->find($storeId) ?? $store;
        $updatedOperator = $modelUsers->find($operatorId) ?? $operator;

        $newStoreBalance = wallet_recharge_balance($updatedStore);
        $newOperatorBalance = wallet_recharge_balance($updatedOperator);

        return $this->response->setJSON([
            'success' => true,
            'message' => $message,
            'balance' => round($newStoreBalance, 2),
            'operatorBalance' => round($newOperatorBalance, 2),
        ]);
    }

    public function storesCommissionsGet()
    {
        if (! session()->get('logged_in') || ! bingo_is_operator()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('unauthorized'),
            ]);
        }

        $operatorId = (int) session()->get('id');
        $modelUsers = new UsersModel();
        $operator = $modelUsers->find($operatorId);
        if (! $operator) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('unauthorized'),
            ]);
        }

        $dateFrom = trim((string) ($this->request->getGet('date_from') ?? $this->request->getPost('date_from') ?? ''));
        $dateTo = trim((string) ($this->request->getGet('date_to') ?? $this->request->getPost('date_to') ?? ''));

        if ($dateFrom !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = '';
        }
        if ($dateTo !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = '';
        }
        if ($dateFrom === '' && $dateTo === '') {
            $dateFrom = date('Y-m-d', strtotime('-30 days'));
            $dateTo = date('Y-m-d');
        }
        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $stores = $modelUsers
            ->where('group', bingo_group_store())
            ->where('operator_id', $operatorId)
            ->where('deleted', 0)
            ->orderBy('business_name', 'ASC')
            ->findAll();

        $chartDays = 30;
        if ($dateFrom !== '' && $dateTo !== '') {
            $chartDays = max(1, (int) ((strtotime($dateTo) - strtotime($dateFrom)) / 86400) + 1);
        }

        $storesCommissions = bingo_fetch_operator_stores_commissions_summary(
            $stores,
            $chartDays,
            $operator,
            $dateFrom !== '' ? $dateFrom : null,
            $dateTo !== '' ? $dateTo : null
        );

        $html = view('operator/partials/commissions_stores', [
            'storesCommissions' => $storesCommissions,
        ]);

        return $this->response->setJSON([
            'success' => true,
            'html'    => $html,
            'chart'   => $storesCommissions['chart'] ?? [],
        ]);
    }

    public function balanceRequestSubmit()
    {
        if (! session()->get('logged_in') || ! bingo_is_operator()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('unauthorized'),
            ]);
        }

        $validationRules = [
            'amount' => [
                'label' => translate('amount'),
                'rules' => 'required|decimal|greater_than[0]',
            ],
            'reference' => [
                'label' => translate('reference'),
                'rules' => 'permit_empty|min_length[3]|max_length[50]',
            ],
            'bank' => [
                'label' => translate('bingo bank'),
                'rules' => 'required|is_natural_no_zero',
            ],
        ];

        if (! $this->validate($validationRules)) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors(),
            ]);
        }

        $voucherImage = (string) $this->request->getPost('voucher');
        if ($voucherImage === '' || strpos($voucherImage, 'data:image') !== 0) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => [
                    'voucher' => translate('enter a') . ' ' . strtolower(translate('voucher')),
                ],
            ]);
        }

        $modelUsers = new UsersModel();
        $modelDeposits = new \App\Models\DepositsModel();
        $modelNotifications = new \App\Models\NotificationsModel();
        $modelBanks = new \App\Models\BanksModel();

        $amount = round((float) $this->request->getPost('amount'), 2);
        $reference = trim((string) $this->request->getPost('reference'));
        $bankId = (int) $this->request->getPost('bank');
        $bingoBank = $modelBanks->where('status', 1)->find($bankId);

        if (! $bingoBank) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => [
                    'bank' => translate('bank not found'),
                ],
            ]);
        }

        $operatorId = (int) session()->get('id');
        $operator = $modelUsers->find($operatorId);
        $operatorName = !empty($operator) ? trim(($operator['firstname'] ?? '') . ' ' . ($operator['lastname'] ?? '')) : 'Operador';
        if ($operatorName === '') {
            $operatorName = $operator['username'] ?? 'Operador';
        }

        $savedVoucher = bingo_save_voucher_base64($voucherImage);
        $voucherFile = $savedVoucher['success'] ? $savedVoucher['filename'] : '';

        if ($voucherFile === '') {
            return $this->response->setJSON([
                'success' => false,
                'errors' => [
                    'voucher' => translate('there was an error in the request to the server.'),
                ],
            ]);
        }

        if (systemGet('activateDeposit') == 1) {
            if ($amount < (float) systemGet('minimumDeposit')) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => translate('minimum deposit amount is') . ' ' . systemGet('minimumDeposit') . ' ' . systemGet('currency'),
                ]);
            }
            if ($amount > (float) systemGet('maximumDeposit')) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => translate('maximum deposit amount is') . ' ' . systemGet('maximumDeposit') . ' ' . systemGet('currency'),
                ]);
            }
        }

        if ($reference === '') {
            $reference = 'SOL-OP-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 8));
        }

        $depositData = [
            'user' => $operatorId,
            'store' => null,
            'account' => (string) $bankId,
            'method' => 'operator funding request',
            'bank' => $bingoBank['name'],
            'document' => $operator['document'] ?? '',
            'phone' => $operator['phone'] ?? '',
            'reference' => $reference,
            'amount' => $amount,
            'date' => date('Y-m-d'),
            'voucher' => $voucherFile,
            'observation' => 'Solicitud de saldo de Operador: ' . $operatorName,
            'status' => 1,
        ];

        $modelDeposits->insert($depositData);
        $depositId = (int) $modelDeposits->getInsertID();

        if ($depositId > 0 && function_exists('bingo_voucher_sync_after_insert')) {
            bingo_voucher_sync_after_insert($depositId, $voucherFile);
        }

        $admins = $modelUsers->select('id')->where('group', bingo_group_admin())->findAll();
        foreach ($admins as $admin) {
            $modelNotifications->insert([
                'user' => $admin['id'],
                'from' => $operatorId,
                'type' => 'deposit',
                'type_id' => $depositId,
                'title' => '👔 SOLICITUD DE SALDO DE OPERADOR',
                'message' => $operatorName . ' solicitó saldo de '
                    . systemGet('currency') . ' ' . number_format($amount, 2)
                    . ' | Ref: #' . $reference,
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Solicitud de saldo registrada con éxito, pendiente de aprobación por el administrador',
            'deposit_id' => $depositId,
            'reference' => $reference,
        ]);
    }

    public function balanceListGet()
    {
        if (! session()->get('logged_in') || ! bingo_is_operator()) {
            return $this->response->setStatusCode(403);
        }

        $operatorId = (int) session()->get('id');
        $modelDeposits = new \App\Models\DepositsModel();
        $operatorDeposits = $modelDeposits
            ->where('user', $operatorId)
            ->orderBy('created_at', 'DESC')
            ->findAll(30);

        return view('operator/partials/balance_history', [
            'operatorDeposits' => $operatorDeposits,
        ]);
    }

    public function movementsListGet()
    {
        if (! session()->get('logged_in') || ! bingo_is_operator()) {
            return $this->response->setStatusCode(403);
        }

        $operatorId = (int) session()->get('id');
        $filters = [
            'date_from' => (string) ($this->request->getGet('date_from') ?? ''),
            'date_to'   => (string) ($this->request->getGet('date_to') ?? ''),
            'store_id'  => (string) ($this->request->getGet('store_id') ?? 'all'),
            'type'      => (string) ($this->request->getGet('type') ?? 'all'),
            'search'    => (string) ($this->request->getGet('search') ?? ''),
        ];

        $ledger = bingo_build_operator_movements_ledger($operatorId, $filters);

        return view('operator/movements_list', [
            'movements' => $ledger['movements'],
            'stats'     => $ledger['stats'],
            'currency'  => systemGet('currency') ?? '$',
        ]);
    }

    public function exportMovements()
    {
        if (! session()->get('logged_in') || ! bingo_is_operator()) {
            return redirect()->to('/signin');
        }

        $operatorId = (int) session()->get('id');
        $filters = [
            'date_from' => (string) ($this->request->getGet('date_from') ?? ''),
            'date_to'   => (string) ($this->request->getGet('date_to') ?? ''),
            'store_id'  => (string) ($this->request->getGet('store_id') ?? 'all'),
            'type'      => (string) ($this->request->getGet('type') ?? 'all'),
            'search'    => (string) ($this->request->getGet('search') ?? ''),
        ];

        $ledger = bingo_build_operator_movements_ledger($operatorId, $filters);
        $export = bingo_operator_movements_export_rows($ledger['movements']);

        $filename = 'movimientos_operador_' . date('Y-m-d_His') . '.xls';

        return (new ExcelExport())->downloadResponse(
            $export['headers'],
            $export['rows'],
            $filename,
            [
                'sheet_name' => 'Movimientos',
                'title' => 'Rey Bingo - Movimientos del Operador',
                'numeric_columns' => [3],
            ]
        );
    }

    public function operatorCommissionsGet()
    {
        if (! session()->get('logged_in') || ! bingo_is_operator()) {
            return $this->response->setStatusCode(403);
        }

        $operatorId = (int) session()->get('id');
        $filters = [
            'date_from' => (string) ($this->request->getGet('date_from') ?? ''),
            'date_to'   => (string) ($this->request->getGet('date_to') ?? ''),
            'store_id'  => (string) ($this->request->getGet('store_id') ?? 'all'),
            'rate_type' => (string) ($this->request->getGet('rate_type') ?? 'all'),
            'search'    => (string) ($this->request->getGet('search') ?? ''),
        ];

        $data = bingo_fetch_operator_detailed_commissions_breakdown($operatorId, $filters);

        $html = view('operator/partials/commissions_operator_table', [
            'items'    => $data['items'],
            'stats'    => $data['stats'],
            'currency' => systemGet('currency') ?? '$',
        ]);

        return $this->response->setJSON([
            'success' => true,
            'html'    => $html,
            'stats'   => $data['stats'],
        ]);
    }

    public function exportOperatorCommissions()
    {
        if (! session()->get('logged_in') || ! bingo_is_operator()) {
            return redirect()->to('/signin');
        }

        $operatorId = (int) session()->get('id');
        $filters = [
            'date_from' => (string) ($this->request->getGet('date_from') ?? ''),
            'date_to'   => (string) ($this->request->getGet('date_to') ?? ''),
            'store_id'  => (string) ($this->request->getGet('store_id') ?? 'all'),
            'rate_type' => (string) ($this->request->getGet('rate_type') ?? 'all'),
            'search'    => (string) ($this->request->getGet('search') ?? ''),
        ];

        $data = bingo_fetch_operator_detailed_commissions_breakdown($operatorId, $filters);

        $headers = [
            'Fecha y Hora',
            'Punto de Venta / Origen',
            'Tipo de Tasa',
            'Referencia',
            'Total apostado',
            'Total premios',
            'Monto Base / GGR',
            'Tasa PV (%)',
            'Comision PV',
            'Tasa Operador (%)',
            'Margen Operador (%)',
            'Ganancia Operador',
            'Estado',
            'Detalle'
        ];

        $rows = [];
        foreach ($data['items'] as $it) {
            $datetime = (string) ($it['datetime'] ?? '');
            if ($datetime !== '' && strtotime($datetime) !== false) {
                $datetime = date('d/m/Y H:i:s', strtotime($datetime));
            }

            $isGgr = (string) ($it['rate_type'] ?? '') === 'ggr';
            $totalStake = $isGgr ? round((float) ($it['total_stake'] ?? 0), 2) : '';
            $totalPayout = $isGgr ? round((float) ($it['total_payout'] ?? 0), 2) : '';

            $rows[] = [
                $datetime,
                (string) ($it['store_name'] ?? ''),
                (string) ($it['rate_type_label'] ?? ''),
                (string) ($it['ref_code'] ?? ''),
                $totalStake,
                $totalPayout,
                round((float) ($it['base_amount'] ?? 0), 2),
                number_format(((float) ($it['store_rate'] ?? 0)) * 100, 2) . '%',
                round((float) ($it['store_commission'] ?? 0), 2),
                number_format(((float) ($it['operator_rate'] ?? 0)) * 100, 2) . '%',
                number_format(((float) ($it['operator_spread'] ?? 0)) * 100, 2) . '%',
                round((float) ($it['operator_profit'] ?? 0), 2),
                (string) ($it['status_label'] ?? ''),
                (string) ($it['detail'] ?? ''),
            ];
        }

        $filename = 'comisiones_operador_' . date('Y-m-d_His') . '.xls';

        return (new ExcelExport())->downloadResponse(
            $headers,
            $rows,
            $filename,
            [
                'sheet_name' => 'Comisiones',
                'title' => 'Rey Bingo - Comisiones del Operador',
                'numeric_columns' => [4, 5, 6, 8, 11],
            ]
        );
    }

    public function exportStoresCommissions()
    {
        if (! session()->get('logged_in') || ! bingo_is_operator()) {
            return redirect()->to('/signin');
        }

        $operatorId = (int) session()->get('id');
        $modelUsers = new UsersModel();
        $operator = $modelUsers->find($operatorId);
        if (! $operator) {
            return redirect()->to('/signin');
        }

        $dateFrom = trim((string) ($this->request->getGet('date_from') ?? ''));
        $dateTo = trim((string) ($this->request->getGet('date_to') ?? ''));

        if ($dateFrom !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = '';
        }
        if ($dateTo !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = '';
        }
        if ($dateFrom === '' && $dateTo === '') {
            $dateFrom = date('Y-m-d', strtotime('-30 days'));
            $dateTo = date('Y-m-d');
        }
        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $stores = $modelUsers
            ->where('group', bingo_group_store())
            ->where('operator_id', $operatorId)
            ->where('deleted', 0)
            ->orderBy('business_name', 'ASC')
            ->findAll();

        $chartDays = 30;
        if ($dateFrom !== '' && $dateTo !== '') {
            $chartDays = max(1, (int) ((strtotime($dateTo) - strtotime($dateFrom)) / 86400) + 1);
        }

        $storesCommissions = bingo_fetch_operator_stores_commissions_summary(
            $stores,
            $chartDays,
            $operator,
            $dateFrom !== '' ? $dateFrom : null,
            $dateTo !== '' ? $dateTo : null
        );

        $storeCodeMap = [];
        foreach ($stores as $st) {
            $sid = (int) ($st['id'] ?? 0);
            if ($sid > 0) {
                $storeCodeMap[$sid] = (string) ($st['code'] ?? '');
            }
        }

        $storeIds = array_keys($storeCodeMap);

        $breakdown = bingo_fetch_operator_detailed_commissions_breakdown($operatorId, [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'store_id'  => 'all',
            'rate_type' => 'all',
        ]);

        $headers = [
            'Fecha',
            'Punto de Venta',
            'Codigo',
            'Tipo',
            'Referencia',
            'Total apostado',
            'Total premios',
            'Monto operacion / GGR',
            'Tasa PV (%)',
            'Comision PV',
        ];

        $rows = [];
        $storeGgrStakeSum = [];
        $storeGgrPayoutSum = [];

        foreach ($breakdown['items'] ?? [] as $item) {
            $stId = (int) ($item['store_id'] ?? 0);
            if ($stId <= 0 || $stId === $operatorId || ! in_array($stId, $storeIds, true)) {
                continue;
            }

            $stCommission = round((float) ($item['store_commission'] ?? 0), 2);
            $baseAmount = round((float) ($item['base_amount'] ?? 0), 2);
            $isGgr = (string) ($item['rate_type'] ?? '') === 'ggr';
            $totalStake = $isGgr ? round((float) ($item['total_stake'] ?? 0), 2) : '';
            $totalPayout = $isGgr ? round((float) ($item['total_payout'] ?? 0), 2) : '';

            if ($isGgr) {
                $storeGgrStakeSum[$stId] = ($storeGgrStakeSum[$stId] ?? 0) + (float) ($item['total_stake'] ?? 0);
                $storeGgrPayoutSum[$stId] = ($storeGgrPayoutSum[$stId] ?? 0) + (float) ($item['total_payout'] ?? 0);
            }

            if ($stCommission <= 0 && $baseAmount <= 0) {
                continue;
            }

            $datetime = (string) ($item['datetime'] ?? '');
            if ($datetime !== '' && strtotime($datetime) !== false) {
                $datetime = date('d/m/Y H:i:s', strtotime($datetime));
            }

            $rows[] = [
                $datetime,
                (string) ($item['store_name'] ?? '-'),
                $storeCodeMap[$stId] ?? '',
                (string) ($item['rate_type_label'] ?? ''),
                (string) ($item['ref_code'] ?? ''),
                $totalStake,
                $totalPayout,
                $baseAmount,
                number_format(((float) ($item['store_rate'] ?? 0)) * 100, 2) . '%',
                $stCommission,
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            $cmp = strcmp((string) ($a[1] ?? ''), (string) ($b[1] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp((string) ($b[0] ?? ''), (string) ($a[0] ?? ''));
        });

        $rows[] = ['', '', '', '', '', '', '', '', '', ''];
        $rows[] = ['', 'RESUMEN POR PUNTO DE VENTA', '', '', '', '', '', '', '', ''];

        $sumRec = 0.0;
        $sumRet = 0.0;
        $sumGgr = 0.0;
        $sumTotal = 0.0;
        $sumStake = 0.0;
        $sumPayout = 0.0;

        foreach ($storesCommissions['stores'] ?? [] as $storeRow) {
            $name = (string) ($storeRow['name'] ?? '-');
            $code = (string) ($storeRow['code'] ?? '');
            $sid = (int) ($storeRow['id'] ?? 0);
            $rec = round((float) ($storeRow['recharge_store'] ?? 0), 2);
            $ret = round((float) ($storeRow['withdraw_store'] ?? 0), 2);
            $ggr = round((float) ($storeRow['ggr_store'] ?? $storeRow['ggr_commissions'] ?? 0), 2);
            $total = round($rec + $ret + $ggr, 2);
            $stakeSum = round((float) ($storeGgrStakeSum[$sid] ?? 0), 2);
            $payoutSum = round((float) ($storeGgrPayoutSum[$sid] ?? 0), 2);

            $sumRec += $rec;
            $sumRet += $ret;
            $sumGgr += $ggr;
            $sumTotal += $total;
            $sumStake += $stakeSum;
            $sumPayout += $payoutSum;

            $rows[] = ['', $name, $code, 'Total Recargas', '', '', '', '', '', $rec];
            $rows[] = ['', $name, $code, 'Total Retiros', '', '', '', '', '', $ret];
            $rows[] = ['', $name, $code, 'Total GGR', '', $stakeSum, $payoutSum, '', '', $ggr];
            $rows[] = ['', $name, $code, 'Total comisiones', '', '', '', '', '', $total];
        }

        $rows[] = ['', '', '', '', '', '', '', '', '', ''];
        $rows[] = ['', 'TOTALES GENERAL', '', 'Total Recargas', '', '', '', '', '', round($sumRec, 2)];
        $rows[] = ['', 'TOTALES GENERAL', '', 'Total Retiros', '', '', '', '', '', round($sumRet, 2)];
        $rows[] = ['', 'TOTALES GENERAL', '', 'Total GGR', '', round($sumStake, 2), round($sumPayout, 2), '', '', round($sumGgr, 2)];
        $rows[] = ['', 'TOTALES GENERAL', '', 'Total comisiones', '', '', '', '', '', round($sumTotal, 2)];

        $periodLabel = ($dateFrom !== '' || $dateTo !== '')
            ? ' (' . ($dateFrom !== '' ? $dateFrom : '…') . ' a ' . ($dateTo !== '' ? $dateTo : '…') . ')'
            : '';

        $filename = 'comisiones_puntos_venta_detalle_' . date('Y-m-d_His') . '.xls';

        return (new ExcelExport())->downloadResponse(
            $headers,
            $rows,
            $filename,
            [
                'sheet_name' => 'Comisiones PV',
                'title' => 'Rey Bingo - Detalle comisiones Puntos de venta' . $periodLabel,
                'numeric_columns' => [5, 6, 7, 9],
            ]
        );
    }
}
