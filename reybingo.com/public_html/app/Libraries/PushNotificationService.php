<?php

namespace App\Libraries;

use App\Models\PushSubscriptionModel;
use App\Config\Push as PushConfig;

class PushNotificationService
{
    private $webPush;
    private $subscriptionModel;
    private $vapidPublicKey;
    private $vapidPrivateKey;
    private $vapidSubject;

    public function __construct()
    {
        $config = config('Push') ?? new PushConfig();

        $this->vapidPublicKey = env('push.vapid.publicKey') ?: ($config->vapidPublicKey ?? 'BHdM69ue9dsb7wxXHWJrea_2F45sWLUd2hx33imtzLotBrIeqReJWcSHW48hCkL7iU5XGns96G7G6ViIC6MC8nI');
        $this->vapidPrivateKey = env('push.vapid.privateKey') ?: ($config->vapidPrivateKey ?? 'A7fqWe5ja-FawBl6wSwjgwsmmELSSJ0_YFuWsx50IEY');
        $this->vapidSubject = env('push.vapid.subject') ?: ($config->vapidSubject ?? 'mailto:marwinjsilva@gmail.com');

        $this->subscriptionModel = new PushSubscriptionModel();

        // Verificar si la biblioteca minishlink está disponible en vendor/
        if (class_exists('\Minishlink\WebPush\WebPush')) {
            try {
                $auth = [
                    'VAPID' => [
                        'subject' => $this->vapidSubject,
                        'publicKey' => $this->vapidPublicKey,
                        'privateKey' => $this->vapidPrivateKey,
                    ]
                ];

                $this->webPush = new \Minishlink\WebPush\WebPush($auth);
                $this->webPush->setAutomaticPadding(true);
                $this->webPush->setDefaultOptions([
                    'TTL' => 86400,
                    'urgency' => 'high',
                    'batchMode' => true,
                    'timeout' => 20
                ]);
            } catch (\Throwable $e) {
                log_message('error', 'PushNotificationService: Falló inicialización de Minishlink, usando fallback nativo: ' . $e->getMessage());
                $this->webPush = null;
            }
        } else {
            $this->webPush = null;
        }
    }

    public function getVapidPublicKey()
    {
        return $this->vapidPublicKey;
    }

    /**
     * Prepara el payload con formato estandarizado para Service Worker y navegadores móviles
     */
    public function formatPayload($payload)
    {
        if (is_string($payload)) {
            $payload = [
                'title' => 'Nueva partida',
                'message' => $payload
            ];
        }

        $title = $payload['title'] ?? 'Nueva partida';
        $message = $payload['message'] ?? ($payload['body'] ?? 'Tienes una nueva notificación');
        $url = $payload['url'] ?? base_url('/');
        $tag = $payload['tag'] ?? 'rey-bingo-' . time();
        $icon = $payload['icon'] ?? base_url('assets/img/logo.png');
        $badge = $payload['badge'] ?? base_url('assets/img/badge.png');

        return [
            'title' => $title,
            'body' => $message,
            'message' => $message,
            'icon' => $icon,
            'badge' => $badge,
            'url' => $url,
            'tag' => $tag,
            'requireInteraction' => true,
            'vibrate' => [300, 100, 300, 100, 300],
            'data' => [
                'url' => $url,
                'timestamp' => round(microtime(true) * 1000),
                'tag' => $tag
            ],
            'actions' => [
                [
                    'action' => 'view',
                    'title' => '🎮 Entrar a Jugar'
                ],
                [
                    'action' => 'close',
                    'title' => '❌ Cerrar'
                ]
            ]
        ];
    }

    /**
     * Envia notificación push directamente a una suscripción específica
     */
    public function sendToSubscription(array $sub, $payload)
    {
        if ($this->webPush && class_exists('\Minishlink\WebPush\Subscription')) {
            $formatted = $this->formatPayload($payload);
            $payloadJson = json_encode($formatted);
            $this->queueSubscription($sub, $payloadJson);
            return $this->sendQueued();
        }

        $success = $this->sendViaNativeVapid($sub, $payload);
        return [
            'success' => $success,
            'total' => 1,
            'sent' => $success ? 1 : 0,
            'failed' => $success ? 0 : 1
        ];
    }

    /**
     * Envia notificación push a un usuario específico
     */
    public function sendToUser($userId, $payload)
    {
        $subscriptions = $this->subscriptionModel->where('user_id', $userId)->findAll();
        if (empty($subscriptions)) {
            return [
                'success' => false,
                'sent' => 0,
                'message' => 'El usuario no tiene suscripciones push activas'
            ];
        }

        if ($this->webPush && class_exists('\Minishlink\WebPush\Subscription')) {
            $formatted = $this->formatPayload($payload);
            $payloadJson = json_encode($formatted);
            foreach ($subscriptions as $sub) {
                $this->queueSubscription($sub, $payloadJson);
            }
            return $this->sendQueued();
        }

        $sent = 0;
        $failed = 0;
        foreach ($subscriptions as $sub) {
            if ($this->sendViaNativeVapid($sub, $payload)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $sent > 0,
            'total' => $sent + $failed,
            'sent' => $sent,
            'failed' => $failed
        ];
    }

    /**
     * Envia notificación push a todos los usuarios suscritos (útil para nuevas partidas en vivo)
     */
    public function sendToAll($payload, $excludeUsers = [])
    {
        $subscriptions = $this->subscriptionModel->findAll();

        if (!empty($excludeUsers) && is_array($excludeUsers)) {
            $excludeIds = array_map('intval', $excludeUsers);
            $subscriptions = array_filter($subscriptions, function($s) use ($excludeIds) {
                return !in_array((int)($s['user_id'] ?? 0), $excludeIds, true);
            });
        }

        if (empty($subscriptions)) {
            return [
                'success' => false,
                'sent' => 0,
                'message' => 'No hay suscripciones push activas registradas'
            ];
        }

        if ($this->webPush && class_exists('\Minishlink\WebPush\Subscription')) {
            $formatted = $this->formatPayload($payload);
            $payloadJson = json_encode($formatted);
            foreach ($subscriptions as $sub) {
                $this->queueSubscription($sub, $payloadJson);
            }
            return $this->sendQueued();
        }

        $sent = 0;
        $failed = 0;
        foreach ($subscriptions as $sub) {
            if ($this->sendViaNativeVapid($sub, $payload)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $sent > 0,
            'total' => $sent + $failed,
            'sent' => $sent,
            'failed' => $failed
        ];
    }

    /**
     * Notificación específica de Partida en Vivo
     */
    public function sendLiveGameNotification($gameId, $gameData, $excludeUsers = [])
    {
        $description = $gameData['description'] ?? 'Partida en Vivo';
        $price = isset($gameData['price']) ? number_format((float)$gameData['price'], 2) : '';
        $currency = function_exists('systemGet') ? systemGet('currency') : '$';

        $title = 'Nueva partida';
        $body = "¡Partida en vivo: {$description}! ";
        if ($price !== '') {
            $body .= "Cartón: {$currency} {$price}. ";
        }
        $body .= "¡Entra ya a comprar tus cartones y jugar!";

        $url = base_url('playings/availableCartonsGet/' . $gameId);

        $payload = [
            'title' => $title,
            'message' => $body,
            'body' => $body,
            'url' => $url,
            'tag' => 'game-' . $gameId,
            'game_id' => $gameId,
            'is_live' => true
        ];

        return $this->sendToAll($payload, $excludeUsers);
    }

    /**
     * Fallback nativo VAPID sin dependencias de Composer:
     * Envía una notificación tickle autenticada con JWT VAPID (RFC 8292).
     * El Service Worker (sw.js) al recibir el evento push sin cuerpo consulta /notifications/getLatestPush
     * y muestra la notificación completa con texto exacto, logo, sonido y vibración.
     */
    private function sendViaNativeVapid(array $sub, $payload): bool
    {
        $endpoint = $sub['endpoint'] ?? '';
        if (empty($endpoint)) {
            return false;
        }

        $parts = parse_url($endpoint);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }
        $audience = $parts['scheme'] . '://' . $parts['host'];

        $jwt = $this->generateVapidJwt($audience);
        if (!$jwt) {
            log_message('error', 'PushNotificationService: Falló la generación del JWT VAPID');
            return false;
        }

        $headers = [
            'TTL: 86400',
            'Urgency: high',
            'Authorization: vapid t=' . $jwt . ', k=' . $this->vapidPublicKey,
            'Content-Length: 0'
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 201 || $httpCode === 200 || $httpCode === 202) {
            log_message('info', "Push VAPID enviado con éxito (HTTP {$httpCode}) a: {$endpoint}");
            return true;
        }

        // Si la suscripción expiró (404 Not Found o 410 Gone), eliminar de la base de datos
        if ($httpCode === 404 || $httpCode === 410) {
            log_message('info', "Suscripción expirada (HTTP {$httpCode}), eliminando endpoint: {$endpoint}");
            try {
                $this->subscriptionModel->where('endpoint', $endpoint)->delete();
            } catch (\Throwable $e) {
                // ignore
            }
            return false;
        }

        log_message('error', "Push VAPID falló con HTTP {$httpCode} a {$endpoint}. Curl error: {$curlError}");
        return false;
    }

    private function generateVapidJwt(string $audience): ?string
    {
        try {
            $header = ['typ' => 'JWT', 'alg' => 'ES256'];
            $claims = [
                'aud' => $audience,
                'exp' => time() + 86400,
                'sub' => $this->vapidSubject
            ];

            $encodedHeader = $this->base64UrlEncode(json_encode($header));
            $encodedClaims = $this->base64UrlEncode(json_encode($claims));
            $dataToSign = $encodedHeader . '.' . $encodedClaims;

            $privBin = $this->base64UrlDecode($this->vapidPrivateKey);
            $pubBin = $this->base64UrlDecode($this->vapidPublicKey);

            if (strlen($privBin) !== 32 || strlen($pubBin) !== 65) {
                log_message('error', 'PushNotificationService: Longitud de claves VAPID inválida');
                return null;
            }

            // PKCS#8 DER standard header for P-256 (1.2.840.10045.3.1.7)
            $der = hex2bin('30770201010420') . $privBin . hex2bin('a00a06082a8648ce3d030107a144034200') . $pubBin;
            $pem = "-----BEGIN PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PRIVATE KEY-----\n";

            $pkey = openssl_pkey_get_private($pem);
            if (!$pkey) {
                log_message('error', 'PushNotificationService: openssl_pkey_get_private falló: ' . openssl_error_string());
                return null;
            }

            $signature = '';
            $success = openssl_sign($dataToSign, $signature, $pkey, OPENSSL_ALGO_SHA256);
            if (!$success) {
                log_message('error', 'PushNotificationService: openssl_sign falló: ' . openssl_error_string());
                return null;
            }

            $rawSignature = $this->derToRawSignature($signature);
            $encodedSignature = $this->base64UrlEncode($rawSignature);

            return $dataToSign . '.' . $encodedSignature;
        } catch (\Throwable $e) {
            log_message('error', 'PushNotificationService: Error generando JWT: ' . $e->getMessage());
            return null;
        }
    }

    private function derToRawSignature(string $der): string
    {
        $pos = 2;
        if ((ord($der[1]) & 0x80) !== 0) {
            $pos = 2 + (ord($der[1]) & 0x7f);
        }

        $pos++; // skip 0x02
        $rLen = ord($der[$pos++]);
        $r = substr($der, $pos, $rLen);
        $pos += $rLen;

        $pos++; // skip 0x02
        $sLen = ord($der[$pos++]);
        $s = substr($der, $pos, $sLen);

        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");
        $r = str_pad($r, 32, "\x00", STR_PAD_LEFT);
        $s = str_pad($s, 32, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    private function queueSubscription(array $sub, string $payloadJson)
    {
        try {
            if (empty($sub['endpoint']) || empty($sub['p256dh_key']) || empty($sub['auth_key'])) {
                return;
            }

            if (!class_exists('\Minishlink\WebPush\Subscription')) {
                return;
            }

            $subscription = \Minishlink\WebPush\Subscription::create([
                'endpoint' => $sub['endpoint'],
                'keys' => [
                    'p256dh' => $sub['p256dh_key'],
                    'auth' => $sub['auth_key']
                ]
            ]);

            $this->webPush->queueNotification($subscription, $payloadJson);
        } catch (\Throwable $e) {
            log_message('error', 'Error creando suscripción WebPush: ' . $e->getMessage());
        }
    }

    private function sendQueued()
    {
        $results = [];
        $totalSent = 0;
        $totalFailed = 0;

        try {
            if (!$this->webPush) {
                return ['success' => false, 'sent' => 0, 'failed' => 0];
            }

            foreach ($this->webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();
                $isSuccess = $report->isSuccess();
                $reason = $report->getReason();

                if ($isSuccess) {
                    $totalSent++;
                    log_message('info', "Push notification enviada con éxito a: {$endpoint}");
                } else {
                    $totalFailed++;
                    log_message('error', "Push notification falló a {$endpoint}: {$reason}");

                    if ($report->isSubscriptionExpired()) {
                        try {
                            $this->subscriptionModel->where('endpoint', $endpoint)->delete();
                            log_message('info', "Suscripción expirada eliminada: {$endpoint}");
                        } catch (\Throwable $ex) {
                            log_message('error', "Error eliminando suscripción expirada: " . $ex->getMessage());
                        }
                    }
                }

                $results[] = [
                    'success' => $isSuccess,
                    'endpoint' => $endpoint,
                    'reason' => $reason
                ];
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error en WebPush flush: ' . $e->getMessage());
            return [
                'success' => false,
                'total' => $totalSent + $totalFailed,
                'sent' => $totalSent,
                'failed' => $totalFailed,
                'error' => $e->getMessage(),
                'results' => $results
            ];
        }

        return [
            'success' => $totalSent > 0 || ($totalSent === 0 && $totalFailed === 0),
            'total' => $totalSent + $totalFailed,
            'sent' => $totalSent,
            'failed' => $totalFailed,
            'results' => $results
        ];
    }
}
