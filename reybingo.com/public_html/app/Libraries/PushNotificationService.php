<?php

namespace App\Libraries;

use App\Models\PushSubscriptionModel;
use App\Config\Push as PushConfig;

if (class_exists('\Minishlink\WebPush\WebPush')) {
    class ParallelWebPush extends \Minishlink\WebPush\WebPush
    {
        public function flushParallel(int $batchSize = 25): \Generator
        {
            if (empty($this->notifications)) {
                return;
            }

            $batches = array_chunk($this->notifications, $batchSize);
            $this->notifications = [];

            try {
                foreach ($batches as $batch) {
                    $requests = [];
                    foreach ($batch as $notification) {
                        // Una clave dañada no debe cancelar el envío a los demás teléfonos.
                        try {
                            $prepared = $this->prepare([$notification]);
                            array_push($requests, ...$prepared);
                        } catch (\Throwable $reason) {
                            $request = $this->requestFactory->createRequest('POST', $notification->getSubscription()->getEndpoint());
                            yield $this->createRejectedReport($request, $reason);
                        }
                    }

                    if (count($requests) <= 1 || !($this->client instanceof \GuzzleHttp\Client)) {
                        foreach ($requests as $request) {
                            try {
                                $response = $this->client->sendRequest($request);
                                yield $this->createReport($request, $response);
                            } catch (\Throwable $reason) {
                                yield $this->createRejectedReport($request, $reason);
                            }
                        }
                        continue;
                    }

                    // Limitar la concurrencia para no agotar las conexiones del servidor.
                    $promises = [];
                    foreach ($requests as $idx => $request) {
                        try {
                            $promises[$idx] = $this->client->sendAsync($request, ['http_errors' => false]);
                        } catch (\Throwable $reason) {
                            yield $this->createRejectedReport($request, $reason);
                        }
                    }

                    $results = \GuzzleHttp\Promise\Utils::settle($promises)->wait();
                    foreach ($results as $idx => $result) {
                        $request = $requests[$idx];
                        if ($result['state'] === 'fulfilled') {
                            yield $this->createReport($request, $result['value']);
                        } else {
                            $reason = $result['reason'];
                            if ($reason instanceof \GuzzleHttp\Exception\RequestException && $reason->hasResponse()) {
                                yield $this->createReport($request, $reason->getResponse());
                            } else {
                                yield $this->createRejectedReport($request, $reason instanceof \Throwable ? $reason : new \RuntimeException('Push transport rejected the request'));
                            }
                        }
                    }
                }

            } finally {
                if ($this->reuseVAPIDHeaders) {
                    $this->vapidHeaders = [];
                }
            }
        }
    }
}

class PushNotificationService
{
    private $webPush = null;
    private $initializationError = null;
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

                $client = new \GuzzleHttp\Client([
                    'connect_timeout' => 5.0,
                    'timeout' => 15.0,
                    'allow_redirects' => false,
                ]);
                $this->webPush = new ParallelWebPush(
                    auth: $auth,
                    defaultOptions: ['TTL' => 86400, 'urgency' => 'high'],
                    client: $client,
                    logger: service('logger')
                );
                $this->webPush->setAutomaticPadding(true);
                $this->webPush->setReuseVAPIDHeaders(true);
            } catch (\Throwable $e) {
                log_message('error', 'PushNotificationService: Falló inicialización de Minishlink: ' . $e->getMessage());
                $this->initializationError = 'push_transport_unavailable';
                $this->webPush = null;
            }
        } else {
            $this->webPush = null;
            $this->initializationError = 'push_dependency_missing';
        }
    }

    public function getVapidPublicKey()
    {
        return $this->vapidPublicKey;
    }

    public function getStatus(): array
    {
        $available = $this->webPush !== null;
        return [
            'available' => $available,
            'error' => $available ? null : ($this->initializationError ?? 'push_transport_unavailable'),
            'message' => $available
                ? 'El servicio push está disponible'
                : 'El servidor no puede enviar notificaciones push. Revisa las dependencias de Composer y la configuración VAPID.'
        ];
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
        $tag = $payload['tag'] ?? 'rey-bingo-' . bin2hex(random_bytes(8));
        $icon = $payload['icon'] ?? base_url('assets/img/logo.png');
        $badge = $payload['badge'] ?? base_url('assets/img/badge.png');
        $image = $payload['image'] ?? base_url('assets/img/push-banner.png');

        return [
            'title' => $title,
            'body' => $message,
            'message' => $message,
            'icon' => $icon,
            'badge' => $badge,
            'image' => $image,
            'url' => $url,
            'tag' => $tag,
            'type' => $payload['type'] ?? null,
            'is_live' => !empty($payload['is_live']),
            'game_id' => $payload['game_id'] ?? null,
            'requireInteraction' => true,
            'vibrate' => [300, 100, 300, 100, 300],
            'data' => [
                'url' => $url,
                'timestamp' => round(microtime(true) * 1000),
                'tag' => $tag,
                'image' => $image,
                'type' => $payload['type'] ?? null,
                'is_live' => !empty($payload['is_live']),
                'game_id' => $payload['game_id'] ?? null
            ],
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'Entrar a Jugar'
                ],
                [
                    'action' => 'close',
                    'title' => 'Cerrar'
                ]
            ]
        ];
    }

    /**
     * Envia notificación push directamente a una suscripción específica
     */
    public function sendToSubscription(array $sub, $payload)
    {
        return $this->sendToSubscriptions([$sub], $payload);
    }

    /**
     * Envia el mismo contenido a todos los dispositivos del usuario.
     */
    public function sendToUser($userId, $payload)
    {
        $subscriptions = $this->subscriptionModel->where('user_id', $userId)->orderBy('id', 'DESC')->findAll();
        return $this->sendToSubscriptions($subscriptions, $payload);
    }

    /**
     * Envia notificación push a todos los usuarios suscritos.
     */
    public function sendToAll($payload, $excludeUsers = [])
    {
        $subscriptions = $this->subscriptionModel->orderBy('id', 'DESC')->findAll();
        if (!empty($excludeUsers) && is_array($excludeUsers)) {
            $excludeIds = array_map('intval', $excludeUsers);
            $subscriptions = array_filter($subscriptions, static function ($subscription) use ($excludeIds) {
                return !in_array((int) ($subscription['user_id'] ?? 0), $excludeIds, true);
            });
        }
        return $this->sendToSubscriptions($subscriptions, $payload);
    }

    private function sendToSubscriptions(array $subscriptions, $payload): array
    {
        $uniqueSubscriptions = [];
        foreach ($subscriptions as $index => $subscription) {
            $endpoint = trim((string) ($subscription['endpoint'] ?? ''));
            $key = $endpoint !== '' ? $endpoint : 'invalid-' . $index;
            if (!isset($uniqueSubscriptions[$key])) {
                $subscription['endpoint'] = $endpoint;
                $uniqueSubscriptions[$key] = $subscription;
            }
        }

        $total = count($uniqueSubscriptions);
        if ($total === 0) {
            return [
                'success' => false, 'total' => 0, 'sent' => 0, 'failed' => 0,
                'error' => 'no_subscriptions',
                'message' => 'No hay suscripciones push activas registradas',
                'results' => []
            ];
        }

        $status = $this->getStatus();
        if (!$status['available']) {
            return [
                'success' => false, 'total' => $total, 'sent' => 0, 'failed' => $total,
                'error' => $status['error'], 'message' => $status['message'], 'results' => []
            ];
        }

        try {
            $payloadJson = json_encode($this->formatPayload($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            log_message('error', 'PushNotificationService: No se pudo preparar el contenido push: ' . $e->getMessage());
            return [
                'success' => false, 'total' => $total, 'sent' => 0, 'failed' => $total,
                'error' => 'invalid_push_payload',
                'message' => 'No se pudo preparar el contenido de la notificación', 'results' => []
            ];
        }

        $results = [];
        $queuedEndpoints = [];
        foreach ($uniqueSubscriptions as $subscription) {
            $endpoint = (string) ($subscription['endpoint'] ?? '');
            try {
                $this->queueSubscription($subscription, $payloadJson);
                $queuedEndpoints[$endpoint] = true;
            } catch (\Throwable $e) {
                log_message('error', 'PushNotificationService: No se pudo encolar una suscripción: ' . $e->getMessage());
                $results[] = [
                    'success' => false, 'endpoint' => $endpoint,
                    'reason' => 'invalid_subscription_or_payload', 'status_code' => 0, 'expired' => false
                ];
            }
        }

        return $this->sendQueued($queuedEndpoints, $results, $total);
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
            'type' => 'live_game',
            'game_id' => $gameId,
            'is_live' => true
        ];

        return $this->sendToAll($payload, $excludeUsers);
    }

    private function queueSubscription(array $subscription, string $payloadJson): void
    {
        $endpoint = (string) ($subscription['endpoint'] ?? '');
        if (!filter_var($endpoint, FILTER_VALIDATE_URL) || parse_url($endpoint, PHP_URL_SCHEME) !== 'https'
            || empty($subscription['p256dh_key']) || empty($subscription['auth_key'])) {
            throw new \InvalidArgumentException('La suscripción push está incompleta o es inválida');
        }

        $webSubscription = \Minishlink\WebPush\Subscription::create([
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => $subscription['p256dh_key'],
                'auth' => $subscription['auth_key']
            ],
            'contentEncoding' => 'aes128gcm'
        ]);

        // No compartir un Topic entre partidas: reemplazaría avisos pendientes en teléfonos bloqueados.
        $this->webPush->queueNotification($webSubscription, $payloadJson, [
            'TTL' => 86400,
            'urgency' => 'high'
        ]);
    }

    private function sendQueued(array $pendingEndpoints, array $results, int $total): array
    {
        try {
            foreach ($this->webPush->flushParallel() as $report) {
                $endpoint = $report->getEndpoint();
                $response = $report->getResponse();
                $statusCode = $response ? $response->getStatusCode() : 0;
                $isSuccess = $report->isSuccess() && $statusCode >= 200 && $statusCode < 300;
                // 400/401/403 pueden indicar un problema de VAPID o del mensaje, no una suscripción caducada.
                $isExpired = $report->isSubscriptionExpired();
                unset($pendingEndpoints[$endpoint]);

                $results[] = [
                    'success' => $isSuccess,
                    'endpoint' => $endpoint,
                    'reason' => $report->getReason(),
                    'status_code' => $statusCode,
                    'expired' => $isExpired
                ];

                if (!$isSuccess) {
                    log_message('error', 'PushNotificationService: Envío rechazado (HTTP ' . $statusCode . ', suscripción ' . substr(hash('sha256', $endpoint), 0, 12) . '): ' . $report->getReason());
                    if ($isExpired) {
                        try {
                            $this->subscriptionModel->where('endpoint', $endpoint)->delete();
                        } catch (\Throwable $e) {
                            log_message('error', 'PushNotificationService: No se pudo eliminar una suscripción caducada: ' . $e->getMessage());
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'PushNotificationService: El envío push se interrumpió: ' . $e->getMessage());
        }

        foreach ($pendingEndpoints as $endpoint => $_) {
            $results[] = [
                'success' => false, 'endpoint' => $endpoint,
                'reason' => 'push_delivery_interrupted', 'status_code' => 0, 'expired' => false
            ];
        }

        $sent = count(array_filter($results, static fn(array $result): bool => $result['success']));
        $failed = $total - $sent;
        return [
            'success' => $sent > 0 && $failed === 0,
            'partial' => $sent > 0 && $failed > 0,
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'error' => $failed > 0 ? 'push_delivery_failed' : null,
            'message' => $failed > 0
                ? 'El servicio push no aceptó todos los envíos. Revisa el registro del servidor.'
                : 'El servicio push aceptó los envíos.',
            'results' => $results
        ];
    }
}
