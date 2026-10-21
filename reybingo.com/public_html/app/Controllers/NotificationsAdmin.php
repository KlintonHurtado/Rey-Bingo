<?php

namespace App\Controllers;

use App\Models\NotificationsModel;
use App\Models\NotificationTemplatesModel;
use App\Models\UsersModel;
use App\Models\PushSubscriptionModel;
use App\Models\FirebaseTokenModel;
use App\Libraries\PushNotificationService;
use App\Models\ContactsModel;
use CodeIgniter\Controller;

class NotificationsAdmin extends Controller
{
    protected $notificationsModel;
    protected $notificationTemplatesModel;
    protected $usersModel;
    protected $pushSubscriptionModel;
    protected $firebaseTokenModel;
    protected $pushService;
    
    public function __construct()
    {
        $this->notificationsModel = new NotificationsModel();
        $this->notificationTemplatesModel = new NotificationTemplatesModel();
        $this->usersModel = new UsersModel();
        $this->pushSubscriptionModel = new PushSubscriptionModel();
        $this->firebaseTokenModel = new FirebaseTokenModel();
        $this->pushService = new PushNotificationService();

        helper(['form', 'url', 'cookie', 'text', 'bingo', 'permissions']);
        session();
    }

    protected function checkAccess()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/signin');
        }

        $can = function_exists('bingo_can') ? bingo_can_any(['notifications.manage', 'settings.manage']) : false;
        $isAdmin = function_exists('bingo_is_admin') ? bingo_is_admin() : ((int) session()->get('group') === 1);

        if (!$can && !$isAdmin) {
            return redirect()->to('/dashboard')->with('error', translate('unauthorized'));
        }

        return null;
    }

    protected function ensureSchema(): void
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('notification_templates')) {
                $db->query("CREATE TABLE IF NOT EXISTS `notification_templates` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) NOT NULL,
                  `title_template` varchar(255) NOT NULL,
                  `message_template` text NOT NULL,
                  `image_url` varchar(255) DEFAULT NULL,
                  `action_url_template` varchar(255) DEFAULT NULL,
                  `created_at` datetime DEFAULT NULL,
                  `updated_at` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            }

            $count = $db->table('notification_templates')->countAllResults();
            if ($count === 0) {
                $now = date('Y-m-d H:i:s');
                $db->table('notification_templates')->insertBatch([
                    [
                        'name' => 'Nueva Partida en Vivo',
                        'title_template' => '🎉 ¡Nueva Partida Abierta!',
                        'message_template' => '¡La sala de bingo ya está disponible! Entra ahora, adquiere tus cartones de la suerte y participa por grandes premios.',
                        'action_url_template' => site_url('games'),
                        'image_url' => site_url('assets/img/logo.png'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'name' => 'Gran Pozo Acumulado',
                        'title_template' => '💰 ¡Gran Pozo Acumulado!',
                        'message_template' => '¡Atención jugadores! El pozo acumulado para la próxima partida está que arde. ¡No te quedes sin tus cartones!',
                        'action_url_template' => site_url('games'),
                        'image_url' => site_url('assets/img/logo.png'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'name' => 'Bono Especial de Recarga',
                        'title_template' => '🎁 ¡Bono Especial de Recarga!',
                        'message_template' => '¡Recarga tu saldo hoy y recibe beneficios adicionales para jugar más cartones en nuestras salas!',
                        'action_url_template' => site_url('profile'),
                        'image_url' => site_url('assets/img/logo.png'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'name' => 'Aviso Oficial de la Sala',
                        'title_template' => '📢 Comunicado Oficial Rey Bingo',
                        'message_template' => 'Apreciada comunidad, recuerden revisar sus modalidades ganadoras y jugar con responsabilidad. ¡Éxito a todos!',
                        'action_url_template' => site_url('games'),
                        'image_url' => site_url('assets/img/logo.png'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'ensureSchema notification_templates error: ' . $e->getMessage());
        }
    }
    
    /**
     * Display notifications dashboard
     */
    public function index() {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $auth;
        }

        $this->ensureSchema();

        $modelUsers = new UsersModel();
        $modelContacts = new ContactsModel();

        $contacts = $modelContacts->findAll();
        $user = $modelUsers->find(session()->get('id'));

        $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');
        
        $notifications = $this->notificationsModel
            ->select('notifications.*, users.username, users.firstname, users.lastname')
            ->join('users', 'users.id = notifications.user', 'left')
            ->orderBy('notifications.created_at', 'DESC')
            ->findAll(50);

        $stats = $this->notificationsModel->getNotificationStats();
        $templates = $this->notificationTemplatesModel->getAllTemplates();
        $users = $modelUsers->where('status', 1)->where('deleted', 0)->orderBy('username', 'ASC')->findAll(100);

        $db = \Config\Database::connect();
        $pushSubscriptionsCount = 0;
        if ($db->tableExists('push_subscriptions')) {
            $pushSubscriptionsCount = $db->table('push_subscriptions')->countAllResults();
        }

        $gamesModel = new \App\Models\GamesModel();
        $activeGamesCount = $gamesModel->whereIn('status', [1, 2])->countAllResults();

        $data = [
            'page' => [
                'title' => translate('notification management')
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('notifications_admin/index', [
                'contacts' => $contacts,
                'notifications' => $notifications,
                'stats' => $stats,
                'templates' => $templates,
                'user' => $user,
                'imagePath' => $imagePath,
                'users' => $users,
                'pushSubscriptionsCount' => $pushSubscriptionsCount,
                'activeGamesCount' => $activeGamesCount
            ]) 
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }

    /**
     * Send notification via AJAX with Live WebSocket + Web Push
     */
    public function sendAjax()
    {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'No tienes permisos para enviar notificaciones.'
            ]);
        }

        $title = trim((string) $this->request->getPost('title'));
        $message = trim((string) $this->request->getPost('message'));
        $segment = trim((string) $this->request->getPost('segment'));
        if ($segment === '') {
            $segment = 'all';
        }
        $specificUserId = (int) $this->request->getPost('user_id');
        $sendLive = (int) ($this->request->getPost('send_live') ?? 1);
        $sendPush = (int) ($this->request->getPost('send_push') ?? 1);
        $actionUrl = trim((string) $this->request->getPost('action_url'));
        $imageUrl = trim((string) $this->request->getPost('image_url'));
        $saveAsTemplate = (int) ($this->request->getPost('save_as_template') ?? 0);
        $templateName = trim((string) $this->request->getPost('template_name'));

        if ($title === '') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Por favor ingrese un título para la notificación.'
            ]);
        }

        if ($message === '') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Por favor ingrese el mensaje de la notificación.'
            ]);
        }

        if ($sendLive === 0 && $sendPush === 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Debe activar al menos un canal de entrega (Live o Push).'
            ]);
        }

        // Guardar como plantilla si se solicitó
        if ($saveAsTemplate === 1 && $templateName !== '') {
            try {
                $this->notificationTemplatesModel->insert([
                    'name' => $templateName,
                    'title_template' => $title,
                    'message_template' => $message,
                    'action_url_template' => $actionUrl ?: site_url('games'),
                    'image_url' => $imageUrl ?: site_url('assets/img/logo.png'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $te) {
                log_message('warning', 'Error al guardar plantilla: ' . $te->getMessage());
            }
        }

        // Destinatarios: siempre todos los usuarios activos
        $targetUsers = $this->usersModel->where('status', 1)->where('deleted', 0)->findAll();

        $adminId = (int) session()->get('id');
        $now = date('Y-m-d H:i:s');
        $formattedUrl = $actionUrl !== '' ? $actionUrl : null;
        $formattedImage = $imageUrl ?: site_url('assets/img/logo.png');

        // 1. Guardar en tabla notifications para persistencia
        $notificationRows = [];
        $recipientUserIds = [];
        foreach ($targetUsers as $tu) {
            $uid = (int) $tu['id'];
            $recipientUserIds[] = $uid;
            $notificationRows[] = [
                'user' => $uid,
                'from' => $adminId,
                'type' => 'admin',
                'type_id' => 0,
                'game' => 0,
                'carton' => 0,
                'modality' => 0,
                'numbers' => '',
                'title' => $title,
                'message' => $message,
                'action_url' => $formattedUrl,
                'image_url' => $formattedImage,
                'status' => 0,
                'sent_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $insertedCount = 0;
        if (!empty($notificationRows)) {
            $chunks = array_chunk($notificationRows, 100);
            foreach ($chunks as $chunk) {
                $this->notificationsModel->insertBatch($chunk);
                $insertedCount += count($chunk);
            }
        }

        // 2. Transmisión LIVE en tiempo real vía WebSocket (Soketi / Pusher)
        $liveBroadcastCount = 0;
        if ($sendLive === 1) {
            helper('bingo');
            $livePayload = [
                'id' => 'admin_' . time() . '_' . rand(100, 999),
                'title' => $title,
                'message' => $message,
                'type' => 'admin',
                'action_url' => $formattedUrl,
                'image_url' => $formattedImage,
                'created_at' => $now,
                'time' => date('H:i')
            ];

            // A) Enviar a canales de usuario privados individuales
            if (!empty($recipientUserIds)) {
                $targetLimit = count($recipientUserIds) > 200 ? 200 : count($recipientUserIds);
                for ($i = 0; $i < $targetLimit; $i++) {
                    $uId = $recipientUserIds[$i];
                    bingo_broadcast_user_notification($uId, $livePayload);
                    $liveBroadcastCount++;
                }
            }

            // B) Transmitir a todas las salas de juego activas
            try {
                $client = bingo_get_broadcast_client();
                $gamesModel = new \App\Models\GamesModel();
                $activeGames = $gamesModel->whereIn('status', [1, 2])->findAll();
                foreach ($activeGames as $ag) {
                    $gId = (int) $ag['id'];
                    $client->trigger(['private-game-' . $gId, 'game-' . $gId], 'game:notification', $livePayload);
                    $client->trigger(['private-game-' . $gId, 'game-' . $gId], 'notification:new', $livePayload);
                }
            } catch (\Throwable $be) {
                log_message('error', 'Live game broadcast error: ' . $be->getMessage());
            }
        }

        // 3. Notificación Push a teléfonos y navegadores (Web Push / Service Worker)
        $pushResult = ['sent' => 0, 'total' => 0, 'failed' => 0];
        if ($sendPush === 1) {
            try {
                $pushPayload = [
                    'title' => $title,
                    'message' => $message,
                    'body' => $message,
                    'url' => $formattedUrl ?: site_url(),
                    'icon' => $formattedImage,
                    'badge' => site_url('assets/img/badge.png'),
                    'tag' => 'admin-msg-' . time(),
                    'requireInteraction' => true,
                ];

                $pushResult = $this->pushService->sendToAll($pushPayload);
            } catch (\Throwable $pe) {
                log_message('error', 'Push sending error: ' . $pe->getMessage());
                $pushResult['error'] = $pe->getMessage();
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => '¡Notificación enviada con éxito!',
            'details' => [
                'users_targeted' => count($targetUsers),
                'live_sent' => $sendLive === 1 ? 'Activo (Soketi Realtime)' : 'No seleccionado',
                'live_count' => $liveBroadcastCount,
                'push_sent' => (int) ($pushResult['sent'] ?? 0),
                'push_total' => (int) ($pushResult['total'] ?? 0),
                'saved_as_template' => ($saveAsTemplate === 1 && $templateName !== '')
            ]
        ]);
    }

    /**
     * Guardar plantilla rápida vía AJAX
     */
    public function saveTemplateAjax()
    {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'No autorizado']);
        }

        $name = trim((string) $this->request->getPost('name'));
        $title = trim((string) $this->request->getPost('title'));
        $message = trim((string) $this->request->getPost('message'));
        $actionUrl = trim((string) $this->request->getPost('action_url'));

        if ($name === '' || $title === '' || $message === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'Nombre, título y mensaje son obligatorios.']);
        }

        $now = date('Y-m-d H:i:s');
        $id = $this->notificationTemplatesModel->insert([
            'name' => $name,
            'title_template' => $title,
            'message_template' => $message,
            'action_url_template' => $actionUrl ?: site_url('games'),
            'image_url' => site_url('assets/img/logo.png'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Plantilla guardada correctamente.',
            'template' => [
                'id' => $id,
                'name' => $name,
                'title_template' => $title,
                'message_template' => $message,
                'action_url_template' => $actionUrl ?: site_url('games')
            ]
        ]);
    }

    /**
     * Eliminar plantilla vía AJAX
     */
    public function deleteTemplateAjax($id = null)
    {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'No autorizado']);
        }

        $id = (int) ($id ?: ($this->request->getPost('id') ?: $this->request->getVar('id')));
        if ($id <= 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID de plantilla inválido.']);
        }

        try {
            $this->notificationTemplatesModel->delete($id);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Notificación eliminada exitosamente.'
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error al eliminar plantilla: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al eliminar la notificación.'
            ]);
        }
    }

    /**
     * Listar plantillas vía AJAX
     */
    public function templatesAjax()
    {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'No autorizado']);
        }

        $templates = $this->notificationTemplatesModel->getAllTemplates();
        return $this->response->setJSON([
            'success' => true,
            'templates' => $templates
        ]);
    }

    /**
     * Obtener historial reciente vía AJAX
     */
    public function historyAjax()
    {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'No autorizado']);
        }

        $notifications = $this->notificationsModel
            ->select('notifications.*, users.username, users.firstname, users.lastname')
            ->join('users', 'users.id = notifications.user', 'left')
            ->orderBy('notifications.created_at', 'DESC')
            ->findAll(30);

        return $this->response->setJSON([
            'success' => true,
            'notifications' => $notifications
        ]);
    }
    
    /**
     * Create new notification (legacy/standard form support)
     */
    public function create() {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $auth;
        }

        $modelUsers = new UsersModel();
        $modelContacts = new ContactsModel();

        $contacts = $modelContacts->findAll();
        $user = $modelUsers->find(session()->get('id'));
        $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');
        
        if ($this->request->getMethod() === 'post') {
            $rules = [
                'title' => 'required|min_length[3]|max_length[255]',
                'message' => 'required',
                'segment' => 'required'
            ];
            
            if ($this->validate($rules)) {
                $title = $this->request->getPost('title');
                $message = $this->request->getPost('message');
                $segment = $this->request->getPost('segment');
                $actionUrl = $this->request->getPost('action_url');
                $imageUrl = $this->request->getPost('image_url');
                $templateId = $this->request->getPost('template_id');
                
                $users = [];
                switch ($segment) {
                    case 'all':
                        $users = $this->usersModel->where('status', 1)->findAll();
                        break;
                    case 'pro':
                        $users = $this->usersModel->where('status', 1)->where('is_pro', 1)->findAll();
                        break;
                    case 'non_pro':
                        $users = $this->usersModel->where('status', 1)->where('is_pro', 0)->findAll();
                        break;
                    default:
                        if (is_numeric($segment)) {
                            $user = $this->usersModel->find($segment);
                            if ($user) {
                                $users = [$user];
                            }
                        }
                        break;
                }
                
                if (empty($users)) {
                    return redirect()->back()->withInput()->with('error', 'No se encontraron usuarios para el segmento seleccionado');
                }
                
                $notificationData = [
                    'from' => session()->get('id'),
                    'type' => 'admin',
                    'type_id' => 0,
                    'game' => 0,
                    'carton' => 0,
                    'modality' => 0,
                    'numbers' => '',
                    'title' => $title,
                    'message' => $message,
                    'template_id' => $templateId ?: null,
                    'action_url' => $actionUrl,
                    'image_url' => $imageUrl,
                    'status' => 0
                ];
                
                $userIds = array_column($users, 'id');
                $this->notificationsModel->createBulkNotifications($notificationData, $userIds);
                
                // Live broadcast & push
                helper('bingo');
                $livePayload = [
                    'title' => $title,
                    'message' => $message,
                    'type' => 'admin',
                    'action_url' => $actionUrl ?: site_url('games'),
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                foreach ($userIds as $uid) {
                    bingo_broadcast_user_notification($uid, $livePayload);
                }

                $this->pushService->sendToAll([
                    'title' => $title,
                    'message' => $message,
                    'body' => $message,
                    'url' => $actionUrl ?: site_url('games')
                ]);
                
                return redirect()->to('/notificationsAdmin')->with('success', 'Notificación creada y enviada exitosamente');
            } else {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }
        }
        
        $users = $this->usersModel->where('status', 1)->findAll();
        $templates = $this->notificationTemplatesModel->getAllTemplates();

        $data = [
            'page' => [
                'title' => translate('create notification')
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('notifications_admin/create', ['contacts' => $contacts, 'users' => $users, 'templates' => $templates, 'user' => $user, 'imagePath' => $imagePath]) 
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }
    
    /**
     * View notification details
     */
    public function view($id = null)
    {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $auth;
        }
        
        if ($id === null) {
            return redirect()->to('/notificationsAdmin');
        }
        
        $notification = $this->notificationsModel->find($id);
        if (!$notification) {
            return redirect()->to('/notificationsAdmin')->with('error', 'Notificación no encontrada');
        }
        
        $user = $this->usersModel->find($notification['user']);
        $sender = $this->usersModel->find($notification['from']);
        $template = $notification['template_id'] ? $this->notificationTemplatesModel->find($notification['template_id']) : null;
        
        $data = [
            'title' => 'Detalles de Notificación',
            'notification' => $notification,
            'user' => $user,
            'sender' => $sender,
            'template' => $template
        ];
        
        return view('notifications_admin/view', $data);
    }
    
    /**
     * Send notification now
     */
    public function sendNow($id = null)
    {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $auth;
        }
        
        if ($id === null) {
            return redirect()->to('/notificationsAdmin');
        }
        
        $notification = $this->notificationsModel->find($id);
        if (!$notification) {
            return redirect()->to('/notificationsAdmin')->with('error', 'Notificación no encontrada');
        }
        
        $result = $this->notificationsModel->sendPushNotification($id);
        
        if ($result['success']) {
            return redirect()->to('/notificationsAdmin/view/' . $id)->with('success', 'Notificación enviada exitosamente');
        } else {
            return redirect()->to('/notificationsAdmin/view/' . $id)->with('error', 'Error al enviar la notificación: ' . ($result['message'] ?? ''));
        }
    }
    
    /**
     * Delete notification
     */
    public function delete($id = null)
    {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $auth;
        }
        
        if ($id === null) {
            return redirect()->to('/notificationsAdmin');
        }
        
        $notification = $this->notificationsModel->find($id);
        if (!$notification) {
            return redirect()->to('/notificationsAdmin')->with('error', 'Notificación no encontrada');
        }
        
        $this->notificationsModel->delete($id);
        return redirect()->to('/notificationsAdmin')->with('success', 'Notificación eliminada exitosamente');
    }
    
    /**
     * Manage notification templates
     */
    public function templates()
    {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $auth;
        }
        
        $templates = $this->notificationTemplatesModel->getAllTemplates();
        $data = [
            'title' => 'Plantillas de Notificación',
            'templates' => $templates
        ];
        
        return view('notifications_admin/templates', $data);
    }
    
    /**
     * Create notification template
     */
    public function createTemplate()
    {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $auth;
        }
        
        if ($this->request->getMethod() === 'post') {
            $rules = [
                'name' => 'required|min_length[3]|max_length[255]',
                'title_template' => 'required',
                'message_template' => 'required'
            ];
            
            if ($this->validate($rules)) {
                $data = [
                    'name' => $this->request->getPost('name'),
                    'title_template' => $this->request->getPost('title_template'),
                    'message_template' => $this->request->getPost('message_template'),
                    'action_url_template' => $this->request->getPost('action_url_template'),
                    'image_url' => $this->request->getPost('image_url')
                ];
                
                $this->notificationTemplatesModel->insert($data);
                return redirect()->to('/notificationsAdmin/templates')->with('success', 'Plantilla creada exitosamente');
            } else {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }
        }
        
        $data = [
            'title' => 'Crear Plantilla de Notificación',
            'validation' => \Config\Services::validation()
        ];
        
        return view('notifications_admin/create_template', $data);
    }
    
    /**
     * Edit notification template
     */
    public function editTemplate($id = null)
    {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $auth;
        }
        
        if ($id === null) {
            return redirect()->to('/notificationsAdmin/templates');
        }
        
        $template = $this->notificationTemplatesModel->find($id);
        if (!$template) {
            return redirect()->to('/notificationsAdmin/templates')->with('error', 'Plantilla no encontrada');
        }
        
        if ($this->request->getMethod() === 'post') {
            $rules = [
                'name' => 'required|min_length[3]|max_length[255]',
                'title_template' => 'required',
                'message_template' => 'required'
            ];
            
            if ($this->validate($rules)) {
                $data = [
                    'name' => $this->request->getPost('name'),
                    'title_template' => $this->request->getPost('title_template'),
                    'message_template' => $this->request->getPost('message_template'),
                    'action_url_template' => $this->request->getPost('action_url_template'),
                    'image_url' => $this->request->getPost('image_url')
                ];
                
                $this->notificationTemplatesModel->update($id, $data);
                return redirect()->to('/notificationsAdmin/templates')->with('success', 'Plantilla actualizada exitosamente');
            } else {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }
        }
        
        $data = [
            'title' => 'Editar Plantilla de Notificación',
            'template' => $template,
            'validation' => \Config\Services::validation()
        ];
        
        return view('notifications_admin/edit_template', $data);
    }
    
    /**
     * Delete notification template
     */
    public function deleteTemplate($id = null)
    {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $auth;
        }
        
        if ($id === null) {
            return redirect()->to('/notificationsAdmin/templates');
        }
        
        $template = $this->notificationTemplatesModel->find($id);
        if (!$template) {
            return redirect()->to('/notificationsAdmin/templates')->with('error', 'Plantilla no encontrada');
        }
        
        $this->notificationTemplatesModel->delete($id);
        return redirect()->to('/notificationsAdmin/templates')->with('success', 'Plantilla eliminada exitosamente');
    }
    
    /**
     * Statistics
     */
    public function statistics()
    {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $auth;
        }
        
        $data = [
            'title' => 'Estadísticas de Notificaciones',
            'stats' => $this->notificationsModel->getNotificationStats()
        ];
        
        return view('notifications_admin/statistics', $data);
    }
    
    /**
     * Subscriptions
     */
    public function subscriptions()
    {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $auth;
        }
        
        $subscriptions = $this->pushSubscriptionModel->findAll();
        $data = [
            'title' => 'Suscripciones Push',
            'subscriptions' => $subscriptions
        ];
        
        return view('notifications_admin/subscriptions', $data);
    }

    public function cleanupOld()
    {
        $auth = $this->checkAccess();
        if ($auth !== null) {
            return $auth;
        }

        $this->notificationsModel->deleteOldNotifications(30);
        return redirect()->to('/notificationsAdmin')->with('success', 'Notificaciones antiguas eliminadas.');
    }
}