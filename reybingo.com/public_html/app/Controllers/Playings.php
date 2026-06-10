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
        helper(['form', 'url', 'cookie', 'text', 'wallet', 'bingo']);
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
            $drawnNumbers = $this->syncSessionUserDrawnMarks((int) $gameId);

            return $this->response->setJSON([
                'success' => true,
                'message' => translate('cartons assigned successfully'),
                'redirect_url' => base_url('playing'),
                'cartons_assigned' => $totalSelectedCartons,
                'total_cost' => $totalCost,
                'new_balance' => wallet_total($userAfter),
                'carton_ids' => $savedCartonIds,
                'drawnNumbers' => $drawnNumbers,
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

        $drawnNumbersOrdered = $this->getOrderedDrawnNumbers((int) $game['id']);
        $totalNumbersGenerated = count($drawnNumbersOrdered);
        $selectedNumbers = $drawnNumbersOrdered;

        $singsCountFinished = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();
        $awardsCountFinished = $modelAwards->where('game', $game['id'])->where('status', 1)->countAllResults();
        $gameIsFinished = ($totalNumbersGenerated >= 75)
            || ($awardsCountFinished > 0 && $singsCountFinished >= $awardsCountFinished);
    
        $cartons = $this->getActivePlayingCartons($modelCartons, (int) session()->get('id'), $game);
    
        if (empty($cartons)) {
            return redirect()->to('/play');
        }

        $modalities = $modelModalities->whereIn('id', explode(',', $game['modalities']))->findAll();

        foreach ($modalities as &$modality) { 
            $award = $modelAwards->where('game', $game['id'])->where('modality', $modality['id'])->where('status', 1)->first();

            $modality['amount'] = $award['amount'] ?? 0; 
        }

        $user = $modelUsers->find(session()->get('id'));

        if (($user['autodial'] ?? 0) != 1) {
            $modelUsers->update((int) session()->get('id'), ['autodial' => 1]);
            $user['autodial'] = 1;
        }

        if (! empty($drawnNumbersOrdered)) {
            $this->syncAutoDialMarks((int) session()->get('id'), (int) $game['id'], $drawnNumbersOrdered);
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
                $drawnNumbers = $this->syncSessionUserDrawnMarks((int) $gameId);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => translate('cartons assigned successfully'),
                    'redirect' => site_url('/playing'),
                    'cartons_assigned' => $totalSelectedCartons,
                    'total_cost' => $totalCost,
                    'new_balance' => wallet_total($userAfter),
                    'drawnNumbers' => $drawnNumbers,
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
                $drawnNumbers = $this->syncSessionUserDrawnMarks((int) $gameId);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => translate('cartons assigned successfully'),
                    'redirect' => site_url('/playing'),
                    'cartons_assigned' => $totalSelectedCartons,
                    'total_cost' => $totalCost,
                    'new_balance' => wallet_total($userAfter),
                    'drawnNumbers' => $drawnNumbers,
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

        if ((int) ($game['status'] ?? 0) !== 1) {
            bingo_ensure_winners_registered((int) $game['id']);

            return $this->response->setJSON([
                'status' => 'completed',
                'totalNumbersGenerated' => bingo_count_drawn_numbers((int) $game['id']),
                'drawnNumbers' => $this->getOrderedDrawnNumbers((int) $game['id']),
                'winners' => $this->getWinnersForGame((int) $game['id'], true),
                'message' => translate('the game is over, all the prizes have been awarded'),
                'number' => '',
                'player' => '',
            ]);
        }

        $lastNumber = $modelBoards->where('game', $game['id'])->orderBy('created_at', 'DESC')->first();

        if (!$lastNumber) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('there are no numbers drawn yet')]);
        }

        $drawnNumbers = $this->getOrderedDrawnNumbers((int) $game['id']);
        $totalNumbersGenerated = count($drawnNumbers);

        $user = $modelUsers->find(session()->get('id'));
        if (! empty($drawnNumbers)) {
            $this->syncAutoDialMarks((int) session()->get('id'), (int) $game['id'], $drawnNumbers);
        }

        // Si salieron las 75 bolas, resolver bingos no cantados, pagar premios y finalizar
        if ($totalNumbersGenerated >= 75) {
            bingo_ensure_winners_registered((int) $game['id']);
            $winners = $this->getWinnersForGame((int) $game['id']);

            return $this->response->setJSON([
                'status' => 'completed',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'drawnNumbers' => $drawnNumbers,
                'winners' => $winners,
                'autodial' => (int) ($user['autodial'] ?? 0),
                'message' => translate('the game has ended, all 75 numbers have already been generated'),
                'number' => $lastNumber['number'],
                'player' => ''
            ]);
        }

        $currentUser = (int) session()->get('id');
        $gameHasWinner = $modelSings->where('game', $game['id'])->countAllResults() > 0;

        // Buscar sings pendientes de notificar a otros jugadores (no al ganador)
        $pendingSings = $modelSings->where('game', $game['id'])->orderBy('created_at', 'DESC')->findAll();
        
        foreach ($pendingSings as $sing) {
            $notified = json_decode($sing['notified'] ?? '[]', true);
            if (! is_array($notified)) {
                $notified = [];
            }

            if (in_array($currentUser, $notified, true)) {
                continue;
            }

            // El ganador ya vio su BINGO al cantar; solo marcarlo como notificado
            if ((int) $sing['user'] === $currentUser) {
                $notified[] = $currentUser;
                $modelSings->update($sing['id'], ['notified' => json_encode(array_values($notified))]);
                continue;
            }

            $singUser = $modelUsers->find($sing['user']);
            $modality = $modelModalities->find($sing['modality']);
            $imagePath = !empty($singUser['image']) ? site_url('uploads/users/' . $singUser['image']) : site_url('assets/img/avatar.jpg');

            $notified[] = $currentUser;
            $modelSings->update($sing['id'], ['notified' => json_encode(array_values($notified))]);

            $game = $modelGames->find($game['id']);
            $gameCompleted = (int) ($game['status'] ?? 0) !== 1;

            return $this->response->setJSON([
                'status' => 'pause',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'drawnNumbers' => $drawnNumbers,
                'winners' => $this->getWinnersForGame((int) $game['id'], true),
                'autodial' => (int) ($user['autodial'] ?? 0),
                'message' => translate('a bingo has been called, pausing the game for 10 seconds'),
                'iscron' => $lastNumber['isCRON'],
                'number' => $lastNumber['number'],
                'player' => $singUser['firstname'] . ' ' . $singUser['lastname'],
                'modality' => translate($modality['name']),
                'modalityId' => $modality['id'],
                'image' => $imagePath,
                'isOwnBingo' => false,
                'winnerUserId' => (int) $sing['user'],
                'gameHasWinner' => true,
                'gameCompleted' => $gameCompleted,
                'currentUserId' => $currentUser,
            ]);
        }

        // Verificar si todos los premios han sido ganados
        $SingsCount = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();
        $AwardsCount = $modelAwards->where('game', $game['id'])->where('status', 1)->countAllResults();

        if ($SingsCount >= $AwardsCount) {
            bingo_ensure_winners_registered((int) $game['id']);
            $winners = $this->getWinnersForGame((int) $game['id']);

            return $this->response->setJSON([
                'status' => 'completed',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'drawnNumbers' => $drawnNumbers,
                'winners' => $winners,
                'autodial' => (int) ($user['autodial'] ?? 0),
                'message' => translate('the game is over, all the prizes have been awarded'),
                'number' => $lastNumber['number'],
                'player' => ''
            ]);
        }

        // Respuesta normal cuando no hay bingos pendientes
        return $this->response->setJSON([
            'status' => 'success',
            'totalNumbersGenerated' => $totalNumbersGenerated,
            'drawnNumbers' => $drawnNumbers,
            'autodial' => (int) ($user['autodial'] ?? 0),
            'message' => translate('last number'),
            'number' => $lastNumber['number'],
            'gameHasWinner' => $gameHasWinner,
            'currentUserId' => $currentUser,
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
    
        $cartons = $this->getActivePlayingCartons($modelCartons, (int) session()->get('id'), $game);
    
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

        $cartons = $this->getActivePlayingCartons($modelCartons, (int) session()->get('id'), $game);
        if (empty($cartons)) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('the user does not have cards')]);
        }

        $modalities = bingo_get_game_modalities($game);
        if (empty($modalities)) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('there are no active modalities')]);
        }

        $lastBall = $modelBoards->where('game', $game['id'])->orderBy('created_at', 'DESC')->first();
        if (!$lastBall) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('no number has been generated')]);
        }

        $drawnNumbersArray = bingo_get_ordered_drawn_numbers((int) $game['id']);

        $userSing = $modelUsers->find(session()->get('id'));

        if (! empty($drawnNumbersArray)) {
            $this->syncAutoDialMarks((int) session()->get('id'), (int) $game['id'], $drawnNumbersArray);
        }

        $lastBallOnCards = $modelNumbersCartons->getNumbersByUserAndGame(
            session()->get('id'),
            $game['id'],
            $lastBall['number']
        );

        if (! empty($lastBallOnCards)) {
            $lastNumber = $modelNumbersCartons->getMarkedNumberByUserAndGame(
                session()->get('id'),
                $game['id'],
                $lastBall['number']
            );

            if (! $lastNumber) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => translate('you cant sing bingo, the last number is not marked on your card')
                ]);
            }
        }
        $imagePath = !empty($userSing['image']) ? site_url('uploads/users/' . $userSing['image']) : site_url('assets/img/avatar.jpg');

        $lastValidNumber = end($drawnNumbersArray); 

        $singBingoOnlyLastBall = systemGet('singBingoOnlyLastBall');

        $bingoAchieved = false;
        $singUser = null;
        $modalitySing = null;

        foreach ($cartons as $carton) {
            foreach ($modalities as $modality) {
                $numberSingsLimit = bingo_get_number_sings_limit();
                $modalityWinners = $modelSings
                    ->where('game', $game['id'])
                    ->where('modality', $modality['id'])
                    ->countAllResults();

                if ($modalityWinners >= $numberSingsLimit) {
                    continue;
                }

                $requiredPositions = explode(',', (string) $modality['positions']);
                $matchResult = null;

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

                $cartonNumbers = $modelNumbersCartons
                    ->where('carton', $carton['id'])
                    ->orderBy('position', 'ASC')
                    ->findAll();
                $matchResult = $this->getModalityMatchResult($requiredPositions, $cartonNumbers, $drawnNumbersArray);

                if ($matchResult['complete']) {
                    $winningNumbers = $matchResult['winningNumbers'];

                    if ($singBingoOnlyLastBall == 1) {
                        if (!in_array((int) $lastValidNumber, $winningNumbers, true)) {
                            continue; 
                        }
                    }

                    $registered = bingo_register_sing_if_missing(
                        (int) $game['id'],
                        (int) session()->get('id'),
                        (int) $carton['id'],
                        $modality,
                        $winningNumbers,
                        (int) $lastBall['number'],
                        false
                    );

                    if (! $registered) {
                        continue;
                    }

                    $id = $modelSings
                        ->where('game', $game['id'])
                        ->where('modality', $modality['id'])
                        ->where('user', session()->get('id'))
                        ->orderBy('id', 'DESC')
                        ->first()['id'] ?? null;

                    if (! $id) {
                        continue;
                    }

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
                            'modality' => $modality['id'],
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

        if ($bingoAchieved) {
            $currentUserId = (int) session()->get('id');
            $modelSings->update($singUser['id'], [
                'status' => 1,
                'notified' => json_encode([$currentUserId]),
            ]);
            $gameCompleted = bingo_finalize_game_when_complete((int) $game['id']);

            return $this->response->setJSON([
                'status' => 'success',
                'carton' => $singUser['carton'],
                'numbers' => explode(',', $singUser['numbers']),
                'player' => $userSing['firstname'] . ' ' . $userSing['lastname'],
                'modality' => translate($modalitySing['name']),
                'modalityId' => $modalitySing['id'],
                'image' => $imagePath,
                'gameCompleted' => $gameCompleted,
                'winners' => $gameCompleted ? $this->getWinnersForGame((int) $game['id'], true) : [],
                'isOwnBingo' => true,
                'winnerUserId' => $currentUserId,
                'gameHasWinner' => true,
            ]);
        }

        $existingWinner = $modelSings
            ->where('game', $game['id'])
            ->countAllResults();

        if ($existingWinner > 0) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Otro jugador ya ganó este premio.',
                'gameHasWinner' => true,
            ]);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => translate('you cant sing bingo, the pattern is not complete')]);
    }

    public function ensureWinnersRegistered(int $gameId): void
    {
        bingo_ensure_winners_registered($gameId);
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
        $modelCartons = new CartonsModel();

        $game = $modelGames->find(session()->get('game_id'));
        $data['game'] = $game;

        if (! $game) {
            $data['sings'] = [];
            return view('playings/awards', $data);
        }

        $this->ensureWinnersRegistered((int) $game['id']);

        $cartonsSold = $modelCartons->where('game', $game['id'])->where('user !=', 0)->countAllResults();
        $accumulated = $cartonsSold * $game['price'];
        $gameAccumulated = $accumulated - ($accumulated * systemGet('rateEarnings'));

        $sings = bingo_get_official_sings_for_game((int) $game['id'], true);

        $singsByModality = [];
        foreach ($sings as $sing) {
            $singsByModality[$sing['modality']][] = $sing;
        }

        foreach ($sings as &$sing) {
            $user = $modelUsers->find($sing['user']);
            $modality = $modelModalities->find($sing['modality']);
            $award = $modelAwards->where('game', $game['id'])->where('modality', $sing['modality'])->where('status', 1)->first();
            $carton = $modelCartons->where('id', $sing['carton'])->first();

            $sing['serial'] = $carton ? $carton['serial'] : translate('serial not found');
            $sing['user_code'] = $user ? $user['code'] : translate('code not found');
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

            $sing['status_raw'] = (int) ($sing['status'] ?? 0);

            if ($sing['status_raw'] === 1) {
                $sing['status'] = '<span class="badge bg-warning text-muted">' . translate('EARRING') . '</span>';
            } elseif ($sing['status_raw'] === 2) {
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

        // Admin (group = 1) puede enviar cualquier mensaje; jugadores (group = 0) están restringidos
        $isAdmin = (int) session()->get('group') === 1;

        if (!$isAdmin && !in_array((int) $game['type'], [3, 4], true)) {
            $allowedReactions = [
                '¡Oe, me falta solo una! 😱', '¡Bravo, salió mi número! 🥳', '¡Este premio es mío! 🤑', '¡Suerte para todos! 🍀', '¡Mi Rey, Bingo! 👑',
                '🥳', '🎉', '😎', '🍀', '🤑', '🌟', '😡', '🔥', '👑', '💵'
            ];
            if (!in_array($message, $allowedReactions, true)) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Solo se permiten reacciones predeterminadas en este modo.']);
            }
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

        // Remove restriction so all game types can get chat messages
        $afterId = (int) ($this->request->getGet('after_id') ?? 0);
        $currentUserId = (int) session()->get('id');

        $builder = $modelMessages
            ->where('game', $game['id'])
            ->where('status', 1)
            ->where('user !=', $currentUserId);

        if ($afterId > 0) {
            $builder->where('id >', $afterId);
        }

        $rows = $builder->orderBy('id', 'ASC')->limit(50)->findAll();

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
            'currentUserId' => (int) session()->get('id'),
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
        $modelGames = new GamesModel();
        $modelBoards = new BoardsModel();

        $user = $modelUsers->getUserById(session()->get('id'));

        $newAutodial = ($user['autodial'] ?? 0) == 1 ? 0 : 1;

        $modelUsers->update(session()->get('id'), ['autodial' => $newAutodial]);

        $response = [
            'status'   => 'success',
            'autodial' => $newAutodial,
        ];

        if ($newAutodial == 1) {
            $game = $modelGames->find(session()->get('game_id'));

            if ($game) {
                $drawnNumbers = $this->getOrderedDrawnNumbers((int) $game['id']);

                if (! empty($drawnNumbers)) {
                    $this->syncAutoDialMarks((int) session()->get('id'), (int) $game['id'], $drawnNumbers);
                }

                $response['drawnNumbers'] = $drawnNumbers;
            }
        }

        return $this->response->setJSON($response);
    }

    private function getOrderedDrawnNumbers(int $gameId): array
    {
        return bingo_get_ordered_drawn_numbers($gameId);
    }

    private function syncSessionUserDrawnMarks(int $gameId): array
    {
        $drawnNumbers = $this->getOrderedDrawnNumbers($gameId);

        if (! empty($drawnNumbers)) {
            $this->syncAutoDialMarks((int) session()->get('id'), $gameId, $drawnNumbers);
        }

        return $drawnNumbers;
    }

    private function syncAutoDialMarks(int $userId, int $gameId, array $drawnNumbers): void
    {
        bingo_sync_drawn_marks_for_user($userId, $gameId, $drawnNumbers);
    }

    private function getModalityMatchResult(array $requiredPositions, array $cartonNumbers, array $drawnNumbersArray): array
    {
        return bingo_get_modality_match_result($requiredPositions, $cartonNumbers, $drawnNumbersArray);
    }

    public function winnersGet()
    {
        if (! session()->get('logged_in') || session()->get('group') != 0) {
            return redirect()->to('/signin');
        }

        $modelGames = new GamesModel();
        $game = $modelGames->find(session()->get('game_id'));

        if (! $game) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('there are no active games')]);
        }

        $winners = $this->getWinnersForGame((int) $game['id'], true);

        if (empty($winners)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => translate('there are no winners yet'),
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'winners' => $winners,
        ]);
    }

    private function getWinnersForGame(int $gameId, bool $includePending = false): array
    {
        $modelUsers = new UsersModel();
        $modelModalities = new ModalitiesModel();

        $rows = bingo_get_official_sings_for_game($gameId, $includePending);
        $winners = [];

        foreach ($rows as $row) {
            $user = $modelUsers->find($row['user']);
            $wmodality = $modelModalities->find($row['modality']);

            $winners[] = [
                'player' => trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')),
                'modality' => translate($wmodality['name'] ?? ''),
                'modalityId' => (int) ($row['modality'] ?? 0),
                'image' => ! empty($user['image'])
                    ? site_url('uploads/users/' . $user['image'])
                    : site_url('assets/img/avatar.jpg'),
            ];
        }

        return $winners;
    }

    private function resolveMissedBingosForGame(int $gameId, bool $finalize = false): void
    {
        bingo_resolve_missed_bingos_for_game($gameId, $finalize);
    }

    private function getActivePlayingCartons(CartonsModel $modelCartons, int $userId, array $game): array
    {
        $cartons = $modelCartons->getCartonsByUser($userId, (int) $game['id']);

        if (empty($cartons)) {
            return [];
        }

        // En partida normal (sin transmisión en vivo) se juega con un solo cartón.
        if (! in_array((int) ($game['type'] ?? 0), [3, 4], true)) {
            return array_slice($cartons, 0, 1);
        }

        return $cartons;
    }
}