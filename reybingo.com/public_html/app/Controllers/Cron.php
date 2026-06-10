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
        $addGamesTime = (int) systemGet('addGamesTime'); // Intervalo en minutos
        $tz = new \DateTimeZone('America/Caracas');
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
            return $this->response->setJSON($result);
        }

        log_message('error', "runAutoAddGames: error al crear juego para {$nextGame->format('Y-m-d H:i:s')}");
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Error al crear el juego automático'
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

                // 2.5) Procesar la bola cantada
                $this->dialNumber($number, $gameId);
                $this->singBingo($gameId);

                // 2.6) Verificar si se completó tras cantar
                if ($this->isGameCompleted($gameId)) {
                    $modelGames->update($gameId, [
                        'status' => 0,
                        'updated_at' => $now
                    ]);
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

                // 2.5) Procesar la bola cantada
                $this->dialNumber($number, $gameId);
                $this->singBingo($gameId);

                // 2.6) Verificar si se completó tras cantar
                if ($this->isGameCompleted($gameId)) {
                    $modelGames->update($gameId, [
                        'status' => 0,
                        'updated_at' => $now
                    ]);
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
        if (systemGet('activateCron') != 1) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Cron desactivado']);
        }

        $modelUsers = new UsersModel();
        $modelGames  = new GamesModel();
        $modelBoards = new BoardsModel();
        $modelAwards = new AwardsModel();
        $modelCartons = new CartonsModel();
        $modelSings = new SingsModel();
        $modelPayments = new PaymentsModel();
        $modelModalities = new ModalitiesModel();

        $singBall = systemGet('singBall');
        [$timeBallGet, $timeBallLast] = explode('-', $singBall);
        $timeBallGet = (int) $timeBallGet;

        $now = date('Y-m-d H:i:s');
        $currentDate = date('Y-m-d');
        $currentTime = date('H:i:s');

        // 1) Iniciar juegos programados
        $gamesToStart = $modelGames->where('type', 1)
            ->where('status', 2)
            ->where('date', $currentDate)
            ->where('time <=', $currentTime)
            ->findAll();

        foreach ($gamesToStart as $gameToStart) {
            $modelGames->update($gameToStart['id'], [
                'status' => 1,
                'updated_at' => $now
            ]);
            
            log_message('info', "Juego {$gameToStart['id']} iniciado automáticamente a las {$now}");
        }

        // 2) Procesar juegos activos
        $activeGames = $modelGames->where('type', 1)
            ->where('status', 1)
            ->where('date', $currentDate)
            ->where('time <=', $currentTime)
            ->findAll();

        $ballsCanted = 0;
        $gamesProcessed = [];
        $gamesCompleted = [];

        foreach ($activeGames as $game) {
            $gameId = (int)$game['id'];
            $gamesProcessed[] = $gameId;

            // VERIFICACIÓN CRÍTICA: Comprobar si el juego debe finalizar ANTES de cantar
            if ($this->isGameCompleted($gameId)) {
                $modelGames->update($gameId, [
                    'status' => 0, // finalizado
                    'updated_at' => $now
                ]);

                $gamesCompleted[] = $gameId;
                log_message('info', "Juego {$gameId} finalizado automáticamente - ya completado");
                continue; // IMPORTANTE: No procesar más este juego
            }

            // Verificar cadencia de bolas (excepto si viene de secuencia)
            if (!$fromSequence && !$this->shouldCantBall($gameId, $timeBallGet, $now)) {
                log_message('info', "Juego {$gameId} - aún no toca cantar bola");
                continue;
            }

            // Pausa por sing reciente
            if ($this->hasRecentSingPause($gameId, 10)) {
                log_message('info', "Juego {$gameId} - pausa por sing reciente");
                continue;
            }

            // Generar y cantar bola
            $number = $this->generateUniqueNumber($gameId);

            $modelBoards->insert([
                'user'       => $game['user'] ?? 1,
                'game'       => $gameId,
                'number'     => $number,
                'status'     => 1,
                'isCRON'     => 1,
                'created_at' => $now
            ]);

            $ballsCanted++;
            log_message('info', "BOLA CANTADA: {$number} en juego {$gameId} a las {$now}");

            // Procesar la bola cantada
            $this->dialNumber($number, $gameId);
            $this->singBingo($gameId);

            // VERIFICACIÓN POST-CANTO: Verificar si se completó después de cantar
            if ($this->isGameCompleted($gameId)) {
                $modelGames->update($gameId, [
                    'status' => 0,
                    'updated_at' => $now
                ]);
                $gamesCompleted[] = $gameId;
                log_message('info', "Juego {$gameId} completado tras cantar bola {$number}");
            }
        }

        return $this->response->setJSON([
            'ok' => true,
            'games_started' => count($gamesToStart),
            'active_games' => count($activeGames),
            'games_processed' => $gamesProcessed,
            'games_completed' => $gamesCompleted,
            'balls_canted' => $ballsCanted,
            'timestamp' => $now,
            'current_time' => $currentTime,
            'from_sequence' => $fromSequence
        ]);
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
        set_time_limit(65);

        $singBall = systemGet('singBall');
        [$timeBallGet, $timeBallLast] = explode('-', $singBall);
        $timeBallGet = (int) $timeBallGet;
        
        $secondsBetweenBalls = $timeBallGet / 1000;
        $maxBalls = floor(60 / $secondsBetweenBalls);
        
        if ($maxBalls > 12) {
            $maxBalls = 12;
        }
        
        log_message('info', "Iniciando secuencia de bolas: {$maxBalls} bolas cada {$secondsBetweenBalls} segundos");
        
        $results = [];
        $totalBallsCanted = 0;
        $activeGamesAtStart = $this->getActiveGamesCount();
        
        for ($i = 0; $i < $maxBalls; $i++) {
            // Verificar si aún hay juegos activos antes de continuar
            $currentActiveGames = $this->getActiveGamesCount();
            if ($currentActiveGames == 0) {
                log_message('info', "Secuencia detenida: No hay juegos activos (iteración " . ($i + 1) . ")");
                break;
            }

            $result = $this->runAutoGames(true);
            $data = json_decode($result->getJSON(), true);
            $results[] = $data;
            
            $totalBallsCanted += ($data['balls_canted'] ?? 0);
            
            log_message('info', "Bola " . ($i + 1) . " cantada en secuencia: " . date('Y-m-d H:i:s'));
            
            // Si no es la última bola y hay juegos activos, esperar
            if ($i < $maxBalls - 1 && $currentActiveGames > 0) {
                sleep($secondsBetweenBalls);
            }
        }
        
        return $this->response->setJSON([
            'success' => true,
            'message' => "Secuencia completada",
            'total_balls_canted' => $totalBallsCanted,
            'active_games_start' => $activeGamesAtStart,
            'active_games_end' => $this->getActiveGamesCount(),
            'interval_ms' => $timeBallGet,
            'interval_seconds' => $secondsBetweenBalls,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    // Función helper para contar juegos activos
    private function getActiveGamesCount(): int
    {
        $modelGames = new GamesModel();
        $currentDate = date('Y-m-d');
        $currentTime = date('H:i:s');
        
        return $modelGames->where('type', 1)
            ->where('status', 1)
            ->where('date', $currentDate)
            ->where('time <=', $currentTime)
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
            $this->createGameAwards($gameId, $gameData['modalities'], $gameData['price']);

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
        // Seleccionar sala aleatoria
        $randomRoom = $rooms[array_rand($rooms)];

        // Seleccionar entre 3 a 6 modalidades aleatorias
        $numModalities = rand(3, 6);
        $selectedModalities = array_rand($allModalities, min($numModalities, count($allModalities)));
        
        // Asegurar que sea array si solo se selecciona una modalidad
        if (!is_array($selectedModalities)) {
            $selectedModalities = [$selectedModalities];
        }

        $modalityIds = [];
        foreach ($selectedModalities as $index) {
            $modalityIds[] = $allModalities[$index]['id'];
        }

        // Generar precio aleatorio en rangos específicos
        if (systemGet('priceRanges') == 1) {
            $priceRanges = [10, 15, 20, 25, 30, 35, 40, 45, 50];
        } else if (systemGet('priceRanges') == 2) {
            $priceRanges = [10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100];
        } else if (systemGet('priceRanges') == 3) {
            $priceRanges = [10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100, 110, 120, 130, 140, 150];
        } else if (systemGet('priceRanges') == 4) {
            $priceRanges = [10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100, 110, 120, 130, 140, 150, 155, 160, 165, 170, 175, 180, 185, 190, 195, 200];
        } else if (systemGet('priceRanges') == 5) {
            $priceRanges = [50, 100, 150, 200, 250, 300, 350, 400, 450, 500];
        }

        $randomPrice = $priceRanges[array_rand($priceRanges)];

        // Generar fecha y hora (entre 5 minutos y 2 horas desde ahora)
        $minMinutes = 5;
        $maxMinutes = 120;
        $randomMinutes = rand($minMinutes, $maxMinutes);
        $gameDateTime = new \DateTime();
        $gameDateTime->add(new \DateInterval('PT' . $randomMinutes . 'M'));

        return [
            'user' => 1, // Usuario del sistema
            'room' => $randomRoom['id'],
            'description' => $this->getRandomGameDescription(),
            'modalities' => implode(',', $modalityIds),
            'price' => $randomPrice,
            'date' => $gameDateTime->format('Y-m-d'),
            'time' => $gameDateTime->format('H:i:s'),
            'award' => 1, // Siempre tipo 1
            'type' => 1,  // Siempre automático
            'url' => '',
            'video' => '',
            'reset' => 2, // Siempre reset 2
            'cover' => '',
            'status' => 1
        ];
    }

    // 5) Crear premios para el juego con distribución correcta
    private function createGameAwards($gameId, $modalitiesString, $gamePrice)
    {
        $modelAwards = new AwardsModel();
        $modelModalities = new ModalitiesModel();
        
        $modalityIds = explode(',', $modalitiesString);
        $numModalities = count($modalityIds);
        
        // El premio total es igual al precio del cartón (100%)
        $totalPrize = $gamePrice;

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

    // 6) Distribuir premios correctamente garantizando siempre 100%
    private function distributePrizesCorrectly($totalPrize, $modalityIds, $hasFullCard = false, $fullCardModalityId = null)
    {
        $numModalities = count($modalityIds);
        $prizes = [];
        
        switch ($numModalities) {
            case 2:
                // 2 modalidades: 50% y 50%
                $prizes[$modalityIds[0]] = 100 * 0.5;
                $prizes[$modalityIds[1]] = 100 * 0.5;
                break;

            case 3:
                // 3 modalidades: 50%, 25%, 25%
                $prizes[$modalityIds[0]] = 100 * 0.5;
                $prizes[$modalityIds[1]] = 100 * 0.25;
                $prizes[$modalityIds[2]] = 100 * 0.25;
                break;

            case 4:
                // 4 modalidades: 25% cada una
                foreach ($modalityIds as $modalityId) {
                    $prizes[$modalityId] = 100 * 0.25;
                }
                break;

            case 5:
                if ($hasFullCard && $fullCardModalityId) {
                    // 5 modalidades con cartón lleno: 50% para cartón lleno, 12.5% para las demás
                    foreach ($modalityIds as $modalityId) {
                        if ($modalityId == $fullCardModalityId) {
                            $prizes[$modalityId] = 100 * 0.5;
                        } else {
                            $prizes[$modalityId] = 100 * 0.125;
                        }
                    }
                } else {
                    // 5 modalidades sin cartón lleno: 20% cada una
                    foreach ($modalityIds as $modalityId) {
                        $prizes[$modalityId] = 100 * 0.2;
                    }
                }
                break;

            default:
                // Para otros casos, distribuir equitativamente
                $percentage = 1.0 / $numModalities;
                foreach ($modalityIds as $modalityId) {
                    $prizes[$modalityId] = 100 * $percentage;
                }
                break;
        }

        // Redondear todos los valores a 2 decimales
        foreach ($prizes as $modalityId => $amount) {
            $prizes[$modalityId] = round($amount, 2);
        }

        // Verificar que la suma sea exactamente igual al total
        $totalDistributed = array_sum($prizes);
        $difference = round(100 - $totalDistributed, 2);
        
        // Si hay diferencia por redondeo, ajustar en la primera modalidad
        if ($difference != 0) {
            $firstModalityId = $modalityIds[0];
            $prizes[$firstModalityId] = round($prizes[$firstModalityId] + $difference, 2);
        }

        // Verificación final para asegurar que suma exactamente 100%
        $finalTotal = array_sum($prizes);
        if (round($finalTotal, 2) != round(100, 2)) {
            // Si aún hay diferencia, hacer un ajuste final
            $finalDifference = round(100 - $finalTotal, 2);
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

        $isCompleted = $SingsCount >= $AwardsCount;
        
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
                $modelUsers->update($user['id'], ['wallet' => $user['wallet'] + $awardPerSing]);
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
}