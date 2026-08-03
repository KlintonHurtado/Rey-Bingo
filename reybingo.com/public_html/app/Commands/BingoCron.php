<?php

namespace App\Commands;

use App\Controllers\Cron;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Cron de partidas automáticas (inicio + canto de balotas).
 *
 * Crontab recomendado (cada minuto):
 *   * * * * * cd /ruta/a/public_html && php spark bingo:cron >> writable/logs/bingo-cron.log 2>&1
 *
 * Alternativa HTTP (hosting sin SSH):
 *   * * * * * curl -s "https://tudominio/cron/run-auto-games" >/dev/null 2>&1
 *   * * * * * curl -s "https://tudominio/cron/ball-sequence" >/dev/null 2>&1
 */
class BingoCron extends BaseCommand
{
    protected $group       = 'Bingo';
    protected $name        = 'bingo:cron';
    protected $description = 'Ejecuta el cron de partidas automáticas (iniciar y cantar balotas)';
    protected $usage       = 'bingo:cron [--sequence]';
    protected $options     = [
        '--sequence' => 'Canta varias balotas en ~1 minuto (equivalente a /cron/ball-sequence)',
    ];

    public function run(array $params)
    {
        helper('bingo');

        if ((int) systemGet('activateCron') !== 1) {
            CLI::error('activateCron está desactivado en ajustes del sistema.');
            return;
        }

        $useSequence = array_key_exists('sequence', $params)
            || CLI::getOption('sequence') !== null
            || in_array('--sequence', $_SERVER['argv'] ?? [], true);

        $cron = new Cron();

        if ($useSequence) {
            // Reutiliza la ruta HTTP logic vía process en bucle corto
            $singBall = (string) (systemGet('singBall') ?: '15000-5000');
            $parts = explode('-', $singBall);
            $timeBallGet = max(1000, (int) ($parts[0] ?? 15000));
            $seconds = max(1, (int) round($timeBallGet / 1000));
            $maxBalls = min(12, max(1, (int) floor(55 / $seconds)));

            $total = 0;
            CLI::write("Secuencia: hasta {$maxBalls} ticks cada {$seconds}s", 'yellow');

            for ($i = 0; $i < $maxBalls; $i++) {
                $data = $cron->processAutoGames(true, true);
                $canted = (int) ($data['balls_canted'] ?? 0);
                $total += $canted;
                CLI::write(sprintf(
                    '[%s] tick %d: started=%s balls=%s completed=%s',
                    $data['timestamp'] ?? date('H:i:s'),
                    $i + 1,
                    $data['games_started'] ?? 0,
                    $canted,
                    isset($data['games_completed']) ? count($data['games_completed']) : 0
                ));

                if ($i < $maxBalls - 1) {
                    sleep($seconds);
                }
            }

            CLI::write("Listo. Balotas cantadas en secuencia: {$total}", 'green');
            return;
        }

        $data = $cron->processAutoGames(false);
        if (empty($data['ok'])) {
            CLI::error($data['message'] ?? 'Error en cron');
            return;
        }

        CLI::write(sprintf(
            'OK %s | started=%s active=%s balls=%s completed=%s interval=%sms',
            $data['timestamp'] ?? '',
            $data['games_started'] ?? 0,
            $data['active_games'] ?? 0,
            $data['balls_canted'] ?? 0,
            isset($data['games_completed']) ? count($data['games_completed']) : 0,
            $data['interval_ms'] ?? '?'
        ), 'green');
    }
}
