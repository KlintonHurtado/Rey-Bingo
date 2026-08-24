<?php

namespace App\Controllers;

use App\Models\SystemModel;
use App\Models\UsersModel;
use App\Models\ContactsModel;
use CodeIgniter\Controller;

class Legal extends Controller
{
    public function __construct()
    {
        helper(['form', 'url', 'cookie', 'text']);
        session();
    }

    public function terms()
    {
        return $this->renderPublicPage(
            translate('terms and conditions'),
            bingo_legal_html('termsHtml'),
            'terms'
        );
    }

    public function promotions()
    {
        return $this->renderPublicPage(
            translate('promotions'),
            bingo_legal_html('promotionsHtml'),
            'promotions'
        );
    }

    public function admin()
    {
        if ($deny = bingo_require_admin_permission('legal.manage')) {
            return $deny;
        }

        if (function_exists('bingo_ensure_system_settings_schema')) {
            bingo_ensure_system_settings_schema();
        }

        $modelUsers = new UsersModel();
        $user = $modelUsers->find(session()->get('id'));
        $imagePath = ! empty($user['image'])
            ? site_url('uploads/users/' . $user['image'])
            : site_url('assets/img/avatar.jpg');

        $data = [
            'page' => [
                'title' => translate('legal content'),
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('legal/admin', [
                'user' => $user,
                'imagePath' => $imagePath,
                'activeNav' => 'legal',
                'termsHtml' => bingo_legal_html('termsHtml'),
                'promotionsHtml' => bingo_legal_html('promotionsHtml'),
                'termsRequireAccept' => bingo_terms_require_accept() ? '1' : '0',
                'termsUpdatedAt' => (string) (systemGet('termsUpdatedAt') ?: ''),
            ]),
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        }

        return view('layout/index', $data);
    }

    public function adminSubmit()
    {
        if ($deny = bingo_require_admin_permission('legal.manage')) {
            return $deny;
        }

        if (function_exists('bingo_ensure_system_settings_schema')) {
            bingo_ensure_system_settings_schema();
        }

        $termsHtml = bingo_sanitize_legal_html((string) $this->request->getPost('termsHtml'));
        $promotionsHtml = bingo_sanitize_legal_html((string) $this->request->getPost('promotionsHtml'));
        $requireAccept = $this->request->getPost('termsRequireAccept') === '1' ? '1' : '0';

        if (trim(strip_tags($termsHtml)) === '') {
            return $this->response->setJSON([
                'success' => false,
                'errors' => [
                    'termsHtml' => translate('terms and conditions') . ' ' . strtolower(translate('it is mandatory')),
                ],
            ]);
        }

        if (trim(strip_tags($promotionsHtml)) === '') {
            return $this->response->setJSON([
                'success' => false,
                'errors' => [
                    'promotionsHtml' => translate('promotions') . ' ' . strtolower(translate('it is mandatory')),
                ],
            ]);
        }

        try {
            $model = new SystemModel();
            $model->updateValue('termsHtml', $termsHtml);
            $model->updateValue('promotionsHtml', $promotionsHtml);
            $model->updateValue('termsRequireAccept', $requireAccept);
            $model->updateValue('termsUpdatedAt', date('Y-m-d H:i:s'));

            return $this->response->setJSON([
                'success' => true,
                'message' => translate('legal content updated successfully'),
                'updated_at' => date('d/m/Y H:i'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Legal::adminSubmit: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => translate('error updating settings'),
            ]);
        }
    }

    public function acceptTerms()
    {
        if (! session()->get('logged_in') || (int) session()->get('group') !== 0) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => translate('unauthorized access'),
            ]);
        }

        if ($this->request->getPost('accept_terms') !== '1') {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('you must accept the terms and conditions'),
            ]);
        }

        $userId = (int) session()->get('id');
        if (! bingo_mark_terms_accepted($userId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('error accepting terms'),
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => translate('terms accepted successfully'),
        ]);
    }

    private function renderPublicPage(string $title, string $html, string $active): string
    {
        if (function_exists('bingo_ensure_system_settings_schema')) {
            bingo_ensure_system_settings_schema();
        }

        $modelContacts = new ContactsModel();
        $contacts = $modelContacts->findAll();

        $user = null;
        $imagePath = site_url('assets/img/avatar.jpg');
        if (session()->get('logged_in')) {
            $modelUsers = new UsersModel();
            $user = $modelUsers->find(session()->get('id'));
            if (! empty($user['image'])) {
                $imagePath = site_url('uploads/users/' . $user['image']);
            }
        }

        $data = [
            'page' => [
                'title' => $title,
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('legal/page', [
                'contacts' => $contacts,
                'user' => $user,
                'imagePath' => $imagePath,
                'title' => $title,
                'html' => $html,
                'active' => $active,
                'updatedAt' => (string) (systemGet('termsUpdatedAt') ?: ''),
            ]),
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        }

        return view('layout/index', $data);
    }
}
