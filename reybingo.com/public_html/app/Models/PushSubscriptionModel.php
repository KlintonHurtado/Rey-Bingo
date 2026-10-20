<?php

namespace App\Models;

use CodeIgniter\Model;

class PushSubscriptionModel extends Model
{
    protected $table = 'push_subscriptions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'endpoint', 'p256dh_key', 'auth_key', 'user_agent'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    public function ensureTable(): void
    {
        try {
            $db = \Config\Database::connect();
            $db->query("CREATE TABLE IF NOT EXISTS `push_subscriptions` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `user_id` int(11) unsigned NOT NULL DEFAULT 0,
                `endpoint` text NOT NULL,
                `p256dh_key` text NOT NULL,
                `auth_key` varchar(255) NOT NULL,
                `user_agent` text DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        } catch (\Throwable $e) {
            log_message('error', 'PushSubscriptionModel ensureTable: ' . $e->getMessage());
        }
    }

    public function getSubscriptionsByUser($userId)
    {
        return $this->where('user_id', $userId)->findAll();
    }

    public function getAllActiveSubscriptions()
    {
        return $this->findAll();
    }

    public function removeSubscription($endpoint)
    {
        return $this->where('endpoint', $endpoint)->delete();
    }
}
