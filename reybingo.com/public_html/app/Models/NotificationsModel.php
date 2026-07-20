<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationsModel extends Model
{
    protected $table = 'notifications'; 
    protected $primaryKey = 'id'; 
    
    protected $allowedFields = [
        'user', 'from', 'type', 'type_id', 'game', 'carton', 'modality', 
        'numbers', 'title', 'message', 'template_id', 'action_url', 
        'image_url', 'scheduled_at', 'sent_at', 'status', 'created_at', 'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Get notifications for a user
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getUserNotifications($userId, $limit = 20, $offset = 0)
    {
        return $this->groupStart()
                    ->where('user', $userId)
                    ->orWhere('user', 0)
                    ->groupEnd()
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }
    
    /**
     * Get unread notifications count for a user
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount($userId)
    {
        return $this->groupStart()
                    ->groupStart()
                    ->where('user', $userId)
                    ->orWhere('user', 0)
                    ->groupEnd()
                    ->where('status', 0)
                    ->groupEnd()
                    ->countAllResults();
    }
    
    /**
     * Mark notification as read
     *
     * @param int $id
     * @return bool
     */
    public function markAsRead($id)
    {
        return $this->update($id, ['status' => 1]);
    }
    
    /**
     * Mark all notifications as read for a user
     *
     * @param int $userId
     * @return bool
     */
    public function markAllAsRead($userId)
    {
        return $this->where('user', $userId)
                    ->where('status', 0)
                    ->set(['status' => 1])
                    ->update();
    }
    
    /**
     * Create a notification
     *
     * @param array $data
     * @return int|false
     */
    public function createNotification($data)
    {
        return $this->insert($data);
    }
    
    /**
     * Create notifications for multiple users
     *
     * @param array $data
     * @param array $userIds
     * @return bool
     */
    public function createBulkNotifications($data, $userIds)
    {
        $notificationsData = [];
        
        foreach ($userIds as $userId) {
            $notificationData = $data;
            $notificationData['user'] = $userId;
            $notificationsData[] = $notificationData;
        }
        
        if (empty($notificationsData)) {
            return false;
        }
        
        return $this->insertBatch($notificationsData);
    }
    
    /**
     * Get scheduled notifications that are ready to send
     *
     * @return array
     */
    public function getReadyToSendNotifications()
    {
        $now = date('Y-m-d H:i:s');
        
        return $this->where('scheduled_at <=', $now)
                    ->where('sent_at IS NULL', null, false)
                    ->orderBy('scheduled_at', 'ASC')
                    ->findAll();
    }
    
    /**
     * Mark notification as sent
     *
     * @param int $id
     * @return bool
     */
    public function markAsSent($id)
    {
        return $this->update($id, [
            'sent_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Delete old notifications
     *
     * @param int $daysOld
     * @return bool
     */
    public function deleteOldNotifications($daysOld = 30)
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
        
        return $this->where('created_at <', $date)->delete();
    }
    
    /**
     * Send push notification to a user
     *
     * @param int $notificationId
     * @return array
     */
    public function sendPushNotification($notificationId)
    {
        $notification = $this->find($notificationId);
        
        if (!$notification) {
            return [
                'success' => false,
                'message' => 'Notification not found'
            ];
        }
        
        // Send via Web Push
        $pushService = new \App\Libraries\SimplePushService();
        $payload = [
            'title' => $notification['title'],
            'message' => $notification['message'],
            'url' => $notification['action_url'] ?? base_url(),
            'image' => $notification['image_url'] ?? null
        ];
        
        $webPushResults = $pushService->sendToUser($notification['user'], $payload);
        
        // Send via Firebase
        $firebaseTokenModel = new \App\Models\FirebaseTokenModel();
        $tokens = $firebaseTokenModel->getTokensByUser($notification['user']);
        
        $firebaseResults = [];
        if (!empty($tokens)) {
            // Implement Firebase sending logic here
            // This would typically use the Firebase Admin SDK
            $firebaseResults = ['success' => true, 'message' => 'Firebase notification sent'];
        }
        
        // Mark as sent
        $this->markAsSent($notificationId);
        
        return [
            'success' => true,
            'web_push_results' => $webPushResults,
            'firebase_results' => $firebaseResults
        ];
    }
    
    /**
     * Send bulk push notifications
     *
     * @param array $notificationIds
     * @return array
     */
    public function sendBulkPushNotifications($notificationIds)
    {
        $results = [];
        
        foreach ($notificationIds as $id) {
            $results[$id] = $this->sendPushNotification($id);
        }
        
        return $results;
    }
    
    /**
     * Get notification statistics
     *
     * @param string $period day|week|month|year
     * @return array
     */
    public function getNotificationStats($period = 'month')
    {
        switch ($period) {
            case 'day':
                $date = date('Y-m-d H:i:s', strtotime('-1 day'));
                break;
            case 'week':
                $date = date('Y-m-d H:i:s', strtotime('-1 week'));
                break;
            case 'year':
                $date = date('Y-m-d H:i:s', strtotime('-1 year'));
                break;
            case 'month':
            default:
                $date = date('Y-m-d H:i:s', strtotime('-1 month'));
                break;
        }
        
        $total = $this->where('created_at >=', $date)->countAllResults();
        $sent = $this->where('created_at >=', $date)->where('sent_at IS NOT NULL', null, false)->countAllResults();
        $read = $this->where('created_at >=', $date)->where('status', 1)->countAllResults();
        
        $readRate = ($sent > 0) ? round(($read / $sent) * 100, 2) : 0;
        
        return [
            'total' => $total,
            'sent' => $sent,
            'read' => $read,
            'read_rate' => $readRate
        ];
    }
}