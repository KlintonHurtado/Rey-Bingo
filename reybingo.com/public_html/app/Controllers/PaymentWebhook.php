<?php

namespace App\Controllers;

use App\Libraries\WalletService;
use App\Models\DepositsModel;
use App\Models\NotificationsModel;
use App\Models\UsersModel;
use CodeIgniter\Controller;

class PaymentWebhook extends Controller
{
    /**
     * Webhook genérico: POST /payments/webhook/{gateway}
     * Body JSON esperado (mínimo): reference, amount, status, user_id, signature (opcional)
     */
    public function gateway(string $gateway = 'generic')
    {
        $payload = $this->request->getJSON(true);
        if (empty($payload)) {
            $payload = $this->request->getPost();
        }

        if (empty($payload)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Payload vacío',
            ]);
        }

        $secret = env('payment.webhookSecret', systemGet('paymentWebhookSecret') ?: '');
        if ($secret !== '') {
            $signature = $this->request->getHeaderLine('X-Webhook-Signature')
                ?: ($payload['signature'] ?? '');

            $expected = hash_hmac('sha256', json_encode($payload), $secret);
            if (! hash_equals($expected, (string) $signature)) {
                return $this->response->setStatusCode(401)->setJSON([
                    'success' => false,
                    'message' => 'Firma inválida',
                ]);
            }
        }

        $status = strtolower((string) ($payload['status'] ?? ''));
        if (! in_array($status, ['paid', 'approved', 'completed', 'success'], true)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Evento ignorado (estado no acreditable)',
            ]);
        }

        $userId = (int) ($payload['user_id'] ?? 0);
        $amount = (float) ($payload['amount'] ?? 0);
        $reference = (string) ($payload['reference'] ?? uniqid('wh_', true));

        if ($userId <= 0 || $amount <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'user_id y amount son obligatorios',
            ]);
        }

        $modelUsers = new UsersModel();
        $user = $modelUsers->find($userId);
        if (! $user) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ]);
        }

        $modelDeposits = new DepositsModel();
        $existing = $modelDeposits->where('reference', $reference)->first();
        if ($existing) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Depósito ya procesado',
                'deposit_id' => $existing['id'],
            ]);
        }

        $depositId = $modelDeposits->insert([
            'user'      => $userId,
            'amount'    => $amount,
            'reference' => $reference,
            'date'      => date('Y-m-d'),
            'status'    => 2,
            'observation' => 'Webhook ' . $gateway,
        ]);

        $wallet = new WalletService();
        $wallet->creditRecharge($userId, $amount);

        $modelNotifications = new NotificationsModel();
        $modelNotifications->insert([
            'user'    => $userId,
            'from'    => 0,
            'type'    => 'deposit',
            'type_id' => $depositId,
            'title'   => '✅ DEPÓSITO ACREDITADO',
            'message' => 'Su depósito por ' . systemGet('currency') . ' ' . number_format($amount, 2) . ' fue acreditado vía ' . $gateway . '.',
            'status'  => 0,
            'sent_at' => date('Y-m-d H:i:s'),
        ]);

        log_message('info', "Payment webhook [{$gateway}] acreditado: user={$userId} amount={$amount} ref={$reference}");

        return $this->response->setJSON([
            'success'    => true,
            'deposit_id' => $depositId,
            'message'    => 'Depósito acreditado en wallet_recharge',
        ]);
    }
}
