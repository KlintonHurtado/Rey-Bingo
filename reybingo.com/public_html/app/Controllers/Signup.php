<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\ContactsModel;
use App\Models\ReferralsModel;
use App\Models\LogsModel;
use CodeIgniter\Controller;

require_once APPPATH . 'Libraries/google/vendor/autoload.php';

use Google_Client;
use Google_Service_Oauth2;

class Signup extends Controller {
    public function __construct() {
        helper(['form', 'url', 'cookie', 'text', 'bingo']);
        session();
    }

    public function index($referred_code = null) {
        helper('bingo');

        if (session()->get('logged_in') && session()->get('group') == 1) {
            return redirect()->to('/games');
        } else if (session()->get('logged_in') && session()->get('group') == 0) {
            return redirect()->to('/play');
        } else if (session()->get('logged_in') && session()->get('group') == bingo_group_store()) {
            return redirect()->to('/store/affiliate');
        } else if (session()->get('logged_in') && session()->get('group') == bingo_group_operator()) {
            return redirect()->to('/operator');
        }

        bingo_clear_operator_signup_session();
        bingo_clear_store_signup_session();

        $model = new UsersModel();

        if ($referred_code !== null) {
            $referrer = $model->where('referred_code', $referred_code)->first();

            if ($referrer && (int) ($referrer['group'] ?? -1) === bingo_group_store()) {
                return redirect()->to('signup/tienda/' . $referrer['referred_code']);
            }

            if ($referrer && (int) ($referrer['group'] ?? -1) === bingo_group_operator()) {
                return redirect()->to('signup/punto-venta/' . $referrer['referred_code']);
            }

            if ($referrer && (int) ($referrer['group'] ?? -1) !== bingo_group_store()) {
                bingo_set_signup_referrer_session($referrer, $referred_code);
            } else {
                session()->remove('referred_code');
                session()->remove('referred_store_id');

                return redirect()->to('/signup');
            }
        }

        $modelContacts = new ContactsModel();

        $contacts = $modelContacts->findAll();
    
        $data = [
            'page' => [
                'title' => translate('create account')
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('signup/index', ['contacts' => $contacts])
        ];
    
        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }

    public function storeAffiliate($affiliateCode = null)
    {
        helper('bingo');

        if (session()->get('logged_in') && session()->get('group') == 1) {
            return redirect()->to('/games');
        }
        if (session()->get('logged_in') && session()->get('group') == 0) {
            return redirect()->to('/play');
        }
        if (session()->get('logged_in') && session()->get('group') == bingo_group_operator()) {
            return redirect()->to('/operator');
        }

        bingo_clear_operator_signup_session();
        bingo_clear_store_signup_session();

        $store = bingo_find_store_by_affiliate_code((string) $affiliateCode);
        if (! $store) {
            return redirect()->to('/signup')->with('error', translate('invalid store affiliate link'));
        }

        if (! bingo_bootstrap_store_player_affiliate_signup($store)) {
            return redirect()->to('/signup')->with('error', translate('invalid store affiliate link'));
        }

        $modelContacts = new ContactsModel();
        $contacts = $modelContacts->findAll();
        $storeName = bingo_store_display_name($store);

        $data = [
            'page' => [
                'title' => translate('create player account'),
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('signup/player_affiliate', [
                'contacts' => $contacts,
                'referrerName' => $storeName,
            ]),
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        }

        return view('layout/index', $data);
    }

    public function storeSignupAffiliate($affiliateCode = null)
    {
        helper('bingo');

        if (session()->get('logged_in') && session()->get('group') == 1) {
            return redirect()->to('/games');
        }
        if (session()->get('logged_in') && session()->get('group') == 0) {
            return redirect()->to('/play');
        }
        if (session()->get('logged_in') && session()->get('group') == bingo_group_store()) {
            return redirect()->to('/store/affiliate');
        }
        $operator = bingo_find_operator_by_affiliate_code((string) $affiliateCode);
        if (! $operator) {
            bingo_clear_store_signup_session();

            return redirect()->to('/signup')->with('error', translate('invalid operator affiliate link'));
        }

        if (session()->get('logged_in') && session()->get('group') == bingo_group_operator()) {
            if ((int) session()->get('id') === (int) ($operator['id'] ?? 0)) {
                return redirect()->to('/operator/register');
            }

            return redirect()->to('/operator');
        }

        bingo_ensure_operator_affiliate_code($operator);
        bingo_set_store_signup_session($operator);

        $modelContacts = new ContactsModel();
        $contacts = $modelContacts->findAll();
        $referrerName = trim(($operator['firstname'] ?? '') . ' ' . ($operator['lastname'] ?? ''));

        $data = [
            'page' => [
                'title' => translate('create point of sale account'),
            ],
            'validation' => \Config\Services::validation(),
            'contacts' => $contacts,
            'contentPage' => view('signup/store_public', [
                'contacts' => $contacts,
                'referrerName' => $referrerName,
                'referrerType' => 'operator',
                'signupRole' => 'store',
            ]),
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        }

        return view('layout/index', $data);
    }

    public function operatorAffiliate($affiliateCode = null)
    {
        return $this->storeSignupAffiliate($affiliateCode);
    }

    public function operatorSignupSubmit()
    {
        helper('bingo');

        if (! session()->get('signup_as_operator') || (int) (session()->get('referred_operator_id') ?? 0) <= 0) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('invalid operator signup session'),
            ]);
        }

        $model = new UsersModel();

        $validationRules = [
            'firstname' => [
                'label' => translate('first name'),
                'rules' => 'required|min_length[2]',
            ],
            'lastname' => [
                'label' => translate('last name'),
                'rules' => 'required|min_length[2]',
            ],
            'email' => [
                'label' => translate('email'),
                'rules' => 'required|valid_email|is_unique[users.email]',
            ],
            'business_name' => [
                'label' => translate('business name'),
                'rules' => 'permit_empty|min_length[2]|max_length[255]|is_unique[users.business_name]',
                'errors' => [
                    'is_unique' => 'El nombre del negocio ya está registrado. No se puede repetir el nombre del negocio.',
                ],
            ],
            'username' => [
                'label' => translate('username'),
                'rules' => 'permit_empty|min_length[3]|max_length[100]|is_unique[users.username]',
                'errors' => [
                    'is_unique' => translate('username already in use'),
                ],
            ],
            'document' => [
                'label' => translate('document'),
                'rules' => 'required|numeric|is_unique[users.document]',
            ],
            'address_line' => [
                'label' => translate('address'),
                'rules' => 'required|min_length[3]|max_length[255]',
            ],
            'password' => [
                'label' => translate('password'),
                'rules' => 'required|min_length[6]',
            ],
            'password_confirm' => [
                'label' => translate('password confirm'),
                'rules' => 'required|matches[password]',
            ],
        ];

        if (! $this->validate($validationRules)) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors(),
            ]);
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        $lastUser = $model->orderBy('id', 'DESC')->first();
        $nextId = $lastUser ? ((int) $lastUser['id'] + 1) : 1;

        $phoneInput = trim((string) $this->request->getPost('phone'));

        $customUsername = trim((string) $this->request->getPost('username'));
        $opUsername = $customUsername !== ''
            ? $customUsername
            : bingo_generate_operator_username($email, $model);

        $data = [
            'firstname' => trim((string) $this->request->getPost('firstname')),
            'lastname' => trim((string) $this->request->getPost('lastname')),
            'business_name' => trim((string) $this->request->getPost('business_name')),
            'email' => $email,
            'document' => trim((string) $this->request->getPost('document')),
            'address_line' => trim((string) $this->request->getPost('address_line')),
            'username' => $opUsername,
            'group' => bingo_group_operator(),
            'status' => 1,
            'sounds' => 0,
            'narration' => 1,
            'autodial' => 1,
            'roulette' => 1,
            'wallet' => 0,
            'phone' => $phoneInput !== '' ? $phoneInput : ('8' . str_pad((string) $nextId, 10, '0', STR_PAD_LEFT)),
            'bank' => '',
            'account' => '',
            'image' => '',
            'verified_email' => 1,
            'verification_token' => '',
            'restore_code' => '',
            'restore_token' => '',
            'is_reseller' => 0,
            'kyc_status' => 'verified',
            'code' => 'BGC-O' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'referred_code' => strtoupper(substr(md5(uniqid('operator', true)), 0, 8)),
        ];

        if (! $model->insert($data)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('there was an error in the system'),
            ]);
        }

        $id = (int) $model->getInsertID();
        bingo_apply_operator_signup_referral($id);
        bingo_clear_operator_signup_session();

        session()->set([
            'id' => $id,
            'group' => bingo_group_operator(),
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'username' => $data['username'],
            'email' => $data['email'],
            'logged_in' => true,
        ]);

        return $this->response->setJSON([
            'success' => true,
            'redirect' => site_url('/operator'),
        ]);
    }

    public function storeSignupSubmit()
    {
        helper('bingo');

        if (! session()->get('signup_as_store')) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('invalid store signup session'),
            ]);
        }

        $signupContext = (string) $this->request->getPost('signup_context');
        $isStoreAffiliateSignup = $signupContext === 'store_affiliate';
        $fromOperatorPanel = ! $isStoreAffiliateSignup
            && (
                $this->request->getPost('from_operator_panel') === '1'
                || (bingo_is_operator() && (int) session()->get('store_signup_operator_id') > 0)
            );
        $fromStorePanel = ! $isStoreAffiliateSignup
            && ! $fromOperatorPanel
            && (
                $this->request->getPost('from_store_panel') === '1'
                || bingo_is_store()
                || (bingo_is_operator() && bingo_get_acting_store_id() > 0)
            );
        $authSessionBackup = ($fromStorePanel || $fromOperatorPanel) ? $this->captureAuthSessionBackup() : null;

        $model = new UsersModel();

        $validationRules = [
            'firstname' => [
                'label' => translate('first name'),
                'rules' => 'required|min_length[2]',
            ],
            'lastname' => [
                'label' => translate('last name'),
                'rules' => 'required|min_length[2]',
            ],
            'document' => [
                'label' => translate('document'),
                'rules' => 'required|numeric|is_unique[users.document]',
            ],
            'business_name' => [
                'label' => translate('business name'),
                'rules' => 'required|min_length[2]|max_length[255]|is_unique[users.business_name]',
                'errors' => [
                    'is_unique' => 'El nombre del negocio ya está registrado. No se puede repetir el nombre del negocio.',
                ],
            ],
            'username' => [
                'label' => translate('username'),
                'rules' => 'permit_empty|min_length[3]|max_length[100]|is_unique[users.username]',
                'errors' => [
                    'is_unique' => translate('username already in use'),
                ],
            ],
            'email' => [
                'label' => translate('email'),
                'rules' => 'required|valid_email|is_unique[users.email]',
            ],
            'password' => [
                'label' => translate('password'),
                'rules' => 'required|min_length[6]',
            ],
            'password_confirm' => [
                'label' => translate('password confirm'),
                'rules' => 'required|matches[password]',
            ],
            'address_line' => [
                'label' => translate('address'),
                'rules' => 'required|min_length[3]',
            ],
        ];

        if (! $this->validate($validationRules)) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors(),
            ]);
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        $lastUser = $model->orderBy('id', 'DESC')->first();
        $nextId = $lastUser ? ((int) $lastUser['id'] + 1) : 1;

        $phoneInput = trim((string) $this->request->getPost('phone'));

        $data = [
            'firstname' => trim((string) $this->request->getPost('firstname')),
            'lastname' => trim((string) $this->request->getPost('lastname')),
            'business_name' => trim((string) $this->request->getPost('business_name')),
            'address_line' => trim((string) $this->request->getPost('address_line')),
            'document' => trim((string) $this->request->getPost('document')),
            'email' => $email,
            'username' => bingo_generate_store_username($email, $model),
            'group' => bingo_group_store(),
            'status' => 1,
            'sounds' => 0,
            'narration' => 1,
            'autodial' => 1,
            'roulette' => 1,
            'wallet' => 0,
            'phone' => $phoneInput !== '' ? $phoneInput : ('8' . str_pad((string) $nextId, 10, '0', STR_PAD_LEFT)),
            'bank' => '',
            'account' => '',
            'image' => '',
            'verified_email' => 1,
            'verification_token' => '',
            'restore_code' => '',
            'restore_token' => '',
            'is_reseller' => 0,
            'kyc_status' => 'verified',
            'code' => 'BGC-T' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'referred_code' => '',
        ];

        if (! $model->insert($data)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => translate('there was an error in the system'),
            ]);
        }

        $id = (int) $model->getInsertID();
        bingo_ensure_store_affiliate_code($model->find($id) ?? array_merge($data, ['id' => $id]));
        bingo_apply_store_signup_operator($id);

        if ($isStoreAffiliateSignup) {
            $this->loginNewStoreSession($id, $data);

            return $this->response->setJSON([
                'success' => true,
                'redirect' => site_url('/store/recharge?store_registered=1'),
            ]);
        }

        if ($fromStorePanel) {
            $this->restoreAuthSessionBackup($authSessionBackup);

            return $this->response->setJSON([
                'success' => true,
                'redirect' => site_url('/store/affiliate?store_registered=1'),
            ]);
        }

        if ($fromOperatorPanel) {
            $this->restoreAuthSessionBackup($authSessionBackup);

            return $this->response->setJSON([
                'success' => true,
                'redirect' => site_url('/operator?store_registered=1'),
            ]);
        }

        $this->loginNewStoreSession($id, $data);

        return $this->response->setJSON([
            'success' => true,
            'redirect' => site_url('/store'),
        ]);
    }

    private function loginNewStoreSession(int $id, array $data): void
    {
        session()->remove('signup_as_store');
        session()->remove('store_signup_operator_id');
        session()->remove('store_signup_referrer_id');
        session()->set([
            'id' => $id,
            'group' => bingo_group_store(),
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'username' => $data['username'],
            'email' => $data['email'],
            'logged_in' => true,
        ]);
    }

    public function signupStepSubmit() {
        $model = new UsersModel();

        $validationRules = [
            'firstname' => [
                'label' => translate('first name'),
                'rules' => 'required|min_length[3]'
            ],
            'lastname' => [
                'label' => translate('last name'),
                'rules' => 'required|min_length[3]'
            ],
            'document' => [
                'label' => translate('document'),
                'rules' => 'required|numeric|is_unique[users.document]'
            ],
            'phone' => [
                'label' => translate('phone'),
                'rules' => 'required|numeric|is_unique[users.phone]'
            ],
            'address_line' => [
                'label' => translate('address'),
                'rules' => 'permit_empty|min_length[3]'
            ]
        ];

        if (!$this->validate($validationRules)) {
            $errors = $this->validator->getErrors();
            $response = [
                'success' => false,
                'errors' => $errors
            ];
            return $this->response->setJSON($response);
        }

        $response = [
            'success' => true
        ];

        return $this->response->setJSON($response);
    }

    public function signupSubmit() {
        $model = new UsersModel();

        if (!bingo_can_authenticate_on_host()) {
            $response = [
                'success' => false,
                'errors' => [
                    'username' => translate('login must use client domain'),
                ],
                'redirect' => bingo_client_login_url('/signup'),
            ];
            return $this->response->setJSON($response);
        }
    
        $validationRules = [
            'firstname' => [
                'label' => translate('first name'),
                'rules' => 'required|min_length[3]'
            ],
            'lastname' => [
                'label' => translate('last name'),
                'rules' => 'required|min_length[3]'
            ],
            'document' => [
                'label' => translate('document'),
                'rules' => 'required|numeric|is_unique[users.document]'
            ],
            'username' => [
                'label' => translate('username'),
                'rules' => 'required|min_length[3]|is_unique[users.username]',
                'errors' => [
                    'is_unique' => translate('username already in use'),
                ],
            ],
            'phone' => [
                'label' => translate('phone'),  
                'rules' => 'required|numeric|is_unique[users.phone]'
            ],
            'email' => [
                'label' => translate('email'), 
                'rules' => 'required|valid_email|is_unique[users.email]'
            ],
            'password' => [
                'label' => translate('password'),
                'rules' => 'required|min_length[6]'
            ],
            'password_confirm' => [
                'label' => translate('password confirm'),
                'rules' => 'required|matches[password]'
            ]
        ];

        if (bingo_terms_require_accept()) {
            $validationRules['accept_terms'] = [
                'label' => translate('terms and conditions'),
                'rules' => 'required|in_list[1]',
                'errors' => [
                    'required' => translate('you must accept the terms and conditions'),
                    'in_list' => translate('you must accept the terms and conditions'),
                ],
            ];
        }

        if ($this->request->getPost('signup_context') === 'store_affiliate') {
            $validationRules['address_line'] = [
                'label' => translate('address'),
                'rules' => 'required|min_length[3]',
            ];
        }
  
        if (!$this->validate($validationRules)) {
            $errors = $this->validator->getErrors();
            $response = [
                'success' => false,
                'errors' => $errors
            ];
            return $this->response->setJSON($response);
        }

        $username = trim((string) $this->request->getPost('username'));
        if ($model->usernameExists($username)) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => [
                    'username' => translate('username already in use'),
                ],
            ]);
        }

        $generateReferred_code = strtoupper(random_string('alnum', 8));
    
        $data = [
            'group' => 0,
            'firstname' => $this->request->getPost('firstname'),
            'lastname' => $this->request->getPost('lastname'),
            'document' => $this->request->getPost('document'),
            'username' => $this->request->getPost('username'),
            'phone' => $this->request->getPost('phone'),
            'email' => $this->request->getPost('email'),
            'address_line' => trim((string) $this->request->getPost('address_line')),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'referred_code' => $generateReferred_code,
            'status' => 1,
            'autodial' => 1,
            'verified_email' => 0,
            'roulette' => 1,
            'kyc_status' => 'pending',
            'last_ip' => (string) ($this->request->getIPAddress() ?: ($_SERVER['REMOTE_ADDR'] ?? '')),
            'last_mac' => function_exists('bingo_capture_client_mac') ? bingo_capture_client_mac($this->request) : null,
        ];

        if ($this->request->getPost('accept_terms') === '1') {
            $data['terms_accepted_at'] = date('Y-m-d H:i:s');
        }
    
        $model->insert($data);
        
        $id = $model->insertID();

        $user = $model->find($id);
        if (!$user) {
            return redirect()->to(site_url('signin'))->with('error', translate('the user could not be created.'));
        }
        
        if ($id) {

            $code = 'BGC-A' . str_pad($id, 5, '0', STR_PAD_LEFT);
            $model->update($id, ['code' => $code]);

            wallet_apply_registration_bonus((int) $id);

            helper('bingo');

            if (bingo_is_store() || (bingo_is_operator() && bingo_get_acting_store_id() > 0)) {
                $storeId = bingo_is_store()
                    ? (int) session()->get('id')
                    : bingo_get_acting_store_id();
                $store = $model->find($storeId);
                if ($store) {
                    bingo_set_signup_referrer_session($store, (string) ($store['referred_code'] ?? ''));
                }
            }

            bingo_apply_signup_referral((int) $id);

            helper('bingo');
            if (function_exists('bingo_ensure_email_verification_schema')) {
                bingo_ensure_email_verification_schema();
            }

            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);
            $now = date('Y-m-d H:i:s');
            $expiresAt = date('Y-m-d H:i:s', time() + 86400); // 24 horas

            $model->update($id, [
                'verification_token' => $tokenHash,
                'verification_token_expires_at' => $expiresAt,
                'verification_token_prev' => null,
                'verification_token_prev_expires_at' => null,
                'email_verification_sent_at' => $now,
            ]);

            $user = $model->find($id);
            $this->sendVerificationEmail($user, $rawToken);

            $modelLogs = new LogsModel();
            $ip = (string) ($this->request->getIPAddress() ?: ($_SERVER['REMOTE_ADDR'] ?? ''));
            $country = $this->getCountryFromIp($ip);

            $log = [
                'id_user'    => $id,
                'action'     => 'account',
                'details'    => 'user account created successfully. pending email verification.',
                'ip_address' => $ip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'country'    => $country,
            ];
            $modelLogs->insert($log);

            if (bingo_is_store() || (bingo_is_operator() && bingo_get_acting_store_id() > 0)) {
                $response = [
                    'success' => true,
                    'redirect' => site_url('/store/affiliate'),
                ];

                return $this->response->setJSON($response);
            }

            // No iniciar sesión hasta confirmar el correo
            session()->remove(['id', 'group', 'firstname', 'lastname', 'document', 'username', 'phone', 'email', 'logged_in']);
        
            $response = [
                'success' => true,
                'redirect' => site_url('signup/verifyPending?email=' . rawurlencode((string) $data['email'])),
                'message' => 'Te enviamos un correo de verificación. Revisa tu bandeja de entrada y la carpeta de spam.',
            ];

            return $this->response->setJSON($response);
        } else {
            $response = [
                'success' => false,
                'error' => translate('there was an error in the system')
            ];
            return $this->response->setJSON($response);
        }
    }

    public function google() 
    {
        if (session()->get('logged_in') && session()->get('group') == 1) {
            return redirect()->to('/games');
        } else if (session()->get('logged_in') && session()->get('group') == 0) {
            return redirect()->to('/play');
        }

        helper('bingo');
        bingo_apply_dynamic_base_url($this->request);

        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID', '171600430722-al53sbabidmetrr45v7t6l9ushl6fveb.apps.googleusercontent.com'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET', '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'));
        $client->setRedirectUri(site_url('signup/signupGoogleSubmit'));
        $client->addScope('email');
        $client->addScope('profile');
        $client->setAccessType('online');
        $client->setPrompt('select_account');
        $client->setIncludeGrantedScopes(true);

        return redirect()->to($client->createAuthUrl());
    }

    public function signupGoogleSubmit()
    {
        $model = new UsersModel();
        helper('bingo');
        bingo_apply_dynamic_base_url($this->request);

        $errorParam = (string) ($this->request->getGet('error') ?? '');
        if ($errorParam !== '') {
            log_message('error', 'Google OAuth denied: ' . $errorParam);
            return redirect()->to(site_url('signin'))->with(
                'error',
                translate('error authenticating with google.') ?: 'Error al autenticar con Google.'
            );
        }

        $authCode = (string) ($this->request->getGet('code') ?? '');
        if ($authCode === '') {
            return redirect()->to(site_url('signin'))->with(
                'error',
                translate('error authenticating with google.') ?: 'Error al autenticar con Google.'
            );
        }

        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID', '171600430722-al53sbabidmetrr45v7t6l9ushl6fveb.apps.googleusercontent.com'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET', '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'));
        $client->setRedirectUri(site_url('signup/signupGoogleSubmit'));

        try {
            $token = $client->fetchAccessTokenWithAuthCode($authCode);
        } catch (\Throwable $e) {
            log_message('error', 'Google OAuth token exception: ' . $e->getMessage());
            return redirect()->to(site_url('signin'))->with(
                'error',
                translate('error authenticating with google.') ?: 'Error al autenticar con Google.'
            );
        }

        if (isset($token['error'])) {
            log_message('error', 'Google OAuth token error: ' . json_encode($token));
            return redirect()->to(site_url('signin'))->with(
                'error',
                translate('error authenticating with google.') ?: 'Error al autenticar con Google.'
            );
        }

        $client->setAccessToken($token);

        try {
            $googleService = new Google_Service_Oauth2($client);
            $googleInfo = $googleService->userinfo->get();
        } catch (\Throwable $e) {
            log_message('error', 'Google OAuth userinfo: ' . $e->getMessage());
            return redirect()->to(site_url('signin'))->with(
                'error',
                translate('error authenticating with google.') ?: 'Error al autenticar con Google.'
            );
        }

        $email     = (string) $googleInfo->email;
        $firstname = (string) ($googleInfo->givenName ?: 'Jugador');
        $lastname  = (string) ($googleInfo->familyName ?: '');
        $picture   = (string) ($googleInfo->picture ?? '');

        if ($email === '') {
            return redirect()->to(site_url('signin'))->with(
                'error',
                translate('error authenticating with google.') ?: 'Error al autenticar con Google.'
            );
        }

        $existingUser = $model->where('email', $email)->first();

        if ($existingUser) {
            if (! bingo_player_email_is_verified($existingUser)) {
                return redirect()->to(site_url('signup/verifyPending?email=' . rawurlencode($email)))
                    ->with('error', translate('please verify your email before login'));
            }
            $this->setSession($existingUser);
            return redirect()->to(site_url('play'))->with('success', translate('login successful.'));
        }

        $suggestedUsername = bingo_generate_player_username($firstname, $lastname, $model);

        session()->set('google_signup_pending', [
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'picture' => $picture,
            'suggested_username' => $suggestedUsername,
            'created_at' => time(),
        ]);

        return redirect()->to(site_url('signup/googleAlias'));
    }

    public function googleAlias()
    {
        $pending = session()->get('google_signup_pending');
        if (! is_array($pending) || empty($pending['email'])) {
            return redirect()->to(site_url('signup'))->with('error', translate('error authenticating with google.'));
        }

        // Expirar pendiente > 30 min
        if ((time() - (int) ($pending['created_at'] ?? 0)) > 1800) {
            session()->remove('google_signup_pending');
            return redirect()->to(site_url('signup'))->with('error', translate('google signup expired'));
        }

        $model = new UsersModel();
        helper('bingo');
        $suggested = (string) ($pending['suggested_username'] ?? '');
        if ($suggested === '' || $model->usernameExists($suggested)) {
            $suggested = bingo_generate_player_username(
                (string) ($pending['firstname'] ?? ''),
                (string) ($pending['lastname'] ?? ''),
                $model
            );
            $pending['suggested_username'] = $suggested;
            session()->set('google_signup_pending', $pending);
        }

        $modelContacts = new ContactsModel();
        $data = [
            'page' => ['title' => translate('choose username')],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('signup/google_alias', [
                'pending' => $pending,
                'suggestedUsername' => $suggested,
                'contacts' => $modelContacts->findAll(),
            ]),
        ];

        return view('layout/index', $data);
    }

    public function googleAliasSubmit()
    {
        $pending = session()->get('google_signup_pending');
        if (! is_array($pending) || empty($pending['email'])) {
            return redirect()->to(site_url('signup'))->with('error', translate('error authenticating with google.'));
        }

        $model = new UsersModel();
        helper(['bingo', 'text']);

        $username = strtolower(trim((string) $this->request->getPost('username')));
        $username = preg_replace('/[^a-z0-9_]/', '', $username) ?? '';

        if (strlen($username) < 3) {
            return redirect()->back()->with('error', translate('username') . ': ' . translate('it is mandatory'));
        }

        if ($model->usernameExists($username)) {
            return redirect()->back()->with('error', translate('username already in use'));
        }

        if ($model->where('email', $pending['email'])->first()) {
            session()->remove('google_signup_pending');
            return redirect()->to(site_url('signin'))->with('error', translate('email already in use'));
        }

        helper('bingo');
        if (function_exists('bingo_ensure_email_verification_schema')) {
            bingo_ensure_email_verification_schema();
        }

        $generateReferred_code = strtoupper(random_string('alnum', 8));
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + 86400); // 24 horas

        $data = [
            'group' => 0,
            'firstname' => $pending['firstname'] ?? 'Jugador',
            'lastname'  => $pending['lastname'] ?? '',
            'username'  => $username,
            'email'     => $pending['email'],
            'password'  => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
            'verified_email' => 0,
            'verification_token' => $tokenHash,
            'verification_token_expires_at' => $expiresAt,
            'verification_token_prev' => null,
            'verification_token_prev_expires_at' => null,
            'email_verification_sent_at' => $now,
            'referred_code' => $generateReferred_code,
            'status'    => 1,
            'autodial'  => 1,
            'roulette'  => 1,
            'kyc_status' => 'pending',
            'last_ip'   => (string) ($this->request->getIPAddress() ?: ($_SERVER['REMOTE_ADDR'] ?? '')),
            'last_mac'  => function_exists('bingo_capture_client_mac') ? bingo_capture_client_mac($this->request) : null,
        ];

        if (bingo_terms_require_accept()) {
            $data['terms_accepted_at'] = date('Y-m-d H:i:s');
        }

        $picture = (string) ($pending['picture'] ?? '');
        if ($picture !== '') {
            $newImageName = time() . '_' . bin2hex(random_bytes(10)) . '.jpg';
            $imageContents = @file_get_contents($picture);
            if ($imageContents !== false) {
                $saved = false;
                foreach (bingo_upload_candidate_dirs('users') as $uploadDir) {
                    if (! is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0755, true);
                    }
                    if (! is_dir($uploadDir) || ! is_writable($uploadDir)) {
                        continue;
                    }
                    $written = @file_put_contents($uploadDir . $newImageName, $imageContents);
                    if ($written !== false && is_file($uploadDir . $newImageName)) {
                        $saved = true;
                        break;
                    }
                }
                // Intento primario FCPATH si aún no hay carpetas candidatas
                if (! $saved) {
                    $uploadDir = FCPATH . 'uploads/users/';
                    if (! is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0755, true);
                    }
                    if (is_dir($uploadDir)) {
                        $written = @file_put_contents($uploadDir . $newImageName, $imageContents);
                        $saved = ($written !== false && is_file($uploadDir . $newImageName));
                    }
                }
                if ($saved) {
                    $data['image'] = $newImageName;
                } else {
                    log_message('error', 'Google signup: no se pudo guardar avatar ' . $newImageName);
                }
            } else {
                log_message('error', 'Google signup: no se pudo descargar avatar de Google');
            }
        }

        $model->insert($data);
        $id = $model->insertID();
        if (! $id) {
            return redirect()->to(site_url('signup'))->with('error', translate('the user could not be created.'));
        }

        $code = 'BGC-A' . str_pad($id, 5, '0', STR_PAD_LEFT);
        $model->update($id, ['code' => $code]);

        wallet_apply_registration_bonus((int) $id);
        bingo_apply_signup_referral((int) $id);

        $user = $model->find($id);
        $this->sendVerificationEmail($user, $rawToken);

        session()->remove('google_signup_pending');

        $modelLogs = new LogsModel();
        $ip = (string) ($this->request->getIPAddress() ?: ($_SERVER['REMOTE_ADDR'] ?? ''));
        $country = $this->getCountryFromIp($ip);
        $modelLogs->insert([
            'id_user' => $id,
            'action' => 'account',
            'details' => 'user google account created successfully. pending email verification.',
            'ip_address' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'country' => $country,
        ]);

        return redirect()->to(site_url('signup/verifyPending?email=' . rawurlencode((string) $pending['email'])))
            ->with('success', 'Te enviamos un correo de verificación. Revisa tu bandeja de entrada y la carpeta de spam.');
    }

    private function getCountryFromIp(string $ip): string
    {
        $ip = trim($ip);
        if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.') || str_starts_with($ip, '172.')) {
            return 'Localhost';
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 1.2,
                'ignore_errors' => true,
            ],
        ]);

        try {
            $raw = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country", false, $ctx);
            if ($raw !== false) {
                $geo = @json_decode($raw, true);
                if (is_array($geo) && ($geo['status'] ?? '') === 'success' && !empty($geo['country'])) {
                    return (string) $geo['country'];
                }
            }
        } catch (\Throwable $e) {
            // Fallback silencioso a Unknown
        }

        return 'Unknown';
    }

    public function verifyPending()
    {
        helper('bingo');
        if (function_exists('bingo_ensure_email_verification_schema')) {
            bingo_ensure_email_verification_schema();
        }

        $email = trim((string) ($this->request->getGet('email') ?: session()->getFlashdata('email') ?: ''));
        $cooldownRemaining = 60;

        if ($email !== '') {
            $model = new UsersModel();
            $user = $model->where('email', $email)->where('group', 0)->first();
            if ($user && !empty($user['email_verification_sent_at'])) {
                $diff = time() - strtotime($user['email_verification_sent_at']);
                $cooldownRemaining = max(0, 60 - $diff);
            }
        }

        $modelContacts = new ContactsModel();

        $data = [
            'page' => ['title' => translate('verify your email')],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('signup/verify_pending', [
                'email' => $email,
                'cooldownRemaining' => $cooldownRemaining,
                'contacts' => $modelContacts->findAll(),
            ]),
        ];

        return view('layout/index', $data);
    }

    public function resendVerification()
    {
        helper('bingo');
        if (function_exists('bingo_ensure_email_verification_schema')) {
            bingo_ensure_email_verification_schema();
        }

        $email = trim((string) $this->request->getPost('email'));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('email') . ' ' . strtolower(translate('it is mandatory')),
            ]);
        }

        $model = new UsersModel();
        $user = $model->where('email', $email)->where('group', 0)->first();
        if (! $user) {
            return $this->response->setJSON([
                'success' => true,
                'cooldown' => 60,
                'message' => translate('if the email exists we sent a new link'),
            ]);
        }

        if ((int) ($user['verified_email'] ?? 0) === 1) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Tu correo ya está verificado. Puedes iniciar sesión y continuar jugando.',
                'redirect' => site_url('signin'),
            ]);
        }

        // Backend rate-limit: 60 segundos entre reenvíos
        if (!empty($user['email_verification_sent_at'])) {
            $diff = time() - strtotime($user['email_verification_sent_at']);
            if ($diff < 60) {
                $remaining = 60 - $diff;
                return $this->response->setJSON([
                    'success' => false,
                    'rate_limited' => true,
                    'remaining' => $remaining,
                    'message' => "Por favor espera {$remaining} segundos antes de solicitar otro reenvío de correo.",
                ]);
            }
        }

        // Transición de tokens: preservar el token actual en prev (2 horas de gracia)
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + 86400); // 24 horas

        $updateData = [
            'verification_token' => $tokenHash,
            'verification_token_expires_at' => $expiresAt,
            'email_verification_sent_at' => $now,
            'verified_email' => 0,
        ];

        if (!empty($user['verification_token'])) {
            $updateData['verification_token_prev'] = $user['verification_token'];
            $updateData['verification_token_prev_expires_at'] = date('Y-m-d H:i:s', time() + 7200); // 2 horas de gracia
        }

        $model->update((int) $user['id'], $updateData);

        $user['verification_token'] = $tokenHash;
        $sent = $this->sendVerificationEmail($user, $rawToken);

        if (! $sent) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo enviar el correo de verificación en este momento por un error temporal del servidor. Por favor intenta de nuevo en unos minutos.',
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'cooldown' => 60,
            'message' => 'Correo de verificación reenviado. Revisa tu bandeja de entrada y la carpeta de spam.',
        ]);
    }

    private function setSession($user)
    {
        $sessionData = [
            'id'        => $user['id'],
            'group'     => (int) ($user['group'] ?? 0),
            'document'  => $user['document'] ?? null,
            'firstname' => $user['firstname'],
            'lastname'  => $user['lastname'],
            'username'  => $user['username'],
            'email'     => $user['email'],
            'phone'     => $user['phone'] ?? null,
            'logged_in' => true
        ];

        session()->set($sessionData);

        if ((int) ($user['group'] ?? 0) === (function_exists('bingo_group_admin') ? bingo_group_admin() : 1) && function_exists('bingo_load_admin_authz_into_session')) {
            bingo_load_admin_authz_into_session($user);
        }
    }

    public function sendVerificationEmail($user, $token) {
        try {
            $emailConfig = \Config\Services::email();
            $config = new \Config\Email();

            $emailConfig->clear(true);

            $subject = translate('please verify your email address');
            $message = view('emails/verification_email', ['user' => $user, 'token' => $token]);

            $emailConfig->setFrom($config->fromEmail, $config->fromName); 
            $emailConfig->setTo($user['email']);
            $emailConfig->setSubject($subject);
            $emailConfig->setMessage($message);

            $sent = $emailConfig->send(false);
            if ($sent) {
                return true;
            }

            $rawDebug = (string) $emailConfig->printDebugger(['headers', 'subject']);
            $cleanDebug = preg_replace('/(pass|password|pwd|auth|key|token)[=:\s]+[^\r\n]+/i', '$1: [REDACTED]', $rawDebug);
            log_message('error', 'SMTP sendVerificationEmail failed for user ID ' . ($user['id'] ?? 0) . ': ' . substr($cleanDebug, 0, 500));
            return false;
        } catch (\Throwable $e) {
            log_message('error', 'SMTP Exception in sendVerificationEmail: ' . $e->getMessage());
            return false;
        }
    }

    public function sendWelcomeEmailGoogle($user) {
        $emailConfig = \Config\Services::email();
        $config = new \Config\Email();

        $subject = translate('welcome to') . ' ' . systemGet('name');
        $message = view('emails/welcome_email_google', ['user' => $user]);

        $emailConfig->setFrom($config->fromEmail, $config->fromName); 
        $emailConfig->setTo($user['email']);
        $emailConfig->setSubject($subject);
        $emailConfig->setMessage($message);

        if ($emailConfig->send())
        {
            return true;
        } else {
            return false;
        }
    }

    public function verifyEmail($token) {
        helper('bingo');
        if (function_exists('bingo_ensure_email_verification_schema')) {
            bingo_ensure_email_verification_schema();
        }

        $method = strtoupper((string) $this->request->getMethod());
        if ($method === 'POST') {
            return $this->confirmVerificationEmail($token);
        }

        // Una petición HEAD de previsualización o escáner no altera BD
        if ($method === 'HEAD') {
            return $this->response->setStatusCode(200);
        }

        $token = trim((string) $token);
        if ($token === '') {
            $data = [
                'page' => ['title' => translate('verify your email')],
                'contentPage' => view('signup/verify_confirm', [
                    'state' => 'invalid',
                    'token' => '',
                    'user' => null,
                ]),
            ];
            return view('layout/index', $data);
        }

        $model = new UsersModel();
        $tokenHash = hash('sha256', $token);
        $now = date('Y-m-d H:i:s');

        // 1. Buscar por hash actual
        $user = $model->where('verification_token', $tokenHash)->first();

        // 2. Buscar por hash previo (periodo de gracia tras reenvío)
        if (! $user) {
            $user = $model->where('verification_token_prev', $tokenHash)->first();
            if ($user && !empty($user['verification_token_prev_expires_at']) && $user['verification_token_prev_expires_at'] < $now) {
                $user = null;
            }
        }

        // 3. Fallback retrocompatible para tokens antiguos en texto plano
        if (! $user) {
            $user = $model->where('verification_token', $token)->first();
        }

        // Comprobar expiración del token principal (24 horas)
        if ($user && !empty($user['verification_token_expires_at']) && $user['verification_token_expires_at'] < $now) {
            $user = null;
        }

        if (! $user) {
            $data = [
                'page' => ['title' => translate('verify your email')],
                'contentPage' => view('signup/verify_confirm', [
                    'state' => 'invalid',
                    'token' => $token,
                    'user' => null,
                ]),
            ];
            return view('layout/index', $data);
        }

        if ((int) ($user['verified_email'] ?? 0) === 1) {
            $data = [
                'page' => ['title' => translate('verify your email')],
                'contentPage' => view('signup/verify_confirm', [
                    'state' => 'already_verified',
                    'token' => $token,
                    'user' => $user,
                ]),
            ];
            return view('layout/index', $data);
        }

        // Mostrar pantalla de confirmación (visita GET NO consume token)
        $data = [
            'page' => ['title' => translate('verify your email')],
            'contentPage' => view('signup/verify_confirm', [
                'state' => 'confirm',
                'token' => $token,
                'user' => $user,
            ]),
        ];
        return view('layout/index', $data);
    }

    public function confirmVerificationEmail($token) {
        helper('bingo');
        if (function_exists('bingo_ensure_email_verification_schema')) {
            bingo_ensure_email_verification_schema();
        }

        $token = trim((string) $token);
        if ($token === '') {
            return redirect()->to(site_url('signin'))->with('error', translate('invalid or expired verification link'));
        }

        $model = new UsersModel();
        $tokenHash = hash('sha256', $token);
        $now = date('Y-m-d H:i:s');

        // 1. Buscar por hash actual
        $user = $model->where('verification_token', $tokenHash)->first();

        // 2. Buscar por hash previo (periodo de gracia)
        if (! $user) {
            $user = $model->where('verification_token_prev', $tokenHash)->first();
            if ($user && !empty($user['verification_token_prev_expires_at']) && $user['verification_token_prev_expires_at'] < $now) {
                $user = null;
            }
        }

        // 3. Fallback retrocompatible
        if (! $user) {
            $user = $model->where('verification_token', $token)->first();
        }

        // Comprobar expiración del token principal
        if ($user && !empty($user['verification_token_expires_at']) && $user['verification_token_expires_at'] < $now) {
            return redirect()->to(site_url('signup/verifyPending' . (!empty($user['email']) ? ('?email=' . rawurlencode($user['email'])) : '')))
                ->with('error', 'El enlace de verificación ha expirado. Por favor solicita uno nuevo.');
        }

        if (! $user) {
            return redirect()->to(site_url('signin'))->with('error', translate('invalid or expired verification link'));
        }

        // Idempotencia: si ya está verificado, iniciar sesión e informar
        if ((int) ($user['verified_email'] ?? 0) === 1) {
            $this->setSession($user);
            return redirect()->to('/play')->with('info', 'Tu correo ya está verificado. Puedes iniciar sesión y continuar jugando.');
        }

        // Confirmación atómica y segura
        $db = \Config\Database::connect();
        $db->transStart();

        $updateData = [
            'verified_email' => 1,
        ];

        $model->update($user['id'], $updateData);
        $db->transComplete();

        // Autenticar al usuario
        $user['verified_email'] = 1;
        $this->setSession($user);

        return redirect()->to('/play')->with('success', '¡Correo verificado! Ya puedes ingresar y participar');
    }

    private function captureAuthSessionBackup(): ?array
    {
        if (! session()->get('logged_in')) {
            return null;
        }

        $backup = [
            'id' => session()->get('id'),
            'group' => session()->get('group'),
            'firstname' => session()->get('firstname'),
            'lastname' => session()->get('lastname'),
            'username' => session()->get('username'),
            'email' => session()->get('email'),
            'document' => session()->get('document'),
            'phone' => session()->get('phone'),
            'logged_in' => true,
        ];

        $actingStoreId = (int) (session()->get('acting_store_id') ?? 0);
        if ($actingStoreId > 0) {
            $backup['acting_store_id'] = $actingStoreId;
        }

        return $backup;
    }

    private function restoreAuthSessionBackup(array $backup): void
    {
        session()->remove('signup_as_store');
        session()->remove('store_signup_operator_id');
        session()->remove('store_signup_referrer_id');
        session()->set($backup);
    }
}