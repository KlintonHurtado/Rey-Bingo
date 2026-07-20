<?php

namespace App\Controllers;

use App\Models\PushSubscriptionModel;
use App\Models\FirebaseTokenModel;
use App\Models\NotificationsModel;
use App\Libraries\SimplePushService;
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
        $this->pushService = new SimplePushService();

        helper(['form', 'url', 'cookie', 'text']);
        session();
    }

    public function subscribe()
    {
        $request = $this->request->getJSON();
        $userId = session()->get('id');

        if (!$userId) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Usuario no autenticado'
            ]);
        }

        $subscriptionModel = new PushSubscriptionModel();
        
        $data = [
            'user_id' => $userId,
            'endpoint' => $request->endpoint,
            'p256dh_key' => $request->keys->p256dh,
            'auth_key' => $request->keys->auth,
            'user_agent' => $this->request->getUserAgent()
        ];

        try {
            // Verificar si ya existe
            $existing = $subscriptionModel->where('user_id', $userId)
                                        ->where('endpoint', $request->endpoint)
                                        ->first();

            if ($existing) {
                $subscriptionModel->update($existing['id'], $data);
            } else {
                $subscriptionModel->insert($data);
            }

            // Enviar notificación de prueba
            $pushService = new SimplePushService();
            $testPayload = [
                'title' => '🎉 ¡Notificaciones Activadas!',
                'message' => 'Ahora recibirás alertas de nuevas partidas de bingo',
                'url' => base_url()
            ];
            
            $result = $pushService->sendToUser($userId, $testPayload);

            return $this->response->setJSON([
                'success' => true, 
                'message' => 'Suscripción guardada correctamente',
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Error al guardar suscripción: ' . $e->getMessage()
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
        $pushService = new SimplePushService();
        
        return $this->response->setJSON([
            'publicKey' => $pushService->getVapidPublicKey()
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

        if ($id === null) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'ID de notificación no proporcionado'
            ]);
        }

        $notification = $this->notificationsModel->find($id);
        
        if (!$notification || ($notification['user'] != $userId && $notification['user'] != 0)) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Notificación no encontrada o no pertenece al usuario'
            ]);
        }

        $this->notificationsModel->markAsRead($id);
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Notificación marcada como leída'
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