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

        helper(['form', 'url', 'cookie', 'text']);
        session();
    }
    
    /**
     * Display notifications dashboard
     */
    public function index() {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        $modelUsers = new UsersModel();
        $modelContacts = new ContactsModel();

        $contacts = $modelContacts->findAll();

        $user = $modelUsers->find(session()->get('id'));

        $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');
        
        $notifications = $this->notificationsModel->orderBy('created_at', 'DESC')->findAll(50);
        $stats = $this->notificationsModel->getNotificationStats();
        $templates = $this->notificationTemplatesModel->getAllTemplates();

        $data = [
            'page' => [
                'title' => translate('notification management')
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('notifications_admin/index', ['contacts' => $contacts, 'notifications' => $notifications, 'stats' => $stats, 'templates' => $templates, 'user' => $user, 'imagePath' => $imagePath]) 
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }
    
    /**
     * Create new notification
     */
    public function create() {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
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
                
                // Get users based on segment
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
                        // Check if segment is a level
                        if (strpos($segment, 'level_') === 0) {
                            $levelId = substr($segment, 6);
                            $users = $this->usersModel->where('status', 1)->where('level_id', $levelId)->findAll();
                        } else if (is_numeric($segment)) {
                            // Single user
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
                
                // Prepare notification data
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
                
                // Handle scheduled sending
                if ($this->request->getPost('schedule') == 'yes') {
                    $scheduledDate = $this->request->getPost('scheduled_date');
                    $scheduledTime = $this->request->getPost('scheduled_time');
                    
                    if ($scheduledDate && $scheduledTime) {
                        $notificationData['scheduled_at'] = $scheduledDate . ' ' . $scheduledTime . ':00';
                    }
                }
                
                // Create notifications for all users
                $userIds = array_column($users, 'id');
                $this->notificationsModel->createBulkNotifications($notificationData, $userIds);
                
                // If not scheduled, send immediately
                if (!isset($notificationData['scheduled_at'])) {
                    // Get the created notifications
                    $createdNotifications = $this->notificationsModel->where('from', session()->get('id'))
                                                                    ->where('title', $title)
                                                                    ->where('message', $message)
                                                                    ->where('sent_at IS NULL', null, false)
                                                                    ->findAll();
                    
                    $notificationIds = array_column($createdNotifications, 'id');
                    
                    if (!empty($notificationIds)) {
                        $this->notificationsModel->sendBulkPushNotifications($notificationIds);
                    }
                }
                
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
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
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
        
        if ($notification['template_id']) {
            $template = $this->notificationTemplatesModel->find($notification['template_id']);
        } else {
            $template = null;
        }
        
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
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }
        
        if ($id === null) {
            return redirect()->to('/notificationsAdmin');
        }
        
        $notification = $this->notificationsModel->find($id);
        
        if (!$notification) {
            return redirect()->to('/notificationsAdmin')->with('error', 'Notificación no encontrada');
        }
        
        // Only send if not already sent
        if ($notification['sent_at']) {
            return redirect()->to('/notificationsAdmin/view/' . $id)->with('error', 'Esta notificación ya ha sido enviada');
        }
        
        $result = $this->notificationsModel->sendPushNotification($id);
        
        if ($result['success']) {
            return redirect()->to('/notificationsAdmin/view/' . $id)->with('success', 'Notificación enviada exitosamente');
        } else {
            return redirect()->to('/notificationsAdmin/view/' . $id)->with('error', 'Error al enviar la notificación: ' . $result['message']);
        }
    }
    
    /**
     * Delete notification
     */
    public function delete($id = null)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/signin');
        }
        
        if (session()->get('group') != 1) {
            return redirect()->to('/dashboard');
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
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/signin');
        }
        
        if (session()->get('group') != 1) {
            return redirect()->to('/dashboard');
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
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/signin');
        }
        
        if (session()->get('group') != 1) {
            return redirect()->to('/dashboard');
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
            'title' => 'Crear Plantilla de Notificación'
        ];
        
        return view('notifications_admin/create_template', $data);
    }
    
    /**
     * Edit notification template
     */
    public function editTemplate($id = null)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/signin');
        }
        
        if (session()->get('group') != 1) {
            return redirect()->to('/dashboard');
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
            'template' => $template
        ];
        
        return view('notifications_admin/edit_template', $data);
    }
    
    /**
     * Delete notification template
     */
    public function deleteTemplate($id = null)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/signin');
        }
        
        if (session()->get('group') != 1) {
            return redirect()->to('/dashboard');
        }
        
        if ($id === null) {
            return redirect()->to('/notificationsAdmin/templates');
        }
        
        $template = $this->notificationTemplatesModel->find($id);
        
        if (!$template) {
            return redirect()->to('/notificationsAdmin/templates')->with('error', 'Plantilla no encontrada');
        }
        
        // Check if template is in use
        $inUse = $this->notificationsModel->where('template_id', $id)->countAllResults();
        
        if ($inUse > 0) {
            return redirect()->to('/notificationsAdmin/templates')->with('error', 'No se puede eliminar la plantilla porque está en uso');
        }
        
        $this->notificationTemplatesModel->delete($id);
        
        return redirect()->to('/notificationsAdmin/templates')->with('success', 'Plantilla eliminada exitosamente');
    }
    
    /**
     * Process scheduled notifications
     * This should be called by a cron job
     */
    public function processScheduled()
    {
        // Check if this is called from CLI or by admin
        if (!is_cli() && (!session()->get('isLoggedIn') || session()->get('group') != 1)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acceso no autorizado'
            ]);
        }
        
        $readyNotifications = $this->notificationsModel->getReadyToSendNotifications();
        
        $processed = 0;
        foreach ($readyNotifications as $notification) {
            $result = $this->notificationsModel->sendPushNotification($notification['id']);
            $processed++;
        }
        
        if (is_cli()) {
            echo "Processed {$processed} notifications.\n";
            return;
        }
        
        return $this->response->setJSON([
            'success' => true,
            'message' => "Procesadas {$processed} notificaciones programadas."
        ]);
    }
    
    /**
     * View notification statistics
     */
    public function statistics()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/signin');
        }
        
        if (session()->get('group') != 1) {
            return redirect()->to('/dashboard');
        }
        
        $dayStats = $this->notificationsModel->getNotificationStats('day');
        $weekStats = $this->notificationsModel->getNotificationStats('week');
        $monthStats = $this->notificationsModel->getNotificationStats('month');
        $yearStats = $this->notificationsModel->getNotificationStats('year');
        
        $data = [
            'title' => 'Estadísticas de Notificaciones',
            'dayStats' => $dayStats,
            'weekStats' => $weekStats,
            'monthStats' => $monthStats,
            'yearStats' => $yearStats
        ];
        
        return view('notifications_admin/statistics', $data);
    }
    
    /**
     * View push subscriptions
     */
    public function subscriptions()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/signin');
        }
        
        if (session()->get('group') != 1) {
            return redirect()->to('/dashboard');
        }
        
        $webPushSubscriptions = $this->pushSubscriptionModel->findAll();
        $firebaseTokens = $this->firebaseTokenModel->findAll();
        
        $data = [
            'title' => 'Suscripciones Push',
            'webPushSubscriptions' => $webPushSubscriptions,
            'firebaseTokens' => $firebaseTokens
        ];
        
        return view('notifications_admin/subscriptions', $data);
    }
    
    /**
     * Delete old notifications
     */
    public function cleanupOld()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/signin');
        }
        
        if (session()->get('group') != 1) {
            return redirect()->to('/dashboard');
        }
        
        $days = $this->request->getGet('days') ?? 30;
        $days = intval($days);
        
        if ($days < 7) {
            $days = 7; // Minimum 7 days
        }
        
        $this->notificationsModel->deleteOldNotifications($days);
        
        return redirect()->to('/notificationsAdmin')->with('success', "Notificaciones antiguas (más de {$days} días) eliminadas exitosamente");
    }
}