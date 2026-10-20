<?php

namespace App\Libraries;

use App\Models\PushSubscriptionModel;
use App\Config\Push as PushConfig;

class PushNotificationService
{
    private $webPush = null;
    private $initializationError;
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

                // En v11 el tiempo de espera pertenece al cliente HTTP, no a las opciones del push.
                $client = new \GuzzleHttp\Client([
                    'connect_timeout' => 8,
                    'timeout' => 20,
                    'allow_redirects' => false,
                ]);
                $this->webPush = new \Minishlink\WebPush\WebPush(
                    auth: $auth,
                    defaultOptions: ['TTL' => 86400, 'urgency' => 'high'],
                    client: $client,
                    // Evitar que CI convierta avisos de extensiones opcionales en excepciones.
                    logger: service('logger')
                );
                $this->webPush->setAutomaticPadding(true);
                $this->webPush->setReuseVAPIDHeaders(true);
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
        $sent = false;

        // 1. Intentar con Minishlink WebPush (aes128gcm)
        if ($this->webPush && class_exists('\Minishlink\WebPush\Subscription')) {
            try {
                $formatted = $this->formatPayload($payload);
                $payloadJson = json_encode($formatted);
                $this->queueSubscription($sub, $payloadJson);
                $res = $this->sendQueued();
                if (!empty($res['success']) && (int) ($res['sent'] ?? 0) > 0) {
                    $sent = true;
                }
            } catch (\Throwable $e) {
                log_message('error', 'PushNotificationService: Error Minishlink en sendToSubscription: ' . $e->getMessage());
            }
        }

        // 2. Si Minishlink no envió con éxito, intentar fallback nativo VAPID (tickle push)
        if (!$sent) {
            try {
                $sent = $this->sendViaNativeVapid($sub, $payload);
            } catch (\Throwable $e) {
                log_message('error', 'PushNotificationService: Error fallback nativo en sendToSubscription: ' . $e->getMessage());
            }
        }

        return [
            'success' => $sent,
            'total' => 1,
            'sent' => $sent ? 1 : 0,
            'failed' => $sent ? 0 : 1
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

        $sent = 0;
        $failed = 0;
        foreach ($subscriptions as $sub) {
            $result = $this->sendToSubscription($sub, $payload);
            if (!empty($result['sent'])) {
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

        $formatted = $this->formatPayload($payload);
        $payloadJson = json_encode($formatted);

        $sentEndpoints = [];
        $failedSubscriptions = [];

        // 1. Intentar envío masivo con Minishlink WebPush
        if ($this->webPush && class_exists('\Minishlink\WebPush\Subscription')) {
            try {
                foreach ($subscriptions as $sub) {
                    $this->queueSubscription($sub, $payloadJson);
                }

                $queuedResult = $this->sendQueued();
                if (!empty($queuedResult['results'])) {
                    foreach ($queuedResult['results'] as $r) {
                        if (!empty($r['success']) && !empty($r['endpoint'])) {
                            $sentEndpoints[$r['endpoint']] = true;
                        }
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', 'PushNotificationService sendToAll WebPush error: ' . $e->getMessage());
            }

            foreach ($subscriptions as $sub) {
                if (empty($sentEndpoints[$sub['endpoint']])) {
                    $failedSubscriptions[] = $sub;
                }
            }
        } else {
            $failedSubscriptions = $subscriptions;
        }

        // 2. Para los endpoints que no recibieron vía Minishlink, enviar vía fallback nativo VAPID (tickle)
        foreach ($failedSubscriptions as $sub) {
            try {
                if ($this->sendViaNativeVapid($sub, $payload)) {
                    $sentEndpoints[$sub['endpoint']] = true;
                }
            } catch (\Throwable $e) {
                log_message('error', 'PushNotificationService sendToAll fallback error: ' . $e->getMessage());
            }
        }

        $totalSent = count($sentEndpoints);
        $totalSubscriptions = count($subscriptions);
        $totalFailed = max(0, $totalSubscriptions - $totalSent);

        return [
            'success' => $totalSent > 0,
            'total' => $totalSubscriptions,
            'sent' => $totalSent,
            'failed' => $totalFailed
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

        $authHeader = $this->getVapidAuthorizationHeader($audience);
        if (!$authHeader) {
            log_message('error', 'PushNotificationService: Falló la generación del encabezado VAPID Authorization');
            return false;
        }

        $headers = [
            'TTL: 86400',
            'Urgency: high',
            'Authorization: ' . $authHeader,
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

    /**
     * Obtiene el encabezado VAPID Authorization ya sea con Minishlink o con OpenSSL nativo
     */
    private function getVapidAuthorizationHeader(string $audience): ?string
    {
        if (class_exists('\Minishlink\WebPush\VAPID')) {
            try {
                $res = \Minishlink\WebPush\VAPID::validate([
                    'subject' => $this->vapidSubject,
                    'publicKey' => $this->vapidPublicKey,
                    'privateKey' => $this->vapidPrivateKey,
                ]);

                $headers = \Minishlink\WebPush\VAPID::getVapidHeaders(
                    $audience,
                    $res['subject'],
                    $res['publicKey'],
                    $res['privateKey'],
                    \Minishlink\WebPush\ContentEncoding::aes128gcm
                );

                if (!empty($headers['Authorization'])) {
                    return $headers['Authorization'];
                }
            } catch (\Throwable $e) {
                log_message('warning', 'VAPID::getVapidHeaders falló, usando generador nativo: ' . $e->getMessage());
            }
        }

        $jwt = $this->generateVapidJwt($audience);
        if ($jwt) {
            return 'vapid t=' . $jwt . ', k=' . $this->vapidPublicKey;
        }

        return null;
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

            // SEC1 DER standard para prime256v1 (P-256)
            $der = hex2bin('30770201010420') . $privBin . hex2bin('a00a06082a8648ce3d030107a144034200') . $pubBin;
            $pem = "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";

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
                ],
                'contentEncoding' => 'aes128gcm'
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
