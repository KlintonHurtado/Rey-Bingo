<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\PaymentsModel;
use App\Models\DepositsModel;
use App\Models\RetiresModel;
use App\Models\RoulettesModel;
use App\Models\TransfersModel;
use App\Models\ReferralsModel;
use App\Models\BanksModel;
use App\Models\SingsModel;
use App\Models\AwardsModel;
use App\Models\NotificationsModel;
use App\Models\GamesModel;
use App\Models\CartonsModel;
use App\Models\ModalitiesModel;
use App\Libraries\ExcelExport;
use CodeIgniter\Controller;

class Payments extends Controller {
    public function __construct() {
        helper(['form', 'url', 'cookie', 'text', 'wallet', 'bingo', 'affiliate_ggr']);
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

    public function createStripeCheckoutSession()
    {
        if (!session()->get('id')) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Usuario no autenticado',
            ]);
        }

        $amount = (float) $this->request->getPost('amount');
        if ($amount <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Monto inválido',
            ]);
        }

        if (systemGet('activateDeposit') == 1) {
            if ($amount < (float) systemGet('minimumDeposit')) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'El monto mínimo de depósito es ' . systemGet('minimumDeposit') . ' ' . systemGet('currency'),
                ]);
            }
            if ($amount > (float) systemGet('maximumDeposit')) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'El monto máximo de depósito es ' . systemGet('maximumDeposit') . ' ' . systemGet('currency'),
                ]);
            }
        }

        $secretKey = env('stripe.secretKey', systemGet('secretStripe') ?: '');
        if ($secretKey === '') {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Stripe no está configurado',
            ]);
        }

        $userId = (int) session()->get('id');
        $reference = uniqid('st_', true);
        $currency = strtolower((string) (env('stripe.currency', systemGet('stripeCurrency') ?: 'usd')));
        $amountCents = (int) round($amount * 100);

        $postFields = http_build_query([
            'mode' => 'payment',
            'success_url' => site_url('payments/stripe/success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => site_url('payments/stripe/cancel'),
            'client_reference_id' => (string) $userId,
            'metadata[user_id]' => (string) $userId,
            'metadata[amount]' => number_format($amount, 2, '.', ''),
            'metadata[reference]' => $reference,
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][unit_amount]' => $amountCents,
            'line_items[0][price_data][product_data][name]' => 'Recarga de billetera',
        ]);

        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $result = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error de conexión con Stripe',
            ]);
        }

        $decoded = json_decode((string) $result, true);
        if ($httpCode >= 400 || empty($decoded['url'])) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => $decoded['error']['message'] ?? 'No se pudo crear la sesión de pago',
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'url' => $decoded['url'],
        ]);
    }

    public function stripeSuccess()
    {
        if ((int) session()->get('group') === 0) {
            return redirect()->to('/play')->with('success', 'Pago completado. Estamos procesando la acreditación.');
        }
        return redirect()->to('/payments')->with('success', 'Pago completado. Estamos procesando la acreditación.');
    }

    public function stripeCancel()
    {
        if ((int) session()->get('group') === 0) {
            return redirect()->to('/play')->with('error', 'Pago cancelado.');
        }
        return redirect()->to('/payments')->with('error', 'Pago cancelado.');
    }

    public function index() {
        
        $modelGames = new GamesModel();
        
        $game = $modelGames->find(session()->get('game_id'));
    
        if ($game) {
            // Jugador: salas. Admin: tablero de la partida activa.
            if ((int) session()->get('group') === 0) {
                return redirect()->to('/play');
            }
            return redirect()->to('/board');
        }

        if ((int) session()->get('group') === 0) {
            return redirect()->to('/play');
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
        $data['adminKpis'] = $this->getAdminKpis($filters);
        
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
                'adminKpis' => $this->getAdminKpis($filters),
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
        $modelPurchaseLogs = new \App\Models\CartonPurchaseLogsModel();
        $modelRoulettes = new RoulettesModel();
        $modelGames = new GamesModel();

        $allTransactions = [];
        $gameCache = [];

        if (session()->get('group') == 0 && session()->get('id')) {
            wallet_backfill_registration_bonus_transaction((int) session()->get('id'));
        }

        $resolveGameLabel = static function (?int $gameId) use ($modelGames, &$gameCache): string {
            if (!$gameId) {
                return 'N/A';
            }
            if (!array_key_exists($gameId, $gameCache)) {
                $gameCache[$gameId] = $modelGames->find($gameId);
            }
            $game = $gameCache[$gameId];
            if (!$game) {
                return '#' . $gameId;
            }

            return trim((string) ($game['description'] ?? '')) !== ''
                ? (string) $game['description']
                : ('#' . $gameId);
        };
        
        try {
            // Obtener pagos
            if (session()->get('group') == 1) {
                $payments = $modelPayments->findAll();
            } else {
                $payments = $modelPayments->where('user', session()->get('id'))->findAll();
            }

            foreach ($payments as $payment) {
                $user = $modelUsers->find($payment['user']);

                if ($payment['type'] === 'registration_bonus' || $payment['type'] === 'admin_bonus' || $payment['type'] === 'admin_bonus_debit') {
                    $isAdminBonus = $payment['type'] === 'admin_bonus' || $payment['type'] === 'admin_bonus_debit';
                    $isDebit = $payment['type'] === 'admin_bonus_debit';
                    $allTransactions[] = [
                        'id' => $payment['id'],
                        'type' => $isDebit ? 'retire' : 'bonus',
                        'type_Tra' => $isDebit ? 'Ajuste Bono (Admin)' : ($isAdminBonus ? 'Bono Admin' : translate('registration bonus')),
                        'user_id' => $payment['user'],
                        'user_name' => $user ? $user['firstname'] . ' ' . $user['lastname'] : 'N/A',
                        'user_code' => $user ? $user['code'] : 'N/A',
                        'bank' => $isAdminBonus
                            ? ($isDebit ? 'Ajuste de saldo de bono' : translate('admin bonus info'))
                            : translate('registration bonus info'),
                        'reference' => ($isAdminBonus ? 'ABO-' : 'ABN-') . str_pad($payment['id'], 4, '0', STR_PAD_LEFT),
                        'amount' => $payment['amount'],
                        'date' => $payment['created_at'],
                        'date_formatted' => date('d/m/Y', strtotime($payment['created_at'])),
                        'status' => $payment['status'],
                        'status_raw' => $payment['status'],
                        'status_formatted' => $this->formatStatusPayment($payment['status']),
                        'created_at' => date('d/m/Y', strtotime($payment['created_at'])),
                    ];
                    continue;
                }

                $typePayment = '';
                $typeTra = translate('payment');
                $ledgerType = 'payment';

                if ($payment['type'] == 'award') {
                    $typePayment = translate('per award paid');
                    $typeTra = translate('award');
                    $ledgerType = 'award';
                } else if ($payment['type'] == 'referred') {
                    $typePayment = translate('per referred player');
                    $typeTra = translate('referred');
                    $ledgerType = 'referred';
                } else if (in_array($payment['type'], ['operator_store_debit', 'admin_store_debit', 'admin_operator_debit', 'store_debit', 'store_balance_remove', 'admin_recharge_debit', 'admin_withdraw_debit', 'store_retire_pay'], true)) {
                    $typePayment = $payment['type'] === 'store_retire_pay' ? 'Pago de Retiro en Efectivo' : ($payment['type'] === 'admin_withdraw_debit' ? 'Ajuste Retiro (Admin)' : ($payment['type'] === 'admin_recharge_debit' ? 'Ajuste Recarga (Admin)' : 'Retiro de saldo'));
                    $typeTra = 'Retiro';
                    $ledgerType = 'retire';
                } else if (in_array($payment['type'], ['operator_store_credit', 'admin_operator_pay', 'admin_operator_credit', 'store_credit', 'store_balance_add', 'admin_recharge_credit', 'admin_withdraw_credit', 'operator_prize_credit', 'operator_prize_payout_credit'], true)) {
                    $typePayment = ($payment['type'] === 'operator_prize_credit' || $payment['type'] === 'operator_prize_payout_credit') ? 'Acreditación por Pago de Premio' : ($payment['type'] === 'admin_withdraw_credit' ? 'Acreditación Retiro (Admin)' : ($payment['type'] === 'admin_recharge_credit' ? 'Acreditación Recarga (Admin)' : 'Acreditación de saldo'));
                    $typeTra = 'Acreditación';
                    $ledgerType = 'deposit';
                }

                $transaction = [
                    'id' => $payment['id'],
                    'type' => $ledgerType,
                    'type_Tra' => $typeTra,
                    'user_id' => $payment['user'],
                    'user_name' => $user ? $user['firstname'] . ' ' . $user['lastname'] : 'N/A',
                    'user_code' => $user ? $user['code'] : 'N/A',
                    'bank' => $this->formatBankInfo(
                        $payment['type'] == 'award' ? 'award' : 'payment',
                        $user,
                        $typePayment
                    ),
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
                $storeUser = ! empty($deposit['store']) ? $modelUsers->find($deposit['store']) : null;
                $isStoreFunding = bingo_deposit_is_store_funding($deposit);
                $isStorePlayerRecharge = bingo_deposit_is_store_player_recharge($deposit);

                if ($isStoreFunding) {
                    $typeTra = translate('store balance request');
                } elseif ($isStorePlayerRecharge) {
                    $typeTra = translate('store player recharge');
                } else {
                    $typeTra = translate('deposit');
                }

                // Usar created_at para ordenar: date solo es Y-m-d (00:00) y queda bajo abonos/premios del mismo día.
                $depositSortDate = $deposit['created_at'] ?? null;
                if (empty($depositSortDate) || $depositSortDate === '0000-00-00 00:00:00') {
                    $depositSortDate = ! empty($deposit['date'])
                        ? $deposit['date'] . ' 12:00:00'
                        : date('Y-m-d H:i:s');
                }

                $transaction = [
                    'id' => $deposit['id'],
                    'type' => 'deposit',
                    'type_Tra' => $typeTra,
                    'user_id' => $deposit['user'],
                    'user_name' => $user ? $user['firstname'] . ' ' . $user['lastname'] : 'N/A',
                    'user_code' => $user ? $user['code'] : 'N/A',
                    'bank' => $isStoreFunding
                        ? translate('store') . ': ' . ($user ? bingo_store_display_name($user) : 'N/A')
                        : ($isStorePlayerRecharge
                            ? translate('store') . ': ' . ($storeUser ? bingo_store_display_name($storeUser) : 'N/A')
                            : $this->formatBankInfo('deposit', $user, $deposit['bank'])),
                    'reference' => $deposit['reference'],
                    'amount' => $deposit['amount'],
                    'date' => $depositSortDate,
                    'date_formatted' => date('d/m/Y', strtotime($deposit['date'] ?: $depositSortDate)),
                    'status' => $deposit['status'],
                    'status_raw' => $deposit['status'],
                    'status_formatted' => $this->formatStatusDeposit($deposit['status']),
                    'created_at' => date('d/m/Y', strtotime($depositSortDate)),
                    'store_name' => $storeUser ? bingo_store_display_name($storeUser) : '',
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

            // Compras de cartones (wallet / bono / ruleta)
            try {
                bingo_ensure_users_schema();
                $db = \Config\Database::connect();
                if ($db->tableExists('carton_purchase_logs')) {
                    if (session()->get('group') == 1) {
                        $purchaseLogs = $modelPurchaseLogs->orderBy('created_at', 'DESC')->findAll();
                    } else {
                        $purchaseLogs = $modelPurchaseLogs
                            ->where('user_id', session()->get('id'))
                            ->orderBy('created_at', 'DESC')
                            ->findAll();
                    }

                    foreach ($purchaseLogs as $log) {
                        $user = $modelUsers->find($log['user_id']);
                        $gameLabel = $resolveGameLabel(! empty($log['game_id']) ? (int) $log['game_id'] : null);
                        $cartonsCount = (int) ($log['cartons_count'] ?? 0);
                        $source = bingo_classify_purchase_source($log);
                        $sourceLabel = bingo_purchase_source_label($source);

                        $splitParts = [];
                        if ((float) ($log['from_bonus'] ?? 0) > 0) {
                            $splitParts[] = translate('bonus') . ': ' . number_format((float) $log['from_bonus'], 2);
                        }
                        if ((float) ($log['from_recharge'] ?? 0) > 0) {
                            $splitParts[] = translate('recharge') . ': ' . number_format((float) $log['from_recharge'], 2);
                        }
                        if ((float) ($log['from_withdraw'] ?? 0) > 0) {
                            $splitParts[] = translate('withdraw') . ': ' . number_format((float) $log['from_withdraw'], 2);
                        }

                        $info = translate('carton purchase') . ': ' . $cartonsCount
                            . ' · ' . $gameLabel
                            . ' · ' . $sourceLabel;
                        if (! empty($splitParts)) {
                            $info .= '<br><small class="text-muted">' . esc(implode(' | ', $splitParts)) . '</small>';
                        }

                        $allTransactions[] = [
                            'id' => $log['id'],
                            'type' => 'purchase',
                            'type_Tra' => translate('carton purchase'),
                            'user_id' => $log['user_id'],
                            'user_name' => $user ? $user['firstname'] . ' ' . $user['lastname'] : 'N/A',
                            'user_code' => $user ? $user['code'] : 'N/A',
                            'bank' => $info,
                            'reference' => 'CP-' . str_pad((string) $log['id'], 4, '0', STR_PAD_LEFT),
                            'amount' => $log['amount'],
                            'date' => $log['created_at'],
                            'date_formatted' => date('d/m/Y', strtotime((string) $log['created_at'])),
                            'status' => 2,
                            'status_raw' => 2,
                            'status_formatted' => $this->formatStatusPayment(2),
                            'created_at' => date('d/m/Y', strtotime((string) $log['created_at'])),
                        ];
                    }

                    // Histórico sin logs: solo en wallet del jugador
                    if ((int) session()->get('group') === 0 && empty($purchaseLogs) && session()->get('id')) {
                        $playerId = (int) session()->get('id');
                        $user = $modelUsers->find($playerId);
                        $legacy = $db->query(
                            'SELECT c.game AS game_id, COUNT(*) AS cartons_count, MIN(c.created_at) AS first_at, MAX(c.created_at) AS last_at
                             FROM cartons c
                             WHERE c.user = ?
                             GROUP BY c.game
                             ORDER BY last_at DESC
                             LIMIT 100',
                            [$playerId]
                        )->getResultArray();

                        foreach ($legacy as $row) {
                            $gameId = (int) ($row['game_id'] ?? 0);
                            $game = $gameId ? ($gameCache[$gameId] ?? $modelGames->find($gameId)) : null;
                            if ($gameId && !array_key_exists($gameId, $gameCache)) {
                                $gameCache[$gameId] = $game;
                            }
                            $price = (float) ($game['price'] ?? 0);
                            $count = (int) ($row['cartons_count'] ?? 0);
                            $gameLabel = $resolveGameLabel($gameId > 0 ? $gameId : null);
                            $sortDate = $row['last_at'] ?? $row['first_at'] ?? date('Y-m-d H:i:s');

                            $allTransactions[] = [
                                'id' => 'L' . $gameId,
                                'type' => 'purchase',
                                'type_Tra' => translate('carton purchase'),
                                'user_id' => $playerId,
                                'user_name' => $user ? $user['firstname'] . ' ' . $user['lastname'] : 'N/A',
                                'user_code' => $user ? $user['code'] : 'N/A',
                                'bank' => translate('carton purchase') . ': ' . $count . ' · ' . $gameLabel,
                                'reference' => 'CG-' . str_pad((string) $gameId, 4, '0', STR_PAD_LEFT),
                                'amount' => round($price * $count, 2),
                                'date' => $sortDate,
                                'date_formatted' => date('d/m/Y', strtotime((string) $sortDate)),
                                'status' => 2,
                                'status_raw' => 2,
                                'status_formatted' => $this->formatStatusPayment(2),
                                'created_at' => date('d/m/Y', strtotime((string) $sortDate)),
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', 'Error cargando compras de cartones: ' . $e->getMessage());
            }

            // Premios / cartones de ruleta otorgados
            if (session()->get('group') == 1) {
                $roulettes = $modelRoulettes->findAll();
            } else {
                $roulettes = $modelRoulettes->where('user', session()->get('id'))->findAll();
            }

            foreach ($roulettes as $roulette) {
                $user = $modelUsers->find($roulette['user']);
                $gameLabel = $resolveGameLabel(! empty($roulette['game']) ? (int) $roulette['game'] : null);
                $isUsed = (int) ($roulette['status'] ?? 0) === 1;
                $cartonsCount = (int) ($roulette['cartons'] ?? 0);
                $amount = (float) ($roulette['amount'] ?? $roulette['price'] ?? 0);

                $info = translate('roulette') . ': ' . $cartonsCount . ' ' . translate('cartons')
                    . ' · ' . $gameLabel;

                $allTransactions[] = [
                    'id' => $roulette['id'],
                    'type' => 'roulette',
                    'type_Tra' => $isUsed ? translate('roulette used') : translate('roulette granted'),
                    'user_id' => $roulette['user'],
                    'user_name' => $user ? $user['firstname'] . ' ' . $user['lastname'] : 'N/A',
                    'user_code' => $user ? $user['code'] : 'N/A',
                    'bank' => $info,
                    'reference' => 'RL-' . str_pad((string) $roulette['id'], 4, '0', STR_PAD_LEFT),
                    'amount' => $amount,
                    'date' => $roulette['created_at'] ?? $roulette['updated_at'] ?? date('Y-m-d H:i:s'),
                    'date_formatted' => date('d/m/Y', strtotime((string) ($roulette['created_at'] ?? 'now'))),
                    'status' => $isUsed ? 2 : 1,
                    'status_raw' => $isUsed ? 2 : 1,
                    'status_formatted' => $isUsed
                        ? $this->formatStatusPayment(2)
                        : $this->formatStatusPayment(1),
                    'created_at' => date('d/m/Y', strtotime((string) ($roulette['created_at'] ?? 'now'))),
                ];
            }

            // Ordenar: pendientes de depósito/retiro primero (admin), luego por fecha desc
            usort($allTransactions, function ($a, $b) {
                $isAdmin = (int) session()->get('group') === 1;
                if ($isAdmin) {
                    $pendingA = in_array($a['type'] ?? '', ['deposit', 'retire'], true) && (int) ($a['status_raw'] ?? 0) === 1 ? 1 : 0;
                    $pendingB = in_array($b['type'] ?? '', ['deposit', 'retire'], true) && (int) ($b['status_raw'] ?? 0) === 1 ? 1 : 0;
                    if ($pendingA !== $pendingB) {
                        return $pendingB - $pendingA;
                    }
                }

                return strtotime((string) ($b['date'] ?? '')) - strtotime((string) ($a['date'] ?? ''));
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
                    // Compatibilidad: el filtro antiguo "payment" incluía premios
                    if ($filters['type'] === 'payment') {
                        return in_array($transaction['type'], ['payment', 'award', 'referred'], true);
                    }

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
            'bonuses' => ['count' => 0, 'amount' => 0],
            'awards' => ['count' => 0, 'amount' => 0],
            'purchases' => ['count' => 0, 'amount' => 0],
            'roulettes' => ['count' => 0, 'amount' => 0],
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
                } elseif ($transaction['type'] === 'bonus') {
                    $stats['bonuses']['count']++;
                    $stats['bonuses']['amount'] += $amount;
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

    private function getUserAccreditationStats(int $userId): array
    {
        $stats = $this->buildAccreditationStats(['user_id' => $userId]);

        helper('wallet');
        $modelUsers = new UsersModel();
        $user = $modelUsers->find($userId);
        if ($user) {
            $user = wallet_service()->normalizeUser($user);
            $stats['wallet_total'] = round(wallet_total($user), 2);
            $stats['wallet_recharge'] = round((float) ($user['wallet_recharge'] ?? 0), 2);
            $stats['wallet_withdraw'] = round((float) ($user['wallet_withdraw'] ?? 0), 2);
            $stats['wallet_bonus'] = round((float) ($user['wallet_bonus'] ?? 0), 2);
        } else {
            $stats['wallet_total'] = 0;
            $stats['wallet_recharge'] = 0;
            $stats['wallet_withdraw'] = 0;
            $stats['wallet_bonus'] = 0;
        }

        $modelRetires = new RetiresModel();
        $stats['total_retires'] = round((float) (($modelRetires
            ->where('user', $userId)
            ->where('status', 2)
            ->selectSum('amount')
            ->get()
            ->getRow()
            ->amount) ?? 0), 2);

        $modelRoulettes = new RoulettesModel();
        $stats['granted_cartons'] = (int) (($modelRoulettes
            ->where('user', $userId)
            ->selectSum('cartons')
            ->get()
            ->getRow()
            ->cartons) ?? 0);
        $stats['pending_cartons'] = bingo_count_pending_roulette_cartons($userId);

        return $stats;
    }

    private function getAdminKpis(array $filters = []): array
    {
        if (session()->get('group') != 1) {
            return [
                'manual_credits' => 0,
                'user_spend' => 0,
                'total_prizes' => 0,
            ];
        }

        return $this->buildAccreditationStats($filters);
    }

    private function buildAccreditationStats(array $filters = []): array
    {
        $modelDeposits = new DepositsModel();
        $modelPayments = new PaymentsModel();

        $manualBuilder = $modelDeposits->builder();
        $manualBuilder->selectSum('amount', 'total')
            ->where('status', 2);
        $this->applyKpiFilters($manualBuilder, $filters, 'date', 'user');
        $manualCredits = (float) ($manualBuilder->get()->getRow()->total ?? 0);

        $spendBuilder = $modelPayments->builder();
        $spendBuilder->selectSum('amount', 'total')
            ->where('status', 2)
            ->whereNotIn('type', ['award', 'referred', 'registration_bonus', 'admin_bonus']);
        $this->applyKpiFilters($spendBuilder, $filters, 'created_at', 'user');
        $userSpend = (float) ($spendBuilder->get()->getRow()->total ?? 0);

        $prizesBuilder = $modelPayments->builder();
        $prizesBuilder->selectSum('amount', 'total')
            ->where('status', 2)
            ->where('type', 'award');
        $this->applyKpiFilters($prizesBuilder, $filters, 'created_at', 'user');
        $totalPrizes = (float) ($prizesBuilder->get()->getRow()->total ?? 0);

        return [
            'manual_credits' => round($manualCredits, 2),
            'user_spend' => round($userSpend, 2),
            'total_prizes' => round($totalPrizes, 2),
        ];
    }

    private function applyKpiFilters($builder, array $filters, string $dateField, string $userField): void
    {
        if (! empty($filters['user_id']) && $filters['user_id'] !== 'all') {
            $builder->where($userField, (int) $filters['user_id']);
        }

        if (! empty($filters['date_from'])) {
            $builder->where($dateField . ' >=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $builder->where($dateField . ' <=', $filters['date_to'] . ' 23:59:59');
        }
    }

    public function exportData()
    {
        if (! session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        $filters = $this->getFilters();
        $allTransactions = $this->getAllTransactions();
        $filteredTransactions = $this->applyFilters($allTransactions, $filters);

        $filename = 'pagos-export-' . date('Ymd-His') . '.xls';
        $headers = [
            'ID',
            'Tipo',
            'Usuario',
            'Codigo',
            'Referencia',
            'Monto',
            'Fecha',
            'Estado',
        ];

        $rows = [];
        foreach ($filteredTransactions as $transaction) {
            $rows[] = [
                $transaction['id'] ?? '',
                strip_tags($transaction['type_Tra'] ?? ($transaction['type'] ?? '')),
                $transaction['user_name'] ?? '',
                $transaction['user_code'] ?? '',
                $transaction['reference'] ?? '',
                (float) ($transaction['amount'] ?? 0),
                $transaction['date_formatted'] ?? ($transaction['date'] ?? ''),
                strip_tags($transaction['status_formatted'] ?? ''),
            ];
        }

        return (new ExcelExport())->downloadResponse($headers, $rows, $filename, [
            'sheet_name' => 'Pagos',
            'numeric_columns' => [5],
        ]);
    }

    private function formatBankInfo($type, $user, $bank = null, $userTo = null) {
        if (session()->get('group') == 1) {
            switch ($type) {
                case 'payment':
                    return translate('payment to wallet') . '<br><small class="text-muted">' . $bank . '</small>';
                    //return translate('payment to wallet') . '<br><small class="text-muted">' . ($user ? $user['code'] . ' - ' . $user['firstname'] . ' ' . $user['lastname'] : 'N/A') . '</small>';
                case 'award':
                    return translate('award to wallet') . '<br><small class="text-muted">' . $bank . '</small>';
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
                case 'award':
                    return translate('award to wallet') . '<br><small class="text-muted">' . $bank . '</small>';
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
        switch ((int) $status) {
            case 1:
                return '<span class="badge bg-warning text-dark"><i class="fa-duotone fa-solid fa-clock"></i> ' . translate('pending') . '</span>';
            case 3:
                return '<span class="badge bg-info text-white"><i class="fa-duotone fa-solid fa-magnifying-glass"></i> En revisión</span>';
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

        if ($type === 'payment' || $type === 'award' || $type === 'bonus' || $type === 'referred') {
            $data['payment'] = $modelPayments->where('id', $id)->first();

            if ($data['payment']) {
                $data['user'] = $modelUsers->find($data['payment']['user']);
            }

            $data['status'] = $this->formatStatusPayment($data['payment']['status'] ?? 0);

            $data['type'] = $type === 'award' || $type === 'bonus' || $type === 'referred' ? $type : 'payment';

            return view('users/requestPayment', $data);
        } else if ($type === 'deposit') {
            helper('bingo');
            $data['deposit'] = $modelDeposits->where('id', $id)->first();

            if ($data['deposit']) {
                // Intentar recuperar comprobante si el nombre en BD no coincide exactamente con el archivo
                if (! empty($data['deposit']['voucher']) && ! bingo_voucher_exists($data['deposit']['voucher'])) {
                    bingo_voucher_sync_after_insert((int) $data['deposit']['id'], (string) $data['deposit']['voucher']);
                    $data['deposit'] = $modelDeposits->where('id', $id)->first() ?: $data['deposit'];
                }

                $data['user'] = $modelUsers->find($data['deposit']['user']);
                if ($data['user']) {
                    $data['user'] = wallet_service()->normalizeUser($data['user']);
                    $data['userStats'] = $this->getUserAccreditationStats((int) $data['deposit']['user']);
                }
                if (! empty($data['deposit']['store'])) {
                    $data['storeUser'] = $modelUsers->find($data['deposit']['store']);
                }

                $data['status'] = $this->formatStatusDeposit($data['deposit']['status']);
            } else {
                $data['status'] = '';
                $data['user'] = null;
            }

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
        helper('bingo');
        $modelDeposits = new DepositsModel();

        $data['deposit'] = $modelDeposits->where('id', $id)->first();
        if ($data['deposit'] && ! empty($data['deposit']['voucher']) && ! bingo_voucher_exists($data['deposit']['voucher'])) {
            bingo_voucher_sync_after_insert((int) $data['deposit']['id'], (string) $data['deposit']['voucher']);
            $data['deposit'] = $modelDeposits->where('id', $id)->first() ?: $data['deposit'];
        }

        return view('users/modalVoucher', $data);
    }

    public function voucher($file = null)
    {
        $path = bingo_voucher_resolve($file);
        if ($path === '') {
            return $this->response->setStatusCode(404)->setBody('Voucher no encontrado');
        }

        $mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($detected) && $detected !== '') {
                    $mime = $detected;
                }
            }
        } elseif (function_exists('mime_content_type')) {
            $detected = @mime_content_type($path);
            if (is_string($detected) && $detected !== '') {
                $mime = $detected;
            }
        }

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'private, max-age=86400')
            ->setBody((string) file_get_contents($path));
    }
  
    public function depositGet() {
        $modelBanks = new BanksModel();

        $data['banks'] = $modelBanks->where('status', 1)->findAll();

        $modelUsers = new UsersModel();

        $data['users'] = $modelUsers->where('status', 1)->where('group', bingo_group_player())->findAll();

        $data['user'] = $modelUsers->find(session()->get('id'));

        return view('users/deposit', $data);
    }

    public function userAccreditationStatsGet($userId = null)
    {
        if (! session()->get('logged_in') || session()->get('group') != 1) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Acceso no autorizado',
            ]);
        }

        $userId = (int) $userId;
        if ($userId < 1) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('user not found'),
            ]);
        }

        $modelUsers = new UsersModel();
        if (! $modelUsers->find($userId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('user not found'),
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'stats' => $this->getUserAccreditationStats($userId),
        ]);
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
        if (function_exists('bingo_ensure_deposits_schema')) {
            bingo_ensure_deposits_schema();
        }

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

        $voucherImage = trim((string) $this->request->getPost('deposit-voucher'));
        $voucherFile = $this->request->getFile('deposit-voucher-file');
        $hasUpload = $voucherFile && $voucherFile->isValid() && ! $voucherFile->hasMoved();
        $hasBase64 = $voucherImage !== '' && strpos($voucherImage, 'data:image') === 0;

        if (! $hasUpload && ! $hasBase64) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => [
                    'deposit-voucher' => translate('voucher') . ' ' . strtolower(translate('it is mandatory')),
                ],
            ]);
        }

        $isAdmin = session()->get('group') == 1;
        $selectedUser = $this->request->getPost('deposit-user');

        if ($isAdmin && $selectedUser) {
            $depositUserId = $selectedUser;
            $observation = $this->request->getPost('observation');
        } else {
            $depositUserId = session()->get('id');
            $observation = '';
        }

        // Todo depósito manual queda pendiente hasta que un admin lo apruebe.
        $status = 1;

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

        // Preferir archivo multipart (más fiable que base64 grande)
        if ($hasUpload) {
            $saved = bingo_save_voucher_upload($voucherFile);
        } else {
            $saved = bingo_save_voucher_base64($voucherImage);
        }

        if (! $saved['success']) {
            $msg = translate('voucher') . ' ' . strtolower(translate('it is mandatory'));
            if (($saved['error'] ?? '') === 'write') {
                $msg = 'No se pudo guardar el comprobante en el servidor. Verifique permisos de public/uploads/vouchers/.';
            }

            return $this->response->setJSON([
                'success' => false,
                'errors' => [
                    'deposit-voucher' => $msg,
                ],
            ]);
        }
        $data['voucher'] = $saved['filename'];

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

        $inserted = $modelDeposits->insert($data);
        $depositId = $inserted ? (int) $modelDeposits->insertID() : 0;

        if (! $depositId) {
            log_message('error', 'depositSubmit insert failed: ' . json_encode($modelDeposits->errors()));
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('error saving payment'),
            ]);
        }

        // Asegurar que el archivo exista con el nombre real guardado en BD (evita truncados)
        if (! bingo_voucher_sync_after_insert($depositId, $saved['filename'])) {
            log_message('error', 'depositSubmit voucher missing after insert id=' . $depositId . ' file=' . $saved['filename']);
            try {
                $modelDeposits->delete($depositId);
            } catch (\Throwable $e) {
                // ignore
            }

            return $this->response->setJSON([
                'success' => false,
                'errors' => [
                    'deposit-voucher' => 'No se pudo guardar el comprobante en el servidor. Verifique permisos de public/uploads/vouchers/.',
                ],
            ]);
        }

        $user = $modelUsers->find($depositUserId);
        $currentUserId = session()->get('id');
        $modelNotifications = new NotificationsModel();

        $admins = $modelUsers->select('id')->where('group', 1)->findAll();
        $creator = $modelUsers->find($currentUserId);
        $creatorName = $creator ? trim($creator['firstname'] . ' ' . $creator['lastname']) : translate('user');

        try {
            foreach ($admins as $admin) {
                $notificationData = [
                    'user' => $admin['id'],
                    'from' => $currentUserId,
                    'type' => 'deposit',
                    'type_id' => $depositId,
                    'title' => '📥 NUEVA SOLICITUD DE DEPÓSITO',
                    'message' => ($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '') . ' ha registrado un depósito por ' . systemGet('currency') . ' ' . number_format($data['amount'], 2) . ' | Ref: #' . $data['reference'] . ' | Fecha: ' . date('d/m/Y', strtotime($data['date'])) . '.',
                ];

                $modelNotifications->insert($notificationData);
            }

            if ((int) $depositUserId !== (int) $currentUserId) {
                $notificationData = [
                    'user' => $depositUserId,
                    'from' => $currentUserId,
                    'type' => 'deposit',
                    'type_id' => $depositId,
                    'title' => '📥 DEPÓSITO EN REVISIÓN',
                    'message' => $creatorName . ' registró un depósito por ' . systemGet('currency') . ' ' . number_format($data['amount'], 2) . '. Está pendiente de verificación; el saldo se acreditará cuando sea aprobado.',
                ];

                $modelNotifications->insert($notificationData);
            } elseif (! $isAdmin) {
                $notificationData = [
                    'user' => $depositUserId,
                    'from' => $currentUserId,
                    'type' => 'deposit',
                    'type_id' => $depositId,
                    'title' => '📥 DEPÓSITO EN REVISIÓN',
                    'message' => 'Su solicitud de depósito por ' . systemGet('currency') . ' ' . number_format($data['amount'], 2) . ' fue recibida y está pendiente de verificación.',
                ];

                $modelNotifications->insert($notificationData);
            }
        } catch (\Throwable $e) {
            log_message('error', 'depositSubmit notification failed: ' . $e->getMessage());
        }

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
                'date' => date('Y-m-d H:i:s'),
                'date_formatted' => date('d/m/Y', strtotime($data['date'])),
                'status' => $data['status'],
                'status_raw' => $data['status'],
                'status_formatted' => $this->formatStatusDeposit($data['status']),
                'created_at' => date('d/m/Y')
            ]
        ];
        
        return $this->response->setJSON($response);
    }

    public function depositPaypalSubmit() {
        $modelDeposits = new DepositsModel();
        $modelUsers = new UsersModel();

        $amount = $this->request->getPost('amount');
        $paymentID = $this->request->getPost('paymentID');
        $paymentToken = $this->request->getPost('paymentToken');
        $payerID = $this->request->getPost('payerID');

        $paypalCredentials = paypalCredentials();
        $paypalClientID = $paypalCredentials['client_id'];
        $paypalSecret = $paypalCredentials['secret'];

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

        $existing = $modelDeposits->where('reference', $paymentID)->first();
        if ($existing) {
            return $this->response->setJSON([
                'success' => true,
                'newRecharge' => [
                    'type'      => translate('paypal'),
                    'date'      => date('Y-m-d'),
                    'amount'    => $amount,
                    'reference' => $paymentID,
                    'bank'      => 'N/A',
                ]
            ]);
        }

        $modelDeposits->insert($data);
        $paymentId = $modelDeposits->insertID();

        if ($paymentId) {
            wallet_credit_recharge((int) session()->get('id'), (float) $amount);
        }

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
            'type' => $bank['type']
        ]);
    }

    public function retireGet() {
        $modelUsers = new UsersModel();
        $modelRetires = new RetiresModel();

        $userId = (int) session()->get('id');
        $data['user'] = wallet_service()->normalizeUser($modelUsers->find($userId));
        $data['pendingRetire'] = $modelRetires->where('user', $userId)->where('status', 1)->first();

        return view('users/retire', $data);
    }

    public function retireSubmit() {
        $modelRetires = new RetiresModel();
        $userId = (int) session()->get('id');

        $pendingRetire = $modelRetires->where('user', $userId)->where('status', 1)->first();
        if ($pendingRetire) {
            $currency = systemGet('currency') ?? '$';
            $amountFormatted = $currency . ' ' . number_format((float) ($pendingRetire['amount'] ?? 0), 2);
            return $this->response->setJSON([
                'success' => false,
                'has_pending' => true,
                'errors' => [
                    'retire-amount' => 'Ya tienes una solicitud de retiro en proceso por ' . $amountFormatted . '. Debes esperar a que sea procesada antes de enviar una nueva.',
                ],
                'message' => 'Ya tienes una solicitud de retiro pendiente de procesar.',
            ]);
        }

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
                'retire-account-type' => [
                    'label' => translate('account type'),
                    'rules' => 'required|in_list[savings,checking]'
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
                'kyc_required' => true,
                'message' => wallet_kyc_withdraw_message($user),
                'kyc_url' => site_url('kyc'),
            ]);
        }

        if (bingo_is_store((int) ($user['group'] ?? -1)) || bingo_is_operator((int) ($user['group'] ?? -1))) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => [
                    'retire-amount' => 'Las comisiones de operadores y puntos de venta son liquidadas y acreditadas por la administración al cierre de cada mes.',
                ],
            ]);
        }

        $withdrawable = wallet_withdrawable($user);
        $retireAmount = (float) $this->request->getPost('retire-amount');
        if ($retireAmount > $withdrawable) {
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
            $accountType = bingo_normalize_account_type($this->request->getPost('retire-account-type'));
            $data = [
                'user'   => session()->get('id'),
                'bank'      => $this->request->getPost('retire-bank'),
                'account'   => $this->request->getPost('retire-account'),
                'account_type' => $accountType,
                'document'  => $this->request->getPost('retire-document'),
                'phone'     => $this->request->getPost('retire-phone'),
                'amount'    => $this->request->getPost('retire-amount'),
                'status' => 1
            ];

            if ($saveAccount) {
                $dataBank = [
                    'bank'     => $data['bank'],
                    'account'  => $data['account'],
                    'account_type' => $accountType,
                    'document' => $data['document'],
                    'phone'    => $data['phone']
                ];

                $modelUsers->update(session()->get('id'), $dataBank);
            }
        } else if ($receiver === "store") {
            // Generar código de retiro estrictamente numérico (ej. 8 dígitos)
            do {
                $retireCode = (string) mt_rand(10000000, 99999999);
            } while ($modelRetires->where('bank', 'Punto de Venta')->where('account', $retireCode)->first());

            $data = [
                'user'         => session()->get('id'),
                'bank'         => 'Punto de Venta',
                'account'      => $retireCode,
                'account_type' => 'store_pickup',
                'document'     => $user['document'] ?? '',
                'phone'        => $user['phone'] ?? '',
                'amount'       => $this->request->getPost('retire-amount'),
                'observation'  => 'Código de retiro: ' . $retireCode,
                'status'       => 1
            ];
        } else {
            $accountType = bingo_normalize_account_type($user['account_type'] ?? '');
            if ($accountType === '') {
                return $this->response->setJSON([
                    'success' => false,
                    'errors' => [
                        'retire-receiver' => translate('please update your bank account type'),
                    ],
                ]);
            }

            $data = [
                'user'   => session()->get('id'),
                'bank'      => $user['bank'],
                'account'   => $user['account'],
                'account_type' => $accountType,
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

        // Deducir inmediatamente el saldo retirable para que no pueda usarse mientras esté pendiente o en revisión
        if (! wallet_deduct_withdrawable((int) session()->get('id'), (float) $data['amount'])) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => [
                    'retire-amount' => translate('the amount cannot exceed what is available') . ': ' . systemGet('currency') . ' ' . number_format($withdrawable, 2)
                ]
            ]);
        }

        $modelRetires->insert($data);
        $retireId = $modelRetires->insertID();

        $retire = $modelRetires->where('id', $retireId)->first();

        $reference = str_pad($retireId, 4, '0', STR_PAD_LEFT);
        $currentUserId = session()->get('id');

        $modelNotifications = new NotificationsModel();

        $admins = $modelUsers->select('id')->where('group', 1)->findAll();

        foreach ($admins as $admin) {
            $notificationData = [
                'user' => $admin['id'],
                'from' => $currentUserId,
                'type' => 'retire',
                'type_id' => $retireId,
                'title' => $receiver === 'store' ? 'NUEVA NOTA DE RETIRO EN PUNTO DE VENTA' : 'NUEVA SOLICITUD DE RETIRO',
                'message' => $user['firstname'] . ' ' . $user['lastname'] . ' ha solicitado un retiro por ' . systemGet('currency') . ' ' . number_format($data['amount'], 2) . ($receiver === 'store' ? (' (Código: ' . $data['account'] . ')') : (' | Ref: #' . $reference)) . ' | Fecha: ' . date('d/m/Y'),
            ];

            $modelNotifications->insert($notificationData);
        }

        if ($retireId) {
            $responseMessage = $receiver === 'store'
                ? 'Solicitud de nota de retiro generada exitosamente. Tu solicitud está en estado Pendiente; una vez que el administrador la apruebe, recibirás tu código de retiro por correo electrónico.'
                : translate('retire request sent successfully');

            $response = [
                'success' => true,
                'message' => $responseMessage,
                'is_store' => ($receiver === 'store'),
                'newRetire' => [
                    'id' => $retireId,
                    'type' => 'retire',
                    'type_Tra' => $receiver === 'store' ? 'Retiro Punto de Venta' : translate('retire'),
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
            'account_type' => bingo_normalize_account_type($user['account_type'] ?? ''),
            'account_type_label' => bingo_account_type_label($user['account_type'] ?? ''),
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

        wallet_credit_withdrawable($receiver['id'], (float) $transfer['amount']);
        wallet_deduct_withdrawable($user['id'], (float) $transfer['amount']);

        $modelNotifications = new NotificationsModel();

        // Obtener balance actualizado para la respuesta
        $userUpdated = wallet_service()->normalizeUser($modelUsers->find($user['id']));
        $wallet = wallet_withdrawable($userUpdated);

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

        $user = wallet_service()->normalizeUser($modelUsers->find(session()->get('id')));

        return $this->response->setJSON([
            'wallet' => wallet_summary_payload($user),
            'withdrawable' => wallet_withdrawable($user),
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
            'setting-account-type' => [
                'label' => translate('account type'),
                'rules' => 'required|in_list[savings,checking]'
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
            'account_type' => bingo_normalize_account_type($this->request->getPost('setting-account-type')),
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
                $depositStatus = (int) $deposit['status'];
                $isStoreFunding = bingo_deposit_is_store_funding($deposit);
                $isStorePlayerRecharge = bingo_deposit_is_store_player_recharge($deposit);

                if ($depositStatus === 2) {
                    return $this->response->setJSON([
                        'success' => false,
                        'error' => 'Este depósito ya fue aprobado y acreditado.',
                    ]);
                }

                if ($depositStatus !== 1) {
                    return $this->response->setJSON([
                        'success' => false,
                        'error' => 'Solo se pueden aprobar depósitos pendientes.',
                    ]);
                }

                if ($isStoreFunding) {
                    if ((int) ($user['group'] ?? -1) !== bingo_group_store()) {
                        return $this->response->setJSON([
                            'success' => false,
                            'error' => translate('store not found'),
                        ]);
                    }

                    wallet_credit_recharge($deposit['user'], (float) $deposit['amount']);
                    $modelDeposits->update($id, ['status' => 2, 'observation' => $observation]);

                    $modelNotifications = new NotificationsModel();
                    $currentUserId = session()->get('id');

                    $notificationData = [
                        'user' => $deposit['user'],
                        'from' => $currentUserId,
                        'type' => 'deposit',
                        'type_id' => $deposit['id'],
                        'title' => '✅ ' . strtoupper(translate('store balance approved')),
                        'message' => translate('your balance request of') . ' ' . systemGet('currency') . ' ' . number_format($deposit['amount'], 2)
                            . ' ' . translate('was approved you can recharge players now'),
                    ];

                    $modelNotifications->insert($notificationData);
                } else {
                    wallet_credit_recharge($deposit['user'], (float) $deposit['amount']);

                    $modelDeposits->update($id, ['status' => 2, 'observation' => $observation]);

                    $totalDeposits = $modelDeposits->where('user', $deposit['user'])->where('status', 2)->countAllResults();

                    $userReferrer = $modelReferrals->where('id_referrer', $deposit['user'])->where('status', 1)->first();

                    $commissionMode = function_exists('bingo_affiliate_commission_mode')
                        ? bingo_affiliate_commission_mode()
                        : 'hybrid';

                    if ($userReferrer && $totalDeposits == 1 && ! $isStorePlayerRecharge
                        && in_array($commissionMode, ['deposit', 'hybrid'], true)) {
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
                }
            } elseif ($action === 'refuse') {
                if ((int) $deposit['status'] === 2) {
                    // Revertir el saldo de recarga usando el servicio correcto (no el campo legacy)
                    // Verificamos que tenga suficiente recarga para descontar
                    $userNormalized = wallet_service()->normalizeUser($user);
                    $deductAmt = min((float) $deposit['amount'], $userNormalized['wallet_recharge']);
                    if ($deductAmt > 0) {
                        wallet_service()->syncLegacyWallet($deposit['user'], [
                            'wallet_bonus'    => $userNormalized['wallet_bonus'],
                            'wallet_recharge' => round($userNormalized['wallet_recharge'] - $deductAmt, 2),
                            'wallet_withdraw' => $userNormalized['wallet_withdraw'],
                        ]);
                    }
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

            $retireStatus = (int) $retire['status'];

            if ($action === 'review') {
                if (! bingo_is_admin()) {
                    return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'No autorizado']);
                }
                if ($retireStatus !== 1) {
                    return $this->response->setJSON(['success' => false, 'error' => 'Solo se pueden poner en revisión solicitudes pendientes.']);
                }

                $modelRetires->update($id, [
                    'status' => 3,
                    'observation' => $observation ?: $retire['observation'],
                ]);

                $modelNotifications = new NotificationsModel();
                $modelNotifications->insert([
                    'user'    => $retire['user'],
                    'from'    => session()->get('id'),
                    'type'    => 'retire',
                    'type_id' => $retire['id'],
                    'title'   => '🔍 SOLICITUD EN REVISIÓN',
                    'message' => 'Tu solicitud de retiro por ' . (systemGet('currency') ?? '$') . ' ' . number_format($retire['amount'], 2) . ' está siendo revisada por el equipo de administración.',
                ]);

                return $this->response->setJSON([
                    'success' => true,
                    'action'  => 'review',
                    'status'  => 3,
                    'message' => 'Solicitud cambiada a En Revisión exitosamente.',
                ]);
            } elseif ($action === 'approve') {
                if (! bingo_is_admin()) {
                    return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'No autorizado']);
                }
                if ($retireStatus === 2) {
                    return $this->response->setJSON(['success' => false, 'error' => 'Esta solicitud ya fue aprobada anteriormente.']);
                }

                $modelRetires->update($id, [
                    'status' => 2,
                    'observation' => $observation ?: $retire['observation'],
                ]);

                $isStore = ($retire['bank'] === 'Punto de Venta' || ($retire['account_type'] ?? '') === 'store_pickup');

                $modelNotifications = new NotificationsModel();
                $modelNotifications->insert([
                    'user'    => $retire['user'],
                    'from'    => session()->get('id'),
                    'type'    => 'retire',
                    'type_id' => $retire['id'],
                    'title'   => '📤 NOTA DE RETIRO APROBADA',
                    'message' => $isStore
                        ? ('Tu nota de retiro por ' . (systemGet('currency') ?? '$') . ' ' . number_format($retire['amount'], 2) . ' ha sido APROBADA. Código de retiro: ' . $retire['account'] . '. Presenta este código en cualquier Punto de Venta para cobrar tu dinero en efectivo.')
                        : ('Tu solicitud de retiro por ' . (systemGet('currency') ?? '$') . ' ' . number_format($retire['amount'], 2) . ' ha sido aprobada y procesada hacia tu cuenta bancaria.'),
                ]);

                return $this->response->setJSON([
                    'success' => true,
                    'action'  => 'approve',
                    'status'  => 2,
                    'message' => 'Solicitud de retiro aprobada exitosamente.',
                ]);
            } elseif ($action === 'refuse') {
                if (! bingo_is_admin()) {
                    return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'No autorizado']);
                }
                if ($retireStatus === 0) {
                    return $this->response->setJSON(['success' => false, 'error' => 'Esta solicitud ya fue rechazada anteriormente.']);
                }

                // Reintegrar saldo de retiro al usuario
                wallet_credit_withdrawable($retire['user'], (float) $retire['amount']);

                $modelRetires->update($id, [
                    'status' => 0,
                    'observation' => $observation ?: $retire['observation'],
                ]);

                $modelNotifications = new NotificationsModel();
                $modelNotifications->insert([
                    'user'    => $retire['user'],
                    'from'    => session()->get('id'),
                    'type'    => 'retire',
                    'type_id' => $retire['id'],
                    'title'   => '❌ SOLICITUD DE RETIRO RECHAZADA',
                    'message' => 'Tu solicitud de retiro por ' . (systemGet('currency') ?? '$') . ' ' . number_format($retire['amount'], 2) . ' ha sido rechazada.' . (!empty($observation) ? (' Motivo: ' . $observation) : '') . ' Tu saldo de retiro ha sido reintegrado a tu billetera para que puedas usarlo.',
                ]);

                return $this->response->setJSON([
                    'success' => true,
                    'action'  => 'refuse',
                    'status'  => 0,
                    'message' => 'Solicitud rechazada y saldo reintegrado al jugador.',
                ]);
            } elseif ($action === 'cancel') {
                // Cancelación ejecutada por el propio usuario jugador
                $currentUserId = (int) session()->get('id');
                if ($currentUserId !== (int) $retire['user'] && ! bingo_is_admin()) {
                    return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'No autorizado']);
                }

                if ($retireStatus !== 1) {
                    return $this->response->setJSON([
                        'success' => false,
                        'error'   => $retireStatus === 3
                            ? 'La solicitud se encuentra en revisión por administración y ya no puede ser cancelada.'
                            : 'Esta solicitud ya no se encuentra pendiente y no puede ser cancelada.',
                    ]);
                }

                // Reintegrar saldo de retiro al usuario
                wallet_credit_withdrawable($retire['user'], (float) $retire['amount']);

                $modelRetires->update($id, [
                    'status' => 0,
                    'observation' => 'Cancelado por el usuario el ' . date('d/m/Y H:i:s'),
                ]);

                $modelNotifications = new NotificationsModel();
                $modelNotifications->insert([
                    'user'    => $retire['user'],
                    'from'    => $currentUserId,
                    'type'    => 'retire',
                    'type_id' => $retire['id'],
                    'title'   => '🚫 SOLICITUD DE RETIRO CANCELADA',
                    'message' => 'Has cancelado tu solicitud de retiro por ' . (systemGet('currency') ?? '$') . ' ' . number_format($retire['amount'], 2) . '. Tu saldo de retiro ha sido reintegrado a tu billetera.',
                ]);

                $updatedUser = wallet_service()->normalizeUser($modelUsers->find($retire['user']));

                return $this->response->setJSON([
                    'success'         => true,
                    'action'          => 'cancel',
                    'status'          => 0,
                    'message'         => 'Solicitud cancelada exitosamente. Tu saldo de retiro ha sido reintegrado.',
                    'wallet_withdraw' => $updatedUser['wallet_withdraw'],
                    'wallet_total'    => wallet_total($updatedUser),
                ]);
            }
        }

        return $this->response->setJSON(['success' => true]);
    }

    public function payawardSubmit() {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => translate('unauthorized')]);
        }

        $requestData = $this->request->getJSON();
        $id = (int) ($requestData->id ?? 0);
        $action = (string) ($requestData->action ?? '');

        if ($id <= 0 || $action === '') {
            return $this->response->setJSON(['success' => false, 'error' => translate('incomplete data')]);
        }

        if ($action === 'pay') {
            $result = bingo_pay_sing_award($id, (int) session()->get('id'));
            if (! ($result['success'] ?? false)) {
                return $this->response->setJSON(['success' => false, 'error' => $result['error'] ?? translate('there was an error in the request to the server.')]);
            }

            return $this->response->setJSON([
                'success' => true,
                'amount'  => $result['amount'] ?? '0.00',
            ]);
        }

        if ($action === 'earring') {
            $modelSings = new SingsModel();
            $modelAwards = new AwardsModel();
            $modelGames = new GamesModel();

            $sing = $modelSings->find($id);
            if (! $sing) {
                return $this->response->setJSON(['success' => false, 'error' => translate('sing not found')]);
            }

            $game = $modelGames->find($sing['game']);
            $award = $modelAwards->where('game', $sing['game'])
                ->where('modality', $sing['modality'])
                ->where('status', 1)
                ->first();

            if (! $game || ! $award) {
                return $this->response->setJSON(['success' => false, 'error' => translate('award not found')]);
            }

            $awardPerSing = bingo_calculate_award_per_sing($game, $award, (int) $sing['game'], (int) $sing['modality']);
            $singStatus = (int) ($sing['status'] ?? 0);

            if ($singStatus === 2) {
                bingo_deduct_award_by_purchase_source(
                    (int) $sing['user'],
                    (int) $sing['game'],
                    $awardPerSing,
                    (int) ($sing['carton'] ?? 0)
                );
            }

            $modelSings->update($id, ['status' => 1]);

            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false, 'error' => translate('invalid action')]);
    }

    public function sendRetireStoreEmail(array $user, string $code, float $amount): bool
    {
        if (empty($user['email'])) {
            return false;
        }

        try {
            $emailConfig = \Config\Services::email();
            $config = new \Config\Email();

            $subject = 'Código de Retiro en Punto de Venta - ' . (systemGet('name') ?: APP_NAME);
            $message = view('emails/retire_store_code', [
                'code'     => $code,
                'user'     => $user,
                'amount'   => $amount,
                'currency' => systemGet('currency') ?? '$',
            ]);

            $emailConfig->clear(true);
            $emailConfig->setFrom($config->fromEmail, $config->fromName);
            $emailConfig->setTo($user['email']);
            $emailConfig->setSubject($subject);
            $emailConfig->setMessage($message);
            $emailConfig->setMailType('html');

            $sent = $emailConfig->send();
            if (! $sent) {
                log_message('error', 'Error sending retire store email: ' . $emailConfig->printDebugger(['headers']));
            }
            return (bool) $sent;
        } catch (\Throwable $e) {
            log_message('error', 'Error sending retire store email: ' . $e->getMessage());
            return false;
        }
    }

}