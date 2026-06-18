<?php

namespace App\Controllers;

use App\Models\SystemModel;
use App\Models\UsersModel;
use App\Models\BanksModel;
use CodeIgniter\Controller;

class Home extends Controller {
    public function __construct() {
        session();
    }

    public function index(): string {
        return view('welcome_message');
    }

    public function system() {
        if (!session()->get('logged_in')) {
            return redirect()->to('/signin');
        }

        $modelUsers = new UsersModel();

        $user = $modelUsers->find(session()->get('id'));

        $logo = $this->systemGet('logo');

        $logoPath = WRITEPATH . 'uploads/users/' . $logo;

        $imagePath = (!empty($logo) && file_exists($logoPath)) ? site_url('uploads/users/' . $logo) : site_url('assets/img/image.jpg');

        $data = [
            'page' => [
                'title' => 'Sistema'
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('home/system', ['user' => $user, 'imagePath' => $imagePath])
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }

    public function bankGet($bankId = null) {
        $modelBanks = new BanksModel();
        
        $data['bank'] = 1;
        
        if ($bankId) {
            $data['bankData'] = $modelBanks->find($bankId);
            $bank = $modelBanks->find($bankId);

            $data['logo_url'] = !empty($bank['logo']) ? site_url('uploads/banks/' . $bank['logo']) : site_url('uploads/banks/image.jpg');
            $data['isUpdate'] = true;
        } else {
            $data['bankData'] = null;
            $data['isUpdate'] = false;
        }

        return view('home/modalBank', $data);
    }

    public function bankSubmit() {

        $modelBanks = new BanksModel();

        $validationRules = [
            'name-bank' => [
                'label' => translate('name bank'),
                'rules' => 'required'
            ],
            'account-bank' => [
                'label' => translate('naccount'),  
                'rules' => 'required|numeric'
            ],
            'holder-bank' => [
                'label' => translate('holder'),
                'rules' => 'required'
            ],
            'document-bank' => [
                'label' => translate('document'),
                'rules' => 'required|numeric'
            ],
            'phone-bank' => [
                'label' => translate('phone'),
                'rules' => 'required|numeric'
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

        $bank = [
            'name'    => $this->request->getPost('name-bank'),
            'account' => $this->request->getPost('account-bank'),
            'holder'  => $this->request->getPost('holder-bank'),
            'document'=> $this->request->getPost('document-bank'),
            'phone'   => $this->request->getPost('phone-bank'),
            'status'  => 1
        ];

        $bankLogoImage = $this->request->getPost('bank-logo');
        if ($bankLogoImage) {
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $bankLogoImage));
            $fileName = uniqid() . '.png';  
            
            $uploadPath = FCPATH . 'uploads/banks/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true); 
            }

            file_put_contents($uploadPath . $fileName, $imageData);

            $bank['logo'] = $fileName;
        }

        $action = $this->request->getPost('bank-action');
        $bankId = $this->request->getPost('bank-id');

        if ($action === 'update' && $bankId) {
            // Actualizar banco existente
            $modelBanks->update($bankId, $bank);
            $bank['id'] = $bankId;
        } else {
            // Crear nuevo banco
            $modelBanks->insert($bank);
            $bankId = $modelBanks->insertID();
            $bank['id'] = $bankId;
        }

        if ($bankId) {
            $bank = $modelBanks->find($bankId);

            if (!empty($bank['logo'])) {
                $bank['logo_url'] = '<img src="'.site_url('uploads/banks/'.$bank['logo']).'" alt="logo banco" class="img-fluid" style="width:50px; height:50px; object-fit:cover;">';
            } else {
                $bank['logo_url'] = '<i class="fa-duotone fa-solid fa-building-columns fs-1 text-white"></i>';
            }

            $response = [
                'success' => true,
                'newdataBank' => $bank
            ];
        } else {
            $response = [
                'success' => false,
                'error'   => 'Error al procesar el banco.'
            ];
        }

        return $this->response->setJSON($response);
    }

    public function deleteBank() {
        if (defined('IS_DEMO') && IS_DEMO === 1) {
            $response = [
                'success' => false,
                'error' => translate('this action is disabled in DEMO mode.')
            ];
            return $this->response->setJSON($response);
        }

        $modelBanks = new BanksModel();
        $bankId = $this->request->getPost('bank_id');

        if (!$bankId) {
            $response = [
                'success' => false,
                'error' => 'ID de banco no válido.'
            ];
            return $this->response->setJSON($response);
        }

        if (systemGet('bank') == $bankId) {
            $response = [
                'success' => false,
                'error' => translate('cannot delete the default bank')
            ];
            return $this->response->setJSON($response);
        }

        $deleted = $modelBanks->delete($bankId);

        if ($deleted) {
            $response = [
                'success' => true,
                'message' => translate('bank deleted successfully')
            ];
        } else {
            $response = [
                'success' => false,
                'error' => translate('error deleting bank')
            ];
        }

        return $this->response->setJSON($response);
    }

    public function settingsGet() {

        $modelBanks = new BanksModel();

        $data['banks'] = $modelBanks->where('status', 1)->findAll();

        $modelUsers = new UsersModel();

        $data['user'] = $modelUsers->find(session()->get('id'));

        return view('home/settings', $data);
    }

    public function settingsSubmit() {
        /*if (defined('IS_DEMO') && IS_DEMO === 1) {
            $response = [
                'success' => false,
                'error' => translate('this action is disabled in DEMO mode.')
            ];
            return $this->response->setJSON($response);
        }*/

        $model = new SystemModel();
        
        $validationRules = [
            // Configuración General
            'name' => [
                'label' => translate('name bingo'),  
                'rules' => 'required'
            ],
            'contact' => [
                'label' => translate('contact'),  
                'rules' => 'required'
            ],
            'phone' => [
                'label' => translate('phone'),  
                'rules' => 'required'
            ],
            'email' => [
                'label' => translate('email'),  
                'rules' => 'required|valid_email'
            ],
            'address' => [
                'label' => translate('address'),  
                'rules' => 'required'
            ],
            'city' => [
                'label' => translate('city'),  
                'rules' => 'required'
            ],
            'zipcode' => [
                'label' => translate('zipcode'),  
                'rules' => 'required'
            ],
            'country' => [
                'label' => translate('country'),  
                'rules' => 'required'
            ],
            'language' => [
                'label' => translate('language'),  
                'rules' => 'required'
            ],
            'activateClientDomain' => [
                'label' => translate('activate client domain'),
                'rules' => 'required|in_list[0,1]'
            ],
            'clientDomain' => [
                'label' => translate('client domain'),
                'rules' => 'permit_empty|max_length[255]'
            ],
            
            // Configuración de Pagos
            'bank' => [
                'label' => translate('bank') . ' ' . translate('predetermined'),  
                'rules' => 'required'
            ],
            'method' => [
                'label' => translate('payment method') . ' ' . translate('predetermined'),  
                'rules' => 'required'
            ],
            'activatePayPal' => [
                'label' => translate('paypal active'),  
                'rules' => 'required'
            ],
            
            // Configuración del Juego
            'singBingoOnlyLastBall' => [
                'label' => translate('sing bingo only with the last ball'),  
                'rules' => 'required'
            ],
            'numberSings' => [
                'label' => translate('number sings'),  
                'rules' => 'required|numeric'
            ],
            'singBall' => [
                'label' => translate('sing ball'),  
                'rules' => 'required'
            ],
            'generateCartons' => [
                'label' => translate('generate cartons'),  
                'rules' => 'required|numeric'
            ],
            'maxCartons' => [
                'label' => translate('maximum cards per player'),  
                'rules' => 'required|numeric'
            ],
            'activateAddGames' => [
                'label' => translate('activate auto creation of games'),  
                'rules' => 'required'
            ],
            'addGamesTime' => [
                'label' => translate('create game every'),  
                'rules' => 'required'
            ],
            'addGamesFrom' => [
                'label' => translate('create games from'),  
                'rules' => 'required'
            ],
            'addGamesTo' => [
                'label' => translate('create games to'),  
                'rules' => 'required'
            ],
            'priceRanges' => [
                'label' => translate('game price range'),  
                'rules' => 'required'
            ],
            
            // Configuración Financiera
            'currency' => [
                'label' => translate('currency'),  
                'rules' => 'required'
            ],
            'rateExchange' => [
                'label' => translate('exchange rate'),  
                'rules' => 'required|decimal'
            ],
            'rateEarnings' => [
                'label' => translate('earnings rate'),  
                'rules' => 'required|decimal'
            ],
            'rateReferrals' => [
                'label' => translate('referrals rate'),  
                'rules' => 'required|decimal'
            ],
            'valueBGC' => [
                'label' => translate('Bingo Coin value'),  
                'rules' => 'required|decimal'
            ],
            'rateBGC' => [
                'label' => translate('bgc rate'),  
                'rules' => 'required|decimal'
            ],
            'registrationBonus' => [
                'label' => 'Abono al registrarse',
                'rules' => 'required|decimal|greater_than_equal_to[0]'
            ],
            
            // Configuración de Depósitos y Retiros
            'activateMinimumDeposit' => [
                'label' => translate('minimum deposit'),  
                'rules' => 'required'
            ],
            'minimumDeposit' => [
                'label' => translate('minimum deposit amount'),  
                'rules' => 'required|numeric'
            ],
            'maximumDeposit' => [
                'label' => translate('maximum deposit amount'),  
                'rules' => 'required|numeric'
            ],
            'minimumRetire' => [
                'label' => translate('minimum retire amount'),  
                'rules' => 'required|numeric'
            ],
            'maximumRetire' => [
                'label' => translate('maximum retire amount'),  
                'rules' => 'required|numeric'
            ],
            'minimumTransfer' => [
                'label' => translate('minimum transfer amount'),  
                'rules' => 'required|numeric'
            ],
            'maximumTransfer' => [
                'label' => translate('maximum transfer amount'),  
                'rules' => 'required|numeric'
            ],
            
            // Activaciones
            'activateDeposit' => [
                'label' => translate('activate deposit'),  
                'rules' => 'required'
            ],
            'activateRetire' => [
                'label' => translate('activate retire'),  
                'rules' => 'required'
            ],
            'activateTransfer' => [
                'label' => translate('activate transfer'),  
                'rules' => 'required'
            ]
        ];

        $paypalActive = $this->request->getPost('activatePayPal');

        if ($paypalActive == 1) {
            $validationRules['idPayPal'] = [
                'label' => translate('PayPal') . ' ' . translate('Client ID'),
                'rules' => 'required'
            ];
            $validationRules['secretPayPal'] = [
                'label' => translate('PayPal') . ' ' . translate('Secret'),
                'rules' => 'required'
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

        $activateClientDomain = (int) $this->request->getPost('activateClientDomain');
        $clientDomain = bingo_normalize_hostname($this->request->getPost('clientDomain'));

        if ($activateClientDomain === 1 && $clientDomain === '') {
            $response = [
                'success' => false,
                'errors' => [
                    'clientDomain' => translate('client domain is required when enabled'),
                ],
            ];
            return $this->response->setJSON($response);
        }

        if ($clientDomain !== '' && !preg_match('/^[a-z0-9]([a-z0-9\-\.]*[a-z0-9])?$/i', $clientDomain)) {
            $response = [
                'success' => false,
                'errors' => [
                    'clientDomain' => translate('client domain format is invalid'),
                ],
            ];
            return $this->response->setJSON($response);
        }

        // Organizar datos por secciones
        $generalSettings = [
            'name' => $this->request->getPost('name'),
            'contact' => $this->request->getPost('contact'),
            'phone' => $this->request->getPost('phone'),
            'email' => $this->request->getPost('email'),
            'address' => $this->request->getPost('address'),
            'city' => $this->request->getPost('city'),
            'zipcode' => $this->request->getPost('zipcode'),
            'country' => $this->request->getPost('country'),
            'language' => $this->request->getPost('language'),
            'accountInstagram' => $this->request->getPost('accountInstagram'),
            'linkGroup' => $this->request->getPost('linkGroup'),
            'activateClientDomain' => (string) $activateClientDomain,
            'clientDomain' => $clientDomain,
        ];

        $paymentSettings = [
            'bank' => $this->request->getPost('bank'),
            'method' => $this->request->getPost('method'),
            'activatePayPal' => $this->request->getPost('activatePayPal'),
            'idPayPal' => $this->request->getPost('idPayPal'),
            'secretPayPal' => $this->request->getPost('secretPayPal')
        ];

        $gameSettings = [
            'singBingoOnlyLastBall' => $this->request->getPost('singBingoOnlyLastBall'),
            'numberSings' => $this->request->getPost('numberSings'),
            'singBall' => $this->request->getPost('singBall'),
            'generateCartons' => $this->request->getPost('generateCartons'),
            'maxCartons' => $this->request->getPost('maxCartons'),
            'activateAddGames' => $this->request->getPost('activateAddGames'),
            'addGamesTime' => $this->request->getPost('addGamesTime'),
            'addGamesFrom' => $this->request->getPost('addGamesFrom'),
            'addGamesTo' => $this->request->getPost('addGamesTo'),
            'priceRanges' => $this->request->getPost('priceRanges'),
            'activateAlgorithm' => $this->request->getPost('activateAlgorithm'),
            'activateCron' => $this->request->getPost('activateCron'),
            'activateRoomCards' => $this->request->getPost('activateRoomCards'),
            'activateShareGame' => $this->request->getPost('activateShareGame'),
            'activateRoulette' => $this->request->getPost('activateRoulette'),
            'activateJoinGroup' => $this->request->getPost('activateJoinGroup'),
            'activateCompleteProfile' => $this->request->getPost('activateCompleteProfile'),
            'activateInstallPWA' => $this->request->getPost('activateInstallPWA')
        ];

        $financialSettings = [
            'currency' => $this->request->getPost('currency'),
            'rateExchange' => $this->request->getPost('rateExchange'),
            'rateEarnings' => $this->request->getPost('rateEarnings') / 100,
            'rateReferrals' => $this->request->getPost('rateReferrals') / 100,
            'valueBGC' => $this->request->getPost('valueBGC'),
            'rateBGC' => $this->request->getPost('rateBGC') / 100,
            'registrationBonus' => $this->request->getPost('registrationBonus'),
            'activateMinimumDeposit' => $this->request->getPost('activateMinimumDeposit'),
            'minimumDeposit' => $this->request->getPost('minimumDeposit'),
            'maximumDeposit' => $this->request->getPost('maximumDeposit'),
            'minimumRetire' => $this->request->getPost('minimumRetire'),
            'maximumRetire' => $this->request->getPost('maximumRetire'),
            'minimumTransfer' => $this->request->getPost('minimumTransfer'),
            'maximumTransfer' => $this->request->getPost('maximumTransfer'),
            'activateDeposit' => $this->request->getPost('activateDeposit'),
            'activateRetire' => $this->request->getPost('activateRetire'),
            'activateTransfer' => $this->request->getPost('activateTransfer')
        ];

        // Combinar todos los datos
        $data = array_merge($generalSettings, $paymentSettings, $gameSettings, $financialSettings);

        // Procesar logo si se envió
        $logoImage = $this->request->getPost('logo');
        if ($logoImage) {
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $logoImage));
            $fileName = uniqid() . '.png';  
            
            $uploadPath = FCPATH . 'uploads/system/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true); 
            }

            if (file_put_contents($uploadPath . $fileName, $imageData)) {
                $data['logo'] = $fileName;
            }
        }

        // Actualizar cada valor en la base de datos
        try {
            foreach ($data as $key => $value) {
                if ($value !== null && $value !== '') {
                    $model->updateValue($key, $value);
                }
            }

            $response = [
                'success' => true,
                'message' => translate('settings updated successfully')
            ];
            
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'error' => translate('error updating settings') . ': ' . $e->getMessage()
            ];
        }
        
        return $this->response->setJSON($response);
    }

    public function activateAlgorithm()
    {
        $model = new SystemModel();
        $input = $this->request->getJSON(true);

        if (isset($input['activateAlgorithm'])) {
            $model->updateValue('activateAlgorithm', $input['activateAlgorithm']);
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false]);
    }
}