<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\DepositsModel;
use App\Models\ContactsModel;
use App\Models\NotificationsModel;
use App\Models\BanksModel;
use App\Models\PaymentsModel;
use App\Models\SingsModel;
use App\Models\RetiresModel;
use CodeIgniter\Controller;

class Store extends Controller
{
    public function __construct()
    {
        helper(['form', 'url', 'cookie', 'text', 'wallet', 'bingo', 'affiliate_ggr']);
        session();
    }

    private function requireStore()
    {
        if (! session()->get('logged_in')) {
            return redirect()->to('/signin');
        }

        if (bingo_is_store()) {
            return null;
        }

        if (bingo_is_operator()) {
            $storeId = $this->getEffectiveStoreId();
            if ($storeId > 0 && bingo_operator_can_access_store((int) session()->get('id'), $storeId)) {
                return null;
            }

            return redirect()->to('/operator');
        }

        return redirect()->to('/signin');
    }

    private function getEffectiveStoreId(): int
    {
        if (bingo_is_store()) {
            return (int) session()->get('id');
        }

        if (bingo_is_operator()) {
            return bingo_get_acting_store_id();
        }

        return 0;
    }

    private function getStoreContext(): array
    {
        $modelUsers = new UsersModel();
        $modelContacts = new ContactsModel();
        $storeId = $this->getEffectiveStoreId();

        $user = $modelUsers->find($storeId);
        $imagePath = ! empty($user['image'])
            ? site_url('uploads/users/' . $user['image'])
            : site_url('assets/img/avatar.jpg');

        $context = [
            'user' => $user,
            'imagePath' => $imagePath,
            'contacts' => $modelContacts->findAll(),
            'isOperatorActing' => bingo_is_operator() && bingo_get_acting_store_id() > 0,
        ];

        if (bingo_is_operator()) {
            $context['operatorUser'] = $modelUsers->find(session()->get('id'));
        }

        return $context;
    }

    private function getFreshStoreUser(): ?array
    {
        $modelUsers = new UsersModel();

        return $modelUsers->find($this->getEffectiveStoreId());
    }

    private function applyStoreFundingFilter($builder)
    {
        return $builder->groupStart()
            ->where('method', 'store funding request')
            ->orWhere('account', 'store_funding')
            ->groupEnd();
    }

    private function saveVoucherFromBase64(?string $voucherImage): string
    {
        $saved = bingo_save_voucher_base64($voucherImage);

        return $saved['success'] ? $saved['filename'] : '';
    }

    public function index()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        return redirect()->to('/store/funding');
    }

    private function renderStorePage(string $view, array $viewData, string $title)
    {
        $context = $this->getStoreContext();
        $storeUser = $this->getFreshStoreUser();
        $storeId = $this->getEffectiveStoreId();
        $earningsSummary = bingo_fetch_store_withdraw_summary($storeId, $storeUser ?? []);
        $walletSummary = array_merge(wallet_summary_payload($storeUser), [
            'earnings_display' => $earningsSummary['display_total'],
        ]);

        $data = [
            'page' => [
                'title' => $title,
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view($view, array_merge($context, $viewData, [
                'walletSummary' => $walletSummary,
                'earningsSummary' => $earningsSummary,
                'pendingPrizes' => $this->countStorePendingPrizes(),
            ])),
        ];

        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache');

        return view('layout/index', $data);
    }

    public function funding()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        $modelDeposits = new DepositsModel();
        $modelBanks = new BanksModel();
        $storeId = $this->getEffectiveStoreId();
        $banks = $modelBanks->where('status', 1)->findAll();

        $fundingRequests = $this->applyStoreFundingFilter(
            $modelDeposits->where('user', $storeId)
        )
            ->orderBy('created_at', 'DESC')
            ->findAll(30);

        foreach ($fundingRequests as &$request) {
            $request['status_label'] = $this->formatRechargeStatus((int) $request['status']);
        }

        return $this->renderStorePage('store/funding', [
            'fundingRequests' => $fundingRequests,
            'banks' => $banks,
        ], translate('request store balance'));
    }

    public function recharge()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        $modelDeposits = new DepositsModel();
        $modelUsers = new UsersModel();
        $storeId = $this->getEffectiveStoreId();

        $recharges = $modelDeposits
            ->where('store', $storeId)
            ->where('method', 'store player recharge')
            ->orderBy('created_at', 'DESC')
            ->findAll(50);

        foreach ($recharges as &$recharge) {
            $player = $modelUsers->find($recharge['user']);
            $recharge['player_name'] = $player
                ? trim($player['firstname'] . ' ' . $player['lastname'])
                : translate('user');
            $recharge['player_code'] = $player['code'] ?? '';
            $recharge['player_document'] = $player['document'] ?? $recharge['document'] ?? '';
            $recharge['status_label'] = $this->formatRechargeStatus((int) $recharge['status']);
        }

        return $this->renderStorePage('store/recharge', [
            'recharges' => $recharges,
            'rechargeCommissionTotal' => bingo_sum_store_recharge_commissions($storeId),
        ], translate('recharge player by document'));
    }

    public function lookupPlayer()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        $query = trim((string) $this->request->getPost('query'));
        if ($query === '') {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('enter player document number'),
            ]);
        }

        $modelUsers = new UsersModel();
        $player = $modelUsers->findPlayerForStoreRecharge($query);

        if (! $player) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('player not found'),
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'player' => [
                'id' => $player['id'],
                'code' => $player['code'],
                'firstname' => $player['firstname'],
                'lastname' => $player['lastname'],
                'phone' => $player['phone'],
                'document' => $player['document'],
                'wallet' => number_format(wallet_total($player), 2),
            ],
            'prizes_summary' => bingo_summarize_player_prizes((int) $player['id'], '1'),
        ]);
    }

    public function playerPrizeSummaryGet()
    {
        if (! session()->get('logged_in') || ! bingo_is_store()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => translate('unauthorized')]);
        }

        $playerId = (int) ($this->request->getGet('player_id') ?? 0);
        $status = (string) ($this->request->getGet('status') ?? '1');

        if ($playerId <= 0) {
            return $this->response->setJSON([
                'success' => true,
                'summary' => bingo_summarize_player_prizes(0, $status),
            ]);
        }

        $modelUsers = new UsersModel();
        $player = $modelUsers
            ->where('id', $playerId)
            ->where('group', bingo_group_player())
            ->where('deleted', 0)
            ->where('status', 1)
            ->first();

        if (! $player) {
            return $this->response->setJSON(['success' => false, 'error' => translate('player not found')]);
        }

        return $this->response->setJSON([
            'success' => true,
            'summary' => bingo_summarize_player_prizes($playerId, $status),
        ]);
    }

    public function balanceRequestSubmit()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
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
        $modelDeposits = new DepositsModel();
        $modelNotifications = new NotificationsModel();
        $modelBanks = new BanksModel();

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

        $storeId = $this->getEffectiveStoreId();
        $store = $modelUsers->find($storeId);
        $storeName = bingo_store_display_name($store ?: []);
        $voucherFile = $this->saveVoucherFromBase64($voucherImage);

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
            $reference = 'SOL-TIENDA-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 8));
        }

        $depositData = [
            'user' => $storeId,
            'store' => null,
            'account' => (string) $bankId,
            'method' => 'store funding request',
            'bank' => $bingoBank['name'],
            'document' => $store['document'] ?? '',
            'phone' => $store['phone'] ?? '',
            'reference' => $reference,
            'amount' => $amount,
            'date' => date('Y-m-d'),
            'voucher' => $voucherFile,
            'observation' => translate('store balance request from') . ' ' . $storeName,
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
                'from' => session()->get('id'),
                'type' => 'deposit',
                'type_id' => $depositId,
                'title' => '🏪 ' . strtoupper(translate('store balance request')),
                'message' => $storeName . ' ' . strtolower(translate('requested balance of')) . ' '
                    . systemGet('currency') . ' ' . number_format($amount, 2)
                    . ' | Ref: #' . $reference,
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => translate('balance request registered pending approval'),
            'deposit_id' => $depositId,
            'reference' => $reference,
        ]);
    }

    public function rechargeSubmit()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        $validationRules = [
            'player_id' => [
                'label' => translate('player'),
                'rules' => 'required|integer|greater_than[0]',
            ],
            'amount' => [
                'label' => translate('amount'),
                'rules' => 'required|decimal|greater_than[0]',
            ],
            'reference' => [
                'label' => translate('reference'),
                'rules' => 'permit_empty|min_length[3]|max_length[50]',
            ],
        ];

        if (! $this->validate($validationRules)) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors(),
            ]);
        }

        $modelUsers = new UsersModel();
        $modelDeposits = new DepositsModel();
        $modelNotifications = new NotificationsModel();
        $modelPayments = new PaymentsModel();

        $storeId = $this->getEffectiveStoreId();
        $playerId = (int) $this->request->getPost('player_id');
        $amount = round((float) $this->request->getPost('amount'), 2);
        $reference = trim((string) $this->request->getPost('reference'));

        $store = $modelUsers->find($storeId);
        if (! $store) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('store not found'),
            ]);
        }

        $store = wallet_service()->normalizeUser($store);
        $storeBalance = wallet_recharge_balance($store);

        if ($storeBalance < $amount) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('insufficient store balance request admin first'),
            ]);
        }

        $player = $modelUsers->where('id', $playerId)->where('group', bingo_group_player())->where('deleted', 0)->where('status', 1)->first();
        if (! $player) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('player not found'),
            ]);
        }

        $affiliateLink = bingo_link_player_to_store_for_affiliation($playerId, $storeId);

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

        if (! wallet_deduct_recharge($storeId, $amount)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('insufficient store balance request admin first'),
            ]);
        }

        wallet_credit_recharge($playerId, $amount);

        $commission = bingo_calculate_store_commission($amount, $store);

        $storeName = bingo_store_display_name($store);

        if ($reference === '') {
            $reference = 'TIENDA-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 8));
        }

        $depositData = [
            'user' => $playerId,
            'store' => $storeId,
            'account' => 'store',
            'method' => 'store player recharge',
            'bank' => translate('store'),
            'document' => $player['document'],
            'phone' => $player['phone'],
            'reference' => $reference,
            'amount' => $amount,
            'commission_amount' => $commission > 0 ? $commission : null,
            'date' => date('Y-m-d'),
            'voucher' => '',
            'observation' => translate('recharge from store') . ': ' . $storeName,
            'status' => 2,
        ];

        $modelDeposits->insert($depositData);
        $depositId = (int) $modelDeposits->getInsertID();

        $fromUserId = (int) session()->get('id');

        if ($commission > 0 && $depositId > 0) {
            $commission = bingo_credit_store_operation_commission(
                $storeId,
                $amount,
                'store_commission',
                $depositId,
                $store,
                $fromUserId
            );
        }

        $modelNotifications->insert([
            'user' => $playerId,
            'from' => $storeId,
            'type' => 'deposit',
            'type_id' => $depositId,
            'title' => '✅ ' . strtoupper(translate('balance credited')),
            'message' => translate('your recharge of') . ' ' . systemGet('currency') . ' ' . number_format($amount, 2)
                . ' ' . translate('was credited from store') . ' ' . $storeName,
        ]);

        $updatedStore = wallet_summary_payload($modelUsers->find($storeId));

        $response = [
            'success' => true,
            'message' => translate('player recharge completed successfully'),
            'deposit_id' => $depositId,
            'reference' => $reference,
            'store_balance' => $updatedStore['recharge'],
            'commission' => $commission,
        ];

        if ($commission > 0) {
            $response['message'] .= '. ' . translate('store commission credited') . ': '
                . systemGet('currency') . ' ' . number_format($commission, 2);
        }

        if (! empty($affiliateLink['newly_linked'])) {
            $response['player_linked_to_store'] = true;
            $response['message'] .= '. ' . translate('player linked to store for ggr');
        }

        $response['recharge_commission_total'] = bingo_sum_store_recharge_commissions($storeId);

        return $this->response->setJSON($response);
    }

    public function fundingListGet()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        $modelDeposits = new DepositsModel();
        $storeId = $this->getEffectiveStoreId();
        $fundingRequests = $this->applyStoreFundingFilter(
            $modelDeposits->where('user', $storeId)
        )
            ->orderBy('created_at', 'DESC')
            ->findAll(30);

        foreach ($fundingRequests as &$request) {
            $request['status_label'] = $this->formatRechargeStatus((int) $request['status']);
        }

        return view('store/fundinglist', ['fundingRequests' => $fundingRequests]);
    }

    public function rechargesListGet()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        $modelDeposits = new DepositsModel();
        $modelUsers = new UsersModel();
        $storeId = $this->getEffectiveStoreId();

        $recharges = $modelDeposits
            ->where('store', $storeId)
            ->where('method', 'store player recharge')
            ->orderBy('created_at', 'DESC')
            ->findAll(50);

        foreach ($recharges as &$recharge) {
            $player = $modelUsers->find($recharge['user']);
            $recharge['player_name'] = $player
                ? trim($player['firstname'] . ' ' . $player['lastname'])
                : translate('user');
            $recharge['player_code'] = $player['code'] ?? '';
            $recharge['player_document'] = $player['document'] ?? $recharge['document'] ?? '';
            $recharge['status_label'] = $this->formatRechargeStatus((int) $recharge['status']);
        }

        return view('store/rechargeslist', ['recharges' => $recharges]);
    }

    public function prizes()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        return $this->renderStorePage('store/prizes', [
            'pendingCount' => $this->countStorePendingPrizes(),
        ], 'Pagar Notas de Retiro');
    }

    public function lookupRetireNote()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        $document = trim((string) $this->request->getPost('document'));
        $code = strtoupper(trim((string) $this->request->getPost('code')));

        if ($document === '' && $code === '') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Ingrese el número de cédula y/o el código de retiro.',
            ]);
        }

        $modelRetires = new RetiresModel();
        $modelUsers = new UsersModel();

        $builder = $modelRetires->builder();
        $builder->where('bank', 'Punto de Venta');
        $builder->where('status', 1);

        if ($code !== '') {
            $builder->where('account', $code);
        }
        if ($document !== '') {
            $builder->where('document', $document);
        }

        $retire = $builder->orderBy('id', 'DESC')->get()->getRowArray();

        if (! $retire) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se encontró ninguna nota de retiro pendiente con la cédula y código ingresados.',
            ]);
        }

        $player = $modelUsers->find((int) $retire['user']);
        $playerName = $player ? trim($player['firstname'] . ' ' . $player['lastname']) : 'Jugador';
        $playerCode = $player['code'] ?? '';

        return $this->response->setJSON([
            'success' => true,
            'retire' => [
                'id'             => (int) $retire['id'],
                'code'           => $retire['account'],
                'amount'         => (float) $retire['amount'],
                'document'       => $retire['document'] ?: ($player['document'] ?? ''),
                'player_name'    => $playerName,
                'player_code'    => $playerCode,
                'player_id'      => (int) $retire['user'],
                'date_formatted' => date('d/m/Y H:i', strtotime($retire['created_at'])),
                'created_at'     => $retire['created_at'],
            ],
        ]);
    }

    public function payRetireSubmit()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        $retireId = (int) $this->request->getPost('retire_id');
        $code = strtoupper(trim((string) $this->request->getPost('code')));
        $document = trim((string) $this->request->getPost('document'));

        $modelRetires = new RetiresModel();
        $modelUsers = new UsersModel();
        $modelNotifications = new NotificationsModel();
        $modelPayments = new PaymentsModel();

        $retire = null;
        if ($retireId > 0) {
            $retire = $modelRetires->find($retireId);
        }
        if (! $retire && $code !== '') {
            $retire = $modelRetires->where('bank', 'Punto de Venta')->where('account', $code)->first();
        }

        if (! $retire || (int) $retire['status'] !== 1) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'La nota de retiro no existe o ya fue procesada anteriormente.',
            ]);
        }

        $playerId = (int) $retire['user'];
        $player = wallet_service()->normalizeUser($modelUsers->find($playerId));

        if (! $player) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Jugador no encontrado.',
            ]);
        }

        $amount = (float) $retire['amount'];
        if (wallet_withdrawable($player) < $amount) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'El saldo retirable disponible del jugador (' . systemGet('currency') . ' ' . number_format(wallet_withdrawable($player), 2) . ') es inferior al monto a retirar.',
            ]);
        }

        if (! wallet_deduct_withdrawable($playerId, $amount)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo debitar el saldo del jugador.',
            ]);
        }

        $storeId = $this->getEffectiveStoreId();
        $storeUser = $modelUsers->find($storeId);
        $storeName = bingo_store_display_name($storeUser ?: []);

        $observation = 'Pagado en efectivo por Punto de Venta: ' . $storeName . ' (ID #' . $storeId . ') el ' . date('d/m/Y H:i:s');
        $modelRetires->update($retire['id'], [
            'status'      => 2,
            'observation' => $observation,
        ]);

        $modelPayments->insert([
            'user'    => $playerId,
            'type'    => 'retire',
            'type_id' => $retire['id'],
            'amount'  => $amount,
            'status'  => 2,
        ]);

        $commission = 0.0;
        if (function_exists('bingo_credit_store_operation_commission')) {
            $commission = bingo_credit_store_operation_commission(
                $storeId,
                $amount,
                'store_prize_commission',
                (int) $retire['id'],
                $storeUser,
                (int) session()->get('id')
            );
        }

        $modelNotifications->insert([
            'user'    => $playerId,
            'from'    => $storeId,
            'type'    => 'retire',
            'type_id' => $retire['id'],
            'title'   => '💵 RETIRO ENTREGADO EN PUNTO DE VENTA',
            'message' => 'Tu retiro por ' . systemGet('currency') . ' ' . number_format($amount, 2) . ' fue pagado en efectivo en el Punto de Venta ' . $storeName . '. Código: ' . $retire['account'],
        ]);

        $updatedStore = wallet_summary_payload($modelUsers->find($storeId));

        $message = 'Retiro de ' . systemGet('currency') . ' ' . number_format($amount, 2) . ' pagado exitosamente en efectivo.';
        if ($commission > 0) {
            $message .= ' Comisión acreditada al punto de venta: ' . systemGet('currency') . ' ' . number_format($commission, 2);
        }

        return $this->response->setJSON([
            'success'       => true,
            'message'       => $message,
            'amount'        => $amount,
            'commission'    => $commission,
            'store_balance' => $updatedStore['recharge'] ?? null,
        ]);
    }

    public function prizesListGet()
    {
        if (! session()->get('logged_in') || ! bingo_is_store()) {
            return $this->response->setStatusCode(403)->setBody(translate('unauthorized'));
        }

        $modelRetires = new RetiresModel();
        $modelUsers = new UsersModel();
        $perPage = 10;
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset = ($page - 1) * $perPage;
        $storeId = $this->getEffectiveStoreId();

        $builder = $modelRetires->builder();
        $builder->where('bank', 'Punto de Venta');
        $builder->where('status', 2);
        $builder->like('observation', 'ID #' . $storeId);

        $totalRecords = $builder->countAllResults(false);
        $retires = $builder->orderBy('id', 'DESC')->limit($perPage, $offset)->get()->getResultArray();
        $totalPages = max(1, (int) ceil($totalRecords / $perPage));

        foreach ($retires as &$r) {
            $player = $modelUsers->find((int) $r['user']);
            $r['player_name'] = $player ? trim($player['firstname'] . ' ' . $player['lastname']) : 'Jugador';
            $r['player_code'] = $player['code'] ?? '';
            $r['code'] = $r['account'] ?: ('#' . $r['id']);
        }
        unset($r);

        return view('store/prizes_list', [
            'retires'        => $retires,
            'currentPage'    => $page,
            'totalPages'     => $totalPages,
            'totalRecords'   => $totalRecords,
            'per_page'       => $perPage,
            'showPagination' => $totalPages > 1,
        ]);
    }

    public function withdraw()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        return redirect()->to(site_url('store/affiliate'));
    }

    public function affiliate()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        $storeId = $this->getEffectiveStoreId();
        $modelUsers = new UsersModel();
        $store = $modelUsers->find($storeId);

        $affiliatedPlayers = bingo_fetch_store_referred_players($storeId, 50);
        $rechargeCommissionTotal = bingo_sum_store_recharge_commissions($storeId);
        $prizeCommissionTotal = bingo_sum_store_prize_commissions($storeId);
        $paymentCommissionTotal = bingo_sum_store_payment_commissions($storeId);
        $ggrDashboard = [
            'total_ggr' => 0,
            'total_commission' => 0,
            'chart' => [],
            'history' => [],
        ];
        if (bingo_ggr_affiliate_active()) {
            $ggrDashboard = bingo_fetch_affiliate_ggr_dashboard($storeId, 'store', 30);
        }
        $ggrCommissionTotal = (float) ($ggrDashboard['total_commission'] ?? 0);

        return $this->renderStorePage('store/affiliate', [
            'commissionRate' => bingo_store_commission_rate($store ?? []),
            'prizeCommissionRate' => bingo_store_prize_commission_rate($store ?? []),
            'rechargeCommissionTotal' => $rechargeCommissionTotal,
            'prizeCommissionTotal' => $prizeCommissionTotal,
            'paymentCommissionTotal' => $paymentCommissionTotal,
            'ggrRate' => bingo_ggr_commission_rate_for($store ?? [], 'store'),
            'ggrCommissionTotal' => $ggrCommissionTotal,
            'totalCommission' => round($paymentCommissionTotal + $ggrCommissionTotal, 2),
            'referredCount' => count($affiliatedPlayers),
            'referredPlayers' => $affiliatedPlayers,
            'ggrDashboard' => $ggrDashboard,
        ], translate('store commissions'));
    }

    public function registerAffiliate()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        $modelUsers = new UsersModel();
        $storeId = $this->getEffectiveStoreId();
        $store = $modelUsers->find($storeId);
        if (! $store) {
            return redirect()->to('/store/affiliate');
        }

        bingo_ensure_store_affiliate_code($store);
        $store = $modelUsers->find($storeId) ?? $store;
        bingo_set_signup_referrer_session($store, (string) ($store['referred_code'] ?? ''));

        $modelContacts = new ContactsModel();
        $data = [
            'page' => [
                'title' => translate('create player account'),
            ],
            'validation' => \Config\Services::validation(),
            'contacts' => $modelContacts->findAll(),
            'contentPage' => view('signup/player', [
                'referrerName' => bingo_store_display_name($store),
                'storeRegistering' => true,
                'backUrl' => site_url('store/affiliate'),
            ]),
        ];

        return view('layout/index', $data);
    }

    public function affiliateCode()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        $modelUsers = new UsersModel();
        $storeId = $this->getEffectiveStoreId();
        $store = $modelUsers->find($storeId);

        if (! $store) {
            return $this->response->setStatusCode(404);
        }

        bingo_ensure_store_affiliate_code($store);
        $store = $modelUsers->find($storeId) ?? $store;

        $data = bingo_store_affiliate_link($store);

        require_once APPPATH . 'Libraries/phpqrcode/qrlib.php';

        ob_start();
        \QRcode::png($data, null, QR_ECLEVEL_M, 6, 2);
        $png = ob_get_clean();

        return $this->response->setContentType('image/png')->setBody($png);
    }

    private function countStorePendingPrizes(): int
    {
        $modelRetires = new RetiresModel();

        return (int) $modelRetires->where('bank', 'Punto de Venta')->where('status', 1)->countAllResults();
    }

    public function movements()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        $storeId = $this->getEffectiveStoreId();
        $dateFrom = trim((string) $this->request->getGet('date_from'));
        $dateTo = trim((string) $this->request->getGet('date_to'));
        $type = trim((string) $this->request->getGet('type'));
        $search = trim((string) $this->request->getGet('search'));

        $ledgerData = bingo_build_store_movements_ledger($storeId, [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'type'      => $type,
            'search'    => $search,
        ]);

        return $this->renderStorePage('store/movements', [
            'movements' => $ledgerData['movements'],
            'stats'     => $ledgerData['stats'],
            'filters'   => [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
                'type'      => $type ?: 'all',
                'search'    => $search,
            ],
            'activeNav' => 'movements',
        ], 'Movimientos del Punto de Venta');
    }

    public function movementsListGet()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        $storeId = $this->getEffectiveStoreId();
        $dateFrom = trim((string) $this->request->getGet('date_from'));
        $dateTo = trim((string) $this->request->getGet('date_to'));
        $type = trim((string) $this->request->getGet('type'));
        $search = trim((string) $this->request->getGet('search'));

        $ledgerData = bingo_build_store_movements_ledger($storeId, [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'type'      => $type,
            'search'    => $search,
        ]);

        return view('store/movements_list', [
            'movements' => $ledgerData['movements'],
            'stats'     => $ledgerData['stats'],
            'currency'  => systemGet('currency') ?? '$',
        ]);
    }

    public function exportMovements()
    {
        if ($redirect = $this->requireStore()) {
            return $redirect;
        }

        $storeId = $this->getEffectiveStoreId();
        $dateFrom = trim((string) $this->request->getGet('date_from'));
        $dateTo = trim((string) $this->request->getGet('date_to'));
        $type = trim((string) $this->request->getGet('type'));
        $search = trim((string) $this->request->getGet('search'));

        $ledgerData = bingo_build_store_movements_ledger($storeId, [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'type'      => $type,
            'search'    => $search,
        ]);

        $export = bingo_store_movements_export_rows($ledgerData['movements']);

        $filename = 'movimientos_punto_venta_' . $storeId . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");

        fputcsv($output, $export['headers'], ';');
        foreach ($export['rows'] as $row) {
            fputcsv($output, $row, ';');
        }
        fclose($output);
        exit();
    }

    private function formatRetireStatus(int $status): string
    {
        return match ($status) {
            2 => '<span class="badge bg-success">' . translate('paid') . '</span>',
            0 => '<span class="badge bg-danger">' . translate('rejected') . '</span>',
            default => '<span class="badge bg-warning text-dark">' . translate('pending') . '</span>',
        };
    }

    private function formatRechargeStatus(int $status): string
    {
        switch ($status) {
            case 2:
                return '<span class="badge bg-success">' . translate('approved') . '</span>';
            case 0:
                return '<span class="badge bg-danger">' . translate('rejected') . '</span>';
            default:
                return '<span class="badge bg-warning text-dark">' . translate('pending') . '</span>';
        }
    }
}
