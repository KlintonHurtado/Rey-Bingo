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
        helper(['form', 'url', 'cookie', 'text']);
        session();
    }

    public function index($referred_code = null) {
        if (session()->get('logged_in') && session()->get('group') == 1) {
            return redirect()->to('/games');
        } else if (session()->get('logged_in') && session()->get('group') == 0) {
            return redirect()->to('/play');
        }

        $model = new UsersModel();

        if ($referred_code !== null) {
            $referrer = $model->where('referred_code', $referred_code)->first();

            if ($referrer) {
                session()->set('referred_code', $referred_code);
            } else {
                session()->remove('referred_code');
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
                'rules' => 'required|min_length[3]|is_unique[users.username]'
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
  
        if (!$this->validate($validationRules)) {
            $errors = $this->validator->getErrors();
            $response = [
                'success' => false,
                'errors' => $errors
            ];
            return $this->response->setJSON($response);
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
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'referred_code' => $generateReferred_code,
            'status' => 1,
            'autodial' => 1,
        ];
    
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

            $modelReferrals = new ReferralsModel();

            $referred_code = session()->get('referred_code');

            if ($referred_code !== null) {
                $referrer = $model->where('referred_code', $referred_code)->first();

                if ($referrer) {
                    $modelReferrals->insert([
                        'id_referred' => $referrer['id'],
                        'id_referrer' => $id,
                        'status' => 1
                    ]);
                }

                session()->remove('referred_code');
            }

            $verificationToken = random_string('md5');

            $model->update($id, ['verification_token' => $verificationToken]);

            $this->sendVerificationEmail($user, $verificationToken);

            $sessionData = [
                'id' => $id,
                'group' => 0,
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'document' => $data['document'],
                'username' => $data['username'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'logged_in' => true
            ];
            
            session()->set($sessionData);
        
            $response = [
                'success' => true,
                'redirect' => site_url('/play')
            ];
        } else {
            $response = [
                'success' => false,
                'error' => translate('there was an error in the system')
            ];
        }

        $modelLogs = new LogsModel();

        $ip = $_SERVER['REMOTE_ADDR'];

        $geo = json_decode(file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country"), true);

        $country = ($geo['status'] === 'success') ? $geo['country'] : 'Unknown';

        $log = [
            'id_user'    => session()->get('id'),
            'action'     => 'login',
            'details'    => 'user account created successfully.',
            'ip_address' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
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

        $client = new Google_Client();
        $client->setClientId('171600430722-al53sbabidmetrr45v7t6l9ushl6fveb.apps.googleusercontent.com');
        $client->setClientSecret('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');
        $client->setRedirectUri(site_url('signup/signupGoogleSubmit'));
        $client->addScope("email");
        $client->addScope("profile");

        return redirect()->to($client->createAuthUrl());
    }

    public function signupGoogleSubmit()
    {
        $model = new UsersModel();

        $client = new Google_Client();
        $client->setClientId('171600430722-al53sbabidmetrr45v7t6l9ushl6fveb.apps.googleusercontent.com');
        $client->setClientSecret('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');
        $client->setRedirectUri(site_url('signup/signupGoogleSubmit'));

        $token = $client->fetchAccessTokenWithAuthCode($this->request->getGet('code'));

        if (isset($token['error'])) {
            return redirect()->to(site_url('signin'))->with('error', translate('error authenticating with google.'));
        }

        $client->setAccessToken($token);

        $googleService = new Google_Service_Oauth2($client);
        $googleInfo = $googleService->userinfo->get();

        $email     = $googleInfo->email;
        $firstname = $googleInfo->givenName;
        $lastname  = $googleInfo->familyName;
        $username  = explode('@', $email)[0];

        $existingUser = $model->where('email', $email)->first();

        if ($existingUser) {
            $this->setSession($existingUser);
            return redirect()->to(site_url('play'))->with('success', translate('login successful.'));
        }

        $generateReferred_code = strtoupper(random_string('alnum', 8));

        $data = [
            'group' => 0,
            'firstname' => $firstname,
            'lastname'  => $lastname,
            'username'  => $username,
            'email'     => $email,
            'password'  => password_hash('123456', PASSWORD_DEFAULT),
            'verified_email' => 1,
            'referred_code' => $generateReferred_code,
            'status'    => 1,
            'autodial'  => 1,
        ];

        $profileImageUrl = $googleInfo->picture;
        $uploadDir = FCPATH . 'uploads/users/';

        $timestamp = time();
        $randomHash = bin2hex(random_bytes(10));
        $extension = 'jpg';
        $newImageName = "{$timestamp}_{$randomHash}.{$extension}";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $imageContents = file_get_contents($profileImageUrl);
        if ($imageContents !== false) {
            file_put_contents($uploadDir . $newImageName, $imageContents);
            $data['image'] = $newImageName;
        }

        $model->insert($data);
        $id = $model->insertID();

        $code = 'BGC-A' . str_pad($id, 5, '0', STR_PAD_LEFT);
        $model->update($id, ['code' => $code]);

        wallet_apply_registration_bonus((int) $id);

        $modelReferrals = new ReferralsModel();

        $referred_code = session()->get('referred_code');

        if ($referred_code !== null) {
            $referrer = $model->where('referred_code', $referred_code)->first();

            if ($referrer) {
                $modelReferrals->insert([
                    'id_referred' => $referrer['id'],
                    'id_referrer' => $id,
                    'status' => 1
                ]);
            }
            
            session()->remove('referred_code');
        }

        $user = $model->find($id);
        if (!$user) {
            return redirect()->to(site_url('signin'))->with('error', translate('the user could not be created.'));
        }

        $this->sendWelcomeEmailGoogle($user);

        $this->setSession($user);

        $modelLogs = new LogsModel();

        $ip = $_SERVER['REMOTE_ADDR'];

        $geo = json_decode(file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country"), true);

        $country = ($geo['status'] === 'success') ? $geo['country'] : 'Unknown';

        $log = [
            'id_user'    => $id,
            'action'     => 'account',
            'details'    => 'user google account created successfully.',
            'ip_address' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'country'    => $country
        ];

        $modelLogs->insert($log);

        return redirect()->to(site_url('play'))->with('success', translate('account created successfully.'));
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

            return redirect()->to('/play');
        } else {
            return redirect()->to('/signin');
        }
    }
}