<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\PaymentsModel;
use App\Models\BoardsModel;
use App\Models\GamesModel;
use App\Models\CartonsModel;
use App\Models\NumbersCartonsModel;
use App\Models\ModalitiesModel;
use App\Models\SingsModel;
use App\Models\AwardsModel;
use App\Models\NotificationsModel;
use App\Models\GameRoomsModel;
use CodeIgniter\Controller;

class Cron extends Controller
{
    // Variable para controlar el último tiempo de creación de juegos
    private static $lastGameCreation = null;

    /** @var array<string, resource> */
    private array $cronLockHandles = [];

    // Plantillas de descripciones creativas
    private $gameBaseTexts = [
        'SUPER PARTIDA',
        'GRAN BINGO',
        'PARTIDA ESPECIAL',
        'BINGO PREMIUM',
        'PARTIDA REAL',
        'BINGO DORADO',
        'GRAN PREMIO',
        'PARTIDA ELITE',
        'BINGO MASTER',
        'PARTIDA IMPERIAL',
        'BINGO CHAMPION',
        'GRAN FORTUNA',
        'PARTIDA LEGEND',
        'BINGO ROYAL',
        'PARTIDA SUPREME',
        'BINGO DELUXE',
        'GRAN FESTIVAL',
        'PARTIDA GOLDEN',
        'BINGO ULTIMATE',
        'PARTIDA MAGIC',
        'BINGO STELLAR'
    ];

    private $emojiCategories = [
        'celebration' => ['🎉', '🎊', '🥳', '🎈', '🎆', '🎇'],
        'royalty' => ['👑', '💎', '🏰', '👸', '🤴', '💰', '🔱', '👑'],
        'trophies' => ['🏆', '🥇', '🥈', '🥉', '🏅', '🎖️', '🎯', '🎪', '🎭'],
        'hearts' => ['❤️', '💖', '💝', '💗', '💓', '💕', '💘', '💌', '💟', '♥️'],
        'luck' => ['🍀', '🌈', '🎲', '🎰', '🔮', '🌙', '☀️', '⚡', '🔥', '💥'],
        'space' => ['🚀', '🌌', '🛸', '🌠', '🪐', '🌕', '🌟', '✨', '💫', '⭐'],
        'gems' => ['💎', '🔷', '🔶', '🔸', '🔹', '💠', '🔺', '🔻']
    ];

    private function generateGameDescription()
    {
        // Seleccionar texto base aleatorio
        $baseText = $this->gameBaseTexts[array_rand($this->gameBaseTexts)];
        
        // Seleccionar dos categorías diferentes de emojis
        $categoryKeys = array_keys($this->emojiCategories);
        $firstCategory = $categoryKeys[array_rand($categoryKeys)];
        
        // Asegurar que la segunda categoría sea diferente
        do {
            $secondCategory = $categoryKeys[array_rand($categoryKeys)];
        } while ($secondCategory === $firstCategory);
        
        // Seleccionar emojis aleatorios de cada categoría
        $firstEmoji = $this->emojiCategories[$firstCategory][array_rand($this->emojiCategories[$firstCategory])];
        $secondEmoji = $this->emojiCategories[$secondCategory][array_rand($this->emojiCategories[$secondCategory])];
        
        // Generar descripción con formato profesional
        return "{$firstEmoji} {$baseText} {$secondEmoji}";
    }

    // Método alternativo para generar descripciones más elaboradas
    private function generateAdvancedGameDescription()
    {
        $prefixes = [
            'GRAN', 'SUPER', 'MEGA', 'ULTRA', 'MAXI', 'PREMIUM', 'DELUXE', 'ROYAL',
            'HYPER', 'TURBO', 'POWER', 'GIGA', 'INFINITY', 'MAGNO', 'SUPRA', 'TOP',
            'EXTRA', 'ULTIMATE', 'FANTASY', 'COSMIC', 'TITAN', 'ESPECIAL', 'GALAXY', 'ASTRO'
        ];

        $gameTypes = [
            'BINGO', 'PARTIDA', 'TORNEO', 'FESTIVAL', 'EVENTO',
            'COMPETENCIA', 'CHAMPIONSHIP', 'MASTER', 'LEGEND',
            'SERIE', 'COPA', 'CLÁSICO', 'SHOW', 'MARATÓN', 'DUEL',
            'ARENA', 'CHALLENGE', 'RALLY', 'LIGA', 'WORLD CUP'
        ];

        $suffixes = [
            'DORADO', 'IMPERIAL', 'REAL', 'VIP', 'ELITE', 'PLATINUM',
            'DIAMOND', 'CRYSTAL', 'STELLAR', 'SUPREME',
            'GALÁCTICO', 'ETERNAL', 'LEGACY', 'MÁGICO',
            'COSMOS', 'UNIVERSAL', 'CELESTIAL', 'LEGENDARIO', 'OLÍMPICO'
        ];
        
        // Construir descripción
        $prefix = $prefixes[array_rand($prefixes)];
        $gameType = $gameTypes[array_rand($gameTypes)];
        $suffix = $suffixes[array_rand($suffixes)];
        
        // Seleccionar emojis
        $categoryKeys = array_keys($this->emojiCategories);
        $firstCategory = $categoryKeys[array_rand($categoryKeys)];
        do {
            $secondCategory = $categoryKeys[array_rand($categoryKeys)];
        } while ($secondCategory === $firstCategory);
        
        $firstEmoji = $this->emojiCategories[$firstCategory][array_rand($this->emojiCategories[$firstCategory])];
        $secondEmoji = $this->emojiCategories[$secondCategory][array_rand($this->emojiCategories[$secondCategory])];
        
        return "{$firstEmoji} {$prefix} {$gameType} {$suffix} {$secondEmoji}";
    }

    // Método para usar en tu función createAutoGame
    private function getRandomGameDescription()
    {
        // 70% probabilidad de descripción simple, 30% de descripción avanzada
        if (rand(1, 100) <= 70) {
            return $this->generateGameDescription();
        } else {
            return $this->generateAdvancedGameDescription();
        }
    }

    // 1) Función principal para crear juegos automáticos
    public function runAutoAddGames()
    {
        if (systemGet('activateAddGames') != 1) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Creación automática de juegos desactivada'
            ]);
        }

        $modelGames = new GamesModel();
        
        // Validar máximo 3 juegos automáticos pendientes (status = 1 o 2)
        $pendingGamesCount = $modelGames->where('type', 1)
            ->whereIn('status', [1, 2])
            ->countAllResults();

        if ($pendingGamesCount >= 3) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Máximo de 3 juegos automáticos pendientes/activos alcanzado.'
            ]);
        }

        $addGamesTime = (int) systemGet('addGamesTime'); // Intervalo en minutos
        if ($addGamesTime <= 0) {
            $addGamesTime = 30;
        }
        $tzName = function_exists('app_timezone') ? app_timezone() : (config('App')->appTimezone ?? 'America/Guayaquil');
        $tz = new \DateTimeZone($tzName);
        $now = new \DateTime('now', $tz);

         // Obtener horarios desde la configuración
        $addGamesFrom = systemGet('addGamesFrom') ?: '08:00'; // Valor por defecto si no existe
        $addGamesTo = systemGet('addGamesTo') ?: '22:30';     // Valor por defecto si no existe

        // Extraer horas y minutos
        list($startHour, $startMinute) = array_map('intval', explode(':', $addGamesFrom));
        list($endHour, $endMinute) = array_map('intval', explode(':', $addGamesTo));

        // Verificar si estamos fuera del horario permitido
        $currentHour = (int) $now->format('H');
        $currentMinute = (int) $now->format('i');

        // Registrar información de depuración
        log_message('info', "runAutoAddGames: Hora actual: {$currentHour}:{$currentMinute}, Horario permitido: {$startHour}:{$startMinute}-{$endHour}:{$endMinute}");
        
        // Si es después del horario de fin
        if ($currentHour > $endHour || ($currentHour == $endHour && $currentMinute > $endMinute)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => "Fuera del horario permitido. Los juegos se pueden crear de {$addGamesFrom} a {$addGamesTo}",
                'current_time' => $now->format('H:i')
            ]);
        }

        // Si es antes del horario de inicio
        if ($currentHour < $startHour || ($currentHour == $startHour && $currentMinute < $startMinute)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => "Fuera del horario permitido. Los juegos se pueden crear de {$addGamesFrom} a {$addGamesTo}",
                'current_time' => $now->format('H:i')
            ]);
        }

        // Obtener el último juego programado
        $lastAutoGame = $modelGames->where('type', 1)
            ->orderBy('date', 'DESC')
            ->orderBy('time', 'DESC')
            ->first();

        // Determinar la próxima fecha/hora del juego
        $nextGame = $this->calculateNextGameTime($lastAutoGame, $now, $addGamesTime, $startHour, $endHour, $endMinute, $tz);

        // Verificar que no exista duplicado y encontrar slot disponible
        $nextGame = $this->findAvailableSlot($modelGames, $nextGame, $addGamesTime, $startHour, $endHour, $endMinute);

        if (!$nextGame) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se encontró slot disponible para crear juego'
            ]);
        }

        // Crear el juego
        $result = $this->createAutoGame([
            'date' => $nextGame->format('Y-m-d'),
            'time' => $nextGame->format('H:i:s')
        ]);

        if ($result['success']) {
            log_message('info', "Juego automático creado: ID {$result['game_id']} para {$nextGame->format('Y-m-d H:i:s')}");
            
            // Enviar notificación Push por Firebase
            helper('firebase_push');
            $horaFormateada = $nextGame->format('h:i A');
            send_firebase_push_to_all(
                '¡Nueva partida de Bingo!', 
                "Se ha programado una nueva partida para las {$horaFormateada}. ¡Entra y compra tus cartones!"
            );

            return $this->response->setJSON($result);
        }

        log_message('error', "runAutoAddGames: error al crear juego para {$nextGame->format('Y-m-d H:i:s')}: " . ($result['message'] ?? 'sin detalle'));
        return $this->response->setJSON([
            'success' => false,
            'message' => $result['message'] ?? 'Error al crear el juego automático'
        ]);
    }

    private function calculateNextGameTime($lastAutoGame, $now, $addGamesTime, $startHour, $endHour, $endMinute, $tz)
    {
        if ($lastAutoGame) {
            $lastDateTime = new \DateTime("{$lastAutoGame['date']} {$lastAutoGame['time']}", $tz);
            
            // Si el último juego es en el futuro, usar como base
            if ($lastDateTime > $now) {
                $nextGame = (clone $lastDateTime)->modify("+{$addGamesTime} minutes");
            } else {
                // Si el último juego ya pasó, usar tiempo actual + intervalo
                $nextGame = (clone $now)->modify("+{$addGamesTime} minutes");
            }
        } else {
            // No hay juegos previos -> usar directamente la hora actual
            $nextGame = clone $now;
        }

        // Ajustar si queda fuera del horario permitido
        return $this->adjustGameTimeToAllowedHours($nextGame, $startHour, $endHour, $endMinute);
    }

    private function adjustGameTimeToAllowedHours($gameTime, $startHour, $endHour, $endMinute)
    {
        $hour = (int) $gameTime->format('H');
        $minute = (int) $gameTime->format('i');

        // Si es antes de las 7:00, mover a las 7:00 del mismo día
        if ($hour < $startHour) {
            $gameTime->setTime($startHour, 0, 0);
        }
        // Si es después de las 22:30, mover a las 7:00 del día siguiente
        elseif ($hour > $endHour || ($hour == $endHour && $minute > $endMinute)) {
            $gameTime->modify('+1 day')->setTime($startHour, 0, 0);
        }

        return $gameTime;
    }

    private function findAvailableSlot($modelGames, $nextGame, $addGamesTime, $startHour, $endHour, $endMinute)
    {
        $maxAttempts = 200;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            // Verificar si ya existe un juego en esta fecha/hora
            $exists = $modelGames
                ->where('type', 1)
                ->where('date', $nextGame->format('Y-m-d'))
                ->where('time', $nextGame->format('H:i:s'))
                ->first();

            if (!$exists) {
                return $nextGame; // Slot disponible encontrado
            }

            // Si existe, avanzar al siguiente intervalo
            $nextGame->modify("+{$addGamesTime} minutes");
            
            // Ajustar si se sale del horario permitido
            $nextGame = $this->adjustGameTimeToAllowedHours($nextGame, $startHour, $endHour, $endMinute);

            $attempt++;
        }

        // No se encontró slot disponible
        log_message('error', "findAvailableSlot: superado maxAttempts al buscar slot disponible");
        return null;
    }

    // 2) Iniciar juegos automáticos cuando llegue su fecha/hora
    public function checkAutoGames()
    {
        if (systemGet('activateCron') == 1) {
            $modelGames = new GamesModel();

            $now = date('Y-m-d H:i:s');
            // Juegos automáticos (type=1), programados (status=0) cuya fecha/hora ya pasó
            $games = $modelGames->where('type', 1)->where('status', 1)->where("CONCAT(date, ' ', time) <=", $now)->findAll();

            /*foreach ($games as $game) {
                // Marcar en curso
                $modelGames->update($game['id'], [
                    'status' => 1,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                // Sin tabla extra: el "tick" será la última fila en boards (si no hay, se fuerza un tick "virtual")
                // No insertamos una bola aquí; el primer número lo genera runAutoGames cuando pase el timeBallGet.
            }*/

            return $this->response->setJSON(['ok' => true, 'started' => count($games)]);
        }
    }

    // 2) Ejecutar generación automática de números según singBall
    /*public function runAutoGames()
    {
        if (systemGet('activateCron') == 1) {
            $modelGames  = new GamesModel();
            $modelBoards = new BoardsModel();

            // singBall = "15000-5000"
            $singBall = systemGet('singBall');
            [$timeBallGet, $timeBallLast] = explode('-', $singBall);
            $timeBallGet = (int) $timeBallGet; // ms

            $now = date('Y-m-d H:i:s');
            $currentDate = date('Y-m-d');
            $currentTime = date('H:i:s');

            // 1) PRIMERO: Verificar juegos que deben iniciar ahora
            $gamesToStart = $modelGames->where('type', 1)->where('status', 2) ->where('date', $currentDate)->where('time <=', $currentTime)->findAll();

            foreach ($gamesToStart as $gameToStart) {
                // Iniciar el juego cambiando status a 1
                $modelGames->update($gameToStart['id'], [
                    'status' => 1,
                    'updated_at' => $now
                ]);
                
                log_message('info', "Juego {$gameToStart['id']} iniciado automáticamente a las {$now}");
            }

            // 2) SEGUNDO: Obtener TODOS los juegos activos que deben procesar bolas
            $activeGames = $modelGames->where('type', 1)->where('status', 1)->where('date', $currentDate)->where('time <=', $currentTime)->findAll();

            $ballsCanted = 0;
            $gamesProcessed = [];

            foreach ($activeGames as $game) {
                $gameId = (int)$game['id'];
                $gamesProcessed[] = $gameId;

                // 2.1) Validar fin de juego
                if ($this->isGameCompleted($gameId)) {
                    $modelGames->update($gameId, [
                        'status' => 0, // finalizado
                        'updated_at' => $now
                    ]);
                    bingo_on_game_finished($gameId);
                    log_message('info', "Juego {$gameId} finalizado automáticamente");
                    continue;
                }

                // 2.2) Verificar si debe cantar bola (control de cadencia)
                if (!$this->shouldCantBall($gameId, $timeBallGet, $now)) {
                    log_message('info', "Juego {$gameId} - aún no toca cantar bola");
                    continue;
                }

                // 2.3) Pausa por sing reciente
                if ($this->hasRecentSingPause($gameId, 10)) {
                    log_message('info', "Juego {$gameId} - pausa por sing reciente");
                    continue;
                }

                // 2.4) Generar y cantar bola
                $number = $this->generateUniqueNumber($gameId);

                $modelBoards->insert([
                    'user'       => $game['user'] ?? 1,
                    'game'       => $gameId,
                    'number'     => $number,
                    'status'     => 1,
                    'created_at' => $now
                ]);

                $ballsCanted++;
                log_message('info', "BOLA CANTADA: {$number} en juego {$gameId} a las {$now}");
                bingo_broadcast_number_drawn((int) $gameId, (int) $number);

                // 2.5) Procesar la bola cantada
                $this->dialNumber($number, $gameId);
                $this->singBingo($gameId);

                // 2.6) Verificar si se completó tras cantar
                if ($this->isGameCompleted($gameId)) {
                    $modelGames->update($gameId, [
                        'status' => 0,
                        'updated_at' => $now
                    ]);
                    bingo_on_game_finished($gameId);
                    log_message('info', "Juego {$gameId} completado tras cantar bola {$number}");
                }
            }

            return $this->response->setJSON([
                'ok' => true,
                'games_started' => count($gamesToStart),
                'active_games' => count($activeGames),
                'games_processed' => $gamesProcessed,
                'balls_canted' => $ballsCanted,
                'timestamp' => $now,
                'current_time' => $currentTime
            ]);
        }

        return $this->response->setJSON(['ok' => false, 'message' => 'Cron desactivado']);
    }*/

    /*ANTERIORpublic function runAutoGames($fromSequence = false)
    {
        if (systemGet('activateCron') == 1) {
            $modelGames  = new GamesModel();
            $modelBoards = new BoardsModel();

            // singBall = "15000-5000"
            $singBall = systemGet('singBall');
            [$timeBallGet, $timeBallLast] = explode('-', $singBall);
            $timeBallGet = (int) $timeBallGet; // ms

            $now = date('Y-m-d H:i:s');
            $currentDate = date('Y-m-d');
            $currentTime = date('H:i:s');

            // 1) PRIMERO: Verificar juegos que deben iniciar ahora
            $gamesToStart = $modelGames->where('type', 1)->where('status', 2)->where('date', $currentDate)->where('time <=', $currentTime)->findAll();

            foreach ($gamesToStart as $gameToStart) {
                // Iniciar el juego cambiando status a 1
                $modelGames->update($gameToStart['id'], [
                    'status' => 1,
                    'updated_at' => $now
                ]);
                
                log_message('info', "Juego {$gameToStart['id']} iniciado automáticamente a las {$now}");
            }

            // 2) SEGUNDO: Obtener TODOS los juegos activos que deben procesar bolas
            $activeGames = $modelGames->where('type', 1)->where('status', 1)->where('date', $currentDate)->where('time <=', $currentTime)->findAll();

            $ballsCanted = 0;
            $gamesProcessed = [];

            foreach ($activeGames as $game) {
                $gameId = (int)$game['id'];
                $gamesProcessed[] = $gameId;

                // 2.1) Validar fin de juego
                if ($this->isGameCompleted($gameId)) {
                    $modelGames->update($gameId, [
                        'status' => 0, // finalizado
                        'updated_at' => $now
                    ]);
                    bingo_on_game_finished($gameId);
                    log_message('info', "Juego {$gameId} finalizado automáticamente");
                    continue;
                }

                // 2.2) Verificar si debe cantar bola (control de cadencia)
                // Si viene de la secuencia, ignoramos esta verificación
                if (!$fromSequence && !$this->shouldCantBall($gameId, $timeBallGet, $now)) {
                    log_message('info', "Juego {$gameId} - aún no toca cantar bola");
                    continue;
                }

                // 2.3) Pausa por sing reciente
                if ($this->hasRecentSingPause($gameId, 10)) {
                    log_message('info', "Juego {$gameId} - pausa por sing reciente");
                    continue;
                }

                // 2.4) Generar y cantar bola
                $number = $this->generateUniqueNumber($gameId);

                $modelBoards->insert([
                    'user'       => $game['user'] ?? 1,
                    'game'       => $gameId,
                    'number'     => $number,
                    'status'     => 1,
                    'created_at' => $now
                ]);

                $ballsCanted++;
                log_message('info', "BOLA CANTADA: {$number} en juego {$gameId} a las {$now}");
                bingo_broadcast_number_drawn((int) $gameId, (int) $number);

                // 2.5) Procesar la bola cantada
                $this->dialNumber($number, $gameId);
                $this->singBingo($gameId);

                // 2.6) Verificar si se completó tras cantar
                if ($this->isGameCompleted($gameId)) {
                    $modelGames->update($gameId, [
                        'status' => 0,
                        'updated_at' => $now
                    ]);
                    bingo_on_game_finished($gameId);
                    log_message('info', "Juego {$gameId} completado tras cantar bola {$number}");
                }
            }

            return $this->response->setJSON([
                'ok' => true,
                'games_started' => count($gamesToStart),
                'active_games' => count($activeGames),
                'games_processed' => $gamesProcessed,
                'balls_canted' => $ballsCanted,
                'timestamp' => $now,
                'current_time' => $currentTime,
                'from_sequence' => $fromSequence
            ]);
        }

        return $this->response->setJSON(['ok' => false, 'message' => 'Cron desactivado']);
    }*/

    public function runAutoGames($fromSequence = false)
    {
        $result = $this->processAutoGames((bool) $fromSequence, true);

        return $this->response->setJSON($result);
    }

    /**
     * Nucleo del cron (HTTP + CLI). Inicia partidas automaticas y canta balotas con catch-up.
     */
    public function processAutoGames(bool $fromSequence = false, bool $useLock = true): array
    {
        if (systemGet('activateCron') != 1) {
            return ['ok' => false, 'message' => 'Cron desactivado'];
        }

        if ($useLock && ! $this->acquireCronLock('auto_games', 55)) {
            return ['ok' => true, 'skipped' => true, 'message' => 'Cron ya en ejecucion'];
        }

        try {
            return $this->doProcessAutoGames($fromSequence);
        } finally {
            if ($useLock) {
                $this->releaseCronLock('auto_games');
            }
        }
    }

    private function doProcessAutoGames(bool $fromSequence = false): array
    {
        helper('bingo');

        $modelGames  = new GamesModel();
        $modelBoards = new BoardsModel();

        $singBall = (string) (systemGet('singBall') ?: '15000-5000');
        $parts = explode('-', $singBall);
        $timeBallGet = max(1000, (int) ($parts[0] ?? 15000));

        $tzName = function_exists('app_timezone') ? app_timezone() : (config('App')->appTimezone ?? 'America/Guayaquil');
        $tz = new \DateTimeZone($tzName);
        $nowObj = new \DateTime('now', $tz);

        $now = $nowObj->format('Y-m-d H:i:s');
        $currentDate = $nowObj->format('Y-m-d');
        $currentTime = $nowObj->format('H:i:s');

        // 1) Iniciar partidas automáticas cuya fecha/hora ya llegó (incluye días anteriores atrasados)
        $gamesToStart = $modelGames->where('type', 1)
            ->where('status', 2)
            ->findAll();

        $startedIds = [];
        foreach ($gamesToStart as $gameToStart) {
            if (! bingo_game_is_due($gameToStart)) {
                continue;
            }

            $postpone = bingo_postpone_game($gameToStart);
            if ($postpone['postponed']) {
                log_message('info', "Juego {$gameToStart['id']} pospuesto automáticamente: {$postpone['message']}");
                continue;
            }

            $modelGames->update($gameToStart['id'], [
                'status' => 1,
                'updated_at' => $now,
            ]);
            $startedIds[] = (int) $gameToStart['id'];
            bingo_broadcast_game_status((int) $gameToStart['id'], 'game:started', ['status' => 1]);
            log_message('info', "Juego {$gameToStart['id']} iniciado automáticamente a las {$now}");
        }

        // 2) Procesar todas las partidas activas automáticas (sin filtrar solo por "hoy")
        $activeGames = $modelGames->where('type', 1)
            ->where('status', 1)
            ->findAll();

        // Máx. balotas por tick para recuperar atraso (p. ej. cron cada 1 min)
        $maxCatchUp = (int) max(3, min(12, (int) floor(60000 / $timeBallGet) + 2));

        $ballsCanted = 0;
        $gamesProcessed = [];
        $gamesCompleted = [];

        foreach ($activeGames as $game) {
            $gameId = (int) $game['id'];
            $gamesProcessed[] = $gameId;

            $numbersDrawn = $modelBoards->where('game', $gameId)->countAllResults();

            // Seguridad: si quedó activa antes de hora (p. ej. por un bug), no cantar y volver a programada
            if ($numbersDrawn === 0 && ! bingo_game_is_due($game)) {
                $modelGames->update($gameId, [
                    'status' => 2,
                    'updated_at' => $now,
                ]);
                log_message('warning', "Juego {$gameId} reprogramado (status 2): se intentó cantar antes de la hora");
                continue;
            }

            if ($numbersDrawn === 0) {
                $postpone = bingo_postpone_game($game);
                if ($postpone['postponed']) {
                    log_message('info', "Juego {$gameId} pospuesto antes de cantar (sin mínimos): {$postpone['message']}");
                    continue;
                }
            }

            // No cantar en el mismo tick que se activó la partida (evita 2 bolas si hay 2 crons)
            if ($numbersDrawn === 0 && in_array($gameId, $startedIds, true)) {
                log_message('info', "Juego {$gameId} recién iniciado: primera bola en el siguiente ciclo");
                continue;
            }

            if ($this->isGameCompleted($gameId)) {
                $modelGames->update($gameId, [
                    'status' => 0,
                    'updated_at' => $now,
                ]);
                bingo_on_game_finished($gameId);
                bingo_broadcast_game_status((int) $gameId, 'game:game_finished', ['status' => 0]);
                $gamesCompleted[] = $gameId;
                log_message('info', "Juego {$gameId} finalizado automáticamente - ya completado");
                continue;
            }

            $ballsToDraw = $fromSequence
                ? 1
                : $this->ballsDueCount($gameId, $timeBallGet, $now, $maxCatchUp);

            // Primera bola: siempre máximo 1 (nunca catch-up de varias al arrancar)
            if ($numbersDrawn === 0) {
                $ballsToDraw = min(1, $ballsToDraw);
            }

            if ($ballsToDraw <= 0) {
                log_message('info', "Juego {$gameId} - aún no toca cantar bola");
                continue;
            }

            $intervalSec = max(1, (int) floor($timeBallGet / 1000));

            for ($b = 0; $b < $ballsToDraw; $b++) {
                if ($this->isGameCompleted($gameId)) {
                    break;
                }

                if ($this->hasRecentSingPause($gameId, 10)) {
                    log_message('info', "Juego {$gameId} - pausa por sing reciente");
                    break;
                }

                // Candado por partida: evita 2 bolas si dos crons pasan a la vez
                $ballLock = 'ball_game_' . $gameId;
                if (! $this->acquireCronLock($ballLock, 8)) {
                    log_message('info', "Juego {$gameId} - otro proceso está cantando bola");
                    break;
                }

                try {
                    // Revalidar intervalo justo antes de cantar
                    if (! $this->canDrawBallNow($gameId, $timeBallGet, $now)) {
                        log_message('info', "Juego {$gameId} - bola omitida (intervalo o ya cantada por otro proceso)");
                        break;
                    }

                    $number = null;
                    $inserted = false;
                    for ($attempt = 0; $attempt < 8; $attempt++) {
                        $candidate = $this->generateUniqueNumber($gameId);
                        if ($candidate === null || $candidate === false || $candidate === 0) {
                            break;
                        }

                        $createdAt = $now;
                        if ($ballsToDraw > 1 && ! $fromSequence) {
                            try {
                                $createdAtObj = clone $nowObj;
                                $backSec = ($ballsToDraw - 1 - $b) * $intervalSec;
                                if ($backSec > 0) {
                                    $createdAtObj->modify('-' . $backSec . ' seconds');
                                }
                                $createdAt = $createdAtObj->format('Y-m-d H:i:s');
                            } catch (\Exception $e) {
                                $createdAt = $now;
                            }
                        }

                        $inserted = bingo_insert_drawn_number((int) $gameId, (int) $candidate, [
                            'user'       => $game['user'] ?? 1,
                            'isCRON'     => 1,
                            'created_at' => $createdAt,
                        ]);

                        if ($inserted) {
                            $number = (int) $candidate;
                            break;
                        }

                        log_message('warning', "Juego {$gameId}: número {$candidate} duplicado, reintentando");
                    }

                    if (! $inserted || ! $number) {
                        log_message('warning', "Juego {$gameId}: no se pudo insertar bola única");
                        break;
                    }

                    $ballsCanted++;
                    log_message('info', "BOLA CANTADA: {$number} en juego {$gameId} a las {$now} (catch-up " . ($b + 1) . "/{$ballsToDraw})");
                    bingo_broadcast_number_drawn((int) $gameId, (int) $number);

                    $this->dialNumber($number, $gameId);
                    $this->singBingo($gameId);
                } finally {
                    $this->releaseCronLock($ballLock);
                }

                if ($this->isGameCompleted($gameId)) {
                    $modelGames->update($gameId, [
                        'status' => 0,
                        'updated_at' => $now,
                    ]);
                    bingo_on_game_finished($gameId);
                    bingo_broadcast_game_status((int) $gameId, 'game:game_finished', ['status' => 0]);
                    $gamesCompleted[] = $gameId;
                    log_message('info', "Juego {$gameId} completado tras cantar bola {$number}");
                    break;
                }
            }
        }

        return [
            'ok' => true,
            'games_started' => count($startedIds),
            'started_ids' => $startedIds,
            'active_games' => count($activeGames),
            'games_processed' => $gamesProcessed,
            'games_completed' => $gamesCompleted,
            'balls_canted' => $ballsCanted,
            'timestamp' => $now,
            'current_date' => $currentDate,
            'current_time' => $currentTime,
            'interval_ms' => $timeBallGet,
            'max_catch_up' => $maxCatchUp,
            'from_sequence' => $fromSequence,
        ];
    }

    private function ballsDueCount(int $gameId, int $timeBallGet, string $now, int $maxCatchUp): int
    {
        $lastBall = $this->getLastBall($gameId);
        if (! $lastBall) {
            return 1;
        }

        $msDiff = $this->diffMs($lastBall['created_at'], $now);
        if ($msDiff < $timeBallGet) {
            return 0;
        }

        $due = (int) floor($msDiff / max(1, $timeBallGet));

        return max(1, min($maxCatchUp, $due));
    }

    /** True si corresponde cantar una bola ahora (relee DB para evitar duplicados). */
    private function canDrawBallNow(int $gameId, int $timeBallGet, string $now): bool
    {
        $lastBall = $this->getLastBall($gameId);
        if (! $lastBall) {
            return true;
        }

        return $this->diffMs($lastBall['created_at'], $now) >= $timeBallGet;
    }

    private function acquireCronLock(string $name, int $ttlSeconds): bool
    {
        $dir = WRITEPATH . 'cache';
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $path = $dir . DIRECTORY_SEPARATOR . 'cron_' . preg_replace('/[^a-z0-9_]/i', '', $name) . '.lock';

        $fh = @fopen($path, 'c+');
        if ($fh === false) {
            return false;
        }

        // Candado exclusivo no bloqueante (evita que dos crons canten a la vez)
        if (! flock($fh, LOCK_EX | LOCK_NB)) {
            fclose($fh);

            return false;
        }

        $this->cronLockHandles[$name] = $fh;

        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, (string) getmypid() . "\n" . time() . "\n" . $ttlSeconds);
        fflush($fh);

        return true;
    }

    private function releaseCronLock(string $name): void
    {
        if (! empty($this->cronLockHandles[$name]) && is_resource($this->cronLockHandles[$name])) {
            $fh = $this->cronLockHandles[$name];
            flock($fh, LOCK_UN);
            fclose($fh);
            unset($this->cronLockHandles[$name]);
        }
    }

    /*ANTERIORpublic function ballSequence()
    {
        // Configurar para que PHP no termine la ejecución
        ignore_user_abort(true);
        set_time_limit(65); // Un poco más de 1 minuto

        // Obtener la configuración de tiempo entre bolas
        $singBall = systemGet('singBall');
        [$timeBallGet, $timeBallLast] = explode('-', $singBall);
        $timeBallGet = (int) $timeBallGet; // ms
        
        // Convertir de milisegundos a segundos
        $secondsBetweenBalls = $timeBallGet / 1000;
        
        // Calcular cuántas bolas podemos cantar en un minuto
        $maxBalls = floor(60 / $secondsBetweenBalls);
        
        // Limitar a un máximo razonable (para evitar sobrecarga)
        if ($maxBalls > 12) {
            $maxBalls = 12; // Máximo 12 bolas por minuto (cada 5 segundos)
        }
        
        log_message('info', "Iniciando secuencia de bolas: {$maxBalls} bolas cada {$secondsBetweenBalls} segundos");
        
        $results = [];
        $totalBallsCanted = 0;
        
        // Cantar las bolas con el intervalo configurado
        for ($i = 0; $i < $maxBalls; $i++) {
            // Cantar una bola
            $result = $this->runAutoGames(true);
            $data = json_decode($result->getJSON(), true);
            $results[] = $data;
            
            // Sumar las bolas cantadas
            $totalBallsCanted += ($data['balls_canted'] ?? 0);
            
            log_message('info', "Bola " . ($i + 1) . " cantada en secuencia: " . date('Y-m-d H:i:s'));
            
            // Si no es la última bola, esperar el tiempo configurado
            if ($i < $maxBalls - 1) {
                sleep($secondsBetweenBalls);
            }
        }
        
        return $this->response->setJSON([
            'success' => true,
            'message' => "Secuencia completada: {$maxBalls} bolas cantadas cada {$secondsBetweenBalls} segundos",
            'total_balls_canted' => $totalBallsCanted,
            'interval_ms' => $timeBallGet,
            'interval_seconds' => $secondsBetweenBalls,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }*/

    public function ballSequence()
    {
        ignore_user_abort(true);
        set_time_limit(70);

        if (systemGet('activateCron') != 1) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Cron desactivado']);
        }

        if (! $this->acquireCronLock('auto_games', 65)) {
            return $this->response->setJSON(['ok' => true, 'skipped' => true, 'message' => 'Cron ya en ejecución']);
        }

        try {
            $singBall = (string) (systemGet('singBall') ?: '15000-5000');
            $parts = explode('-', $singBall);
            $timeBallGet = max(1000, (int) ($parts[0] ?? 15000));

            $secondsBetweenBalls = max(1, (int) round($timeBallGet / 1000));
            $maxBalls = (int) floor(55 / $secondsBetweenBalls);
            if ($maxBalls < 1) {
                $maxBalls = 1;
            }
            if ($maxBalls > 12) {
                $maxBalls = 12;
            }

            log_message('info', "Iniciando secuencia de bolas: {$maxBalls} bolas cada {$secondsBetweenBalls} segundos");

            $results = [];
            $totalBallsCanted = 0;
            $activeGamesAtStart = $this->getActiveGamesCount();

            for ($i = 0; $i < $maxBalls; $i++) {
                // Sin lock interno: ya tenemos auto_games
                $data = $this->processAutoGames(true, false);
                $results[] = $data;
                $totalBallsCanted += (int) ($data['balls_canted'] ?? 0);

                $currentActiveGames = $this->getActiveGamesCount();
                if ($currentActiveGames == 0 && $i > 0) {
                    log_message('info', 'Secuencia detenida: No hay juegos activos');
                    break;
                }

                if ($i < $maxBalls - 1) {
                    sleep($secondsBetweenBalls);
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'ok' => true,
                'message' => 'Secuencia completada',
                'total_balls_canted' => $totalBallsCanted,
                'active_games_start' => $activeGamesAtStart,
                'active_games_end' => $this->getActiveGamesCount(),
                'interval_ms' => $timeBallGet,
                'interval_seconds' => $secondsBetweenBalls,
                'timestamp' => date('Y-m-d H:i:s'),
            ]);
        } finally {
            $this->releaseCronLock('auto_games');
        }
    }

    // Función helper para contar juegos activos
    private function getActiveGamesCount(): int
    {
        $modelGames = new GamesModel();

        return $modelGames->where('type', 1)
            ->where('status', 1)
            ->countAllResults();
    }

    // Nueva función helper para determinar si debe cantar bola
    private function shouldCantBall(int $gameId, int $timeBallGet, string $now): bool
    {
        $lastBall = $this->getLastBall($gameId);
        
        // Si no hay bolas previas, cantar inmediatamente
        if (!$lastBall) {
            log_message('info', "Juego {$gameId} - primera bola, cantar inmediatamente");
            return true;
        }

        // Verificar tiempo transcurrido desde última bola
        $msDiff = $this->diffMs($lastBall['created_at'], $now);
        $shouldCant = $msDiff >= $timeBallGet;
        
        log_message('info', "Juego {$gameId} - Tiempo desde última bola: {$msDiff}ms, Requerido: {$timeBallGet}ms, ¿Cantar?: " . ($shouldCant ? 'SÍ' : 'NO'));
        
        return $shouldCant;
    }

    // 3) Función para crear un juego automático
    private function createAutoGame(array $schedule = [])
    {
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $modelAwards = new AwardsModel();
        $modelGameRooms = new GameRoomsModel();
        $modelModalities = new ModalitiesModel();
        $modelUsers = new UsersModel();
        $modelNotifications = new NotificationsModel();

        try {
            // Obtener datos necesarios
            $rooms = $modelGameRooms->where('status', 1)->findAll();
            $allModalities = $modelModalities->where('status', 1)->findAll();

            if (empty($rooms) || empty($allModalities)) {
                return ['success' => false, 'message' => 'No hay salas o modalidades disponibles'];
            }

            // Generar datos aleatorios para el juego
            $gameData = $this->generateGameData($rooms, $allModalities);

            // Si se pasó fecha/hora desde runAutoAddGames, sobreescribir
            if (!empty($schedule)) {
                if (isset($schedule['date'])) {
                    $gameData['date'] = $schedule['date'];
                }
                if (isset($schedule['time'])) {
                    $gameData['time'] = $schedule['time'];
                }
            }

            // Forzar tipo automático
            $gameData['type'] = 1;

            // Crear el juego
            $modelGames->insert($gameData);
            $gameId = $modelGames->getInsertID();

            // Crear los premios
            $awardType = (int) (systemGet('autoGameAwardType') ?: 1);
            $awardValue = (float) (systemGet('autoGameAwardValue') ?: 100);
            $totalPrize = ($awardType === 2) ? $awardValue : 100;
            $this->createGameAwards($gameId, $gameData['modalities'], $totalPrize);

            // Generar cartones si está configurado
            /*if (systemGet('generateCartons') >= 1) {
                $this->generateGameCartons($gameId);
            }*/

            // Enviar notificaciones
            $this->sendGameNotifications($gameId, $gameData);

            return [
                'success' => true, 
                'message' => 'Juego automático creado exitosamente',
                'game_id' => $gameId,
                'date' => $gameData['date'] ?? null,
                'time' => $gameData['time'] ?? null,
                'description' => $gameData['description'],
                'price' => $gameData['price']
            ];

        } catch (\Exception $e) {
            log_message('error', 'Error creando juego automático: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al crear juego automático'];
        }
    }

    // 4) Generar datos aleatorios para el juego
    private function generateGameData($rooms, $allModalities)
    {
        // Seleccionar sala
        $configuredRoom = systemGet('autoGameRoom');
        if ($configuredRoom) {
            $selectedRoom = null;
            foreach ($rooms as $room) {
                if ($room['id'] == $configuredRoom) {
                    $selectedRoom = $room;
                    break;
                }
            }
            if (!$selectedRoom) {
                $selectedRoom = $rooms[array_rand($rooms)];
            }
        } else {
            $selectedRoom = $rooms[array_rand($rooms)];
        }

        // Seleccionar modalidades
        $configuredModalities = systemGet('autoGameModalities');
        $modalityIds = [];
        if ($configuredModalities) {
            $configModIds = array_map('intval', array_filter(explode(',', $configuredModalities)));
            foreach ($allModalities as $mod) {
                if (in_array((int) $mod['id'], $configModIds, true)) {
                    $modalityIds[] = $mod['id'];
                }
            }
        }

        if ($modalityIds === []) {
            $numModalities = min(rand(3, 6), count($allModalities));
            $selectedModalities = array_rand($allModalities, $numModalities);
            if (! is_array($selectedModalities)) {
                $selectedModalities = [$selectedModalities];
            }
            foreach ($selectedModalities as $index) {
                $modalityIds[] = $allModalities[$index]['id'];
            }
        }

        // Generar precio aleatorio en rangos específicos (0.25 a 5)
        $priceRangeMap = [
            1 => [0.25, 0.5, 0.75, 1, 1.25, 1.5, 1.75, 2, 2.25, 2.5, 2.75, 3, 3.25, 3.5, 3.75, 4, 4.25, 4.5, 4.75, 5],
            2 => [0.25, 0.5, 0.75, 1, 1.25, 1.5, 1.75, 2, 2.25, 2.5, 2.75, 3],
            3 => [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2, 2.5, 3, 3.5, 4, 4.5, 5],
            4 => [0.25, 0.5, 0.75, 1, 1.25, 1.5, 1.75, 2],
            5 => [1, 1.25, 1.5, 1.75, 2, 2.5, 3, 3.5, 4, 4.5, 5],
        ];
        $priceRangeKey = (int) (systemGet('priceRanges') ?: 1);
        $priceRanges = $priceRangeMap[$priceRangeKey] ?? $priceRangeMap[1];
        $randomPrice = $priceRanges[array_rand($priceRanges)];

        // Generar fecha y hora (entre 5 minutos y 2 horas desde ahora)
        $minMinutes = 5;
        $maxMinutes = 120;
        $randomMinutes = rand($minMinutes, $maxMinutes);
        $gameDateTime = new \DateTime();
        $gameDateTime->add(new \DateInterval('PT' . $randomMinutes . 'M'));

        $rouletteMaxPrice = function_exists('bingo_roulette_max_carton_price')
            ? bingo_roulette_max_carton_price()
            : 0.50;

        return [
            'user' => 1,
            'room' => $selectedRoom['id'],
            'description' => $this->getRandomGameDescription(),
            'modalities' => implode(',', $modalityIds),
            'price' => $randomPrice,
            'date' => $gameDateTime->format('Y-m-d'),
            'time' => $gameDateTime->format('H:i:s'),
            'award' => (int) (systemGet('autoGameAwardType') ?: 1),
            'type' => 1,
            'url' => '',
            'video' => '',
            'reset' => 2,
            'cover' => '',
            'min_players' => max(1, (int) (systemGet('autoGameMinPlayers') ?: 10)),
            'min_cartons' => max(1, (int) (systemGet('autoGameMinCartons') ?: 10)),
            'allow_roulette_cartons' => (
                (systemGet('autoGameAllowRoulette') ?? 1) == 1
                && (float) $randomPrice > 0
                && (float) $randomPrice <= ($rouletteMaxPrice + 0.001)
            ) ? 1 : 0,
            'status' => 2
        ];
    }

    // 5) Crear premios para el juego con distribución correcta
    private function createGameAwards($gameId, $modalitiesString, $totalPrize)
    {
        $modelAwards = new AwardsModel();
        $modelModalities = new ModalitiesModel();
        
        $modalityIds = array_values(array_filter(array_map('intval', explode(',', (string) $modalitiesString))));
        $numModalities = count($modalityIds);

        if ($numModalities === 0) {
            return;
        }

        // Obtener información de las modalidades para identificar cartón lleno
        $modalities = $modelModalities->whereIn('id', $modalityIds)->findAll();
        $hasFullCard = false;
        $fullCardModalityId = null;
        
        // Buscar si hay modalidad de cartón lleno (generalmente tiene 25 posiciones)
        foreach ($modalities as $modality) {
            $positions = explode(',', $modality['positions']);
            if (count($positions) >= 24) { // Cartón lleno o casi lleno
                $hasFullCard = true;
                $fullCardModalityId = $modality['id'];
                break;
            }
        }

        // Distribuir premios según las reglas especificadas
        $prizeDistribution = $this->distributePrizesCorrectly($totalPrize, $modalityIds, $hasFullCard, $fullCardModalityId);

        foreach ($modalityIds as $modalityId) {
            $awardData = [
                'game' => $gameId,
                'modality' => $modalityId,
                'observation' => 'Premio automático generado',
                'amount' => $prizeDistribution[$modalityId],
                'status' => 1
            ];

            $modelAwards->insert($awardData);
        }
    }

    // 6) Distribuir premios correctamente garantizando siempre $totalPrize
    private function distributePrizesCorrectly($totalPrize, $modalityIds, $hasFullCard = false, $fullCardModalityId = null)
    {
        $numModalities = count($modalityIds);
        $prizes = [];
        
        switch ($numModalities) {
            case 2:
                // 2 modalidades: 50% y 50%
                $prizes[$modalityIds[0]] = $totalPrize * 0.5;
                $prizes[$modalityIds[1]] = $totalPrize * 0.5;
                break;

            case 3:
                // 3 modalidades: 50%, 25%, 25%
                $prizes[$modalityIds[0]] = $totalPrize * 0.5;
                $prizes[$modalityIds[1]] = $totalPrize * 0.25;
                $prizes[$modalityIds[2]] = $totalPrize * 0.25;
                break;

            case 4:
                // 4 modalidades: 25% cada una
                foreach ($modalityIds as $modalityId) {
                    $prizes[$modalityId] = $totalPrize * 0.25;
                }
                break;

            case 5:
                if ($hasFullCard && $fullCardModalityId) {
                    // 5 modalidades con cartón lleno: 50% para cartón lleno, 12.5% para las demás
                    foreach ($modalityIds as $modalityId) {
                        if ($modalityId == $fullCardModalityId) {
                            $prizes[$modalityId] = $totalPrize * 0.5;
                        } else {
                            $prizes[$modalityId] = $totalPrize * 0.125;
                        }
                    }
                } else {
                    // 5 modalidades sin cartón lleno: 20% cada una
                    foreach ($modalityIds as $modalityId) {
                        $prizes[$modalityId] = $totalPrize * 0.2;
                    }
                }
                break;

            default:
                // Para otros casos, distribuir equitativamente
                $percentage = 1.0 / $numModalities;
                foreach ($modalityIds as $modalityId) {
                    $prizes[$modalityId] = $totalPrize * $percentage;
                }
                break;
        }

        // Redondear todos los valores a 2 decimales
        foreach ($prizes as $modalityId => $amount) {
            $prizes[$modalityId] = round($amount, 2);
        }

        // Verificar que la suma sea exactamente igual al total
        $totalDistributed = array_sum($prizes);
        $difference = round($totalPrize - $totalDistributed, 2);
        
        // Si hay diferencia por redondeo, ajustar en la primera modalidad
        if ($difference != 0) {
            $firstModalityId = $modalityIds[0];
            $prizes[$firstModalityId] = round($prizes[$firstModalityId] + $difference, 2);
        }

        // Verificación final para asegurar que suma exactamente $totalPrize
        $finalTotal = array_sum($prizes);
        if (round($finalTotal, 2) != round($totalPrize, 2)) {
            // Si aún hay diferencia, hacer un ajuste final
            $finalDifference = round($totalPrize - $finalTotal, 2);
            $firstModalityId = $modalityIds[0];
            $prizes[$firstModalityId] = round($prizes[$firstModalityId] + $finalDifference, 2);
        }

        return $prizes;
    }

    // 7) Generar cartones para el juego
    private function generateGameCartons($gameId)
    {
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();

        $cartonData = [];
        for ($i = 0; $i < systemGet('generateCartons'); $i++) {
            $cartonData[] = [
                'user' => 0,
                'game' => $gameId,
                'status' => 1
            ];
        }

        $modelCartons->insertBatch($cartonData);
        $cartonIds = $modelCartons->select('id')->where('game', $gameId)->findColumn('id');

        $numbersData = [];
        foreach ($cartonIds as $cartonId) {
            // Generar serial único
            $prefix = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
            $cartonFormatted = str_pad($cartonId, 6, '0', STR_PAD_LEFT);
            $serial = $cartonFormatted . $prefix;

            $modelCartons->update($cartonId, ['serial' => $serial]);

            // Generar números para el cartón
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

            // Columna B (posiciones 1, 6, 11, 16, 21)
            for ($pos = 0; $pos < 5; $pos++) {
                $numbersData[] = [
                    'carton' => $cartonId,
                    'number' => $bColumn[$pos],
                    'position' => 1 + ($pos * 5),
                    'status' => 0
                ];
            }

            // Columna I (posiciones 2, 7, 12, 17, 22)
            for ($pos = 0; $pos < 5; $pos++) {
                $numbersData[] = [
                    'carton' => $cartonId,
                    'number' => $iColumn[$pos],
                    'position' => 2 + ($pos * 5),
                    'status' => 0
                ];
            }

            // Columna N (posiciones 3, 8, 13, 18, 23)
            for ($pos = 0; $pos < 5; $pos++) {
                $numbersData[] = [
                    'carton' => $cartonId,
                    'number' => $nColumn[$pos],
                    'position' => 3 + ($pos * 5),
                    'status' => 0
                ];
            }

            // Columna G (posiciones 4, 9, 14, 19, 24)
            for ($pos = 0; $pos < 5; $pos++) {
                $numbersData[] = [
                    'carton' => $cartonId,
                    'number' => $gColumn[$pos],
                    'position' => 4 + ($pos * 5),
                    'status' => 0
                ];
            }

            // Columna O (posiciones 5, 10, 15, 20, 25)
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
    }

    // 8) Enviar notificaciones del nuevo juego
    private function sendGameNotifications($gameId, $gameData)
    {
        $modelUsers = new UsersModel();
        $modelAwards = new AwardsModel();
        $modelNotifications = new NotificationsModel();

        // Obtener todos los usuarios activos
        $users = $modelUsers->where('status', 1)->findAll();
        
        // Calcular premio total
        $totalPrize = $modelAwards->where('game', $gameId)->selectSum('amount')->get()->getRow()->amount ?? 0;

        foreach ($users as $user) {
            $awardText = $gameData['award'] == 2 ? systemGet('currency') . ' ' . number_format($totalPrize, 2) : translate('accumulated');

            $notificationData = [
                'user' => $user['id'],
                'from' => 1, // Usuario del sistema
                'type' => 'game',
                'type_id' => $gameId,
                'game' => $gameId,
                'modality' => $gameData['modalities'],
                'title' => '✅ NUEVA PARTIDA AGREGADA',
                'message' => $gameData['description'] . ' 🗓️ ' . translate_day($gameData['date'] . ' ' . $gameData['time']) . ', ' . translate_date($gameData['date']) . ' | 🎫 Precio: ' . systemGet('currency') . ' ' . number_format($gameData['price'], 2) . ' | 🏆 Premio total: ' . $awardText,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $modelNotifications->insert($notificationData);
        }
    }

    // ================= Helpers =================

    private function diffMs($from, $to)
    {
        $a = strtotime($from);
        $b = strtotime($to);
        return ($b - $a) * 1000;
    }

    private function getLastBall(int $gameId): ?array
    {
        $db = \Config\Database::connect();
        return $db->table('boards')
            ->where('game', $gameId)
            ->orderBy('created_at', 'DESC')
            ->get()->getRowArray() ?: null;
    }

    /*ANTERIORprivate function isGameCompleted(int $gameId): bool
    {
        $db = \Config\Database::connect();

        // 75 números
        $totalNumbersGenerated = $db->table('boards')->where('game', $gameId)->select('number')->distinct()->countAllResults();
        if ($totalNumbersGenerated >= 75) {
            return true;
        }

        // Sings vs Awards
        $SingsCount = $db->table('sings')->select('modality')->where('game', $gameId)->groupBy('modality')->countAllResults();

        $AwardsCount = $db->table('awards')->where('game', $gameId)->where('status', 1)->countAllResults();

        return $SingsCount >= $AwardsCount;
    }*/

    private function isGameCompleted(int $gameId): bool
    {
        $db = \Config\Database::connect();

        // Verificar si ya salieron las 75 bolas
        $totalNumbersGenerated = $db->table('boards')
            ->where('game', $gameId)
            ->select('number')
            ->distinct()
            ->countAllResults();
        
        if ($totalNumbersGenerated >= 75) {
            log_message('info', "Juego {$gameId} completado: 75 bolas cantadas");
            return true;
        }

        // Verificar si todos los premios han sido cantados
        $SingsCount = $db->table('sings')
            ->select('modality')
            ->where('game', $gameId)
            ->where('status', 1) // Solo sings confirmados
            ->groupBy('modality')
            ->countAllResults();

        $AwardsCount = $db->table('awards')
            ->where('game', $gameId)
            ->where('status', 1)
            ->countAllResults();

        $isCompleted = $AwardsCount > 0 && $SingsCount >= $AwardsCount;
        
        if ($isCompleted) {
            log_message('info', "Juego {$gameId} completado: Todos los premios cantados ({$SingsCount}/{$AwardsCount}). Pendientes de pago manual.");
        }

        return $isCompleted;
    }

    private function payAwards(int $gameId): bool
    {
        $modelSings = new SingsModel();
        $modelAwards = new AwardsModel();
        $modelUsers = new UsersModel();
        $modelGames = new GamesModel();
        $modelPayments = new PaymentsModel();
        $modelCartons = new CartonsModel();
        $modelModalities = new ModalitiesModel();
        $modelNotifications = new NotificationsModel();

        $sings = $modelSings->where('game', $gameId)->where('status', 1)->findAll();

        $game = $modelGames->find($gameId);

        $cartons = $modelCartons->where('game', $game['id'])->countAllResults();
        $accumulated = $cartons * $game['price'];
        $total_award = $accumulated - ($accumulated * systemGet('rateEarnings'));

        foreach ($sings as &$sing) {
            $award = $modelAwards->where('game', $sing['game'])->where('modality', $sing['modality'])->first();
            $singsCount = $modelSings->where('game', $sing['game'])->where('modality', $sing['modality'])->countAllResults();
            $user = $modelUsers->find($sing['user']);

            if ($game['award'] == 2) {
                $awardPerSing = $award['amount'] / $singsCount;
            } else {
                $awardPerSing = ($total_award * $award['amount'] / 100) / $singsCount;
            }

            if ($sing['status'] == '1') {
                wallet_credit_recharge((int) $user['id'], (float) $awardPerSing);
            }
            $modelSings->update($sing['id'], ['status' => 2]);

            $dataPayment = [
                'user' => $user['id'],
                'type' => 'award',
                'type_id' => $sing['id'],
                'amount' => $awardPerSing,
                'status' => 2
            ];

            $modelPayments->insert($dataPayment);
            $paymentId = $modelPayments->insertID();

            $modalitySing = $modelModalities->find($sing['modality']);

            $notificationData = [
                'user' => $user['id'],
                'from' => $game['user'],
                'game' => $game['id'],
                'modality' => $sing['modality'],
                'type' => 'payment',
                'type_id' => $paymentId,
                'title' => '💵 PAGO ACREDITADO',
                'message' => 'Se ha acreditado en su billetera la suma de ' . systemGet('currency') . ' ' . number_format($awardPerSing, 2) . ' como pago por el 🏆 premio ganado en la partida "' . $game['description'] . '" modalidad ' . translate($modalitySing['name']) . '.',
            ];

            $modelNotifications->insert($notificationData);
        }

        return true;
    }

    private function hasRecentSingPause(int $gameId, int $pauseSeconds): bool
    {
        $db = \Config\Database::connect();

        $lastBall = $this->getLastBall($gameId);
        if (!$lastBall) return false;

        $lastBallTime = strtotime($lastBall['created_at']);

        // Sings con status 0 (pendientes) en los últimos pauseSeconds
        $lastSings = $db->table('sings')->where('game', $gameId)->where('status', 0)->get()->getResultArray();

        foreach ($lastSings as $sing) {
            $lastSingTime = strtotime($sing['created_at']);
            $timeDifference = $lastSingTime - $lastBallTime;
            if ($timeDifference <= $pauseSeconds && $timeDifference >= 0) {
                return true;
            }
        }
        return false;
    }

    /*ANTERIORprivate function generateUniqueNumber($gameId)
    {
        $db = \Config\Database::connect();

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
    }*/

    private function generateUniqueNumber($gameId)
    {
        $db = \Config\Database::connect();

        // Obtener números ya cantados
        $sungNumbers = $db->table('boards')
            ->select('number')
            ->where('game', $gameId)
            ->get()
            ->getResultArray();
        
        $sungNumbersArray = array_column($sungNumbers, 'number');
        
        // Si ya se cantaron 75 números, no generar más
        if (count($sungNumbersArray) >= 75) {
            log_message('warning', "Juego {$gameId}: Intento de generar número cuando ya se cantaron 75 bolas");
            return null; // O lanzar excepción
        }

        if (systemGet('activateAlgorithm') == 1) {
            // Tu lógica de algoritmo existente...
            $query = $db->table('numbers n')
                ->select('n.number, COUNT(*) as count')
                ->join('cartons c', 'n.carton = c.id')
                ->where('c.game', $gameId)
                ->groupBy('n.number')
                ->get()
                ->getResultArray();

            $frequencies = [];
            foreach ($query as $row) {
                $frequencies[$row['number']] = (int)$row['count'];
            }

            for ($i = 1; $i <= 75; $i++) {
                if (!isset($frequencies[$i])) {
                    $frequencies[$i] = 0;
                }
            }

            // Remover números ya cantados
            foreach ($sungNumbersArray as $sung) {
                unset($frequencies[$sung]);
            }

            if (empty($frequencies)) {
                log_message('warning', "Juego {$gameId}: No hay más números disponibles");
                return null;
            }

            asort($frequencies);
            $minFrequency = reset($frequencies);
            $lessRecurring = array_keys(array_filter($frequencies, function ($v) use ($minFrequency) {
                return $v === $minFrequency;
            }));

            return $lessRecurring[array_rand($lessRecurring)];
        }

        // Método aleatorio simple
        $availableNumbers = array_diff(range(1, 75), $sungNumbersArray);
        
        if (empty($availableNumbers)) {
            log_message('warning', "Juego {$gameId}: No hay más números disponibles (método aleatorio)");
            return null;
        }

        return $availableNumbers[array_rand($availableNumbers)];
    }

    // Función para marcar números automáticamente en el cron
    public function dialNumber($number, $gameId) {
        $modelBoards = new BoardsModel();
        $modelGames = new GamesModel();
        $modelNumbersCartons = new NumbersCartonsModel();

        $game = $modelGames->find($gameId);
        if (!$game) {
            return false;
        }

        // Marcar automáticamente para todos los usuarios que tienen el número
        $existingNumbers = $modelNumbersCartons->select('numbers.*')
            ->join('cartons', 'cartons.id = numbers.carton')
            ->where('cartons.game', $gameId)
            ->where('cartons.user !=', 0)
            ->where('numbers.number', $number)
            ->where('numbers.status', 0) // Solo los no marcados
            ->findAll();

        if (!empty($existingNumbers)) {
            $db = \Config\Database::connect();
            $db->transStart();

            $ids = array_column($existingNumbers, 'id');
            $modelNumbersCartons->whereIn('id', $ids)->set(['status' => 1])->update();

            $db->transComplete();

            return $db->transStatus() !== FALSE;
        }

        return true;
    }

    // Función para cantar bingo automáticamente en el cron
    public function singBingo($gameId) {
        $modelUsers = new UsersModel();
        $modelBoards = new BoardsModel();
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $modelModalities = new ModalitiesModel();
        $modelSings = new SingsModel();
        $modelNotifications = new NotificationsModel();

        $game = $modelGames->find($gameId);

        $modalities = $modelModalities->getModalitiesByIds(explode(',', $game['modalities']));

        $lastBall = $modelBoards->where('game', $game['id'])->orderBy('created_at', 'DESC')->first();

        $drawnNumbers = $modelBoards->getNumbersByBoard($game['id']);
        $drawnNumbersArray = array_column($drawnNumbers, 'number');
        $lastValidNumber = end($drawnNumbersArray);

        $singBingoOnlyLastBall = systemGet('singBingoOnlyLastBall');

        // Verificar bingos para todos los usuarios
        $cartons = $modelCartons->where('game', $game['id'])->where('user !=', 0)->findAll();

        foreach ($cartons as $carton) {
            $singUser = $modelUsers->find($carton['user']);
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

                $userAlreadySang = $modelSings->where('game', $game['id'])->where('modality', $modality['id'])->where('user', $singUser['id'])->countAllResults();

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
                            'user' => $singUser['id'],
                            'game' => $game['id'],
                            'carton' => $carton['id'],
                            'modality' => $modality['id'],
                            'numbers' => implode(',', array_unique($winningNumbers)),
                            'lastnumber' => $lastBall['number'],
                            'status' => 1
                        ];

                        $modelSings->insert($data);
                        $id = $modelSings->insertID();

                        // Pagar los premios AUTOMÁTICAMENTE para todos los ganadores pendientes
                        try {
                            bingo_pay_pending_awards_for_game((int) $game['id']);
                        } catch (\Throwable $pe) {
                            log_message('error', 'Error al pagar premio automático en Cron::singBingo: ' . $pe->getMessage());
                        }

                        // Notificar el bingo cantado en tiempo real a todos los clientes por Pusher
                        bingo_broadcast_sing_accepted((int) $game['id'], [
                            'singId'       => $id,
                            'userId'       => (int) $singUser['id'],
                            'playerId'     => (string) $singUser['id'],
                            'player'       => trim(($singUser['firstname'] ?? '') . ' ' . ($singUser['lastname'] ?? '')),
                            'playerName'   => trim(($singUser['firstname'] ?? '') . ' ' . ($singUser['lastname'] ?? '')),
                            'modality'     => translate($modality['name'] ?? ''),
                            'modalityId'   => (int) $modality['id'],
                            'modalityName' => translate($modality['name'] ?? ''),
                            'cartonId'     => (int) $carton['id'],
                            'lastNumber'   => (int) ($lastBall['number'] ?? 0),
                        ]);

                        $usersFromCartons = $modelCartons->select('user')->where('game', $game['id'])->groupBy('user')->findAll();

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
                            if ($userId == $singUser['id']) {
                                $notificationDataSelf = [
                                    'user'     => $singUser['id'],
                                    'from'     => 1,
                                    'type'     => 'sing',
                                    'game'     => $game['id'],
                                    'modality' => $data['modality'],
                                    'title'    => '🎉 ¡HAS CANTADO BINGO!',
                                    'message'  => '¡Felicidades ' . $singUser['firstname'] . ' ' . $singUser['lastname'] . '! Tu bingo ha sido registrado en la modalidad ' . translate($modalitySing['name']) . '.',
                                ];

                                $modelNotifications->insert($notificationDataSelf);
                                continue;
                            }

                            $notificationData = [
                                'user'     => $userId,
                                'from'     => 1,
                                'type'     => 'sing',
                                'game'     => $game['id'],
                                'modality' => $data['modality'],
                                'title'    => '🎉 ¡BINGO CANTADO!',
                                'message'  => $singUser['firstname'] . ' ' . $singUser['lastname'] . ' ha cantado ¡BINGO! en la modalidad ' . translate($modalitySing['name']) . '.',
                            ];

                            $modelNotifications->insert($notificationData);
                        }
                    }
                }
            }
        }

        return true;
    }

    public function settleMonthlyGgr()
    {
        helper('affiliate_ggr');

        $yearMonth = trim((string) $this->request->getGet('period'));
        if ($yearMonth === '') {
            $yearMonth = null;
        }

        $result = bingo_settle_monthly_ggr_commissions($yearMonth);

        return $this->response->setJSON($result);
    }

    /**
     * Valida el token de seguridad X-Cron-Token para prevenir peticiones externas no autorizadas.
     */
    private function validateCronToken(): bool
    {
        $expectedToken = env('CRON_TOKEN') ?: systemGet('cronToken') ?: 'reybingo_cron_secret_key_2026';
        $providedToken = $this->request->getHeaderLine('X-Cron-Token') 
            ?: $this->request->getGet('cron_token') 
            ?: $this->request->getPost('cron_token');

        if (empty($providedToken) || !hash_equals((string) $expectedToken, (string) $providedToken)) {
            return false;
        }

        return true;
    }

    /**
     * Endpoint API Sub-segundo: Retorna partidas automáticas activas para el runner Node.js.
     */
    public function activeAutoGames()
    {
        if (!$this->validateCronToken()) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'Unauthorized: Invalid or missing X-Cron-Token',
            ]);
        }

        helper('bingo');
        $modelGames = new GamesModel();
        $modelBoards = new BoardsModel();

        $singBall = (string) (systemGet('singBall') ?: '15000-5000');
        $parts = explode('-', $singBall);
        $timeBallGet = max(1000, (int) ($parts[0] ?? 15000));

        // 1) Iniciar partidas automáticas pendientes cuya hora ya llegó
        $gamesToStart = $modelGames->where('type', 1)->where('status', 2)->findAll();
        foreach ($gamesToStart as $gameToStart) {
            if (bingo_game_is_due($gameToStart)) {
                $postpone = bingo_postpone_game($gameToStart);
                if (!$postpone['postponed']) {
                    $modelGames->update($gameToStart['id'], [
                        'status' => 1,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    bingo_broadcast_game_status((int) $gameToStart['id'], 'game:started', ['status' => 1]);
                }
            }
        }

        // 2) Obtener partidas automáticas activas
        $activeGames = $modelGames->where('type', 1)->where('status', 1)->findAll();
        $gamesList = [];

        foreach ($activeGames as $game) {
            $gameId = (int) $game['id'];

            // Verificar si el juego ya completó todas las modalidades
            if ($this->isGameCompleted($gameId)) {
                $modelGames->update($gameId, [
                    'status' => 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                bingo_on_game_finished($gameId);
                bingo_broadcast_game_status($gameId, 'game:game_finished', ['status' => 0]);
                continue;
            }

            $numbersDrawnCount = $modelBoards->where('game', $gameId)->countAllResults();

            $gamesList[] = [
                'id' => $gameId,
                'description' => $game['description'] ?? '',
                'numbersDrawn' => $numbersDrawnCount,
                'intervalMs' => $timeBallGet,
                'date' => $game['date'] ?? '',
                'time' => $game['time'] ?? '',
            ];
        }

        return $this->response->setJSON([
            'ok' => true,
            'activeGames' => $gamesList,
            'timestamp' => date('c'),
        ]);
    }

    /**
     * Endpoint API Sub-segundo: Extrae 1 sola balota en <15ms sin sleep() para el runner Node.js.
     */
    public function tickAutoGame()
    {
        if (!$this->validateCronToken()) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'Unauthorized: Invalid or missing X-Cron-Token',
            ]);
        }

        helper('bingo');
        $gameId = (int) ($this->request->getPost('game_id') ?: $this->request->getGet('game_id') ?: 0);
        if ($gameId < 1) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => false,
                'message' => 'game_id es requerido',
            ]);
        }

        $modelGames = new GamesModel();
        $game = $modelGames->find($gameId);
        if (!$game || (int)$game['type'] !== 1 || (int)$game['status'] !== 1) {
            return $this->response->setJSON([
                'ok' => false,
                'message' => 'Juego no activo o no es automático',
            ]);
        }

        if ($this->isGameCompleted($gameId)) {
            $modelGames->update($gameId, [
                'status' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            bingo_on_game_finished($gameId);
            bingo_broadcast_game_status($gameId, 'game:game_finished', ['status' => 0]);
            return $this->response->setJSON([
                'ok' => true,
                'completed' => true,
                'message' => 'Partida finalizada',
            ]);
        }

        if ($this->hasRecentSingPause($gameId, 10)) {
            return $this->response->setJSON([
                'ok' => true,
                'paused' => true,
                'message' => 'Pausa por bingo reciente',
            ]);
        }

        $candidate = $this->generateUniqueNumber($gameId);
        if (!$candidate) {
            return $this->response->setJSON([
                'ok' => false,
                'message' => 'No hay números disponibles para cantar',
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $inserted = bingo_insert_drawn_number($gameId, (int) $candidate, [
            'user'       => $game['user'] ?? 1,
            'isCRON'     => 1,
            'created_at' => $now,
        ]);

        if (!$inserted) {
            return $this->response->setJSON([
                'ok' => false,
                'message' => 'Número duplicado o ya cantado',
            ]);
        }

        $number = (int) $candidate;
        bingo_broadcast_number_drawn($gameId, $number);

        $this->dialNumber($number, $gameId);
        $this->singBingo($gameId);

        $completedNow = $this->isGameCompleted($gameId);
        if ($completedNow) {
            $modelGames->update($gameId, [
                'status' => 0,
                'updated_at' => $now,
            ]);
            bingo_on_game_finished($gameId);
            bingo_broadcast_game_status($gameId, 'game:game_finished', ['status' => 0]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'gameId' => $gameId,
            'number' => $number,
            'completed' => $completedNow,
            'timestamp' => $now,
        ]);
    }
}