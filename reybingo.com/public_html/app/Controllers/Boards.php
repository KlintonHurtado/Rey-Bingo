<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\BoardsModel;
use App\Models\GamesModel;
use App\Models\GameRoomsModel;
use App\Models\CartonsModel;
use App\Models\NumbersCartonsModel;
use App\Models\ModalitiesModel;
use App\Models\SingsModel;
use App\Models\AwardsModel;
use App\Models\ContactsModel;
use App\Models\NotificationsModel;
use CodeIgniter\Controller;

class Boards extends Controller {
    public function __construct() {
        helper(['form', 'url', 'cookie', 'text', 'bingo']);
        session();
    }
    
    public function index() {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }
        
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
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
    
        $cartonData = [];
        foreach ($cartons as $carton) {
            $numbers = $modelNumbersCartons->where('carton', $carton['id'])->orderBy('position', 'ASC')->findAll();

            $cartonData[] = [
                'cartonId' => $carton['id'],
                'numbers' => $numbers
            ];
        }
    
        $data = [
            'page' => [
                'title' => translate('list of') . ' ' . translate('games')
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('boards/index', ['contacts' => $contacts, 'cartons' => $cartonData]) 
        ];
    
        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }
    
    public function board() {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        if (!session()->get('game_id')) {
            return redirect()->to('/games');
        }
        
        $modelUsers = new UsersModel();
        $model = new BoardsModel();
        $modelGames = new GamesModel();
        $modelModalities = new ModalitiesModel();
        $modelAwards = new AwardsModel();
        $modelSings = new SingsModel();
        $modelContacts = new ContactsModel();

        $contacts = $modelContacts->findAll();
        
        $game = $modelGames->find(session()->get('game_id'));
    
        if (!$game) {
            return redirect()->to('/games');
        }

        $status = 'start';

        $totalNumbersGenerated = $model->where('game', $game['id'])->countAllResults();

        if ($totalNumbersGenerated >= 75) {
            $status = 'stop';
        }

        $SingsCount = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();

        $AwardsCount = $modelAwards->where('game', $game['id'])->where('status', 1)->countAllResults();

        if ($SingsCount >= $AwardsCount) {
            $status = 'stop';
        }

        $modalities = $modelModalities->whereIn('id', explode(',', $game['modalities']))->findAll();

        foreach ($modalities as &$modality) { 
            $award = $modelAwards->where('game', $game['id'])->where('modality', $modality['id'])->where('status', 1)->first();

            $modality['amount'] = $award['amount'] ?? 0;
        }

        $drawnNumbersOrdered = $model->where('game', $game['id'])->where('status', 1)->orderBy('created_at', 'ASC')->findAll();
        $drawnNumbersOrdered = array_values(array_map('intval', array_column($drawnNumbersOrdered, 'number')));
        $selectedNumbers = $drawnNumbersOrdered;

        $lastNumber = $model->where('game', $game['id'])->where('status', 1)->orderBy('created_at', 'DESC')->first();  

        $fourNumbers = $model->where('game', $game['id'])->where('status', 1)->orderBy('created_at', 'DESC')->limit(5)->findAll();
        array_shift($fourNumbers);
        $fourNumbers = array_reverse(array_column($fourNumbers, 'number'));

        $fiveNumbers = $model->where('game', $game['id'])->where('status', 1)->orderBy('created_at', 'DESC')->limit(5)->findAll();
        $fiveNumbers = array_reverse(array_column($fiveNumbers, 'number'));

        $singsModalities = $modelSings->where('game', $game['id'])->findAll();
        $singsModalities = array_column($singsModalities, 'modality');

        $winners = bingo_get_official_sings_for_game((int) $game['id'], true);
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

        $user = $modelUsers->find(session()->get('id'));

        $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');
            
        $data = [
            'page' => [
                'title' => $game['description']
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('boards/board', ['contacts' => $contacts, 'user' => $user, 'status' => $status, 'game' => $game, 'selectedNumbers' => $selectedNumbers, 'drawnNumbersOrdered' => $drawnNumbersOrdered, 'singsModalities' => $singsModalities, 'lastNumber' => $lastNumber['number'] ?? '', 'fourNumbers' => $fourNumbers, 'lastNumbersJson' => json_encode($fiveNumbers), 'getClass' => $getClass, 'modalities' => $modalities, 'winners' => $winners, 'totalNumbersGenerated' => $totalNumbersGenerated, 'imagePath' => $imagePath])
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }

    public function live() {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        if (!session()->get('game_id')) {
            return redirect()->to('/games');
        }
        
        $modelUsers = new UsersModel();
        $model = new BoardsModel();
        $modelGames = new GamesModel();
        $modelModalities = new ModalitiesModel();
        $modelAwards = new AwardsModel();
        $modelSings = new SingsModel();
        $modelContacts = new ContactsModel();

        $contacts = $modelContacts->findAll();
        
        $game = $modelGames->find(session()->get('game_id'));
    
        if (!$game) {
            return redirect()->to('/games');
        }

        $status = 'start';

        $totalNumbersGenerated = $model->where('game', $game['id'])->countAllResults();

        if ($totalNumbersGenerated >= 75) {
            $status = 'stop';
        }

        $SingsCount = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();

        $AwardsCount = $modelAwards->where('game', $game['id'])->where('status', 1)->countAllResults();

        if ($SingsCount >= $AwardsCount) {
            $status = 'stop';
        }

        $modalities = $modelModalities->whereIn('id', explode(',', $game['modalities']))->findAll();

        foreach ($modalities as &$modality) { 
            $award = $modelAwards->where('game', $game['id'])->where('modality', $modality['id'])->where('status', 1)->first();

            $modality['amount'] = $award['amount'] ?? 0;
        }

        $selectedNumbers = $model->where('game', $game['id'])->where('status', 1)->findAll();
        $selectedNumbers = array_column($selectedNumbers, 'number');

        $lastNumber = $model->where('game', $game['id'])->where('status', 1)->orderBy('created_at', 'DESC')->first();  

        $fourNumbers = $model->where('game', $game['id'])->where('status', 1)->orderBy('created_at', 'DESC')->limit(5)->findAll();
        array_shift($fourNumbers);
        $fourNumbers = array_reverse(array_column($fourNumbers, 'number'));

        $fiveNumbers = $model->where('game', $game['id'])->where('status', 1)->orderBy('created_at', 'DESC')->limit(5)->findAll();
        $fiveNumbers = array_reverse(array_column($fiveNumbers, 'number'));

        $singsModalities = $modelSings->where('game', $game['id'])->findAll();
        $singsModalities = array_column($singsModalities, 'modality');

        $winners = bingo_get_official_sings_for_game((int) $game['id'], true);
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

        $user = $modelUsers->find(session()->get('id'));

        $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');
            
        $data = [
            'page' => [
                'title' => $game['description']
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('boards/live', ['contacts' => $contacts, 'user' => $user, 'status' => $status, 'game' => $game, 'selectedNumbers' => $selectedNumbers, 'singsModalities' => $singsModalities, 'lastNumber' => $lastNumber['number'] ?? '', 'fourNumbers' => $fourNumbers, 'lastNumbersJson' => json_encode($fiveNumbers), 'getClass' => $getClass, 'modalities' => $modalities, 'winners' => $winners, 'totalNumbersGenerated' => $totalNumbersGenerated, 'imagePath' => $imagePath])
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }

    public function numberAutoSubmit() {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        $modelUsers = new UsersModel();
        $model = new BoardsModel();
        $modelModalities = new ModalitiesModel();
        $modelGames = new GamesModel();
        $modelSings = new SingsModel();
        $modelAwards = new AwardsModel();

        $game = $modelGames->find(session()->get('game_id'));

        if (!$game) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('game not found')]);
        }

        $totalNumbersGenerated = $model->where('game', $game['id'])->select('number')->distinct()->countAllResults();

        if ($totalNumbersGenerated === 0 && !bingo_can_start_game($game)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => bingo_min_players_start_message($game),
            ]);
        }

        $lastBall = $model->where('game', $game['id'])->orderBy('created_at', 'DESC')->first();

        if ($totalNumbersGenerated >= 75) {
            $modelGames->where('game', $game['id'])->where('status', 0)->set(['status' => 1])->update();
            return $this->response->setJSON([
                'status' => 'completed',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'message' => translate('the game has ended, all 75 numbers have already been generated'),
                'number' => $lastBall['number']
            ]);
        }

        $SingsCount = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();

        $AwardsCount = $modelAwards->where('game', $game['id'])->where('status', 1)->countAllResults();

        if ($SingsCount >= $AwardsCount) {
            $modelGames->where('game', $game['id'])->where('status', 0)->set(['status' => 1])->update();
            return $this->response->setJSON([
                'status' => 'completed',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'message' => translate('the game is over, all the prizes have been awarded'),
                'number' => $lastBall['number']
            ]);
        }

        $lastSings = $modelSings->where('game', $game['id'])->where('status', 0)->findAll();

        $winners = bingo_get_official_sings_for_game((int) $game['id'], true);
        foreach ($winners as &$winner) {
            $user = $modelUsers->find($winner['user']);
            $wmodality = $modelModalities->find($winner['modality']);

            $winner['player'] = $user['firstname'] . ' ' . $user['lastname'];
            $winner['modality'] = translate($wmodality['name']);
        }

        if ($lastBall && !empty($lastSings)) {
            foreach ($lastSings as $sing) {
                $user = $modelUsers->find($sing['user']);
                $modality = $modelModalities->find($sing['modality']);

                $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');

                $lastBallTime = strtotime($lastBall['created_at']);
                $lastSingTime = strtotime($sing['created_at']);
                $timeDifference = $lastSingTime - $lastBallTime;

                if ($timeDifference <= 10) {
                    $modelSings->where('game', $game['id'])->where('status', 0)->set(['status' => 1])->update();

                    return $this->response->setJSON([
                        'status' => 'pause',
                        'totalNumbersGenerated' => $totalNumbersGenerated,
                        'winners' => $winners,
                        'message' => 'Se ha cantado un bingo. Pausando el juego por 10 segundos.',
                        'player' => $user['firstname'] . ' ' . $user['lastname'],
                        'modality' => translate($modality['name']),
                        'modalityId' => $modality['id'],
                        'image' => $imagePath
                    ]);
                }
            }
        }

        $number = $this->generateUniqueNumber();

        $data = [
            'user' => session()->get('id'),
            'game' => $game['id'],
            'number' => $number,
            'status' => 1
        ];

        $model->insert($data);
        $drawnNumbers = $this->getOrderedDrawnNumbers((int) $game['id']);

        return $this->response->setJSON([
            'status' => 'success',
            'totalNumbersGenerated' => $totalNumbersGenerated + 1,
            'message' => translate('new number generated'),
            'number' => $number,
            'drawnNumbers' => $drawnNumbers,
        ]);
    }

    public function numberSubmit($number) {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        $modelUsers = new UsersModel();
        $model = new BoardsModel();
        $modelModalities = new ModalitiesModel();
        $modelGames = new GamesModel();
        $modelSings = new SingsModel();
        $modelAwards = new AwardsModel();

        $game = $modelGames->find(session()->get('game_id'));

        if (!$game) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('game not found')]);
        }

        $totalNumbersGenerated = $model->where('game', $game['id'])->select('number')->distinct()->countAllResults();

        if ($totalNumbersGenerated === 0 && !bingo_can_start_game($game)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => bingo_min_players_start_message($game),
            ]);
        }

        $lastBall = $model->where('game', $game['id'])->orderBy('created_at', 'DESC')->first();

        if ($totalNumbersGenerated >= 75) {
            $modelGames->where('game', $game['id'])->where('status', 0)->set(['status' => 1])->update();
            return $this->response->setJSON([
                'status' => 'completed',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'message' => translate('the game has ended, all 75 numbers have already been generated'),
                'number' => $lastBall['number']
            ]);
        }

        $SingsCount = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();

        $AwardsCount = $modelAwards->where('game', $game['id'])->where('status', 1)->countAllResults();

        if ($SingsCount >= $AwardsCount) {
            $modelGames->where('game', $game['id'])->where('status', 0)->set(['status' => 1])->update();
            return $this->response->setJSON([
                'status' => 'completed',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'message' => translate('the game is over, all the prizes have been awarded'),
                'number' => $lastBall['number']
            ]);
        }

        $lastSings = $modelSings->where('game', $game['id'])->where('status', 0)->findAll();

        $winners = bingo_get_official_sings_for_game((int) $game['id'], true);
        foreach ($winners as &$winner) {
            $user = $modelUsers->find($winner['user']);
            $wmodality = $modelModalities->find($winner['modality']);

            $winner['player'] = $user['firstname'] . ' ' . $user['lastname'];
            $winner['modality'] = translate($wmodality['name']);
        }

        if ($lastBall && !empty($lastSings)) {
            foreach ($lastSings as $sing) {
                $user = $modelUsers->find($sing['user']);
                $modality = $modelModalities->find($sing['modality']);

                $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');

                $lastBallTime = strtotime($lastBall['created_at']);
                $lastSingTime = strtotime($sing['created_at']);
                $timeDifference = $lastSingTime - $lastBallTime;

                if ($timeDifference <= 10) {
                    $modelSings->where('game', $game['id'])->where('status', 0)->set(['status' => 1])->update();

                    return $this->response->setJSON([
                        'status' => 'pause',
                        'totalNumbersGenerated' => $totalNumbersGenerated,
                        'winners' => $winners,
                        'message' => 'Se ha cantado un bingo. Pausando el juego por 10 segundos.',
                        'player' => $user['firstname'] . ' ' . $user['lastname'],
                        'modality' => translate($modality['name']),
                        'modalityId' => $modality['id'],
                        'image' => $imagePath
                    ]);
                }
            }
        }

        $number = $number;

        $data = [
            'user' => session()->get('id'),
            'game' => $game['id'],
            'number' => $number,
            'status' => 1
        ];

        $model->insert($data);
        $drawnNumbers = $this->getOrderedDrawnNumbers((int) $game['id']);

        return $this->response->setJSON([
            'status' => 'success',
            'totalNumbersGenerated' => $totalNumbersGenerated + 1,
            'message' => translate('new number generated'),
            'number' => $number,
            'drawnNumbers' => $drawnNumbers,
        ]);
    }

    public function numberGet() {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }
        
        $modelUsers = new UsersModel();
        $model = new BoardsModel();
        $modelModalities = new ModalitiesModel();
        $modelGames = new GamesModel();
        $modelSings = new SingsModel();
        $modelAwards = new AwardsModel();

        $game = $modelGames->find(session()->get('game_id'));

        if (!$game) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('there are no active games')]);
        }

        $lastNumber = $model->where('game', $game['id'])->orderBy('created_at', 'DESC')->first();  

        if (!$lastNumber) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('there are no numbers drawn yet')]);
        }

        $drawnNumbers = $this->getOrderedDrawnNumbers((int) $game['id']);
        $totalNumbersGenerated = count($drawnNumbers);

        // Obtener lista de ganadores para incluir en todas las respuestas
        $winners = $modelSings->where('game', $game['id'])->where('status', 1)->findAll();
        foreach ($winners as &$winner) {
            $user = $modelUsers->find($winner['user']);
            $wmodality = $modelModalities->find($winner['modality']);
            $winner['player'] = $user['firstname'] . ' ' . $user['lastname'];
            $winner['modality'] = translate($wmodality['name']);
        }

        // Si salieron las 75 bolas, finalizar juego
        if ($totalNumbersGenerated >= 75) {
            bingo_ensure_winners_registered((int) $game['id']);
            $winners = $this->buildWinnersList((int) $game['id'], $modelSings, $modelUsers, $modelModalities);
            $modelGames->where('id', $game['id'])->where('status', 1)->set(['status' => 0])->update();

            return $this->response->setJSON([
                'status' => 'completed',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'drawnNumbers' => $drawnNumbers,
                'winners' => $winners,
                'message' => translate('the game has ended, all 75 numbers have already been generated'),
                'number' => $lastNumber['number'],
                'player' => '',
                'modality' => '',
                'modalityId' => '',
                'image' => ''
            ]);
        }

        $SingsCount = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();
        $AwardsCount = $modelAwards->where('game', $game['id'])->where('status', 1)->countAllResults();
        $lastSing = $modelSings->where('game', $game['id'])->orderBy('created_at', 'DESC')->first();

        // Verificar si hay un bingo pendiente de procesar
        if ($lastSing && $lastSing['status'] == 0) {
            $user = $modelUsers->find($lastSing['user']);
            $modality = $modelModalities->find($lastSing['modality']);
            $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');

            // Marcar el sing como procesado
            $modelSings->update($lastSing['id'], ['status' => 1]);

            // Verificar si este era el último premio
            $updatedSingsCount = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();
            
            if ($updatedSingsCount >= $AwardsCount) {
                bingo_ensure_winners_registered((int) $game['id']);
                $winners = $this->buildWinnersList((int) $game['id'], $modelSings, $modelUsers, $modelModalities);
                $modelGames->where('id', $game['id'])->where('status', 1)->set(['status' => 0])->update();
                
                return $this->response->setJSON([
                    'status' => 'completed',
                    'totalNumbersGenerated' => $totalNumbersGenerated,
                    'drawnNumbers' => $drawnNumbers,
                    'winners' => $winners,
                    'message' => translate('the game is over, all the prizes have been awarded'),
                    'number' => $lastNumber['number'],
                    'player' => $user['firstname'] . ' ' . $user['lastname'],
                    'modality' => translate($modality['name']),
                    'modalityId' => $modality['id'],
                    'image' => $imagePath
                ]);
            }
            
            // Aún hay más premios por ganar
            return $this->response->setJSON([
                'status' => 'pause',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'drawnNumbers' => $drawnNumbers,
                'winners' => $winners,
                'message' => translate('a bingo has been called, pausing the game for 10 seconds'),
                'number' => $lastNumber['number'],
                'player' => $user['firstname'] . ' ' . $user['lastname'],
                'modality' => translate($modality['name']),
                'modalityId' => $modality['id'],
                'image' => $imagePath
            ]);
        }

        // Verificar si todos los premios ya fueron ganados (sin bingos pendientes)
        if ($SingsCount >= $AwardsCount) {
            bingo_ensure_winners_registered((int) $game['id']);
            $winners = $this->buildWinnersList((int) $game['id'], $modelSings, $modelUsers, $modelModalities);
            $modelGames->where('id', $game['id'])->where('status', 1)->set(['status' => 0])->update();
            
            return $this->response->setJSON([
                'status' => 'completed',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'drawnNumbers' => $drawnNumbers,
                'winners' => $winners,
                'message' => translate('the game is over, all the prizes have been awarded'),
                'number' => $lastNumber['number'],
                'player' => '',
                'modality' => '',
                'modalityId' => '',
                'image' => ''
            ]);
        }

        if ($lastNumber['isCRON'] == 1) {
            return $this->response->setJSON([
                'status' => 'iscron',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'drawnNumbers' => $drawnNumbers,
                'winners' => $winners,
                'message' => translate('last number'),
                'number' => $lastNumber['number'],
                'player' => '',
                'modality' => '',
                'modalityId' => '',
                'image' => ''
            ]);
        }

        // Funcionamiento normal - continuar el juego
        return $this->response->setJSON([
            'status' => 'success',
            'totalNumbersGenerated' => $totalNumbersGenerated,
            'drawnNumbers' => $drawnNumbers,
            'winners' => $winners,
            'message' => translate('last number'),
            'number' => $lastNumber['number'],
            'player' => '',
            'modality' => '',
            'modalityId' => '',
            'image' => ''
        ]);
    }

    public function winnersGet()
    {
        $modelUsers = new UsersModel();
        $modelSings = new SingsModel();
        $modelModalities = new ModalitiesModel();
        $modelGames = new GamesModel();
    
        $game = $modelGames->find(session()->get('game_id'));
    
        if (!$game) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('there are no active games')]);
        }
    
        $lastSings = $modelSings->where('game', $game['id'])->findAll();
    
        if (empty($lastSings)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => translate('there are no winners yet')
            ]);
        }
    
        $winners = [];
    
        foreach ($lastSings as $sing) {
            $user = $modelUsers->find($sing['user']);
            $modality = $modelModalities->find($sing['modality']);
            $imagePath = !empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');
    
            $winners[] = [
                'player' => $user['firstname'] . ' ' . $user['lastname'],
                'modality' => translate($modality['name']),
                'modalityId' => $modality['id'],
                'image' => $imagePath
            ];
        }
    
        return $this->response->setJSON([
            'status' => 'success',
            'winners' => $winners
        ]);
    }

    private function generateUniqueNumber() {
        $db = \Config\Database::connect();
        $gameId = session()->get('game_id');

        if (systemGet('activateAlgorithm') == 1) {

            $query = $db->table('numbers n')->select('n.number, COUNT(*) as count')->join('cartons c', 'n.carton = c.id')->where('c.game', $gameId)->groupBy('n.number')->get()->getResultArray();

            $frequencies = [];
            foreach ($query as $row) {
                $frequencies[$row['number']] = (int)$row['count'];
            }

            for ($i = 1; $i <= 75; $i++) {
                if (!isset($frequencies[$i])) {
                    $frequencies[$i] = 0;
                }
            }

            $sungNumbers = $db->table('boards')->select('number')->where('game', $gameId)->get()->getResultArray();

            $sungNumbers = array_column($sungNumbers, 'number');

            foreach ($sungNumbers as $sung) {
                unset($frequencies[$sung]);
            }

            if (empty($frequencies)) {
                return rand(1, 75); 
            }

            asort($frequencies); 

            $minFrequency = reset($frequencies);
            $lessRecurring = array_keys(array_filter($frequencies, function ($v) use ($minFrequency) {
                return $v === $minFrequency;
            }));

            $number = $lessRecurring[array_rand($lessRecurring)];

            return $number;
        }

        do {
            $number = rand(1, 75);

            $query = $db->table('boards')->where('game', $gameId)->where('number', $number)->countAllResults();
        } while ($query > 0);

        return $number;
    }

    public function playersGet() {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelSings = new SingsModel();
        $modelUsers = new UsersModel();
        
        $game = $modelGames->find(session()->get('game_id'));

        if (!$game) {
            return $this->response->setStatusCode(404)->setJSON(['userCount' => 0, 'message' => translate('game not found')]);
        }

        $data['game'] = $game;

        $cartons = $modelCartons->where('game', $game['id'])->where('user !=', 0)->findAll();

        $userCartons = [];
        foreach ($cartons as $carton) {
            $userId = $carton['user'];
            if (!isset($userCartons[$userId])) {
                $userCartons[$userId] = [
                    'user_name' => $modelUsers->find($userId)['firstname'] . ' ' . $modelUsers->find($userId)['lastname'],
                    'cartons_count' => 0,
                    'bingo_count' => 0
                ];
            }
            $userCartons[$userId]['cartons_count']++;

            $userCartons[$userId]['bingo_count'] = $modelSings->where('game', $game['id'])->where('user', $userId)->countAllResults();
        }

        $data['users'] = $userCartons;

        return view('boards/players', $data);
    }

    public function playersGetCount() {
        if (!session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        $model = new BoardsModel();
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelSings = new SingsModel();
        $modelAwards = new AwardsModel();

        $game = $modelGames->find(session()->get('game_id'));

        if (!$game) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'stop', 'userCount' => 0, 'message' => translate('game not found')]);
        }

        $userCount = $modelCartons->where('game', $game['id'])->where('user !=', 0)->select('user')->distinct()->countAllResults();

        $response = ['userCount' => $userCount];
        $response['status'] = 'success';

        $totalNumbersGenerated = $model->where('game', $game['id'])->countAllResults();

        if ($totalNumbersGenerated == 75) {
            $response['status'] = 'completed';
            return $this->response->setJSON([
                'status' => 'completed',
                'userCount' => $userCount,
                'message' => translate('the game has ended, all 75 numbers have already been generated'),
            ]);
        }

        $SingsCount = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();

        $AwardsCount = $modelAwards->where('game', $game['id'])->where('status', 1)->countAllResults();

        if ($SingsCount >= $AwardsCount) {
            return $this->response->setJSON([
                'status' => 'completed',
                'userCount' => $userCount,
                'message' => translate('the game is over, all the prizes have been awarded'),
            ]);
        }

        return $this->response->setJSON($response);
    }

    public function awardsGet() {
        $modelGames = new GamesModel();
        $modelGameRooms = new GameRoomsModel();
        $modelCartons = new CartonsModel();
        $modelSings = new SingsModel();
        $modelUsers = new UsersModel();  
        $modelModalities = new ModalitiesModel(); 
        $modelAwards = new AwardsModel();

        $game = $modelGames->find(session()->get('game_id'));
        $data['game'] = $game;

        if ($game) {
            bingo_ensure_winners_registered((int) $game['id']);
        }

        if (! $game) {
            $data['sings'] = [];
            return view('playings/awards', $data);
        }

        $room = $modelGameRooms->where('id', $game['room'])->first();

        $data['room'] = $room ? $room['name'] : translate('room not found');

        $sings = bingo_get_official_sings_for_game((int) $game['id'], true);

        $singsByModality = [];
        foreach ($sings as $sing) {
            $singsByModality[$sing['modality']][] = $sing;
        }

        foreach ($sings as &$sing) {
            $user = $modelUsers->find($sing['user']);
            $modality = $modelModalities->find($sing['modality']);
            $award = $modelAwards->where('game', $game['id'])->where('modality', $sing['modality'])->first();

            $cartons = $modelCartons->where('game', $game['id'])->where('user !=', 0)->countAllResults();

            $total_sing = max(1, count($singsByModality[$sing['modality']] ?? []));

            $carton = $modelCartons->where('id', $sing['carton'])->first();

            $sing['serial'] = $carton ? $carton['serial'] : translate('serial not found');

            $sing['room_name'] = $room ? $room['name'] : translate('room not found');
            $sing['user_code'] = $user ? $user['code'] : translate('code not found');
            $sing['user_name'] = $user ? $user['firstname'] . ' ' . $user['lastname'] : translate('user not found');
            $sing['modality_name'] = $modality ? translate($modality['name']) : translate('modality not found');

            $singsCount = count($singsByModality[$sing['modality']]);

            $accumulated = $cartons * $game['price'];

            $total_award = $accumulated - ($accumulated * systemGet('rateEarnings'));

            if ($game['award'] == 2) {
                if ($award) {
                    $sing['award_amount'] = number_format($award['amount'] / $total_sing, 2);
                } else {
                    $sing['award_amount'] = translate('amount not available');
                }
            } else {
                if ($award) {
                    $accumulated_modality = ($total_award * $award['amount']) / 100;
                    $sing['award_amount'] = number_format($accumulated_modality / $total_sing, 2);
                } else {
                    $sing['award_amount'] = translate('amount not available');
                }
            }

            $sing['status_raw'] = (int) ($sing['status'] ?? 0);

            if ($sing['status_raw'] === 0) {
                $sing['status'] = '<span class="status-badge"><span class="badge bg-danger"><i class="fa-duotone fa-solid fa-xmark"></i> ' . translate('rejected') . '</span></span>';
            } elseif ($sing['status_raw'] === 1) {
                $sing['status'] = '<span class="status-badge"><span class="badge bg-warning"><i class="fa-duotone fa-solid fa-clock"></i> ' . translate('pending') . '</span></span>';
            } elseif ($sing['status_raw'] === 2) {
                $sing['status'] = '<span class="status-badge"><span class="badge bg-success"><i class="fa-duotone fa-solid fa-check-double"></i> ' . translate('paid') . '</span></span>';
            }
        }

        // Siguiente juego después del actual
        $data['lastGame'] = $modelGames
            ->groupStart()
                ->where('date >', $game['date'])
                ->orGroupStart()
                    ->where('date', $game['date'])
                    ->where('time >', $game['time'])
                ->groupEnd()
            ->groupEnd()
            ->where('id !=', $game['id'])
            ->orderBy('date', 'ASC')
            ->orderBy('time', 'ASC')
            ->first();

        $data['sings'] = $sings;

        return view('playings/awards', $data);
    }

    public function awardsGameGet() {
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelGameRooms = new GameRoomsModel();
        $modelSings = new SingsModel();
        $modelUsers = new UsersModel();  
        $modelModalities = new ModalitiesModel(); 
        $modelAwards = new AwardsModel();

        // Obtener datos para los filtros
        $rooms = $modelGameRooms->findAll();
        $games = $modelGames->findAll();
        $modalities = $modelModalities->findAll();

        // Parámetros de paginación
        $per_page = 10;
        $currentPage = 1;

        $sings = $modelSings->paginate($per_page);
        $totalRecords = $modelSings->countAllResults(false);
        $totalPages = ceil($totalRecords / $per_page);

        // Procesar datos de sings (tu código existente)
        $singsByModality = [];
        foreach ($sings as $sing) {
            $singsByModality[$sing['modality']][] = $sing;
        }

        foreach ($sings as &$sing) {
            // Tu código existente para procesar cada sing
            $user = $modelUsers->find($sing['user']);
            $modality = $modelModalities->find($sing['modality']);
            $award = $modelAwards->where('game', $sing['game'])->where('modality', $sing['modality'])->first();
            $game = $modelGames->where('id', $sing['game'])->first();

            $cartons = $modelCartons->where('game', $game['id'])->where('user !=', 0)->countAllResults();
            $total_sing = $modelSings->where('game', $game['id'])->where('modality', $sing['modality'])->countAllResults();
            $room = $modelGameRooms->where('id', $game['id'])->first();
            $carton = $modelCartons->where('id', $sing['carton'])->first();

            $sing['serial'] = $carton ? $carton['serial'] : translate('serial not found');
            $sing['room_name'] = $room ? $room['name'] : translate('room not found');
            $sing['game_description'] = $game ? $game['description'] : translate('game not found');
            $sing['modality_name'] = $modality ? translate($modality['name']) : translate('modality not found');
            $sing['user_code'] = $user ? $user['code'] : translate('code not found');
            $sing['user_name'] = $user ? $user['firstname'] . ' ' . $user['lastname'] : translate('user not found');

            $singsCount = count($singsByModality[$sing['modality']]);
            $accumulated = $cartons * $game['price'];
            $total_award = $accumulated - ($accumulated * systemGet('rateEarnings'));

            if ($game['award'] == 2) {
                if ($award) {
                    $sing['award_amount'] = number_format($award['amount'] / $total_sing, 2);
                } else {
                    $sing['award_amount'] = translate('amount not available');
                }
            } else {
                if ($award) {
                    $accumulated_modality = ($total_award * $award['amount']) / 100;
                    $sing['award_amount'] = number_format($accumulated_modality / $total_sing, 2);
                } else {
                    $sing['award_amount'] = translate('amount not available');
                }
            }

            $sing['status_raw'] = (int) ($sing['status'] ?? 0);

            if ($sing['status_raw'] === 1) {
                $sing['status'] = '<span class="status-badge"><span class="badge bg-warning"><i class="fa-duotone fa-solid fa-clock"></i> ' . translate('pending') . '</span></span>';
            } elseif ($sing['status_raw'] === 2) {
                $sing['status'] = '<span class="status-badge"><span class="badge bg-success"><i class="fa-duotone fa-solid fa-check-double"></i> ' . translate('paid') . '</span></span>';
            }
        }

        $data = [
            'sings' => $sings,
            'rooms' => $rooms,
            'games' => $games,
            'modalities' => $modalities,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'per_page' => $per_page,
            'showPagination' => $totalPages > 1
        ];

        return view('playings/awardsgame', $data);
    }

    public function winnersListGet($room = 'all', $game = 'all', $player = 'all', $modality = 'all', $status = 'all', $page = 1) {
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelGameRooms = new GameRoomsModel();
        $modelSings = new SingsModel();
        $modelUsers = new UsersModel();  
        $modelModalities = new ModalitiesModel(); 
        $modelAwards = new AwardsModel();

        $per_page = 10;
        $currentPage = (int)$page;
        $offset = ($currentPage - 1) * $per_page;

        // Decodificar parámetros URL si contienen espacios o caracteres especiales
        $player = urldecode($player);

        // Construir query base
        $builder = $modelSings->builder();
        $builder->select('sings.*');

        // Aplicar filtros
        $filtersApplied = false;

        // Filtro por sala
        if ($room !== 'all' && !empty($room)) {
            if (!$filtersApplied) {
                $builder->join('games g', 'g.id = sings.game');
                $filtersApplied = true;
            }
            $builder->where('g.room', $room);
        }

        // Filtro por juego
        if ($game !== 'all' && !empty($game)) {
            $builder->where('sings.game', $game);
        }

        // Filtro por modalidad
        if ($modality !== 'all' && !empty($modality)) {
            $builder->where('sings.modality', $modality);
        }

        // Filtro por estado
        if ($status !== 'all' && !empty($status)) {
            $builder->where('sings.status', $status);
        }

        // Filtro por jugador (buscar en código, nombre y apellido)
        if ($player !== 'all' && !empty($player)) {
            $builder->join('users u', 'u.id = sings.user');
            $builder->groupStart()
                   ->like('u.code', $player)
                   ->orLike('u.firstname', $player)
                   ->orLike('u.lastname', $player)
                   ->orLike("CONCAT(u.firstname, ' ', u.lastname)", $player)
                   ->groupEnd();
        }

        // Ordenar por ID descendente (más recientes primero)
        $builder->orderBy('sings.id', 'DESC');

        // Contar total de registros
        $totalRecords = $builder->countAllResults(false);

        // Obtener registros paginados
        $sings = $builder->limit($per_page, $offset)->get()->getResultArray();

        // Calcular páginas
        $totalPages = ceil($totalRecords / $per_page);

        // Procesar datos de sings
        $singsByModality = [];
        foreach ($sings as $sing) {
            $singsByModality[$sing['modality']][] = $sing;
        }

        foreach ($sings as &$sing) {
            try {
                // Obtener datos del usuario
                $user = $modelUsers->find($sing['user']);
                
                // Obtener datos de la modalidad
                $modality = $modelModalities->find($sing['modality']);
                
                // Obtener datos del juego
                $game = $modelGames->where('id', $sing['game'])->first();
                
                // Obtener datos del premio
                $award = $modelAwards->where('game', $sing['game'])
                                     ->where('modality', $sing['modality'])
                                     ->first();

                // Obtener datos de la sala
                $room_data = null;
                if ($game) {
                    $room_data = $modelGameRooms->where('id', $game['room'])->first();
                }

                // Obtener datos del cartón
                $carton = $modelCartons->where('id', $sing['carton'])->first();

                // Contar cartones del juego
                $cartons = 0;
                if ($game) {
                    $cartons = $modelCartons->where('game', $game['id'])
                                           ->where('user !=', 0)
                                           ->countAllResults();
                }

                // Contar total de ganadores de esta modalidad en este juego
                $total_sing = $modelSings->where('game', $sing['game'])
                                        ->where('modality', $sing['modality'])
                                        ->countAllResults();

                // Asignar datos básicos
                $sing['serial'] = $carton ? $carton['serial'] : translate('serial not found');
                $sing['room_name'] = $room_data ? $room_data['name'] : translate('room not found');
                $sing['game_description'] = $game ? $game['description'] : translate('game not found');
                $sing['modality_name'] = $modality ? translate($modality['name']) : translate('modality not found');
                $sing['user_code'] = $user ? $user['code'] : translate('code not found');
                $sing['user_name'] = $user ? $user['firstname'] . ' ' . $user['lastname'] : translate('user not found');

                // Calcular premio
                if ($game && $award && $total_sing > 0) {
                    $accumulated = $cartons * $game['price'];
                    $total_award = $accumulated - ($accumulated * systemGet('rateEarnings'));

                    if ($game['award'] == 2) {
                        // Premio fijo
                        $sing['award_amount'] = number_format($award['amount'] / $total_sing, 2);
                    } else {
                        // Premio por porcentaje
                        $accumulated_modality = ($total_award * $award['amount']) / 100;
                        $sing['award_amount'] = number_format($accumulated_modality / $total_sing, 2);
                    }
                } else {
                    $sing['award_amount'] = translate('amount not available');
                }

                $sing['status_raw'] = (int) ($sing['status'] ?? 0);

                if ($sing['status_raw'] === 1) {
                    $sing['status'] = '<span class="status-badge"><span class="badge bg-warning"><i class="fa-duotone fa-solid fa-clock"></i> ' . translate('pending') . '</span></span>';
                } elseif ($sing['status_raw'] === 2) {
                    $sing['status'] = '<span class="status-badge"><span class="badge bg-success"><i class="fa-duotone fa-solid fa-check-double"></i> ' . translate('paid') . '</span></span>';
                } else {
                    $sing['status'] = '<span class="status-badge"><span class="badge bg-secondary"><i class="fa-duotone fa-solid fa-question"></i> ' . translate('unknown') . '</span></span>';
                }

            } catch (Exception $e) {
                // En caso de error, asignar valores por defecto
                log_message('error', 'Error processing sing ID ' . $sing['id'] . ': ' . $e->getMessage());
                
                $sing['serial'] = translate('error');
                $sing['room_name'] = translate('error');
                $sing['game_description'] = translate('error');
                $sing['modality_name'] = translate('error');
                $sing['user_code'] = translate('error');
                $sing['user_name'] = translate('error');
                $sing['award_amount'] = translate('error');
                $sing['status'] = '<span class="status-badge"><span class="badge bg-danger"><i class="fa-duotone fa-solid fa-exclamation-triangle"></i> ' . translate('error') . '</span></span>';
            }
        }

        // Preparar datos para la vista
        $data = [
            'sings' => $sings,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'per_page' => $per_page,
            'showPagination' => $totalPages > 1,
            'filters' => [
                'room' => $room,
                'game' => $game,
                'player' => $player,
                'modality' => $modality,
                'status' => $status
            ]
        ];

        // Si es una petición AJAX, devolver solo la tabla
        if ($this->request->isAJAX()) {
            return view('playings/winners_table_content', $data);
        }

        // Si no es AJAX, devolver la vista completa
        return view('playings/winners_table', $data);
    }

    private function getOrderedDrawnNumbers(int $gameId): array
    {
        return bingo_get_ordered_drawn_numbers($gameId);
    }

    private function buildWinnersList(int $gameId, SingsModel $modelSings, UsersModel $modelUsers, ModalitiesModel $modelModalities): array
    {
        $winners = bingo_get_official_sings_for_game($gameId, true);

        foreach ($winners as &$winner) {
            $user = $modelUsers->find($winner['user']);
            $wmodality = $modelModalities->find($winner['modality']);
            $winner['player'] = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
            $winner['modality'] = translate($wmodality['name'] ?? '');
        }

        return $winners;
    }
}