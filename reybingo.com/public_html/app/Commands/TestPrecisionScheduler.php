<?php

namespace App\Commands;

use App\Controllers\Cron;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestPrecisionScheduler extends BaseCommand
{
    protected $group       = 'Bingo';
    protected $name        = 'bingo:test-precision';
    protected $description = 'Valida la precisión en milisegundos, remainingMs y no-colisión del scheduler';

    public function run(array $params)
    {
        CLI::write("=======================================================", 'yellow');
        CLI::write("TEST DE PRECISIÓN Y CONTROL TEMPORAL (PHP BACKEND)", 'yellow');
        CLI::write("=======================================================", 'yellow');

        $cron = new Cron();
        $testGameId = 987654;

        // TEST 1: Heartbeat del runner y detección de daemon activo
        CLI::write("\n[TEST 1] Verificando Heartbeat y detección de daemon...", 'cyan');
        $cron->recordRunnerHeartbeat();
        $isActive = $cron->isRunnerActive();
        if ($isActive) {
            CLI::write("✓ PASS: isRunnerActive() detectó el daemon activo en milisegundos.", 'green');
        } else {
            CLI::error("✗ FAIL: isRunnerActive() no detectó el daemon.");
            return;
        }

        // TEST 2: Primera balota de partida (numbersDrawn = 0)
        CLI::write("\n[TEST 2] Verificando inicio de partida sin balotas...", 'cyan');
        @unlink(WRITEPATH . 'cache/ball_timing_' . $testGameId . '.json');
        $timing0 = $cron->getBallTimingInfo($testGameId, 5000);
        if ($timing0['canDraw'] === true && $timing0['remainingMs'] === 0) {
            CLI::write("✓ PASS: Primera balota autorizada a 0ms sin retraso artificial.", 'green');
        } else {
            CLI::error("✗ FAIL: Primera balota bloqueada inesperadamente.");
            return;
        }

        // TEST 3: Cálculo exacto de remainingMs tras cantar balota
        CLI::write("\n[TEST 3] Verificando remainingMs inmediatamente tras cantar balota...", 'cyan');
        $nowMs = (int) round(microtime(true) * 1000);
        $interval = 5000;
        $cron->saveBallTimingRecord($testGameId, 25, $nowMs, $interval);

        $timingFresh = $cron->getBallTimingInfo($testGameId, $interval);
        CLI::write("  RemainingMs: {$timingFresh['remainingMs']} ms (esperado: ~4950-5000ms)");
        if ($timingFresh['canDraw'] === false && $timingFresh['remainingMs'] > 4800 && $timingFresh['remainingMs'] <= 5000) {
            CLI::write("✓ PASS: Balota bloqueada correctamente y remainingMs exacto.", 'green');
        } else {
            CLI::error("✗ FAIL: remainingMs fuera del rango esperado.");
            return;
        }

        // TEST 4: Simulación de llamada anticipada (faltan 150ms)
        CLI::write("\n[TEST 4] Verificando llamada anticipada (faltan 150ms)...", 'cyan');
        $simulatedDrawn = (int) round(microtime(true) * 1000) - 4850;
        $cron->saveBallTimingRecord($testGameId, 30, $simulatedDrawn, $interval);

        $timing150 = $cron->getBallTimingInfo($testGameId, $interval);
        CLI::write("  RemainingMs reportado: {$timing150['remainingMs']} ms");
        if ($timing150['canDraw'] === false && $timing150['remainingMs'] >= 130 && $timing150['remainingMs'] <= 170) {
            CLI::write("✓ PASS: El runner recibe exactamente remainingMs ~150ms y no esperará 5000ms.", 'green');
        } else {
            CLI::error("✗ FAIL: remainingMs no calculó los 150ms restantes.");
            return;
        }

        // TEST 5: Simulación de tiempo cumplido (+5ms de holgura)
        CLI::write("\n[TEST 5] Verificando tiempo cumplido (5005ms tras balota previa)...", 'cyan');
        $simulatedExpired = (int) round(microtime(true) * 1000) - 5005;
        $cron->saveBallTimingRecord($testGameId, 45, $simulatedExpired, $interval);

        $timingExpired = $cron->getBallTimingInfo($testGameId, $interval);
        CLI::write("  CanDraw: " . ($timingExpired['canDraw'] ? 'true' : 'false'));
        CLI::write("  RemainingMs: {$timingExpired['remainingMs']} ms");
        if ($timingExpired['canDraw'] === true && $timingExpired['remainingMs'] === 0) {
            CLI::write("✓ PASS: Balota autorizada con precisión de milisegundos.", 'green');
        } else {
            CLI::error("✗ FAIL: Balota no fue autorizada en tiempo cumplido.");
            return;
        }

        // TEST 6: Verificación de no-competencia (doProcessAutoGames con runner activo)
        CLI::write("\n[TEST 6] Verificando que doProcessAutoGames NO canta balotas si el runner está activo...", 'cyan');
        $cron->recordRunnerHeartbeat();
        $resProcess = $cron->processAutoGames(false, true);
        if (isset($resProcess['runner_active']) && $resProcess['runner_active'] === true && $resProcess['balls_canted'] === 0) {
            CLI::write("✓ PASS: Competencia eliminada. doProcessAutoGames delegó al daemon sin cantar balotas duplicadas.", 'green');
        } else {
            CLI::error("✗ FAIL: doProcessAutoGames resultado: " . json_encode($resProcess));
            return;
        }

        // Limpieza
        @unlink(WRITEPATH . 'cache/ball_timing_' . $testGameId . '.json');

        CLI::write("\n=======================================================", 'green');
        CLI::write("TODAS LAS PRUEBAS DE LA SUITE PHP PASARON AL 100%!", 'green');
        CLI::write("=======================================================", 'green');
    }
}
