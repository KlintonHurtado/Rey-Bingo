<?php

namespace App\Libraries;

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
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

        $auth = [
            'VAPID' => [
                'subject' => $this->vapidSubject,
                'publicKey' => $this->vapidPublicKey,
                'privateKey' => $this->vapidPrivateKey,
            ]
        ];

        $this->webPush = new WebPush($auth);
        $this->webPush->setAutomaticPadding(true);
        $this->webPush->setDefaultOptions([
            'TTL' => 86400,
            'urgency' => 'high',
            'batchMode' => true,
            'timeout' => 20
        ]);

        $this->subscriptionModel = new PushSubscriptionModel();
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
                'title' => 'Rey Bingo',
                'message' => $payload
            ];
        }

        $title = $payload['title'] ?? 'Rey Bingo';
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

        $formatted = $this->formatPayload($payload);
        $payloadJson = json_encode($formatted);

        foreach ($subscriptions as $sub) {
            $this->queueSubscription($sub, $payloadJson);
        }

        return $this->sendQueued();
    }

    /**
     * Envia notificación push a todos los usuarios suscritos (útil para nuevas partidas en vivo)
     */
    public function sendToAll($payload, $excludeUsers = [])
    {
        $builder = $this->subscriptionModel;
        if (!empty($excludeUsers)) {
            $builder = $builder->whereNotIn('user_id', $excludeUsers);
        }

        $subscriptions = $builder->findAll();
        if (empty($subscriptions)) {
            return [
                'success' => false,
                'sent' => 0,
                'message' => 'No hay suscripciones push activas registradas'
            ];
        }

        $formatted = $this->formatPayload($payload);
        $payloadJson = json_encode($formatted);

        foreach ($subscriptions as $sub) {
            $this->queueSubscription($sub, $payloadJson);
        }

        return $this->sendQueued();
    }

    /**
     * Notificación específica de Partida en Vivo
     */
    public function sendLiveGameNotification($gameId, $gameData, $excludeUsers = [])
    {
        $description = $gameData['description'] ?? 'Partida en Vivo';
        $price = isset($gameData['price']) ? number_format((float)$gameData['price'], 2) : '';
        $currency = function_exists('systemGet') ? systemGet('currency') : '$';

        $title = '🔴 ¡NUEVA PARTIDA EN VIVO!';
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
            'tag' => 'live-game-' . $gameId,
            'game_id' => $gameId,
            'is_live' => true
        ];

        return $this->sendToAll($payload, $excludeUsers);
    }

    private function queueSubscription(array $sub, string $payloadJson)
    {
        try {
            if (empty($sub['endpoint']) || empty($sub['p256dh_key']) || empty($sub['auth_key'])) {
                return;
            }

            $subscription = Subscription::create([
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

                    // Si la suscripción expiró o ya no es válida (código 410 o 404), eliminarla
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
