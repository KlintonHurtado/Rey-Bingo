<?php

namespace App\Commands;

use App\Libraries\BotManager;
use App\Models\GamesModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ManageBots extends BaseCommand
{
    protected $group       = 'Bingo';
    protected $name        = 'bingo:bots';
    protected $description = 'Gestiona e inyecta bots (500 bots x 2 cartones) en cualquier partida de bingo';
    protected $usage       = 'bingo:bots [--game=<id>] [--action=assign|seed|clear|status] [--bots=500] [--cartons=2] [--clean]';
    protected $options     = [
        '--game'    => 'ID de la partida a la que se asignarán o limpiarán los bots',
        '--action'  => 'Acción a realizar: assign (por defecto), seed, clear, status',
        '--bots'    => 'Cantidad de bots a utilizar (por defecto: 500)',
        '--cartons' => 'Cantidad de cartones por cada bot (por defecto: 2)',
        '--clean'   => 'Limpia los cartones existentes de bots en la partida antes de asignar nuevos',
    ];

    public function run(array $params)
    {
        helper(['bingo', 'wallet']);

        $action = CLI::getOption('action') ?? $params['action'] ?? 'assign';
        $gameId = (int) (CLI::getOption('game') ?? $params['game'] ?? ($params[0] ?? 0));
        $botsCount = (int) (CLI::getOption('bots') ?? $params['bots'] ?? 500);
        $cartonsPerBot = (int) (CLI::getOption('cartons') ?? $params['cartons'] ?? 2);
        $forceClean = (bool) (CLI::getOption('clean') ?? isset($params['clean']));

        if ($botsCount < 1) $botsCount = 500;
        if ($cartonsPerBot < 1) $cartonsPerBot = 2;

        $botManager = new BotManager();

        CLI::write('');
        CLI::write('=============================================', 'cyan');
        CLI::write('       REY BINGO - SISTEMA DE BOTS           ', 'yellow');
        CLI::write('=============================================', 'cyan');
        CLI::write('');

        if ($action === 'seed') {
            CLI::write("Creando / Asegurando pool de {$botsCount} bots en la base de datos...", 'yellow');
            $bots = $botManager->ensureBots($botsCount);
            CLI::write("✓ Se verificaron / crearon " . count($bots) . " usuarios bot exitosamente.", 'green');
            CLI::write('');
            return;
        }

        if ($gameId < 1) {
            CLI::error('Debes especificar el ID de la partida usando --game=<id> o como primer argumento.');
            CLI::write('Ejemplo: php spark bingo:bots --game=15');
            CLI::write('');
            return;
        }

        $gameModel = new GamesModel();
        $game = $gameModel->find($gameId);
        if (!$game) {
            CLI::error("La partida con ID #{$gameId} no existe en la base de datos.");
            CLI::write('');
            return;
        }

        CLI::write("Partida seleccionada: #{$game['id']} - " . ($game['description'] ?? 'Bingo'), 'white');

        if ($action === 'status') {
            $status = $botManager->getGameBotsStatus($gameId);
            CLI::write("Bots totales en BD:    " . $status['total_bots_in_db'], 'white');
            CLI::write("Bots en esta partida:  " . $status['bots_in_game'], 'green');
            CLI::write("Cartones de bots:      " . $status['cartons_in_game'], 'cyan');
            CLI::write('');
            return;
        }

        if ($action === 'clear') {
            CLI::write("Limpiando cartones de bots de la partida #{$gameId}...", 'yellow');
            $res = $botManager->clearBotsFromGame($gameId);
            CLI::write("✓ " . ($res['message'] ?? 'Cartones eliminados.'), 'green');
            CLI::write('');
            return;
        }

        if ($action === 'assign') {
            CLI::write("Inyectando {$botsCount} bots con {$cartonsPerBot} cartones cada uno (" . ($botsCount * $cartonsPerBot) . " cartones)...", 'yellow');
            
            $startTime = microtime(true);
            $res = $botManager->assignBotsToGame($gameId, $botsCount, $cartonsPerBot, $forceClean);
            $elapsed = round(microtime(true) - $startTime, 2);

            if (($res['status'] ?? '') === 'success') {
                CLI::write("✓ ÉXITO: {$res['message']}", 'green');
                CLI::write("Tiempo de ejecución: {$elapsed} segundos.", 'cyan');
                CLI::write("Los bots están listos con autodial activado para jugar y cantar bingo.", 'light_green');
            } elseif (($res['status'] ?? '') === 'info') {
                CLI::write("ℹ INFO: {$res['message']}", 'yellow');
                CLI::write("Si deseas reasignar, usa la bandera --clean para limpiar primero.", 'white');
            } else {
                CLI::error("ERROR: " . ($res['message'] ?? 'No se pudo completar la asignación.'));
            }
            CLI::write('');
            return;
        }

        CLI::error("Acción desconocida: '{$action}'. Las acciones válidas son: assign, seed, clear, status.");
        CLI::write('');
    }
}
