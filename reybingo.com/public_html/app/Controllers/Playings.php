<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\BoardsModel;
use App\Models\GamesModel;
use App\Models\CartonsModel;
use App\Models\NumbersCartonsModel;
use App\Models\TempCartonsModel;
use App\Models\ModalitiesModel;
use App\Models\SingsModel;
use App\Models\AwardsModel;
use App\Models\MessagesModel;
use App\Models\ContactsModel;
use App\Models\DepositsModel;
use App\Models\NotificationsModel;
use App\Models\RoulettesModel;
use App\Models\GameRoomsModel;
use CodeIgniter\Controller;

class Playings extends Controller {
    public function __construct() {
        helper(['form', 'url', 'cookie', 'text', 'wallet']);
        session();
    }
    
    public function index() {
        if (!session()->get('logged_in') || session()->get('group') != 0) {
            return redirect()->to('/signin');
        }
        
        $modelUsers = new UsersModel();
        $modelGames = new GamesModel();
        $modelBoards = new BoardsModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $modelModalities = new ModalitiesModel();
        $modelAwards = new AwardsModel();
        $modelContacts = new ContactsModel();

        $contacts = $modelContacts->findAll();
    
        $game = $modelGames->find(session()->get('game_id'));
    
        if (!$game) {
            return redirect()->to('/signin');
        }
    
        $cartons = $modelCartons->getCartonsByUser(session()->get('id'), $game['id']);
    
        if (empty($cartons)) {
            return redirect()->to('/signin');
        }

        $modalities = $modelModalities->whereIn('id', explode(',', $game['modalities']))->findAll();

        foreach ($modalities as &$modality) {
            $award = $modelAwards->where('game', $game['id'])->where('modality', $modality['id'])->where('status', 1)->first();

            $modality['amount'] = $award['amount'] ?? 0;
        }

        $selectedNumbers = $modelBoards->where('game', $game['id'])->where('status', 1)->findAll();
        $selectedNumbers = array_column($selectedNumbers, 'number');

        $getClass = function($number) {
            if ($number <= 15) {
                return 'b-col';
            } elseif ($number <= 30) {
                return 'i-col';
            } elseif ($number <= 45) {
                return 'n-col';
            } elseif ($number <= 60) {
                return 'g-col';
            } else {
                return 'o-col';
            }
        };
    
        $cartonData = [];
        foreach ($cartons as $carton) {
            $numbers = $modelNumbersCartons->where('carton', $carton['id'])->orderBy('position', 'ASC')->findAll();
            $cartonData[] = [
                'cartonId' => $carton['id'],
                'numbers' => $numbers
            ];
        }

        $user = $modelUsers->find(session()->get('id'));

        $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');
    
        $data = [
            'page' => [
                'title' => $game['description']
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('playings/index', ['contacts' => $contacts, 'game' => $game, 'user' => $user, 'selectedNumbers' => $selectedNumbers, 'getClass' => $getClass, 'cartons' => $cartonData, 'modalities' => $modalities, 'imagePath' => $imagePath]) 
        ];
    
        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }
    
    public function play() {
        if (!session()->get('logged_in') || session()->get('group') != 0) {
            return redirect()->to('/signin');
        }
        
        $modelUsers = new UsersModel();
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelContacts = new ContactsModel();
        $modelGameRooms = new GameRoomsModel();

        $contacts = $modelContacts->findAll();

        $user = wallet_service()->normalizeUser($modelUsers->find(session()->get('id')));

        $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');

        $lastGame = $modelGames->orderBy('created_at', 'DESC')->first();

        $games = $modelGames->where('status', 1)->findAll();

        foreach ($games as &$game) { 
            $room = $modelGameRooms->where('id', $game['room'])->where('status', 1)->first();
            $cartons = $modelCartons->where('user', $user['id'])->where('game', $game['id'])->countAllResults();
            $game['room'] = $room['name']; 
            $game['cartons'] = $cartons;
        }

        //$games = $modelGames->getGamesByDate(date('Y-m-d'));

        $data = [
            'page' => [
                'title' => translate('start game')
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('playings/play', ['contacts' => $contacts, 'games' => $games, 'lastGame' => $lastGame, 'user' => $user, 'imagePath' => $imagePath])
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }

    public function claimPrize() {
        $cartons = $this->request->getPost('cartons');
        if (!$cartons || !is_numeric($cartons)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Cantidad inválida']);
        }

        $modelUsers = new UsersModel();
        $modelGames = new GamesModel();
        $modelRoulettes = new RoulettesModel();

        $user = $modelUsers->find(session()->get('id'));
        $lastGame = $modelGames->orderBy('created_at', 'DESC')->first();

        if (!$lastGame) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No hay ninguna partida de bingo creada en el sistema para poder reclamar cartones.'
            ]);
        }

        $credit = $cartons * $lastGame['price'];
        wallet_credit_withdrawable($user['id'], $credit);
        $modelUsers->update($user['id'], ['roulette' => 1]);

        $data = [
            'user'    => session()->get('id'),
            'cartons' => $cartons,
            'price'   => $lastGame['price'],
            'amount'  => $credit,
            'status'  => 1
        ];

        $modelRoulettes->insert($data);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => "¡Se acreditaron $cartons cartones a tu cuenta!",
        ]);
    }

    public function totalCartonsGet($id) {
        $modelCartons = new CartonsModel();
        $totalCartons = $modelCartons->where('user', session()->get('id'))->where('game', $id)->countAllResults();

        return $this->response->setJSON([
            'totalCartons' => $totalCartons
        ]);
    }

    public function generateCartonsGet($gameId) {
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $modelUsers = new UsersModel();
        $modelGameRooms = new GameRoomsModel();

        $game = $modelGames->find($gameId);
        $data['user'] = $modelUsers->find(session()->get('id'));
        $data['game'] = $game;

        $room = $modelGameRooms->where('id', $game['room'])->where('status', 1)->first();

        $perPage = 20;
        $page = $this->request->getGet('page') ?? 1;
        
        $cartons = $modelCartons->where('game', $game['id'])->where('user', 0)->paginate($perPage, 'default', $page);

        $cartonData = [];
        foreach ($cartons as $carton) {
            $numbers = $modelNumbersCartons->where('carton', $carton['id'])->orderBy('position', 'ASC')->findAll();
            $cartonData[] = [
                'cartonId' => $carton['id'],
                'serial' => $carton['serial'],
                'numbers' => $numbers
            ];
        }

        $data['room'] = $room; 
        $data['cartons'] = $cartonData;
        $data['pager'] = $modelCartons->pager;
        $data['currentPage'] = $page;
        $data['totalPages'] = $data['pager']->getPageCount();

        return view('playings/selectCartons', $data);
    }

    public function saveCartons() {
        $modelUsers = new UsersModel();
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $modelDeposits = new DepositsModel();

        // Obtener datos del request
        $data = $this->request->getJSON(true);
        
        $userId = session()->get('id') ?? null;
        $gameId = $data['game_id'] ?? null;
        $cartonData = $data['carton_data'] ?? null; // Datos completos de los cartones generados en el frontend

        // Validaciones básicas
        if (!$userId || !$gameId || !$cartonData) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Datos incompletos'
            ]);
        }

        // Verificar que el juego existe
        $game = $modelGames->find($gameId);
        if (!$game) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('game not found')
            ]);
        }

        session()->set('game_id', $gameId);

        // Verificar que el usuario existe
        $user = $modelUsers->getUserById($userId);
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('user not found')
            ]);
        }

        $totalDeposits = $modelDeposits->where('user', $userId)->where('status', 2)->countAllResults();

        if ($totalDeposits == 0 && systemGet('activateMinimumDeposit') == 1) {
            return $this->response->setJSON([
                'success' => false,
                'message'  => 'Para poder jugar debes realizar una recarga mínima de ' . systemGet('minimumDeposit')
            ]);
        }

        $totalSelectedCartons = count($cartonData);
        
        // Validar límite máximo de cartones
        $maxCartons = systemGet('maxCartons');
        $existingCartons = $modelCartons->where('user', $userId)->where('game', $gameId)->countAllResults();
        $totalCartons = $existingCartons + $totalSelectedCartons;
        
        if ($totalCartons > $maxCartons) {
            return $this->response->setJSON([
                'success' => false,
                'message' => str_replace('{cartons}', $maxCartons, translate('only {cartons} cards can be played per game.'))
            ]);
        }

        // Calcular costo total
        $totalCost = $totalSelectedCartons * $game['price'];

        // Verificar saldo suficiente
        $user = wallet_service()->normalizeUser($user);
        if (! wallet_service()->canAfford($user, $totalCost)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('insufficient wallet balance')
            ]);
        }

        // Iniciar transacción
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $savedCartonIds = [];
            
            // Procesar cada cartón seleccionado
            foreach ($cartonData as $cartonInfo) {
                // Insertar cartón en la base de datos
                $cartonInsertData = [
                    'user' => $userId,
                    'game' => $gameId,
                    'serial' => $cartonInfo['serial'],
                    'status' => 1
                ];
                
                $modelCartons->insert($cartonInsertData);
                $cartonId = $modelCartons->insertID();
                $savedCartonIds[] = $cartonId;

                // Preparar números del cartón para inserción batch
                $numbersData = [];
                foreach ($cartonInfo['numbers'] as $numberInfo) {
                    // Solo insertar números que no sean la estrella del centro
                    if ($numberInfo['number'] !== '⭐️') {
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => $numberInfo['number'],
                            'position' => $numberInfo['position'],
                            'status' => 0
                        ];
                    } else {
                        // Para la posición central (estrella), insertar con número especial o null
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => 0, // o null, dependiendo de tu esquema de BD
                            'position' => $numberInfo['position'],
                            'status' => 1 // Marcar como ya seleccionado (estrella)
                        ];
                    }
                }
                
                // Insertar todos los números del cartón
                if (!empty($numbersData)) {
                    $modelNumbersCartons->insertBatch($numbersData);
                }
            }

            if ($totalCost > 0 && ! wallet_deduct_purchase($userId, $totalCost)) {
                throw new \Exception(translate('insufficient wallet balance'));
            }

            // Completar transacción
            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            $userAfter = wallet_service()->normalizeUser($modelUsers->find($userId));

            return $this->response->setJSON([
                'success' => true,
                'message' => translate('cartons assigned successfully'),
                'redirect_url' => base_url('playing'),
                'cartons_assigned' => $totalSelectedCartons,
                'total_cost' => $totalCost,
                'new_balance' => wallet_total($userAfter),
                'carton_ids' => $savedCartonIds
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('error processing payment') . ': ' . $e->getMessage()
            ]);
        }
    }

    public function availableCartonsGet($gameId) {
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $modelUsers = new UsersModel();
        $modelGameRooms = new GameRoomsModel();

        $game = $modelGames->find($gameId);
        $data['user'] = $modelUsers->find(session()->get('id'));
        $data['game'] = $game;

        $room = $modelGameRooms->where('id', $game['room'])->where('status', 1)->first();

        $perPage = 20;
        $page = $this->request->getGet('page') ?? 1;
        
        $cartons = $modelCartons->where('game', $game['id'])->where('user', 0)->paginate($perPage, 'default', $page);

        $cartonData = [];
        foreach ($cartons as $carton) {
            $numbers = $modelNumbersCartons->where('carton', $carton['id'])->orderBy('position', 'ASC')->findAll();
            $cartonData[] = [
                'cartonId' => $carton['id'],
                'serial' => $carton['serial'],
                'numbers' => $numbers
            ];
        }

        $data['room'] = $room; 
        $data['cartons'] = $cartonData;
        $data['pager'] = $modelCartons->pager;
        $data['currentPage'] = $page;
        $data['totalPages'] = $data['pager']->getPageCount();

        return view('playings/availablecartons', $data);
    }

    public function loadMoreCartons() {
        $gameId = $this->request->getPost('game_id');
        $page = $this->request->getPost('page');
        
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        
        $perPage = 20;
        $cartons = $modelCartons->where('game', $gameId)->where('user', 0)->paginate($perPage, 'default', $page);

        $cartonData = [];
        foreach ($cartons as $carton) {
            $numbers = $modelNumbersCartons->where('carton', $carton['id'])->orderBy('position', 'ASC')->findAll();
            $cartonData[] = [
                'cartonId' => $carton['id'],
                'serial' => $carton['serial'],
                'numbers' => $numbers
            ];
        }

        return $this->response->setJSON([
            'success' => true,
            'cartons' => $cartonData,
            'hasMore' => $page < $modelCartons->pager->getPageCount()
        ]);
    }

    public function selectCarton() {
        $modelGames = new GamesModel();
        $modelTempCartons = new TempCartonsModel();
        $modelDeposits = new DepositsModel();
        
        $cartonId = $this->request->getPost('carton_id');
        $gameId = $this->request->getPost('game_id');
        $userId = session()->get('id');

        $totalDeposits = $modelDeposits->where('user', $userId)->where('status', 2)->countAllResults();

        if ($totalDeposits == 0 && systemGet('activateMinimumDeposit') == 1) {
            $response = [
                'success' => false,
                'message'  => 'Para poder jugar debes realizar una recarga mínima de ' + systemGet('minimumDeposit')
            ];

            return $this->response->setJSON($response);
        }
        
        $existing = $modelTempCartons->where('carton', $cartonId)->first();
        
        if ($existing) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Cartón ya seleccionado'
            ]);
        }
        
        $data = [
            'carton' => $cartonId,
            'user' => $userId,
            'game' => $gameId
        ];
        
        if ($modelTempCartons->insert($data)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Cartón seleccionado correctamente'
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Error al seleccionar cartón'
        ]);
    }

    public function deselectCarton() {
        $modelTempCartons = new TempCartonsModel();
        
        $cartonId = $this->request->getPost('carton_id');
        $userId = session()->get('id');
        
        $deleted = $modelTempCartons->where('carton', $cartonId)->where('user', $userId)->delete();
        
        if ($deleted) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Cartón deseleccionado'
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Error al deseleccionar cartón'
        ]);
    }

    public function getSelectedCartons($gameId) {
        $modelTempCartons = new TempCartonsModel();
        $userId = session()->get('id');
        
        $userSelectedCartons = $modelTempCartons->where('user', $userId)->where('game', $gameId)->findAll();
        
        $otherUsersCartons = $modelTempCartons->where('user !=', $userId)->where('game', $gameId)->findAll();
        
        return $this->response->setJSON([
            'success' => true,
            'userCartons' => $userSelectedCartons,
            'otherUsersCartons' => $otherUsersCartons
        ]);
    }

    public function getCartonsStatus() {
        $modelTempCartons = new TempCartonsModel();
        $gameId = $this->request->getPost('game_id');
        $userId = session()->get('id');
        
        $fiveMinutesAgo = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        $expiredCartons = $modelTempCartons->select('carton')->where('game', $gameId)->where('created_at <', $fiveMinutesAgo)->findAll();
        $expiredIds = array_column($expiredCartons, 'carton');
        
        if (!empty($expiredIds)) {
            $modelTempCartons->whereIn('carton', $expiredIds)->delete();
        }

        $userSelectedCartons = $modelTempCartons->select('carton')->where('user', $userId)->where('game', $gameId)->findAll();
        $otherUsersCartons = $modelTempCartons->select('carton')->where('user !=', $userId)->where('game', $gameId)->findAll();
        
        $userCartonIds = array_column($userSelectedCartons, 'carton');
        $otherUsersCartonIds = array_column($otherUsersCartons, 'carton');
        
        return $this->response->setJSON([
            'success' => true,
            'userCartons' => $userCartonIds,
            'otherUsersCartons' => $otherUsersCartonIds,
            'expiredCartons' => $expiredIds
        ]);
    }

    public function cleanExpiredCartons() {
        $modelTempCartons = new TempCartonsModel();
        
        $deleted = $modelTempCartons->cleanExpired(5);
        
        return $this->response->setJSON([
            'success' => true,
            'message' => "Se eliminaron {$deleted} cartones expirados",
            'deleted_count' => $deleted
        ]);
    }

    public function checkExpiredCartons() {
        $modelTempCartons = new TempCartonsModel();
        
        $gameId = $this->request->getPost('game_id');
        $userId = session()->get('id');
        
        $fiveMinutesAgo = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        
        $expiredCartons = $modelTempCartons->select('carton')->where('user', $userId)->where('game', $gameId)->where('created_at <', $fiveMinutesAgo)->findAll();
        
        $expiredIds = array_column($expiredCartons, 'carton');
        
        if (!empty($expiredIds)) {
            $modelTempCartons->whereIn('carton', $expiredIds)->where('user', $userId)->delete();
        }
        
        return $this->response->setJSON([
            'success' => true,
            'expiredCartons' => $expiredIds
        ]);
    }

    public function getRealTimeCartonsStatus() {
        // Verificar que el usuario esté logueado
        if (!session()->get('logged_in') || session()->get('group') != 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
        }

        $modelTempCartons = new TempCartonsModel();
        $gameId = $this->request->getPost('game_id');
        $userId = session()->get('id');
        
        // Validar que se envió el game_id
        if (!$gameId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Game ID is required'
            ]);
        }
        
        try {
            // Limpiar cartones expirados automáticamente (más de 5 minutos)
            $fiveMinutesAgo = date('Y-m-d H:i:s', strtotime('-5 minutes'));
            $modelTempCartons->where('created_at <', $fiveMinutesAgo)->delete();
            
            // Obtener todos los cartones seleccionados en este juego
            $allSelectedCartons = $modelTempCartons->select('carton, user, created_at')
                                                  ->where('game', $gameId)
                                                  ->findAll();
            
            $userCartons = [];
            $otherUsersCartons = [];
            
            // Separar cartones por usuario
            foreach ($allSelectedCartons as $selection) {
                if ($selection['user'] == $userId) {
                    $userCartons[] = (int)$selection['carton'];
                } else {
                    $otherUsersCartons[] = (int)$selection['carton'];
                }
            }
            
            return $this->response->setJSON([
                'success' => true,
                'timestamp' => time(),
                'userCartons' => $userCartons,
                'otherUsersCartons' => $otherUsersCartons,
                'totalUserCartons' => count($userCartons),
                'totalOtherCartons' => count($otherUsersCartons),
                'gameId' => $gameId
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Error in getRealTimeCartonsStatus: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error retrieving cartons status',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function playing() {
        if (!session()->get('logged_in') || session()->get('group') != 0) {
            return redirect()->to('/signin');
        }
        
        $modelUsers = new UsersModel();
        $modelGames = new GamesModel();
        $modelBoards = new BoardsModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $modelModalities = new ModalitiesModel();
        $modelAwards = new AwardsModel();
        $modelSings = new SingsModel();
        $modelContacts = new ContactsModel();

        $contacts = $modelContacts->findAll();
    
        $game = $modelGames->find(session()->get('game_id'));
    
        if (!$game) {
            return redirect()->to('/play');
        }

        $totalNumbersGenerated = $modelBoards->where('game', $game['id'])->select('number')->distinct()->countAllResults();

        $singsCountFinished = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();
        $awardsCountFinished = $modelAwards->where('game', $game['id'])->where('status', 1)->countAllResults();
        $gameIsFinished = ($totalNumbersGenerated >= 75)
            || ($awardsCountFinished > 0 && $singsCountFinished >= $awardsCountFinished);
    
        $cartons = $modelCartons->getCartonsByUser(session()->get('id'), $game['id']);
    
        if (empty($cartons)) {
            return redirect()->to('/play');
        }

        $modalities = $modelModalities->whereIn('id', explode(',', $game['modalities']))->findAll();

        foreach ($modalities as &$modality) { 
            $award = $modelAwards->where('game', $game['id'])->where('modality', $modality['id'])->where('status', 1)->first();

            $modality['amount'] = $award['amount'] ?? 0; 
        }

        $selectedNumbers = $modelBoards->where('game', $game['id'])->where('status', 1)->findAll();
        $selectedNumbers = array_column($selectedNumbers, 'number');

        if (!empty($selectedNumbers)) {
            foreach ($selectedNumbers as &$selectedNumber) {

                $existingNumbers = $modelNumbersCartons->getNumbersByUserAndGame(session()->get('id'), $game['id'], $selectedNumber);

                if (!empty($existingNumbers)) {
                    $ids = array_column($existingNumbers, 'id');

                    $modelNumbersCartons->whereIn('id', $ids)->set(['status' => 1])->update();
                }
            }
        }

        $lastNumber = $modelBoards->where('game', $game['id'])->where('status', 1)->orderBy('created_at', 'DESC')->first();  

        $fourNumbers = $modelBoards->where('game', $game['id'])->where('status', 1)->orderBy('created_at', 'DESC')->limit(5)->findAll();
        array_shift($fourNumbers);
        $fourNumbers = array_reverse(array_column($fourNumbers, 'number'));

        $fiveNumbers = $modelBoards->where('game', $game['id'])->where('status', 1)->orderBy('created_at', 'DESC')->limit(5)->findAll();
        $fiveNumbers = array_reverse(array_column($fiveNumbers, 'number'));

        $singsModalities = $modelSings->where('game', $game['id'])->findAll();
        $singsModalities = array_column($singsModalities, 'modality');

        $singsUser = $modelSings->where('user', session()->get('id'))->where('game', $game['id'])->findAll();

        $winners = $modelSings->where('game', $game['id'])->where('status', 1)->findAll();
        foreach ($winners as &$winner) {
            $user = $modelUsers->find($winner['user']);
            $wmodality = $modelModalities->find($winner['modality']);

            $winner['player'] = $user['firstname'] . ' ' . $user['lastname'];
            $winner['modality'] = translate($wmodality['name']);
        }

        $getClass = function($number) {
            if ($number <= 15) {
                return 'B';
            } elseif ($number <= 30) {
                return 'I';
            } elseif ($number <= 45) {
                return 'N';
            } elseif ($number <= 60) {
                return 'G';
            } else {
                return 'O';
            }
        };
    
        $cartonData = [];
        foreach ($cartons as $carton) {
            $numbers = $modelNumbersCartons->where('carton', $carton['id'])->orderBy('position', 'ASC')->findAll();
            $cartonData[] = [
                'cartonId' => $carton['id'],
                'serial' => $carton['serial'],
                'numbers' => $numbers
            ];
        }

        $user = $modelUsers->find(session()->get('id'));

        $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');
    
        $data = [
            'page' => [
                'title' => $game['description']
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('playings/playing', ['contacts' => $contacts, 'game' => $game, 'user' => $user, 'selectedNumbers' => $selectedNumbers, 'singsModalities' => $singsModalities, 'lastNumber' => $lastNumber['number'] ?? '', 'fourNumbers' => $fourNumbers, 'lastNumbersJson' => json_encode($fiveNumbers), 'getClass' => $getClass, 'cartons' => $cartonData, 'modalities' => $modalities, 'winners' => $winners, 'totalNumbersGenerated' => $totalNumbersGenerated, 'gameIsFinished' => $gameIsFinished, 'singsUser' => $singsUser, 'imagePath' => $imagePath])
        ];
    
        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }

    public function playSubmit() {
        if (!session()->get('logged_in') || session()->get('group') != 0) {
            return redirect()->to('/signin');
        }
        
        $modelUsers = new UsersModel();
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $modelBoards = new BoardsModel();
        $modelAwards = new AwardsModel();
        $modelSings = new SingsModel();
        $modelDeposits = new DepositsModel();

        $maxCartons = systemGet('maxCartons');
        
        $validationRules = [
            'game' => [
                'label' => translate('game'),
                'rules' => 'required'
            ],
            'cartons' => [
                'label' => translate('no. of cartons'), 
                'rules' => "required|greater_than_equal_to[1]|less_than_equal_to[$maxCartons]"
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
    
        $cartons = $this->request->getPost('cartons');
        $gameId = $this->request->getPost('game');

        session()->set('game_id', $gameId);
    
        $game = $modelGames->find(session()->get('game_id'));

        if (!$game) {
            $response = [
                'success' => false,
                'errors' => [
                    'cartons' => translate('there are no active games')
                ]
            ];
            return $this->response->setJSON($response);
        }

        $user = $modelUsers->getUserById(session()->get('id'));

        $totalCartons = $modelCartons->where('user', $user['id'])->where('game', $game['id'])->countAllResults();

        $toGenerate = $cartons - $totalCartons;
    
        /*if ($user['wallet'] == 0) {
            $response = [
                'success' => false,
                'errors' => [
                    'cartons' => translate('you do not have enough balance in your wallet')
                ]
            ];
            return $this->response->setJSON($response);
        }*/

        $totalDeposits = $modelDeposits->where('user', $user['id'])->where('status', 2)->countAllResults();

        if ($totalDeposits == 0 && systemGet('activateMinimumDeposit') == 1) {
            $response = [
                'success' => false,
                'amount'  => systemGet('minimumDeposit'),
                'payments'=> true
            ];

            return $this->response->setJSON($response);
        }

        $gameDateTime = strtotime($game['date'] . ' ' . $game['time']);

        $now = time();

        $diff = $gameDateTime - $now;

        /*if ($diff > 600) {
            $response = [
                'success' => false,
                'time' => true 
            ];

            return $this->response->setJSON($response);
        }*/

        if ($toGenerate * $game['price'] > wallet_total(wallet_service()->normalizeUser($user))) {
            $response = [
                'success' => false,
                'errors' => [
                    'cartons' => translate('you do not have enough balance in your wallet')
                ]
            ];
            return $this->response->setJSON($response);
        }

        $totalNumbersGenerated = $modelBoards->where('game', $game['id'])->select('number')->distinct()->countAllResults();

        $SingsCount = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();

        $AwardsCount = $modelAwards->where('game', $game['id'])->where('status', 1)->countAllResults();

        if ($totalNumbersGenerated == 0) {
            if ($cartons <= $totalCartons) {
                $response = [
                    'success' => true,
                    'redirect' => site_url('/playing')
                ];
                return $this->response->setJSON($response);
            }

            if ($toGenerate > systemGet('maxCartons')) {
                $maxCartons = systemGet('maxCartons');
                $response = [
                    'success' => false,
                    'errors' => [
                        'cartons' => str_replace('{cartons}', $maxCartons, translate('only {cartons} cards can be played per game.'))
                    ]
                ];
                return $this->response->setJSON($response);
            }

            if ($cartons >= 1 && $cartons <= systemGet('maxCartons')) {

                if ($toGenerate <= 0) {
                    return $this->response->setJSON([
                        'status' => 'warning',
                        'message' => str_replace('{cartons}', $totalCartons, translate('you already have assigned {cartons} cartons or more.'))
                    ]);
                }

                $cartonData = [];
                
                for ($i = 0; $i < $toGenerate; $i++) {
                    $cartonData[] = [
                        'user' => $user['id'],
                        'game' => $game['id'],
                        'status' => 1
                    ];
                }
            
                $modelCartons->insertBatch($cartonData);
                $insertedCartons = $modelCartons->insertID(); 
                $cartonIds = range($insertedCartons, $insertedCartons + $toGenerate - 1);
            
                $numbersData = [];
                foreach ($cartonIds as $cartonId) {

                    $prefix = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

                    $cartonFormatted = str_pad($cartonId, 6, '0', STR_PAD_LEFT);

                    $serial = $cartonFormatted . $prefix;

                    $modelCartons->update($cartonId, ['serial' => $serial]);

                    $bColumn = range(1, 15);  
                    $iColumn = range(16, 30);
                    $nColumn = range(31, 45); 
                    $gColumn = range(46, 60); 
                    $oColumn = range(61, 75); 
            
                    shuffle($bColumn);
                    shuffle($iColumn);
                    shuffle($nColumn);
                    shuffle($gColumn);
                    shuffle($oColumn);
            
                    for ($pos = 0; $pos < 5; $pos++) {
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => $bColumn[$pos],
                            'position' => 1 + ($pos * 5),
                            'status' => 0
                        ];
                    }
            
                    for ($pos = 0; $pos < 5; $pos++) {
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => $iColumn[$pos],
                            'position' => 2 + ($pos * 5),
                            'status' => 0
                        ];
                    }
            
                    for ($pos = 0; $pos < 5; $pos++) {
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => $nColumn[$pos],
                            'position' => 3 + ($pos * 5),
                            'status' => 0
                        ];
                    }

                    for ($pos = 0; $pos < 5; $pos++) {
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => $gColumn[$pos],
                            'position' => 4 + ($pos * 5),
                            'status' => 0
                        ];
                    }
            
                    for ($pos = 0; $pos < 5; $pos++) {
                        $numbersData[] = [
                            'carton' => $cartonId,
                            'number' => $oColumn[$pos],
                            'position' => 5 + ($pos * 5),
                            'status' => 0
                        ];
                    }
                }
            
                $modelNumbersCartons->insertBatch($numbersData);

                wallet_deduct_purchase($user['id'], $toGenerate * $game['price']);
            }        
                
            $response = [
                'success' => true,
                'redirect' => site_url('/playing')
            ];
            
            return $this->response->setJSON($response);
        } elseif ($totalNumbersGenerated >= 75 || $SingsCount >= $AwardsCount) {
            $response = [
                'success' => false,
                'finished' => true,
                'redirect' => site_url('/playing')
            ];

            return $this->response->setJSON($response);
        } elseif ($totalNumbersGenerated > 0 && $totalNumbersGenerated < 75) {
            if ($totalCartons > 0) {
                $response = [
                    'success' => false,
                    'play' => true,
                    'redirect' => site_url('/playing')
                ];

                return $this->response->setJSON($response);
            } else {
                $response = [
                    'success' => false,
                    'initiated' => true
                ];

                return $this->response->setJSON($response);
            }
        } elseif ($totalNumbersGenerated == 75) {
             $response = [
                'success' => false,
                'finished' => true
            ];

            return $this->response->setJSON($response);
        }
    }

    public function playGame() {
        if (!session()->get('logged_in') || session()->get('group') != 0) {
            return redirect()->to('/signin');
        }
        
        $modelUsers = new UsersModel();
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $modelBoards = new BoardsModel();
        $modelAwards = new AwardsModel();
        $modelSings = new SingsModel();
        $modelDeposits = new DepositsModel();
        $modelTempCartons = new TempCartonsModel();

        $gameId = $this->request->getPost('game_id');
        $userId = session()->get('id');

        if (!$gameId) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => ['game' => translate('game is required')]
            ]);
        }

        $game = $modelGames->find($gameId);
        if (!$game) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => ['game' => translate('game not found')]
            ]);
        }

        session()->set('game_id', $gameId);

        $user = $modelUsers->getUserById($userId);
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => ['user' => translate('user not found')]
            ]);
        }

        // Obtener cartones seleccionados por el usuario
        $selectedCartons = $modelTempCartons->where('user', $userId)->where('game', $gameId)->findAll();

        // Validar que haya seleccionado al menos un cartón
        if (empty($selectedCartons)) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => ['cartons' => translate('you must select at least one carton')]
            ]);
        }

        $totalSelectedCartons = count($selectedCartons);

        $tempCartons = $modelTempCartons->where('user', $userId)->where('game', $gameId)->countAllResults();
        $totalCartons = $modelCartons->where('user', $userId)->where('game', $gameId)->countAllResults();

        $toGenerate = $tempCartons + $totalCartons;

        // Validar límite máximo de cartones
        $maxCartons = systemGet('maxCartons');
        if ($toGenerate > $maxCartons) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => ['cartons' => str_replace('{cartons}', $maxCartons, translate('only {cartons} cards can be played per game.'))]
            ]);
        }

        // Verificar si ya tiene cartones asignados en este juego
        /*$existingCartons = $modelCartons->where('user', $userId)->where('game', $gameId)->countAllResults();

        if ($existingCartons > 0) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => ['cartons' => translate('you already have cartons assigned to this game')]
            ]);
        }*/

        // Validar pagos mínimos
        $totalDeposits = $modelDeposits->where('user', $userId)->where('status', 2)->countAllResults();
        
        if ($totalDeposits == 0 && systemGet('activateMinimumDeposit') == 1) {
            return $this->response->setJSON([
                'success' => false,
                'amount' => systemGet('minimumDeposit'),
                'payments' => true
            ]);
        }

        // Validar tiempo de entrada (10 minutos antes)
        $gameDateTime = strtotime($game['date'] . ' ' . $game['time']);
        $now = time();
        $diff = $gameDateTime - $now;

        /*if ($diff > 600) {
            return $this->response->setJSON([
                'success' => false,
                'time' => true
            ]);
        }*/

        $totalCost = $totalSelectedCartons * $game['price'];

        $user = wallet_service()->normalizeUser($user);
        if (! wallet_service()->canAfford($user, $totalCost)) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => ['wallet' => translate('insufficient wallet balance')]
            ]);
        }

        $totalNumbersGenerated = $modelBoards->where('game', $gameId)->select('number')->distinct()->countAllResults();

        $singsCount = $modelSings->select('modality')->where('game', $gameId)->groupBy('modality')->countAllResults();

        $awardsCount = $modelAwards->where('game', $gameId)->where('status', 1)->countAllResults();

        if ($totalNumbersGenerated == 0) {
            
            $cartonIds = array_column($selectedCartons, 'carton');
            $unavailableCartons = $modelCartons->whereIn('id', $cartonIds)->where('user !=', 0)->findAll();

            if (!empty($unavailableCartons)) {
                return $this->response->setJSON([
                    'success' => false,
                    'errors' => ['cartons' => translate('some selected cartons are no longer available')]
                ]);
            }
            // Iniciar transacción
            $db = \Config\Database::connect();
            $db->transStart();

            try {
                foreach ($cartonIds as $cartonId) {
                    $modelCartons->update($cartonId, [
                        'user' => $userId,
                        'status' => 1
                    ]);
                }

                if ($totalCost > 0 && ! wallet_deduct_purchase($userId, $totalCost)) {
                    throw new \Exception(translate('insufficient wallet balance'));
                }

                $modelTempCartons->where('user', $userId)->where('game', $gameId)->delete();

                $db->transComplete();

                if ($db->transStatus() === false) {
                    throw new \Exception('Transaction failed');
                }

                $userAfter = wallet_service()->normalizeUser($modelUsers->find($userId));

                return $this->response->setJSON([
                    'success' => true,
                    'message' => translate('cartons assigned successfully'),
                    'redirect' => site_url('/playing'),
                    'cartons_assigned' => $totalSelectedCartons,
                    'total_cost' => $totalCost,
                    'new_balance' => wallet_total($userAfter)
                ]);

            } catch (\Exception $e) {
                $db->transRollback();
                
                return $this->response->setJSON([
                    'success' => false,
                    'errors' => ['general' => translate('error processing payment')]
                ]);
            }

        } elseif ($totalNumbersGenerated >= 75 || $singsCount >= $awardsCount) {
          
            return $this->response->setJSON([
                'success' => false,
                'finished' => true,
                'redirect' => site_url('/playing')
            ]);

        } elseif ($totalNumbersGenerated > 0 && $totalNumbersGenerated < 75) {

            $userCartonsInGame = $modelCartons->where('user', $userId)->where('game', $gameId)->countAllResults();

            if ($userCartonsInGame > 0) {
                return $this->response->setJSON([
                    'success' => false,
                    'play' => true,
                    'redirect' => site_url('/playing')
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'initiated' => true
                ]);
            }
        }
    }

    public function playCartonsGame() {
        if (!session()->get('logged_in') || session()->get('group') != 0) {
            return redirect()->to('/signin');
        }
        
        $modelUsers = new UsersModel();
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $modelBoards = new BoardsModel();
        $modelAwards = new AwardsModel();
        $modelSings = new SingsModel();
        $modelDeposits = new DepositsModel();
        $modelTempCartons = new TempCartonsModel();

        $gameId = $this->request->getPost('game_id');
        $userId = session()->get('id');

        if (!$gameId) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => ['game' => translate('game is required')]
            ]);
        }

        $game = $modelGames->find($gameId);
        if (!$game) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => ['game' => translate('game not found')]
            ]);
        }

        session()->set('game_id', $gameId);

        $user = $modelUsers->getUserById($userId);
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => ['user' => translate('user not found')]
            ]);
        }

        // Obtener cartones seleccionados por el usuario
        $selectedCartons = $modelTempCartons->where('user', $userId)->where('game', $gameId)->findAll();

        // Validar que haya seleccionado al menos un cartón
        if (empty($selectedCartons)) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => ['cartons' => translate('you must select at least one carton')]
            ]);
        }

        $totalSelectedCartons = count($selectedCartons);

        $tempCartons = $modelTempCartons->where('user', $userId)->where('game', $gameId)->countAllResults();
        $totalCartons = $modelCartons->where('user', $userId)->where('game', $gameId)->countAllResults();

        $toGenerate = $tempCartons + $totalCartons;

        // Validar límite máximo de cartones
        $maxCartons = systemGet('maxCartons');
        if ($toGenerate > $maxCartons) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => ['cartons' => str_replace('{cartons}', $maxCartons, translate('only {cartons} cards can be played per game.'))]
            ]);
        }

        // Verificar si ya tiene cartones asignados en este juego
        /*$existingCartons = $modelCartons->where('user', $userId)->where('game', $gameId)->countAllResults();

        if ($existingCartons > 0) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => ['cartons' => translate('you already have cartons assigned to this game')]
            ]);
        }*/

        // Validar pagos mínimos
        $totalDeposits = $modelDeposits->where('user', $userId)->where('status', 2)->countAllResults();
        
        if ($totalDeposits == 0 && systemGet('activateMinimumDeposit') == 1) {
            return $this->response->setJSON([
                'success' => false,
                'amount' => systemGet('minimumDeposit'),
                'payments' => true
            ]);
        }

        // Validar tiempo de entrada (10 minutos antes)
        $gameDateTime = strtotime($game['date'] . ' ' . $game['time']);
        $now = time();
        $diff = $gameDateTime - $now;

        /*if ($diff > 600) {
            return $this->response->setJSON([
                'success' => false,
                'time' => true
            ]);
        }*/

        $totalCost = $totalSelectedCartons * $game['price'];

        $user = wallet_service()->normalizeUser($user);
        if (! wallet_service()->canAfford($user, $totalCost)) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => ['wallet' => translate('insufficient wallet balance')]
            ]);
        }

        $totalNumbersGenerated = $modelBoards->where('game', $gameId)->select('number')->distinct()->countAllResults();

        $singsCount = $modelSings->select('modality')->where('game', $gameId)->groupBy('modality')->countAllResults();

        $awardsCount = $modelAwards->where('game', $gameId)->where('status', 1)->countAllResults();

        if ($totalNumbersGenerated == 0) {
            
            $cartonIds = array_column($selectedCartons, 'carton');
            $unavailableCartons = $modelCartons->whereIn('id', $cartonIds)->where('user !=', 0)->findAll();

            if (!empty($unavailableCartons)) {
                return $this->response->setJSON([
                    'success' => false,
                    'errors' => ['cartons' => translate('some selected cartons are no longer available')]
                ]);
            }
            // Iniciar transacción
            $db = \Config\Database::connect();
            $db->transStart();

            try {
                foreach ($cartonIds as $cartonId) {
                    $modelCartons->update($cartonId, [
                        'user' => $userId,
                        'status' => 1
                    ]);
                }

                if ($totalCost > 0 && ! wallet_deduct_purchase($userId, $totalCost)) {
                    throw new \Exception(translate('insufficient wallet balance'));
                }

                $modelTempCartons->where('user', $userId)->where('game', $gameId)->delete();

                $db->transComplete();

                if ($db->transStatus() === false) {
                    throw new \Exception('Transaction failed');
                }

                $userAfter = wallet_service()->normalizeUser($modelUsers->find($userId));

                return $this->response->setJSON([
                    'success' => true,
                    'message' => translate('cartons assigned successfully'),
                    'redirect' => site_url('/playing'),
                    'cartons_assigned' => $totalSelectedCartons,
                    'total_cost' => $totalCost,
                    'new_balance' => wallet_total($userAfter)
                ]);

            } catch (\Exception $e) {
                $db->transRollback();
                
                return $this->response->setJSON([
                    'success' => false,
                    'errors' => ['general' => translate('error processing payment')]
                ]);
            }

        } elseif ($totalNumbersGenerated >= 75 || $singsCount >= $awardsCount) {
          
            return $this->response->setJSON([
                'success' => false,
                'finished' => true,
                'redirect' => site_url('/playing')
            ]);

        } elseif ($totalNumbersGenerated > 0 && $totalNumbersGenerated < 75) {

            $userCartonsInGame = $modelCartons->where('user', $userId)->where('game', $gameId)->countAllResults();

            if ($userCartonsInGame > 0) {
                return $this->response->setJSON([
                    'success' => false,
                    'play' => true,
                    'redirect' => site_url('/playing')
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'initiated' => true
                ]);
            }
        }
    }

    public function numberGet() {
        if (!session()->get('logged_in') || session()->get('group') != 0) {
            return redirect()->to('/signin');
        }
        
        $modelUsers = new UsersModel();
        $modelBoards = new BoardsModel();
        $modelModalities = new ModalitiesModel();
        $modelGames = new GamesModel();
        $modelSings = new SingsModel();
        $modelAwards = new AwardsModel();

        $game = $modelGames->find(session()->get('game_id'));

        if (!$game) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('there are no active games')]);
        }

        $lastNumber = $modelBoards->where('game', $game['id'])->orderBy('created_at', 'DESC')->first();  

        if (!$lastNumber) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('there are no numbers drawn yet')]);
        }

        $totalNumbersGenerated = $modelBoards->where('game', $game['id'])->select('number')->distinct()->countAllResults();

        // Si salieron las 75 bolas, marcar todos los sings como notificados y retornar status completed
        if ($totalNumbersGenerated >= 75) {
            $modelSings->where('game', $game['id'])->where('status', 0)->set(['status' => 1])->update();
            
            return $this->response->setJSON([
                'status' => 'completed',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'message' => translate('the game has ended, all 75 numbers have already been generated'),
                'number' => $lastNumber['number'],
                'player' => '' // Asegurar que player esté vacío
            ]);
        }

        $currentUser = session()->get('id');

        // Buscar todos los sings recientes que no han sido notificados al usuario actual
        // Modificamos para obtener todos los sings pendientes, no solo el último
        $pendingSings = $modelSings->where('game', $game['id'])->orderBy('created_at', 'DESC')->findAll();
        
        // Verificar si hay sings pendientes de notificar al usuario actual
        foreach ($pendingSings as $sing) {
            $notified = json_decode($sing['notified'] ?? '[]', true);
            
            // Si el usuario actual no ha sido notificado de este sing Y no es el que cantó bingo
            if (!in_array($currentUser, $notified)) {
                $user = $modelUsers->find($sing['user']);
                $modality = $modelModalities->find($sing['modality']);
                
                $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');

                // Agregar usuario actual a la lista de notificados
                $notified[] = $currentUser;
                $modelSings->update($sing['id'], ['notified' => json_encode($notified)]);

                return $this->response->setJSON([
                    'status' => 'pause',
                    'totalNumbersGenerated' => $totalNumbersGenerated,
                    'message' => translate('a bingo has been called, pausing the game for 10 seconds'),
                    'iscron' => $lastNumber['isCRON'],
                    'number' => $lastNumber['number'],
                    'player' => $user['firstname'] . ' ' . $user['lastname'],
                    'modality' => translate($modality['name']),
                    'modalityId' => $modality['id'],
                    'image' => $imagePath
                ]);
            }
        }

        // Verificar si todos los premios han sido ganados
        $SingsCount = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();
        $AwardsCount = $modelAwards->where('game', $game['id'])->where('status', 1)->countAllResults();

        if ($SingsCount >= $AwardsCount) {
            return $this->response->setJSON([
                'status' => 'completed',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'message' => translate('the game is over, all the prizes have been awarded'),
                'number' => $lastNumber['number'],
                'player' => '' // Asegurar que player esté vacío
            ]);
        }

        // Respuesta normal cuando no hay bingos pendientes
        return $this->response->setJSON([
            'status' => 'success',
            'totalNumbersGenerated' => $totalNumbersGenerated,
            'message' => translate('last number'),
            'number' => $lastNumber['number']
        ]);
    }

    public function dialNumber() {
        if (!session()->get('logged_in') || session()->get('group') != 0) {
            return redirect()->to('/signin');
        }
    
        $number = $this->request->getPost('number');
    
        $modelBoards = new BoardsModel();
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
    
        $game = $modelGames->find(session()->get('game_id'));
    
        if (!$game) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('there are no active games')]);
        }
    
        $cartons = $modelCartons->getCartonsByUser(session()->get('id'), $game['id']);
    
        if (empty($cartons)) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('the user does not have cards')]);
        }
    
        $cartonIds = array_column($cartons, 'id');
    
        $existingNumbers = $modelNumbersCartons->getNumbersByUserAndGame(session()->get('id'), $game['id'], $number);
    
        if (empty($existingNumbers)) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('the number does not belong to your active cards for this game')]);
        }

        $numeroExistente = $modelBoards->getNumberByBoard($game['id'], $number);
    
        if (empty($numeroExistente)) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('the number has not been generated, it cannot be marked')]);
        }
    
        $db = \Config\Database::connect();
        $db->transStart();
    
        $ids = array_column($existingNumbers, 'id');
        $modelNumbersCartons->whereIn('id', $ids)->set(['status' => 1])->update();
    
        $db->transComplete();
    
        if ($db->transStatus() === FALSE) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('error updating numbers')]);
        }
    
        return $this->response->setJSON(['status' => 'success', 'message' => translate('number marked correctly on all cards')]);
    }

    public function singBingo() {
        if (!session()->get('logged_in') || session()->get('group') != 0) {
            return redirect()->to('/signin');
        }

        $modelUsers = new UsersModel();
        $modelBoards = new BoardsModel();
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $modelModalities = new ModalitiesModel();
        $modelAwards = new AwardsModel();
        $modelSings = new SingsModel();

        $game = $modelGames->find(session()->get('game_id'));
        if (!$game) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('there are no active games')]);
        }

        $cartons = $modelCartons->getCartonsByUser(session()->get('id'), $game['id']);
        if (empty($cartons)) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('the user does not have cards')]);
        }

        $modalities = $modelModalities->getModalitiesByIds(explode(',', $game['modalities']));
        if (empty($modalities)) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('there are no active modalities')]);
        }

        $lastBall = $modelBoards->where('game', $game['id'])->orderBy('created_at', 'DESC')->first();
        if (!$lastBall) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('no number has been generated')]);
        }

        $lastNumber = $modelNumbersCartons->getMarkedNumberByUserAndGame(session()->get('id'), $game['id'], $lastBall['number']);
        if (!$lastNumber) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => translate('you cant sing bingo, the last number is not marked on your card')
            ]);
        }

        $drawnNumbers = $modelBoards->getNumbersByBoard($game['id']);
        $drawnNumbersArray = array_column($drawnNumbers, 'number');

        $userSing = $modelUsers->find(session()->get('id'));
        $imagePath = !empty($userSing['image']) ? site_url('uploads/users/' . $userSing['image']) : site_url('assets/img/avatar.jpg');

        $lastValidNumber = end($drawnNumbersArray); 

        $singBingoOnlyLastBall = systemGet('singBingoOnlyLastBall');

        $bingoAchieved = false;
        $singUser = null;
        $modalitySing = null;

        foreach ($cartons as $carton) {
            foreach ($modalities as $modality) {
                $requiredPositions = explode(',', $modality['positions']);
                $matches = 0;
                $winningNumbers = [];

                if ($singBingoOnlyLastBall == 1) {
                    $singLastNumber = $modelSings->where('game', $game['id'])->where('modality', $modality['id'])->first();
                    if ($singLastNumber) {
                        if ($singLastNumber['lastnumber'] != $lastBall['number']) {
                            continue; 
                        }
                    }
                }

                $userAlreadySang = $modelSings->where('game', $game['id'])->where('modality', $modality['id'])->where('user', session()->get('id'))->countAllResults();

                if ($userAlreadySang > 0) {
                    continue; 
                }

                $markedNumbers = $modelNumbersCartons->getMarkedNumbersByCarton($carton['id']);
                $markedNumbersArray = array_column($markedNumbers, 'number');

                foreach ($markedNumbers as $markedNumber) {
                    if (in_array($markedNumber['position'], $requiredPositions) && in_array($markedNumber['number'], $drawnNumbersArray)) {
                        $matches++;
                        $winningNumbers[] = $markedNumber['number'];
                    }
                }

                if ($matches == count($requiredPositions)) {
                    if ($singBingoOnlyLastBall == 1) {
                        if (!in_array($lastValidNumber, $winningNumbers)) {
                            continue; 
                        }
                    }

                    $existingsings = $modelSings->where('game', $game['id'])->where('modality', $modality['id'])->countAllResults();

                    if ($existingsings < systemGet('numberSings')) { 
                        $data = [
                            'user' => session()->get('id'),
                            'game' => $game['id'],
                            'carton' => $carton['id'],
                            'modality' => $modality['id'],
                            'numbers' => implode(',', array_unique($winningNumbers)),
                            'lastnumber' => $lastBall['number'],
                            'status' => 1
                        ];

                        $modelSings->insert($data);
                        $id = $modelSings->insertID();

                        $modelNotifications = new NotificationsModel();

                        $currentUserId = session()->get('id');

                        $usersFromCartons = $modelCartons->select('user')->where('game', $game['id'])->where('user !=', $currentUserId)->groupBy('user')->findAll();

                        $cartonUserIds = array_column($usersFromCartons, 'user');

                        $admins = $modelUsers->select('id')->where('group', 1)->findAll();

                        $adminIds = array_column($admins, 'id');

                        $allUserIds = array_unique(array_merge($cartonUserIds, $adminIds));

                        $sings = $modelSings->where('game', $game['id'])->findAll();

                        $modalitySing = $modelModalities->find($modality['id']);

                        $singsByModality = [];
                        foreach ($sings as $sing) {
                            $singsByModality[$sing['modality']][] = $sing;
                        }

                        foreach ($allUserIds as $userId) {
                            $notificationData = [
                                'user' => $userId,
                                'from' => $currentUserId,
                                'type' => 'sing',
                                'game' => $game['id'],
                                'modality' => $data['modality'],
                                'title' => '🎉 ¡BINGO CANTADO!',
                                'message' => $userSing['firstname'] . ' ' . $userSing['lastname'] . ' ha cantado ¡BINGO! en la modalidad ' . translate($modalitySing['name']) . '.',
                            ];

                            $modelNotifications->insert($notificationData);
                        }

                        $bingoAchieved = true;

                        $singUser = $modelSings->find($id);
                    }
                }
            }
        }

        if ($bingoAchieved) {
            return $this->response->setJSON([
                'status' => 'success',
                'carton' => $singUser['carton'],
                'numbers' => explode(',', $singUser['numbers']),
                'player' => $userSing['firstname'] . ' ' . $userSing['lastname'],
                'modality' => translate($modalitySing['name']),
                'modalityId' => $modalitySing['id'],
                'image' => $imagePath
            ]);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => translate('you cant sing bingo, the pattern is not complete')]);
    }

    public function awardsGet() {
        if (!session()->get('logged_in') || session()->get('group') != 0) {
            return redirect()->to('/signin');
        }

        $modelGames = new GamesModel();
        $modelSings = new SingsModel();
        $modelUsers = new UsersModel();  
        $modelModalities = new ModalitiesModel(); 
        $modelAwards = new AwardsModel();

        $game = $modelGames->find(session()->get('game_id'));
        $data['game'] = $game;

        $modelCartons = new CartonsModel();
        $cartonsSold = $modelCartons->where('game', $game['id'])->where('user !=', 0)->countAllResults();
        $accumulated = $cartonsSold * $game['price'];
        $gameAccumulated = $accumulated - ($accumulated * systemGet('rateEarnings'));

        $sings = $modelSings->where('game', $game['id'])->findAll();

        $singsByModality = [];
        foreach ($sings as $sing) {
            $singsByModality[$sing['modality']][] = $sing;
        }

        foreach ($sings as &$sing) {
            $user = $modelUsers->find($sing['user']);
            $modality = $modelModalities->find($sing['modality']);
            $award = $modelAwards->where('game', $game['id'])->where('modality', $sing['modality'])->where('status', 1)->first();

            $sing['user_name'] = $user ? $user['firstname'] . ' ' . $user['lastname'] : translate('user not found');
            $sing['modality_name'] = $modality ? translate($modality['name']) : translate('modality not found');

            $singsCount = count($singsByModality[$sing['modality']]);

            if ($award) {
                if ($game['award'] == 2) {
                    $prize = (float) $award['amount'];
                } else {
                    $prize = $gameAccumulated * (float) $award['amount'] / 100;
                }
                $sing['award_amount'] = number_format($prize / max(1, $singsCount), 2);
            } else {
                $sing['award_amount'] = translate('amount not available');
            }

            if ($sing['status'] == 1) {
                $sing['status'] = '<span class="badge bg-warning text-muted">' . translate('EARRING') . '</span>';
            } elseif ($sing['status'] == 2) {
                $sing['status'] = '<span class="badge bg-success">' . translate('PAID') . '</span>';
            }
        }

        $data['sings'] = $sings;

        return view('playings/awards', $data);
    }

    public function messageSubmit() {
        $modelGames = new GamesModel();
        $modelMessages = new MessagesModel();

        $game = $modelGames->find(session()->get('game_id'));

        $message = $this->request->getPost('message');
        
        if (!$game) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('there are no active games')]);
        }
    
        $data = [
            'user' => session()->get('id'),
            'game' => $game['id'],
            'message' => $message,
            'status' => 1
        ];
    
        $insertId = $modelMessages->insert($data, true);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => translate('message sent'),
            'id' => $insertId,
        ]);
    }

    public function messageGet() {
        $modelMessages = new MessagesModel();
        $modelUsers = new UsersModel();
        $modelGames = new GamesModel();

        $game = $modelGames->find(session()->get('game_id'));
        if (!$game) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'stop', 'message' => translate('there are no active games')
            ]);
        }

        $afterId = (int) ($this->request->getGet('after_id') ?? 0);
        $currentUserId = session()->get('id');

        $builder = $modelMessages
            ->where('game', $game['id'])
            ->where('user !=', $currentUserId);

        if ($afterId > 0) {
            $builder->where('id >', $afterId);
        }

        $rows = $builder->orderBy('id', 'ASC')->limit(30)->findAll();

        if (empty($rows)) {
            return $this->response->setJSON([
                'status' => 'empty',
                'messages' => [],
            ]);
        }

        $messages = [];
        foreach ($rows as $row) {
            $user = $modelUsers->find($row['user']);
            $messages[] = [
                'id' => (int) $row['id'],
                'message' => $row['message'],
                'user' => (int) $row['user'],
                'game' => (int) $row['game'],
                'created_at' => $row['created_at'] ?? null,
                'image' => !empty($user['image'])
                    ? site_url('uploads/users/' . $user['image'])
                    : site_url('assets/img/avatar.jpg'),
            ];
        }

        $last = $messages[count($messages) - 1];

        return $this->response->setJSON([
            'status' => 'success',
            'messages' => $messages,
            'message' => $last,
            'image' => $last['image'],
        ]);
    }

    public function volumeSubmit() {
        $modelUsers = new UsersModel();

        $user = $modelUsers->getUserById(session()->get('id'));

        if ($user['sounds'] == 1) {
            $data['sounds'] = 0;
        } else {
            $data['sounds'] = 1;
        }

        $modelUsers->update(session()->get('id'), $data);     

        return $this->response->setJSON(['status' => 'success']);
    }

    public function microphoneSubmit() {
        $modelUsers = new UsersModel();

        $user = $modelUsers->getUserById(session()->get('id'));

        if ($user['narration'] == 1) {
            $data['narration'] = 0;
        } else {
            $data['narration'] = 1;
        }

        $modelUsers->update(session()->get('id'), $data);     

        return $this->response->setJSON(['status' => 'success']);
    }

    public function checkSubmit() {
        $modelUsers = new UsersModel();

        $user = $modelUsers->getUserById(session()->get('id'));

        if ($user['autodial'] == 1) {
            $data['autodial'] = 0;
        } else {
            $data['autodial'] = 1;
        }

        $modelUsers->update(session()->get('id'), $data);     

        return $this->response->setJSON(['status' => 'success']);
    }
}