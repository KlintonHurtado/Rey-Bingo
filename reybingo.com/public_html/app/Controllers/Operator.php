<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\ContactsModel;
use App\Models\RetiresModel;
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
        $storesCommissions = bingo_fetch_operator_stores_commissions_summary($stores, 30, $operator ?? []);

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
            'sounds' => 1,
            'narration' => 1,
            'autodial' => 1,
            'roulette' => 1,
            'wallet' => 0,
            'document' => 'ST-' . str_pad((string) $nextId, 8, '0', STR_PAD_LEFT),
            'phone' => '9' . str_pad((string) $nextId, 10, '0', STR_PAD_LEFT),
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
}
