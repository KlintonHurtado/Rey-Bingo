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
        $rawBody = $this->request->getBody();
        $payload = $this->request->getJSON(true);
        if (empty($payload)) {
            $payload = $this->request->getPost();
        }

        if ($gateway === 'stripe') {
            if (!empty($rawBody)) {
                $stripeEvent = json_decode($rawBody, true);
                if (is_array($stripeEvent) && isset($stripeEvent['type'])) {
                    $endpointSecret = env('stripe.webhookSecret', systemGet('stripeWebhookSecret') ?: '');
                    if ($endpointSecret !== '') {
                        $signatureHeader = $this->request->getHeaderLine('Stripe-Signature');
                        if (! $this->validateStripeSignature($rawBody, $signatureHeader, $endpointSecret)) {
                            return $this->response->setStatusCode(401)->setJSON([
                                'success' => false,
                                'message' => 'Firma Stripe inválida',
                            ]);
                        }
                    }

                    $eventType = (string) ($stripeEvent['type'] ?? '');
                    $eventData = $stripeEvent['data']['object'] ?? [];
                    if (in_array($eventType, ['checkout.session.completed', 'payment_intent.succeeded'], true)) {
                        $metadata = is_array($eventData['metadata'] ?? null) ? $eventData['metadata'] : [];
                        $payload = [
                            'status' => 'completed',
                            'user_id' => (int) ($metadata['user_id'] ?? $eventData['client_reference_id'] ?? 0),
                            'amount' => (float) ($metadata['amount'] ?? ((float) ($eventData['amount_total'] ?? $eventData['amount_received'] ?? 0) / 100)),
                            'reference' => (string) ($metadata['reference'] ?? $eventData['id'] ?? uniqid('st_', true)),
                        ];
                    }
                }
            }
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
            'title'   => '✅ CRÉDITO APROBADO',
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

    private function validateStripeSignature(string $payload, string $signatureHeader, string $secret): bool
    {
        if ($signatureHeader === '' || $secret === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) === 2) {
                $parts[$kv[0]] = $kv[1];
            }
        }

        $timestamp = $parts['t'] ?? null;
        $v1 = $parts['v1'] ?? null;
        if (! $timestamp || ! $v1) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);
        return hash_equals($expected, $v1);
    }
}
