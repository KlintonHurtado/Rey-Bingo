<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\ContactsModel;
use App\Models\LogsModel;
use CodeIgniter\Controller;

class Signin extends Controller {
    public function __construct() {
        helper(['form', 'url', 'cookie', 'text']);
        session();
    }

    public function index() {
        if (session()->get('logged_in') && session()->get('group') == 1) {
            return redirect()->to('/games');
        } else if (session()->get('logged_in') && session()->get('group') == 0) {
            return redirect()->to('/play');
        }

        $modelContacts = new ContactsModel();

        $contacts = $modelContacts->findAll();
    
        $data = [
            'page' => [
                'title' => translate('login')
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('signin/index', ['contacts' => $contacts])
        ];
    
        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }

    public function signinSubmit() {
        $modelUsers = new UsersModel();

        if (!bingo_can_authenticate_on_host()) {
            $response = [
                'success' => false,
                'errors' => [
                    'username' => translate('login must use client domain'),
                ],
                'redirect' => bingo_client_login_url('/signin'),
            ];
            return $this->response->setJSON($response);
        }
        
        $validationRules = [
            'username' => [
                'label' => translate('username'),
                'rules' => 'required'
            ],
            'password' => [
                'label' => translate('password'),
                'rules' => 'required'
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
    
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $remember = $this->request->getPost('remember');
    
        $user = $modelUsers->getUserByUsername($username);
    
        if (!$user) {
            $response = [
                'success' => false,
                'errors' => [
                    'username' => translate('unregistered user')
                ]
            ];
            return $this->response->setJSON($response);
        }
    
        if ($user['deleted'] == 1) {
            $response = [
                'success' => false,
                'errors' => [
                    'username' => translate('your account has been deleted')
                ]
            ];
            return $this->response->setJSON($response);
        }

        if ($user['status'] == 2) {
            $response = [
                'success' => false,
                'errors' => [
                    'username' => translate('your account is inactive')
                ]
            ];
            return $this->response->setJSON($response);
        }

        if (!password_verify($password, $user['password'])) {
            $response = [
                'success' => false,
                'errors' => [
                    'password' => translate('incorrect password')
                ]
            ];
            return $this->response->setJSON($response);
        }
    
        $sessionData = [
            'id' => $user['id'],
            'group' => $user['group'],
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname'],
            'username' => $user['username'],
            'phone' => $user['phone'],
            'email' => $user['email'],
            'logged_in' => true
        ];
        
        session()->set($sessionData);
        
        if ($remember == '1') {
            $rememberToken = random_string('md5');
    
            $token = [
                'remember_token' => $rememberToken
            ];
    
            $modelUsers->update($user['id'], $token);
            
            set_cookie([
                'name'   => '_signin',
                'value'  => $rememberToken,
                'expire' => (60 * 60 * 24 * 7)
            ]);
        }
        
        if ($user['group'] == 1) {
            $response = [
                'success' => true,
                'redirect' => site_url('/games')
            ];
        } else {
            $response = [
                'success' => true,
                'redirect' => site_url('/play')
            ];
        }

        $modelLogs = new LogsModel();

        $ip = $_SERVER['REMOTE_ADDR'];

        $geo = json_decode(file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country"), true);

        $country = ($geo['status'] === 'success') ? $geo['country'] : 'Unknown';

        $log = [
            'id_user'    => session()->get('id'),
            'action'     => 'login',
            'details'    => 'user logged in successfully.',
            'ip_address' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'country'    => $country
        ];

        $modelLogs->insert($log);

        return $this->response->setJSON($response);
    }

    public function logout() {
        session()->destroy();
        return redirect()->to('/signin');
    }
}