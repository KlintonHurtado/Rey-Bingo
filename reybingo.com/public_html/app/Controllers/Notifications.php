<?php

namespace App\Controllers;

use App\Models\PushSubscriptionModel;
use App\Models\FirebaseTokenModel;
use App\Models\NotificationsModel;
use App\Libraries\PushNotificationService;
use CodeIgniter\Controller;

class Notifications extends Controller {

    protected $pushSubscriptionModel;
    protected $firebaseTokenModel;
    protected $notificationsModel;
    protected $pushService;
    
    public function __construct()
    {
        $this->pushSubscriptionModel = new PushSubscriptionModel();
        $this->firebaseTokenModel = new FirebaseTokenModel();
        $this->notificationsModel = new NotificationsModel();
        $this->pushService = new PushNotificationService();

        helper(['form', 'url', 'cookie', 'text']);
        session();
    }

    public function subscribe()
    {
        $request = $this->request->getJSON();
        $userId = (int) (session()->get('id') ?? 0);

        if (!$request || empty($request->endpoint) || empty($request->keys) || empty($request->keys->p256dh) || empty($request->keys->auth)) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Datos de suscripción incompletos'
            ]);
        }

        $subscriptionModel = new PushSubscriptionModel();
        
        $data = [
            'user_id' => $userId,
            'endpoint' => $request->endpoint,
            'p256dh_key' => $request->keys->p256dh,
            'auth_key' => $request->keys->auth,
            'user_agent' => $this->request->getUserAgent() ? (string)$this->request->getUserAgent() : null
        ];

        try {
            // Verificar si ya existe el endpoint
            $existing = $subscriptionModel->where('endpoint', $request->endpoint)->first();

            if ($existing) {
                // Actualizar user_id si ahora está autenticado
                if ($userId > 0) {
                    $data['user_id'] = $userId;
                }
                $saved = $subscriptionModel->update($existing['id'], $data);
            } else {
                $saved = $subscriptionModel->insert($data);
            }

            if ($saved === false) {
                log_message('error', 'No se pudo guardar la suscripción Web Push.');
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'subscribed' => false,
                    'message' => 'No se pudo registrar este dispositivo. Vuelve a intentarlo.'
                ]);
            }

            // Enviar notificación de confirmación si es nuevo dispositivo o se solicitó explícitamente
            $confirmationRequested = !$existing || ($request->confirm ?? false) === true;
            $confirmationAccepted = null;
            if ($confirmationRequested) {
                $confirmationPayload = [
                    'title' => '🎉 ¡Notificaciones Activadas!',
                    'message' => 'Recibirás avisos en tu teléfono cuando empiece una partida en vivo de Rey Bingo',
                    'url' => base_url('/')
                ];

                try {
                    $result = $this->pushService->sendToSubscription($data, $confirmationPayload);
                    $confirmationAccepted = !empty($result['success']) && (int) ($result['sent'] ?? 0) > 0;
                } catch (\Throwable $e) {
                    log_message('error', 'No se pudo enviar la confirmación Web Push: ' . $e->getMessage());
                    $confirmationAccepted = false;
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'subscribed' => true,
                'confirmation_requested' => $confirmationRequested,
                'confirmation_accepted' => $confirmationAccepted,
                'message' => 'Suscripción guardada correctamente',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error al guardar la suscripción Web Push: ' . get_class($e));
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'subscribed' => false,
                'message' => 'No se pudo registrar este dispositivo. Vuelve a intentarlo.'
            ]);
        }
    }
    
    /**
     * Register Firebase token
     */
    public function registerToken()
    {
        $request = $this->request->getJSON();
        $userId = session()->get('id');

        if (!$userId) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Usuario no autenticado'
            ]);
        }

        if (!isset($request->token) || empty($request->token)) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Token no proporcionado'
            ]);
        }

        $token = $request->token;
        $device = $request->device ?? null;

        try {
            // Verificar si ya existe
            $existing = $this->firebaseTokenModel->where('user_id', $userId)
                                               ->where('token', $token)
                                               ->first();

            if ($existing) {
                $this->firebaseTokenModel->update($existing['id'], [
                    'device' => $device
                ]);
            } else {
                $this->firebaseTokenModel->insert([
                    'user_id' => $userId,
                    'token' => $token,
                    'device' => $device
                ]);
            }

            return $this->response->setJSON([
                'success' => true, 
                'message' => 'Token registrado correctamente'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Error al registrar token: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Unsubscribe from push notifications
     */
    public function unsubscribe()
    {
        $request = $this->request->getJSON();
        $userId = session()->get('id');

        if (!$userId) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Usuario no autenticado'
            ]);
        }

        if (isset($request->endpoint)) {
            // Web Push unsubscribe
            $endpoint = $request->endpoint;
            $this->pushSubscriptionModel->removeSubscription($endpoint);
        } else if (isset($request->token)) {
            // Firebase unsubscribe
            $token = $request->token;
            $this->firebaseTokenModel->removeToken($token);
        } else {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Endpoint o token no proporcionado'
            ]);
        }

        return $this->response->setJSON([
            'success' => true, 
            'message' => 'Suscripción eliminada correctamente'
        ]);
    }
    
    /**
     * Get VAPID public key
     */
    public function getPublicKey()
    {
        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON([
            'publicKey' => $this->pushService->getVapidPublicKey()
        ]);
    }

    /**
     * Devuelve los datos de la última partida en vivo creada para el Service Worker
     */
    public function getLatestPush()
    {
        $gamesModel = new \App\Models\GamesModel();
        // Buscar la partida en vivo más reciente activa por ID descendente
        $game = $gamesModel->whereIn('type', [3, 4])
                           ->whereIn('status', [1, 2])
                           ->orderBy('id', 'DESC')
                           ->first();

        if (!$game) {
            $game = $gamesModel->whereIn('status', [1, 2])
                               ->orderBy('id', 'DESC')
                               ->first();
        }

        if ($game) {
            $currency = function_exists('systemGet') ? systemGet('currency') : '$';
            $formattedPrice = number_format((float)($game['price'] ?? 0), 2);
            $modelAwards = new \App\Models\AwardsModel();
            $total = $modelAwards->where('game', $game['id'])->selectSum('amount')->get()->getRow()->amount ?? 0;
            $awardText = ($game['award'] ?? 0) == 2 ? $currency . ' ' . number_format($total, 2) : translate('accumulated');
            helper(['system', 'text']);
            $gameMessage = $game['description'] . ' 🗓️ ' . translate_day($game['date'] . ' ' . $game['time']) . ', ' . translate_date($game['date']) . ' | 🎫 Precio del cartón: ' . $currency . ' ' . $formattedPrice . ' | 🏆 Premio total: ' . $awardText;

            return $this->response->setJSON([
                'title' => 'Nueva partida',
                'message' => $gameMessage,
                'body' => $gameMessage,
                'url' => base_url('playings/availableCartonsGet/' . $game['id']),
                'tag' => 'game-' . $game['id'],
                'icon' => base_url('assets/img/logo.png'),
                'badge' => base_url('assets/img/badge.png'),
                'requireInteraction' => true
            ]);
        }

        return $this->response->setJSON([
            'title' => 'Rey Bingo',
            'message' => '¡Hay nuevas partidas disponibles en Rey Bingo!',
            'url' => base_url('/'),
            'icon' => base_url('assets/img/logo.png'),
            'badge' => base_url('assets/img/badge.png')
        ]);
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications()
    {
        $userId = session()->get('id');

        if (!$userId) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Usuario no autenticado'
            ]);
        }

        $page = $this->request->getGet('page') ?? 1;
        $limit = $this->request->getGet('limit') ?? 20;
        
        $offset = ($page - 1) * $limit;
        
        $notifications = $this->notificationsModel->getUserNotifications($userId, $limit, $offset);
        $unreadCount = $this->notificationsModel->getUnreadCount($userId);
        
        return $this->response->setJSON([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($id = null)
    {
        $userId = session()->get('id');

        if (!$userId) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Usuario no autenticado'
            ]);
        }

        $json = $this->request->getJSON();
        $ids = [];
        if ($json && !empty($json->ids) && is_array($json->ids)) {
            $ids = array_map('intval', $json->ids);
        } elseif ($json && !empty($json->id)) {
            $ids = [(int) $json->id];
        } elseif ($id !== null) {
            $ids = [(int) $id];
        }

        $ids = array_values(array_filter($ids, fn($v) => $v > 0));

        if (empty($ids)) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'ID(s) de notificación no proporcionado(s)'
            ]);
        }

        $this->notificationsModel->whereIn('id', $ids)
            ->groupStart()
                ->where('user', $userId)
                ->orWhere('user', 0)
            ->groupEnd()
            ->set(['status' => 1])
            ->update();
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Notificación(es) marcada(s) como leída(s)'
        ]);
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $userId = session()->get('id');

        if (!$userId) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Usuario no autenticado'
            ]);
        }

        $this->notificationsModel->groupStart()
            ->where('user', $userId)
            ->orWhere('user', 0)
            ->groupEnd()
            ->where('status', 0)
            ->set(['status' => 1])
            ->update();
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Todas las notificaciones marcadas como leídas'
        ]);
    }
    
    /**
     * Get unread notifications count
     */
    public function getUnreadCount()
    {
        $userId = session()->get('id');

        if (!$userId) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Usuario no autenticado'
            ]);
        }

        $unreadCount = $this->notificationsModel->getUnreadCount($userId);
        
        return $this->response->setJSON([
            'success' => true,
            'unread_count' => $unreadCount
        ]);
    }
    
    /**
     * Send test notification
     */
    public function sendTest()
    {
        $userId = session()->get('id');

        if (!$userId) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Usuario no autenticado'
            ]);
        }

        $payload = [
            'title' => '🧪 Notificación de Prueba',
            'message' => 'Esta es una notificación de prueba enviada a las ' . date('H:i:s'),
            'url' => base_url()
        ];
        
        $result = $this->pushService->sendToUser($userId, $payload);
        
        // Create notification record
        $this->notificationsModel->insert([
            'user' => $userId,
            'from' => $userId,
            'type' => 'test',
            'type_id' => 0,
            'game' => 0,
            'carton' => 0,
            'modality' => 0,
            'numbers' => '',
            'title' => $payload['title'],
            'message' => $payload['message'],
            'action_url' => $payload['url'],
            'status' => 0,
            'sent_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Notificación de prueba enviada',
            'result' => $result
        ]);
    }
    
    /**
     * Get user subscription status
     */
    public function getSubscriptionStatus()
    {
        $userId = session()->get('id');

        if (!$userId) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Usuario no autenticado'
            ]);
        }

        $webPushCount = $this->pushSubscriptionModel->where('user_id', $userId)->countAllResults();
        $firebaseCount = $this->firebaseTokenModel->where('user_id', $userId)->countAllResults();
        
        return $this->response->setJSON([
            'success' => true,
            'web_push_subscribed' => $webPushCount > 0,
            'firebase_subscribed' => $firebaseCount > 0
        ]);
    }
}
