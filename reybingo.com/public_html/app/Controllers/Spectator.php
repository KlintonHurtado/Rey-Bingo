<?php

namespace App\Controllers;

use App\Models\GamesModel;
use App\Models\UsersModel;
use App\Models\CartonsModel;
use App\Models\NumbersCartonsModel;
use App\Models\BoardsModel;
use App\Models\ModalitiesModel;
use App\Models\AwardsModel;
use App\Models\SingsModel;
use App\Models\GameSpectatorTokensModel;
use CodeIgniter\Controller;

class Spectator extends Controller
{
    public function __construct()
    {
        helper(['form', 'url', 'cookie', 'text', 'bingo']);
        session();
    }

    /**
     * Página pública del Modo Espectador
     */
    public function view(string $token = '')
    {
        $token = trim($token);
        if ($token === '') {
            return view('spectator/not_found', [
                'message' => 'El enlace proporcionado no es válido.'
            ]);
        }

        $spectatorTokensModel = new GameSpectatorTokensModel();
        $shareInfo = $spectatorTokensModel->getShareInfoByToken($token);

        if (!$shareInfo) {
            return view('spectator/not_found', [
                'message' => 'El enlace de espectador no existe o ya no está disponible.'
            ]);
        }

        $gameId = (int) $shareInfo['game_id'];
        $userId = (int) $shareInfo['user_id'];

        $modelGames = new GamesModel();
        $game = $modelGames->find($gameId);

        if (!$game) {
            return view('spectator/not_found', [
                'message' => 'La partida asociada a este enlace ya no está disponible.'
            ]);
        }

        $modelUsers = new UsersModel();
        $player = $modelUsers->find($userId);

        if (!$player) {
            return view('spectator/not_found', [
                'message' => 'El jugador no fue encontrado.'
            ]);
        }

        // Registrar en sesión para autorizar la conexión al canal WebSocket
        session()->set('spectator_game_id', $gameId);
        session()->set('spectator_token', $token);
        session()->set('spectator_viewing_game_id', $gameId);

        $modelBoards = new BoardsModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $modelModalities = new ModalitiesModel();
        $modelAwards = new AwardsModel();
        $modelSings = new SingsModel();

        // 1. Balotas cantadas ordenadas
        $boards = $modelBoards->where('game', $gameId)
                             ->where('status', 1)
                             ->orderBy('created_at', 'ASC')
                             ->orderBy('id', 'ASC')
                             ->findAll();
        $drawnNumbersOrdered = array_map('intval', array_column($boards, 'number'));
        $totalNumbersGenerated = count($drawnNumbersOrdered);
        $selectedNumbers = $drawnNumbersOrdered;

        $lastNumberRow = !empty($boards) ? end($boards) : null;
        $lastNumber = $lastNumberRow ? (int) $lastNumberRow['number'] : '';

        // Últimas 5 balotas para el historial
        $isLiveGame = ((int) ($game['type'] ?? 0)) === 3 || ((int) ($game['type'] ?? 0)) === 4;
        $historyFetchLimit = $isLiveGame ? 4 : 5;
        $recentBoards = $modelBoards->where('game', $gameId)
                                   ->where('status', 1)
                                   ->orderBy('created_at', 'DESC')
                                   ->orderBy('id', 'DESC')
                                   ->limit($historyFetchLimit)
                                   ->findAll();
        if (!$isLiveGame && count($recentBoards) > 0) {
            array_shift($recentBoards);
        }
        $fourNumbers = array_map('intval', array_reverse(array_column($recentBoards, 'number')));

        // 2. Estado de finalización de la partida
        $singsCountFinished = $modelSings->select('modality')->where('game', $gameId)->groupBy('modality')->countAllResults();
        $awardsCountFinished = $modelAwards->where('game', $gameId)->where('status', 1)->countAllResults();
        $isGameCompleted = ($totalNumbersGenerated >= 75)
            || ($awardsCountFinished > 0 && $singsCountFinished >= $awardsCountFinished);
        $isLiveFinalized = ((int) ($game['status'] ?? 0) === 0);
        $gameIsFinished = $isLiveFinalized || $isGameCompleted;

        // 3. Cartones exclusivos del jugador observado
        $cartons = $modelCartons->where('game', $gameId)
                                ->where('user', $userId)
                                ->where('status', 1)
                                ->findAll();

        $cartonData = [];
        foreach ($cartons as $carton) {
            $numbers = $modelNumbersCartons->where('carton', $carton['id'])
                                           ->orderBy('position', 'ASC')
                                           ->findAll();
            $cartonData[] = [
                'cartonId' => (int) $carton['id'],
                'serial'   => $carton['serial'],
                'numbers'  => $numbers
            ];
        }

        // 4. Modalidades activas y premios
        $modalities = [];
        if (!empty($game['modalities'])) {
            $modIds = explode(',', $game['modalities']);
            $modalities = $modelModalities->whereIn('id', $modIds)->findAll();
            foreach ($modalities as &$modality) {
                $award = $modelAwards->where('game', $gameId)
                                     ->where('modality', $modality['id'])
                                     ->where('status', 1)
                                     ->first();
                $modality['amount'] = $award['amount'] ?? 0;
            }
        }

        // 5. Ganadores oficiales
        $officialSings = bingo_get_official_sings_for_game($gameId, true);
        $singsModalities = array_column($officialSings, 'modality');
        $winners = [];
        foreach ($officialSings as $officialSing) {
            $u = $modelUsers->find($officialSing['user']);
            $wmodality = $modelModalities->find($officialSing['modality']);
            $winners[] = [
                'id'            => (int) ($officialSing['id'] ?? 0),
                'user'          => (int) $officialSing['user'],
                'game'          => (int) $officialSing['game'],
                'carton'        => (int) $officialSing['carton'],
                'modality'      => (int) $officialSing['modality'],
                'modalityId'    => (int) $officialSing['modality'],
                'modalityName'  => $wmodality ? translate($wmodality['name'] ?? '') : 'Bingo',
                'player'        => $u ? trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? '')) : ('Jugador #' . $officialSing['user']),
                'modality_name' => $wmodality ? translate($wmodality['name'] ?? '') : 'Bingo',
                'image'         => !empty($u['image']) ? site_url('uploads/users/' . $u['image']) : site_url('assets/img/avatar.jpg'),
            ];
        }

        // Función auxiliar de letra de columna
        $getClass = function ($number) {
            $num = (int) $number;
            if ($num <= 15) return 'B';
            if ($num <= 30) return 'I';
            if ($num <= 45) return 'N';
            if ($num <= 60) return 'G';
            return 'O';
        };

        // Nombre público y avatar del jugador observado
        $playerName = trim(($player['firstname'] ?? '') . ' ' . ($player['lastname'] ?? ''));
        if ($playerName === '') {
            $playerName = $player['username'] ?? 'Jugador';
        }
        $playerImage = !empty($player['image'])
            ? site_url('uploads/users/' . $player['image'])
            : site_url('assets/img/avatar.jpg');

        $data = [
            'token'                  => $token,
            'game'                   => $game,
            'player'                 => $player,
            'playerName'             => $playerName,
            'playerImage'            => $playerImage,
            'cartons'                => $cartonData,
            'selectedNumbers'        => $selectedNumbers,
            'drawnNumbersOrdered'    => $drawnNumbersOrdered,
            'lastNumber'             => $lastNumber,
            'fourNumbers'            => $fourNumbers,
            'totalNumbersGenerated'  => $totalNumbersGenerated,
            'modalities'             => $modalities,
            'singsModalities'        => $singsModalities,
            'winners'                => $winners,
            'getClass'               => $getClass,
            'isLiveGame'             => $isLiveGame,
            'isGameCompleted'        => $isGameCompleted,
            'isLiveFinalized'        => $isLiveFinalized,
            'gameIsFinished'         => $gameIsFinished,
        ];

        return view('spectator/index', $data);
    }

    /**
     * Endpoint ligero para reconexión y sincronización de estado del espectador
     */
    public function status(string $token = '')
    {
        $token = trim($token);
        if ($token === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'Token inválido']);
        }

        $spectatorTokensModel = new GameSpectatorTokensModel();
        $shareInfo = $spectatorTokensModel->getShareInfoByToken($token);

        if (!$shareInfo) {
            return $this->response->setJSON(['success' => false, 'message' => 'Token no encontrado']);
        }

        $gameId = (int) $shareInfo['game_id'];
        $modelGames = new GamesModel();
        $game = $modelGames->find($gameId);

        if (!$game) {
            return $this->response->setJSON(['success' => false, 'message' => 'Partida no encontrada']);
        }

        $modelBoards = new BoardsModel();
        $boards = $modelBoards->where('game', $gameId)
                             ->where('status', 1)
                             ->orderBy('created_at', 'ASC')
                             ->orderBy('id', 'ASC')
                             ->findAll();
        $drawnNumbers = array_map('intval', array_column($boards, 'number'));
        $totalNumbers = count($drawnNumbers);
        $lastNumber = !empty($drawnNumbers) ? end($drawnNumbers) : null;

        $isLiveGame = ((int) ($game['type'] ?? 0)) === 3 || ((int) ($game['type'] ?? 0)) === 4;
        $historyFetchLimit = $isLiveGame ? 4 : 5;
        $recentBoards = $modelBoards->where('game', $gameId)
                                   ->where('status', 1)
                                   ->orderBy('created_at', 'DESC')
                                   ->orderBy('id', 'DESC')
                                   ->limit($historyFetchLimit)
                                   ->findAll();
        if (!$isLiveGame && count($recentBoards) > 0) {
            array_shift($recentBoards);
        }
        $fourNumbers = array_map('intval', array_reverse(array_column($recentBoards, 'number')));

        $modelSings = new SingsModel();
        $modelAwards = new AwardsModel();
        $singsCountFinished = $modelSings->select('modality')->where('game', $gameId)->groupBy('modality')->countAllResults();
        $awardsCountFinished = $modelAwards->where('game', $gameId)->where('status', 1)->countAllResults();
        $isGameCompleted = ($totalNumbers >= 75) || ($awardsCountFinished > 0 && $singsCountFinished >= $awardsCountFinished);
        $isLiveFinalized = ((int) ($game['status'] ?? 0) === 0);
        $gameIsFinished = $isLiveFinalized || $isGameCompleted;

        $officialSings = bingo_get_official_sings_for_game($gameId, true);

        return $this->response->setJSON([
            'success'               => true,
            'game_id'               => $gameId,
            'status'                => (int) ($game['status'] ?? 0),
            'drawnNumbers'          => $drawnNumbers,
            'lastNumber'            => $lastNumber,
            'fourNumbers'           => $fourNumbers,
            'totalNumbersGenerated' => $totalNumbers,
            'gameIsFinished'        => $gameIsFinished,
            'winners'               => $officialSings
        ]);
    }
}
