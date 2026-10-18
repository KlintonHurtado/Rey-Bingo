<?php

namespace App\Controllers;

use App\Models\EmailCampaignsModel;
use App\Models\EmailStatsModel;
use App\Models\UsersModel;
use CodeIgniter\I18n\Time;

class EmailMarketing extends BaseController
{
    protected $emailCampaignsModel;
    protected $emailStatsModel;
    protected $usersModel;
    
    public function __construct()
    {
        $this->emailCampaignsModel = new EmailCampaignsModel();
        $this->emailStatsModel = new EmailStatsModel();
        $this->usersModel = new UsersModel();
    }
    
    /**
     * Display email campaigns dashboard
     */
    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/signin');
        }
        
        if (session()->get('group') != 1) {
            return redirect()->to('/dashboard');
        }
        
        $campaigns = $this->emailCampaignsModel->getAllCampaigns();
        
        // Get stats for each campaign
        foreach ($campaigns as &$campaign) {
            $campaign['stats'] = $this->emailCampaignsModel->getCampaignStats($campaign['id']);
        }
        
        $data = [
            'title' => 'Email Marketing',
            'campaigns' => $campaigns
        ];
        
        return view('email_marketing/index', $data);
    }
    
    /**
     * Create new email campaign
     */
    public function create()
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
                'subject' => 'required|min_length[3]|max_length[255]',
                'content' => 'required',
                'segment' => 'required'
            ];
            
            if ($this->validate($rules)) {
                $data = [
                    'name' => $this->request->getPost('name'),
                    'subject' => $this->request->getPost('subject'),
                    'content' => $this->request->getPost('content'),
                    'segment' => $this->request->getPost('segment'),
                    'created_by' => session()->get('id'),
                    'status' => 'draft'
                ];
                
                // Handle scheduled sending
                if ($this->request->getPost('schedule') == 'yes') {
                    $scheduledDate = $this->request->getPost('scheduled_date');
                    $scheduledTime = $this->request->getPost('scheduled_time');
                    
                    if ($scheduledDate && $scheduledTime) {
                        $data['scheduled_at'] = $scheduledDate . ' ' . $scheduledTime . ':00';
                        $data['status'] = 'scheduled';
                    }
                }
                
                $campaignId = $this->emailCampaignsModel->insert($data);
                
                if ($campaignId) {
                    // Create initial stats for users in segment
                    $users = $this->emailCampaignsModel->getUsersForSegment($data['segment']);
                    $this->emailStatsModel->createInitialStats($campaignId, $users);
                    
                    return redirect()->to('/emailMarketing')->with('success', 'Campaña creada exitosamente');
                } else {
                    return redirect()->back()->withInput()->with('error', 'Error al crear la campaña');
                }
            } else {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }
        }
        
        $data = [
            'title' => 'Crear Campaña de Email'
        ];
        
        return view('email_marketing/create', $data);
    }
    
    /**
     * View email campaign details
     */
    public function view($id = null)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/signin');
        }
        
        if (session()->get('group') != 1) {
            return redirect()->to('/dashboard');
        }
        
        if ($id === null) {
            return redirect()->to('/emailMarketing');
        }
        
        $campaign = $this->emailCampaignsModel->find($id);
        
        if (!$campaign) {
            return redirect()->to('/emailMarketing')->with('error', 'Campaña no encontrada');
        }
        
        $stats = $this->emailCampaignsModel->getCampaignStats($id);
        $emailStats = $this->emailStatsModel->getCampaignStats($id);
        
        $data = [
            'title' => 'Detalles de Campaña',
            'campaign' => $campaign,
            'stats' => $stats,
            'emailStats' => $emailStats
        ];
        
        return view('email_marketing/view', $data);
    }
    
    /**
     * Edit email campaign
     */
    public function edit($id = null)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/signin');
        }
        
        if (session()->get('group') != 1) {
            return redirect()->to('/dashboard');
        }
        
        if ($id === null) {
            return redirect()->to('/emailMarketing');
        }
        
        $campaign = $this->emailCampaignsModel->find($id);
        
        if (!$campaign) {
            return redirect()->to('/emailMarketing')->with('error', 'Campaña no encontrada');
        }
        
        // Only allow editing of draft campaigns
        if ($campaign['status'] != 'draft') {
            return redirect()->to('/emailMarketing/view/' . $id)->with('error', 'Solo se pueden editar campañas en estado borrador');
        }
        
        if ($this->request->getMethod() === 'post') {
            $rules = [
                'name' => 'required|min_length[3]|max_length[255]',
                'subject' => 'required|min_length[3]|max_length[255]',
                'content' => 'required',
                'segment' => 'required'
            ];
            
            if ($this->validate($rules)) {
                $data = [
                    'name' => $this->request->getPost('name'),
                    'subject' => $this->request->getPost('subject'),
                    'content' => $this->request->getPost('content'),
                    'segment' => $this->request->getPost('segment')
                ];
                
                // Handle scheduled sending
                if ($this->request->getPost('schedule') == 'yes') {
                    $scheduledDate = $this->request->getPost('scheduled_date');
                    $scheduledTime = $this->request->getPost('scheduled_time');
                    
                    if ($scheduledDate && $scheduledTime) {
                        $data['scheduled_at'] = $scheduledDate . ' ' . $scheduledTime . ':00';
                        $data['status'] = 'scheduled';
                    }
                }
                
                $this->emailCampaignsModel->update($id, $data);
                
                // Update users in segment
                $this->emailStatsModel->where('campaign_id', $id)->delete();
                $users = $this->emailCampaignsModel->getUsersForSegment($data['segment']);
                $this->emailStatsModel->createInitialStats($id, $users);
                
                return redirect()->to('/emailMarketing/view/' . $id)->with('success', 'Campaña actualizada exitosamente');
            } else {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }
        }
        
        $data = [
            'title' => 'Editar Campaña',
            'campaign' => $campaign
        ];
        
        return view('email_marketing/edit', $data);
    }
    
    /**
     * Send test email
     */
    public function sendTest($id = null)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/signin');
        }
        
        if (session()->get('group') != 1) {
            return redirect()->to('/dashboard');
        }
        
        if ($id === null) {
            return redirect()->to('/emailMarketing');
        }
        
        $campaign = $this->emailCampaignsModel->find($id);
        
        if (!$campaign) {
            return redirect()->to('/emailMarketing')->with('error', 'Campaña no encontrada');
        }
        
        if ($this->request->getMethod() === 'post') {
            $rules = [
                'test_email' => 'required|valid_email'
            ];
            
            if ($this->validate($rules)) {
                $testEmail = $this->request->getPost('test_email');
                
                // Send test email
                $email = \Config\Services::email();
                
                $emailConfig = config(\Config\Email::class);
                $email->setFrom($emailConfig->fromEmail, $emailConfig->fromName);
                $email->setTo($testEmail);
                $email->setSubject($campaign['subject']);
                $email->setMessage($campaign['content']);
                
                if ($email->send()) {
                    return redirect()->to('/emailMarketing/view/' . $id)->with('success', 'Email de prueba enviado exitosamente');
                } else {
                    return redirect()->to('/emailMarketing/view/' . $id)->with('error', 'Error al enviar el email de prueba: ' . $email->printDebugger(['headers']));
                }
            } else {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }
        }
        
        $data = [
            'title' => 'Enviar Email de Prueba',
            'campaign' => $campaign
        ];
        
        return view('email_marketing/send_test', $data);
    }
    
    /**
     * Send campaign now
     */
    public function sendNow($id = null)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/signin');
        }
        
        if (session()->get('group') != 1) {
            return redirect()->to('/dashboard');
        }
        
        if ($id === null) {
            return redirect()->to('/emailMarketing');
        }
        
        $campaign = $this->emailCampaignsModel->find($id);
        
        if (!$campaign) {
            return redirect()->to('/emailMarketing')->with('error', 'Campaña no encontrada');
        }
        
        // Only allow sending of draft or scheduled campaigns
        if (!in_array($campaign['status'], ['draft', 'scheduled'])) {
            return redirect()->to('/emailMarketing/view/' . $id)->with('error', 'Esta campaña ya ha sido enviada o cancelada');
        }
        
        // Update campaign status
        $this->emailCampaignsModel->updateStatus($id, 'sending');
        
        // Queue the campaign for sending
        // In a real implementation, this would be handled by a background job
        // For now, we'll simulate sending to a small batch
        $pendingEmails = $this->emailStatsModel->getPendingEmails($id, 10);
        
        $email = \Config\Services::email();
        $emailConfig = config(\Config\Email::class);
        $email->setFrom($emailConfig->fromEmail, $emailConfig->fromName);
        
        $sentCount = 0;
        foreach ($pendingEmails as $recipient) {
            $email->setTo($recipient['email']);
            $email->setSubject($campaign['subject']);
            
            // Personalize content
            $personalizedContent = str_replace(
                ['{{firstname}}', '{{lastname}}', '{{username}}'],
                [$recipient['firstname'], $recipient['lastname'], $recipient['username'] ?? ''],
                $campaign['content']
            );
            
            $email->setMessage($personalizedContent);
            
            if ($email->send()) {
                $this->emailStatsModel->updateStatus($recipient['id'], 'sent');
                $sentCount++;
            } else {
                $this->emailStatsModel->updateStatus($recipient['id'], 'failed');
            }
            
            // Clear email for next recipient
            $email->clear();
        }
        
        // Check if all emails have been sent
        $pendingCount = $this->emailStatsModel->where('campaign_id', $id)->where('status', 'pending')->countAllResults();
        
        if ($pendingCount == 0) {
            $this->emailCampaignsModel->updateStatus($id, 'sent');
        }
        
        return redirect()->to('/emailMarketing/view/' . $id)->with('success', "Enviados {$sentCount} emails. " . ($pendingCount > 0 ? "{$pendingCount} pendientes." : "Campaña completada."));
    }
    
    /**
     * Schedule campaign
     */
    public function schedule($id = null)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/signin');
        }
        
        if (session()->get('group') != 1) {
            return redirect()->to('/dashboard');
        }
        
        if ($id === null) {
            return redirect()->to('/emailMarketing');
        }
        
        $campaign = $this->emailCampaignsModel->find($id);
        
        if (!$campaign) {
            return redirect()->to('/emailMarketing')->with('error', 'Campaña no encontrada');
        }
        
        // Only allow scheduling of draft campaigns
        if ($campaign['status'] != 'draft') {
            return redirect()->to('/emailMarketing/view/' . $id)->with('error', 'Solo se pueden programar campañas en estado borrador');
        }
        
        if ($this->request->getMethod() === 'post') {
            $rules = [
                'scheduled_date' => 'required',
                'scheduled_time' => 'required'
            ];
            
            if ($this->validate($rules)) {
                $scheduledDate = $this->request->getPost('scheduled_date');
                $scheduledTime = $this->request->getPost('scheduled_time');
                $scheduledAt = $scheduledDate . ' ' . $scheduledTime . ':00';
                
                $this->emailCampaignsModel->update($id, [
                    'scheduled_at' => $scheduledAt,
                    'status' => 'scheduled'
                ]);
                
                return redirect()->to('/emailMarketing/view/' . $id)->with('success', 'Campaña programada exitosamente');
            } else {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }
        }
        
        $data = [
            'title' => 'Programar Campaña',
            'campaign' => $campaign
        ];
        
        return view('email_marketing/schedule', $data);
    }
    
    /**
     * Cancel campaign
     */
    public function cancel($id = null)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/signin');
        }
        
        if (session()->get('group') != 1) {
            return redirect()->to('/dashboard');
        }
        
        if ($id === null) {
            return redirect()->to('/emailMarketing');
        }
        
        $campaign = $this->emailCampaignsModel->find($id);
        
        if (!$campaign) {
            return redirect()->to('/emailMarketing')->with('error', 'Campaña no encontrada');
        }
        
        // Only allow cancellation of draft or scheduled campaigns
        if (!in_array($campaign['status'], ['draft', 'scheduled'])) {
            return redirect()->to('/emailMarketing/view/' . $id)->with('error', 'Esta campaña no puede ser cancelada');
        }
        
        $this->emailCampaignsModel->updateStatus($id, 'cancelled');
        
        return redirect()->to('/emailMarketing')->with('success', 'Campaña cancelada exitosamente');
    }
    
    /**
     * Delete campaign
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
            return redirect()->to('/emailMarketing');
        }
        
        $campaign = $this->emailCampaignsModel->find($id);
        
        if (!$campaign) {
            return redirect()->to('/emailMarketing')->with('error', 'Campaña no encontrada');
        }
        
        // Delete stats first
        $this->emailStatsModel->where('campaign_id', $id)->delete();
        
        // Then delete campaign
        $this->emailCampaignsModel->delete($id);
        
        return redirect()->to('/emailMarketing')->with('success', 'Campaña eliminada exitosamente');
    }
    
    /**
     * Process scheduled campaigns
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
        
        $readyCampaigns = $this->emailCampaignsModel->getReadyToSendCampaigns();
        
        $processed = 0;
        foreach ($readyCampaigns as $campaign) {
            // Update campaign status
            $this->emailCampaignsModel->updateStatus($campaign['id'], 'sending');
            
            // Get pending emails
            $pendingEmails = $this->emailStatsModel->getPendingEmails($campaign['id'], 50);
            
            if (empty($pendingEmails)) {
                // No pending emails, mark as sent
                $this->emailCampaignsModel->updateStatus($campaign['id'], 'sent');
                $processed++;
                continue;
            }
            
            $email = \Config\Services::email();
            $emailConfig = config(\Config\Email::class);
            $email->setFrom($emailConfig->fromEmail, $emailConfig->fromName);
            
            foreach ($pendingEmails as $recipient) {
                $email->setTo($recipient['email']);
                $email->setSubject($campaign['subject']);
                
                // Personalize content
                $personalizedContent = str_replace(
                    ['{{firstname}}', '{{lastname}}', '{{username}}'],
                    [$recipient['firstname'], $recipient['lastname'], $recipient['username'] ?? ''],
                    $campaign['content']
                );
                
                $email->setMessage($personalizedContent);
                
                if ($email->send()) {
                    $this->emailStatsModel->updateStatus($recipient['id'], 'sent');
                } else {
                    $this->emailStatsModel->updateStatus($recipient['id'], 'failed');
                }
                
                // Clear email for next recipient
                $email->clear();
            }
            
            // Check if all emails have been sent
            $pendingCount = $this->emailStatsModel->where('campaign_id', $campaign['id'])->where('status', 'pending')->countAllResults();
            
            if ($pendingCount == 0) {
                $this->emailCampaignsModel->updateStatus($campaign['id'], 'sent');
            }
            
            $processed++;
        }
        
        if (is_cli()) {
            echo "Processed {$processed} campaigns.\n";
            return;
        }
        
        return $this->response->setJSON([
            'success' => true,
            'message' => "Procesadas {$processed} campañas programadas."
        ]);
    }
    
    /**
     * Track email open
     */
    public function trackOpen($campaignId = null, $email = null)
    {
        if (!$campaignId || !$email) {
            return;
        }
        
        $email = base64_decode($email);
        $this->emailStatsModel->updateStatusByTracking($campaignId, $email, 'opened');
        
        // Return a 1x1 transparent GIF
        header('Content-Type: image/gif');
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
    }
    
    /**
     * Track email click
     */
    public function trackClick($campaignId = null, $email = null, $url = null)
    {
        if (!$campaignId || !$email || !$url) {
            return redirect()->to('/');
        }
        
        $email = base64_decode($email);
        $url = base64_decode($url);
        
        $this->emailStatsModel->updateStatusByTracking($campaignId, $email, 'clicked');
        
        return redirect()->to($url);
    }
    
    /**
     * Generate email templates
     */
    public function templates()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/signin');
        }
        
        if (session()->get('group') != 1) {
            return redirect()->to('/dashboard');
        }
        
        $data = [
            'title' => 'Plantillas de Email'
        ];
        
        return view('email_marketing/templates', $data);
    }
    
    /**
     * Get template content
     */
    public function getTemplate($template = null)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acceso no autorizado'
            ]);
        }
        
        if (session()->get('group') != 1) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acceso no autorizado'
            ]);
        }
        
        if (!$template) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Plantilla no especificada'
            ]);
        }
        
        $templatePath = APPPATH . 'Views/emails/templates/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Plantilla no encontrada'
            ]);
        }
        
        $content = file_get_contents($templatePath);
        
        return $this->response->setJSON([
            'success' => true,
            'content' => $content
        ]);
    }
}