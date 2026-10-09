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

        if ($AwardsCount > 0 && $SingsCount >= $AwardsCount) {
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
        if (!session()->get('logged_in') || !in_array((int) session()->get('group'), [0, 1, 2, 3], true)) {
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

        if ($AwardsCount > 0 && $SingsCount >= $AwardsCount) {
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

        $isGameCompleted = ($status === 'stop' || $totalNumbersGenerated >= 75 || ($AwardsCount > 0 && $SingsCount >= $AwardsCount));
        $isGameFinalized = ((int) ($game['status'] ?? 0) === 0);
        $isLiveGame = in_array((int) ($game['type'] ?? 0), [3, 4], true);
        $isAdmin = bingo_is_admin();

        $data = [
            'page' => [
                'title' => $game['description']
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('boards/live', [
                'contacts' => $contacts,
                'user' => $user,
                'status' => $status,
                'game' => $game,
                'selectedNumbers' => $selectedNumbers,
                'singsModalities' => $singsModalities,
                'lastNumber' => $lastNumber['number'] ?? '',
                'fourNumbers' => $fourNumbers,
                'lastNumbersJson' => json_encode($fiveNumbers),
                'getClass' => $getClass,
                'modalities' => $modalities,
                'winners' => $winners,
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'imagePath' => $imagePath,
                'isAdmin' => $isAdmin,
                'isLiveGame' => $isLiveGame,
                'isGameCompleted' => $isGameCompleted,
                'isGameFinalized' => $isGameFinalized,
            ])
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        } else {
            return view('layout/index', $data);
        }
    }

    public function numberAutoSubmit() {
        if (!session()->get('logged_in') || !in_array((int) session()->get('group'), [0, 1, 2, 3], true)) {
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
        $isLiveGame = in_array((int) ($game['type'] ?? 0), [3, 4], true);

        // 1. Si la partida ya fue finalizada (status 0) o todos los premios fueron cantados, detener de inmediato
        if ((int) ($game['status'] ?? 0) === 0 || bingo_is_game_finished_by_awards((int) $game['id'])) {
            if ((int) ($game['status'] ?? 0) !== 0 && !$isLiveGame) {
                bingo_finalize_game_when_complete((int) $game['id']);
            }
            return $this->response->setJSON([
                'status' => 'completed',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'message' => translate('the game is over, all the prizes have been awarded'),
                'number' => null
            ]);
        }

        if ($totalNumbersGenerated === 0 && !bingo_can_start_game($game, null, null, true)) {
            $postpone = bingo_postpone_game($game);
            return $this->response->setJSON([
                'status' => 'error',
                'postponed' => true,
                'message' => $postpone['message'] ?: bingo_game_start_block_message($game),
                'new_time' => $postpone['new_time'] ?? null,
            ]);
        }

        // Si el admin inicia, asegurar que la partida quede activa (1)
        if ((int) ($game['status'] ?? 0) === 2) {
            $modelGames->update((int) $game['id'], ['status' => 1]);
            $game['status'] = 1;
        }

        $lastBall = $model->where('game', $game['id'])->orderBy('created_at', 'DESC')->first();

        if ($totalNumbersGenerated >= 75) {
            if ($isLiveGame) {
                bingo_ensure_winners_registered((int) $game['id']);
                bingo_pay_pending_awards_for_game((int) $game['id']);
            } else {
                bingo_finalize_game_when_complete((int) $game['id']);
            }
            return $this->response->setJSON([
                'status' => 'completed',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'message' => translate('the game has ended, all 75 numbers have already been generated'),
                'number' => null
            ]);
        }

        if (bingo_is_game_finished_by_awards((int) $game['id'])) {
            if ($isLiveGame) {
                bingo_ensure_winners_registered((int) $game['id']);
                bingo_pay_pending_awards_for_game((int) $game['id']);
            } else {
                bingo_finalize_game_when_complete((int) $game['id']);
            }
            return $this->response->setJSON([
                'status' => 'completed',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'message' => translate('the game is over, all the prizes have been awarded'),
                'number' => null
            ]);
        }

        $number = null;
        for ($i = 0; $i < 8; $i++) {
            $candidate = $this->generateUniqueNumber();
            if ($candidate && !bingo_number_already_drawn((int) $game['id'], (int) $candidate)) {
                $number = (int) $candidate;
                break;
            }
        }

        if (!$number) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => translate('could not generate a unique number') ?: 'No se pudo generar un número único',
            ]);
        }

        $result = bingo_process_ball_cycle((int) $game['id'], $number, [
            'userId' => session()->get('id'),
            'isCRON' => 0,
        ]);

        if (!($result['ok'] ?? false)) {
            return $this->response->setJSON([
                'status' => $result['status'] ?? 'error',
                'message' => $result['message'] ?? 'Error al procesar la balota',
                'totalNumbersGenerated' => $result['totalNumbersGenerated'] ?? $totalNumbersGenerated,
                'drawnNumbers' => $result['drawnNumbers'] ?? $this->getOrderedDrawnNumbers((int) $game['id']),
            ]);
        }

        $responsePayload = [
            'status' => $result['status'],
            'totalNumbersGenerated' => $result['totalNumbersGenerated'],
            'message' => $result['message'],
            'number' => $result['number'],
            'drawnNumbers' => $result['drawnNumbers'],
            'winners' => $result['winners'] ?? [],
        ];

        if (!empty($result['player'])) {
            $responsePayload['player'] = $result['player'];
            $responsePayload['modality'] = $result['modality'] ?? '';
            $responsePayload['modalityId'] = $result['modalityId'] ?? 0;
            $responsePayload['image'] = $result['image'] ?? site_url('assets/img/avatar.jpg');
        } elseif ($result['status'] === 'pause' && !empty($result['pendingSing'])) {
            $ps = $result['pendingSing'];
            $responsePayload['player'] = $ps['player'] ?? ($ps['userName'] ?? '');
            $responsePayload['modality'] = $ps['modality'] ?? ($ps['modalityName'] ?? '');
            $responsePayload['modalityId'] = $ps['modalityId'] ?? ($ps['modality'] ?? 0);
            $responsePayload['image'] = $ps['image'] ?? site_url('assets/img/avatar.jpg');
        }

        return $this->response->setJSON($responsePayload);
    }

    public function numberSubmit($number) {
        if (!session()->get('logged_in') || !in_array((int) session()->get('group'), [0, 1, 2, 3], true)) {
            return redirect()->to('/signin');
        }

        $modelGames = new GamesModel();
        $game = $modelGames->find(session()->get('game_id'));

        if (!$game) {
            return $this->response->setJSON(['status' => 'error', 'message' => translate('game not found')]);
        }

        $number = (int) $number;
        if ($number < 1 || $number > 75) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => translate('invalid number') ?: 'Número inválido',
            ]);
        }

        $gameId = (int) $game['id'];

        if (bingo_number_already_drawn($gameId, $number)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => translate('number already drawn') ?: 'Ese número ya fue cantado',
                'totalNumbersGenerated' => bingo_count_drawn_numbers($gameId),
                'drawnNumbers' => $this->getOrderedDrawnNumbers($gameId),
            ]);
        }

        $totalNumbersGenerated = bingo_count_drawn_numbers($gameId);
        $isLiveGame = in_array((int) ($game['type'] ?? 0), [3, 4], true);

        if ((int) ($game['status'] ?? 0) === 0 || bingo_is_game_finished_by_awards($gameId)) {
            if ((int) ($game['status'] ?? 0) !== 0 && !$isLiveGame) {
                bingo_finalize_game_when_complete($gameId);
            }
            return $this->response->setJSON([
                'status' => 'completed',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'message' => translate('the game is over, all the prizes have been awarded'),
                'number' => null,
            ]);
        }

        if ($totalNumbersGenerated === 0 && !bingo_can_start_game($game, null, null, true)) {
            $postpone = bingo_postpone_game($game);
            return $this->response->setJSON([
                'status' => 'error',
                'postponed' => true,
                'message' => $postpone['message'] ?: bingo_game_start_block_message($game),
                'new_time' => $postpone['new_time'] ?? null,
            ]);
        }

        // Ejecutar el pipeline unificado idéntico a Automática
        $result = bingo_process_ball_cycle($gameId, $number, [
            'userId' => session()->get('id'),
            'isCRON' => 0,
        ]);

        if (!($result['ok'] ?? false)) {
            return $this->response->setJSON([
                'status' => $result['status'] ?? 'error',
                'message' => $result['message'] ?? 'Error al procesar la balota',
                'totalNumbersGenerated' => $result['totalNumbersGenerated'] ?? $totalNumbersGenerated,
                'drawnNumbers' => $result['drawnNumbers'] ?? $this->getOrderedDrawnNumbers($gameId),
            ]);
        }

        $responsePayload = [
            'status' => $result['status'],
            'totalNumbersGenerated' => $result['totalNumbersGenerated'],
            'message' => $result['message'],
            'number' => $result['number'],
            'drawnNumbers' => $result['drawnNumbers'],
            'winners' => $result['winners'] ?? [],
        ];

        if (!empty($result['player'])) {
            $responsePayload['player'] = $result['player'];
            $responsePayload['modality'] = $result['modality'] ?? '';
            $responsePayload['modalityId'] = $result['modalityId'] ?? 0;
            $responsePayload['image'] = $result['image'] ?? site_url('assets/img/avatar.jpg');
        } elseif ($result['status'] === 'pause' && !empty($result['pendingSing'])) {
            $ps = $result['pendingSing'];
            $responsePayload['player'] = $ps['player'] ?? ($ps['userName'] ?? '');
            $responsePayload['modality'] = $ps['modality'] ?? ($ps['modalityName'] ?? '');
            $responsePayload['modalityId'] = $ps['modalityId'] ?? ($ps['modality'] ?? 0);
            $responsePayload['image'] = $ps['image'] ?? site_url('assets/img/avatar.jpg');
        }

        return $this->response->setJSON($responsePayload);
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

        $isLiveGame = in_array((int) ($game['type'] ?? 0), [3, 4], true);

        // Si salieron las 75 bolas, finalizar juego
        if ($totalNumbersGenerated >= 75) {
            bingo_ensure_winners_registered((int) $game['id']);
            $winners = $this->buildWinnersList((int) $game['id'], $modelSings, $modelUsers, $modelModalities);
            if (!$isLiveGame) {
                $modelGames->where('id', $game['id'])->where('status', 1)->set(['status' => 0])->update();
                bingo_on_game_finished((int) $game['id'], (int) session()->get('id'));
            }

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

        // Bingo pendiente de anunciar en tablero (aunque el jugador ya lo confirmó con status=1)
        $pendingBoardSing = bingo_claim_pending_board_sing((int) $game['id']);
        if ($pendingBoardSing) {
            $updatedSingsCount = $modelSings->select('modality')->where('game', $game['id'])->groupBy('modality')->countAllResults();

            if ($AwardsCount > 0 && $updatedSingsCount >= $AwardsCount) {
                bingo_ensure_winners_registered((int) $game['id']);
                $winners = $this->buildWinnersList((int) $game['id'], $modelSings, $modelUsers, $modelModalities);
                if (!$isLiveGame) {
                    $modelGames->where('id', $game['id'])->where('status', 1)->set(['status' => 0])->update();
                    bingo_on_game_finished((int) $game['id'], (int) session()->get('id'));
                }

                return $this->response->setJSON([
                    'status' => 'completed',
                    'totalNumbersGenerated' => $totalNumbersGenerated,
                    'drawnNumbers' => $drawnNumbers,
                    'winners' => $winners,
                    'message' => translate('the game is over, all the prizes have been awarded'),
                    'number' => $lastNumber['number'],
                    'player' => $pendingBoardSing['player'],
                    'modality' => $pendingBoardSing['modality'],
                    'modalityId' => $pendingBoardSing['modalityId'],
                    'image' => $pendingBoardSing['image'],
                ]);
            }

            return $this->response->setJSON([
                'status' => 'pause',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'drawnNumbers' => $drawnNumbers,
                'winners' => $winners,
                'message' => translate('a bingo has been called, pausing the game for 10 seconds'),
                'number' => $lastNumber['number'],
                'player' => $pendingBoardSing['player'],
                'modality' => $pendingBoardSing['modality'],
                'modalityId' => $pendingBoardSing['modalityId'],
                'image' => $pendingBoardSing['image'],
            ]);
        }

        // Verificar si todos los premios ya fueron ganados (sin bingos pendientes)
        if ($AwardsCount > 0 && $SingsCount >= $AwardsCount) {
            bingo_ensure_winners_registered((int) $game['id']);
            $winners = $this->buildWinnersList((int) $game['id'], $modelSings, $modelUsers, $modelModalities);
            if (!$isLiveGame) {
                $modelGames->where('id', $game['id'])->where('status', 1)->set(['status' => 0])->update();
                bingo_on_game_finished((int) $game['id'], (int) session()->get('id'));
            }
            
            $lastW = !empty($winners) ? end($winners) : null;

            return $this->response->setJSON([
                'status' => 'completed',
                'totalNumbersGenerated' => $totalNumbersGenerated,
                'drawnNumbers' => $drawnNumbers,
                'winners' => $winners,
                'message' => translate('the game is over, all the prizes have been awarded'),
                'number' => $lastNumber['number'],
                'player' => $lastW['player'] ?? '',
                'modality' => $lastW['modality'] ?? '',
                'modalityId' => $lastW['modalityId'] ?? 0,
                'image' => $lastW['image'] ?? site_url('assets/img/avatar.jpg')
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

    /**
     * Finaliza oficialmente una partida en modalidad LIVE.
     * Acción exclusiva para usuarios con rol ADMIN.
     */
    public function finalizeLiveSubmit()
    {
        if (!session()->get('logged_in')) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => translate('unauthorized') ?: 'No autorizado',
            ]);
        }

        helper(['bingo', 'wallet']);

        if (!bingo_is_admin()) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => translate('unauthorized access') ?: 'Acceso denegado. Se requiere rol de administrador para finalizar el Live.',
            ]);
        }

        $gameId = (int) ($this->request->getPost('game_id') ?: session()->get('game_id'));
        if ($gameId < 1) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('game not found') ?: 'ID de partida no especificado.',
            ]);
        }

        $modelGames = new GamesModel();
        $game = $modelGames->find($gameId);
        if (!$game) {
            return $this->response->setJSON([
                'success' => false,
                'message' => translate('game not found') ?: 'Partida no encontrada.',
            ]);
        }

        $isLiveGame = in_array((int) ($game['type'] ?? 0), [3, 4], true);
        if (!$isLiveGame) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Esta acción solo está disponible para partidas en modalidad Live.',
            ]);
        }

        // Idempotencia: si ya estaba finalizada en BD
        if ((int) ($game['status'] ?? 0) === 0) {
            return $this->response->setJSON([
                'success' => true,
                'alreadyFinalized' => true,
                'message' => 'El Live ya ha sido finalizado previamente.',
                'redirect' => site_url('games'),
            ]);
        }

        // 1. Asegurar registro oficial de ganadores y pago de premios pendientes
        bingo_ensure_winners_registered($gameId);
        bingo_pay_pending_awards_for_game($gameId);

        // 2. Finalizar oficialmente la partida en base de datos (status = 0)
        $modelGames->update($gameId, [
            'status' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (function_exists('bingo_on_game_finished')) {
            bingo_on_game_finished($gameId, (int) session()->get('id'));
        }

        $officialWinners = bingo_get_official_sings_for_game($gameId, true);

        // 3. Notificar a todos los jugadores conectados vía WebSocket/Soketi
        bingo_broadcast_game_status($gameId, 'game:live_finalized', [
            'status'        => 0,
            'gameId'        => $gameId,
            'liveFinalized' => true,
            'message'       => 'La transmisión en vivo ha finalizado.',
            'winners'       => $officialWinners,
        ]);

        bingo_broadcast_game_status($gameId, 'game:game_finished', [
            'status'        => 0,
            'liveFinalized' => true,
            'gameId'        => $gameId,
            'winners'       => $officialWinners,
        ]);

        return $this->response->setJSON([
            'success'  => true,
            'message'  => 'El Live ha sido finalizado exitosamente.',
            'redirect' => site_url('games'),
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
    
        helper('bingo');
        $lastSings = bingo_get_official_sings_for_game((int) $game['id'], true);
    
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
                'player' => $user ? trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')) : ('Jugador #' . $sing['user']),
                'modality' => $modality ? translate($modality['name'] ?? '') : 'Bingo',
                'modalityId' => (int) ($sing['modality'] ?? 0),
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
        if (!session()->get('logged_in') || !in_array((int) session()->get('group'), [0, 1, 2, 3], true)) {
            return redirect()->to('/signin');
        }

        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelSings = new SingsModel();
        $modelUsers = new UsersModel();
        
        $gameId = session()->get('game_id');
        if (!$gameId) {
            return view('boards/players', ['users' => []]);
        }

        $game = $modelGames->find($gameId);
        if (!$game) {
            return view('boards/players', ['users' => []]);
        }

        $data['game'] = $game;

        $cartons = $modelCartons->where('game', $game['id'])->where('user !=', 0)->findAll();

        $userCartons = [];
        foreach ($cartons as $carton) {
            $userId = (int) ($carton['user'] ?? 0);
            if (!$userId) continue;

            if (!isset($userCartons[$userId])) {
                $userData = $modelUsers->find($userId);
                $userName = $userData ? trim(($userData['firstname'] ?? '') . ' ' . ($userData['lastname'] ?? '')) : '';
                if (empty($userName) && $userData) {
                    $userName = $userData['username'] ?? ('Jugador #' . $userId);
                } elseif (empty($userName)) {
                    $userName = 'Jugador #' . $userId;
                }

                $userCartons[$userId] = [
                    'user_name' => $userName,
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
        // Admin (1) y jugadores (0): ambos necesitan el contador en live/playing
        if (!session()->get('logged_in') || !in_array((int) session()->get('group'), [0, 1], true)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'userCount' => 0,
                'message' => translate('unauthorized') ?: 'No autorizado',
            ]);
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

        if ($AwardsCount > 0 && $SingsCount >= $AwardsCount) {
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

        $gameId = $this->request->getGet('game_id') ?? $this->request->getGet('id') ?? session()->get('game_id');
        $game = $modelGames->find($gameId);
        $data['game'] = $game;

        if ($game) {
            bingo_ensure_winners_registered((int) $game['id']);
            bingo_pay_pending_awards_for_game((int) $game['id']);
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

            if ($award) {
                $awardPerSing = bingo_calculate_award_per_sing($game, $award, (int) $game['id'], (int) $sing['modality']);
                $sing['award_amount'] = number_format($awardPerSing, 2);
            } else {
                $sing['award_amount'] = translate('amount not available');
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