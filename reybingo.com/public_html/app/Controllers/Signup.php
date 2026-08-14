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

        $data = [
            'firstname' => trim((string) $this->request->getPost('firstname')),
            'lastname' => trim((string) $this->request->getPost('lastname')),
            'email' => $email,
            'document' => trim((string) $this->request->getPost('document')),
            'address_line' => trim((string) $this->request->getPost('address_line')),
            'username' => bingo_generate_operator_username($email, $model),
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
                'rules' => 'required|min_length[2]|max_length[255]',
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
                'redirect' => site_url('/store/funding?store_registered=1'),
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

            $verificationToken = random_string('md5');

            $model->update($id, ['verification_token' => $verificationToken]);

            $user = $model->find($id);
            $this->sendVerificationEmail($user, $verificationToken);

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
                'message' => translate('please verify your email before login'),
            ];
        } else {
            $response = [
                'success' => false,
                'error' => translate('there was an error in the system')
            ];
        }

        $modelLogs = new LogsModel();

        $ip = $_SERVER['REMOTE_ADDR'];

        $geo = @json_decode(@file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country"), true);

        $country = (is_array($geo) && ($geo['status'] ?? '') === 'success') ? $geo['country'] : 'Unknown';

        $log = [
            'id_user'    => $id,
            'action'     => 'account',
            'details'    => 'user account created successfully. pending email verification.',
            'ip_address' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'country'    => $country
        ];

        $modelLogs->insert($log);
        
        return $this->response->setJSON($response);
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

        $generateReferred_code = strtoupper(random_string('alnum', 8));
        $verificationToken = random_string('md5');

        $data = [
            'group' => 0,
            'firstname' => $pending['firstname'] ?? 'Jugador',
            'lastname'  => $pending['lastname'] ?? '',
            'username'  => $username,
            'email'     => $pending['email'],
            'password'  => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
            'verified_email' => 0,
            'verification_token' => $verificationToken,
            'referred_code' => $generateReferred_code,
            'status'    => 1,
            'autodial'  => 1,
            'roulette'  => 1,
            'kyc_status' => 'pending',
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
        $this->sendVerificationEmail($user, $verificationToken);

        session()->remove('google_signup_pending');

        $modelLogs = new LogsModel();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $geo = @json_decode(@file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country"), true);
        $country = (is_array($geo) && ($geo['status'] ?? '') === 'success') ? $geo['country'] : 'Unknown';
        $modelLogs->insert([
            'id_user' => $id,
            'action' => 'account',
            'details' => 'user google account created successfully. pending email verification.',
            'ip_address' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'country' => $country,
        ]);

        return redirect()->to(site_url('signup/verifyPending?email=' . rawurlencode((string) $pending['email'])))
            ->with('success', translate('please verify your email before login'));
    }

    public function verifyPending()
    {
        $email = trim((string) ($this->request->getGet('email') ?: session()->getFlashdata('email') ?: ''));
        $modelContacts = new ContactsModel();

        $data = [
            'page' => ['title' => translate('verify your email')],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('signup/verify_pending', [
                'email' => $email,
                'contacts' => $modelContacts->findAll(),
            ]),
        ];

        return view('layout/index', $data);
    }

    public function resendVerification()
    {
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
                'message' => translate('if the email exists we sent a new link'),
            ]);
        }

        if ((int) ($user['verified_email'] ?? 0) === 1) {
            return $this->response->setJSON([
                'success' => true,
                'message' => translate('email already verified'),
                'redirect' => site_url('signin'),
            ]);
        }

        $token = random_string('md5');
        $model->update((int) $user['id'], [
            'verification_token' => $token,
            'verified_email' => 0,
        ]);
        $user['verification_token'] = $token;
        $this->sendVerificationEmail($user, $token);

        return $this->response->setJSON([
            'success' => true,
            'message' => translate('verification email resent'),
        ]);
    }

    private function setSession($user)
    {
        $sessionData = [
            'id'        => $user['id'],
            'group'     => 0,
            'document'  => $user['document'] ?? null,
            'firstname' => $user['firstname'],
            'lastname'  => $user['lastname'],
            'username'  => $user['username'],
            'email'     => $user['email'],
            'phone'     => $user['phone'] ?? null,
            'logged_in' => true
        ];

        session()->set($sessionData);
    }

    public function sendVerificationEmail($user, $token) {
        $emailConfig = \Config\Services::email();
        $config = new \Config\Email();

        $subject = translate('please verify your email address');
        $message = view('emails/verification_email', ['user' => $user, 'token' => $token]);

        $emailConfig->setFrom($config->fromEmail, $config->fromName); 
        $emailConfig->setTo($user['email']);
        $emailConfig->setSubject($subject);
        $emailConfig->setMessage($message);

        if ($emailConfig->send()) {
            return true;
        } else {
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
        $model = new UsersModel();

        $user = $model->where('verification_token', $token)->first();

        if ($user) {

            $model->update($user['id'], ['verified_email' => 1, 'verification_token' => null]);

            $sessionData = [
                'id' => $user['id'],
                'group' => $user['group'],
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'document' => $user['document'],
                'username' => $user['username'],
                'phone' => $user['phone'],
                'email' => $user['email'],
                'logged_in' => true
            ];
            
            session()->set($sessionData);

            return redirect()->to('/play')->with('success', translate('email verified successfully'));
        } else {
            return redirect()->to('/signin')->with('error', translate('invalid or expired verification link'));
        }
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