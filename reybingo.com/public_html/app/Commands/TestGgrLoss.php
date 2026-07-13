<?php

namespace App\Commands;

use App\Models\DepositsModel;
use App\Models\GamesModel;
use App\Models\UsersModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Prueba GGR: recarga jugador desde PV, simula pérdida (apuesta sin premio) y liquida comisiones.
 *
 * Uso: php spark ggr:test-loss [playerEmail] [storeName] [amount] [stakeAmount]
 */
class TestGgrLoss extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'ggr:test-loss';
    protected $description = 'Recarga jugador desde punto de venta, simula pérdida y genera GGR';
    protected $usage       = 'ggr:test-loss [playerEmail] [storeName] [rechargeAmount] [stakeAmount]';

    public function run(array $params)
    {
        helper(['bingo', 'wallet', 'affiliate_ggr']);

        $playerEmail    = $params[0] ?? 'jugadorJ@gmail.com';
        $storeName      = $params[1] ?? 'juancho';
        $rechargeAmount = (float) ($params[2] ?? 100);
        $stakeAmount    = (float) ($params[3] ?? 50);

        $modelUsers = new UsersModel();
        $db         = Database::connect();

        bingo_ensure_affiliate_ggr_schema();
        bingo_ensure_users_schema();

        $player = $modelUsers->where('email', $playerEmail)->where('deleted', 0)->first();
        if (! $player) {
            CLI::error("Jugador no encontrado: {$playerEmail}");
            return;
        }

        $store = $modelUsers
            ->groupStart()
                ->like('firstname', $storeName)
                ->orLike('lastname', $storeName)
                ->orLike('username', $storeName)
                ->orLike('email', $storeName)
                ->orLike('code', $storeName)
                ->orLike('business_name', $storeName)
            ->groupEnd()
            ->where('group', bingo_group_store())
            ->where('deleted', 0)
            ->first();

        if (! $store) {
            CLI::error("Punto de venta no encontrado: {$storeName}");
            $this->listStores($modelUsers);
            return;
        }

        $playerId = (int) $player['id'];
        $storeId  = (int) $store['id'];

        CLI::write('=== Datos iniciales ===', 'cyan');
        $this->printUser('Jugador', $player);
        $this->printUser('PV', $store);
        CLI::write('referred_store_id jugador: ' . ($player['referred_store_id'] ?? 'null'), 'yellow');

        if ((int) ($player['referred_store_id'] ?? 0) !== $storeId) {
            $modelUsers->update($playerId, ['referred_store_id' => $storeId]);
            $player = $modelUsers->find($playerId);
            CLI::write("Jugador vinculado al PV {$storeName} (id {$storeId})", 'green');
        }

        $storeRate = bingo_ggr_commission_rate_for($store, 'store');
        if ($storeRate <= 0) {
            $modelUsers->update($storeId, ['ggr_commission_rate' => 0.10]);
            $store = $modelUsers->find($storeId);
            CLI::write('Tasa GGR del PV configurada al 10%', 'yellow');
        }

        CLI::newLine();
        CLI::write('=== Paso 1: Recarga desde PV ===', 'cyan');

        $storeBefore = wallet_recharge_balance($store);
        if ($storeBefore < $rechargeAmount) {
            wallet_credit_recharge($storeId, $rechargeAmount * 2);
            $store = $modelUsers->find($storeId);
            CLI::write('Saldo PV insuficiente; se acreditó saldo extra al PV', 'yellow');
        }

        if (! wallet_deduct_recharge($storeId, $rechargeAmount)) {
            CLI::error('No se pudo descontar del PV para la recarga');
            return;
        }

        wallet_credit_recharge($playerId, $rechargeAmount);

        $modelDeposits = new DepositsModel();
        $modelDeposits->insert([
            'user'        => $playerId,
            'store'       => $storeId,
            'account'     => 'store',
            'method'      => 'store player recharge',
            'bank'        => 'store',
            'document'    => $player['document'] ?? '',
            'phone'       => $player['phone'] ?? '',
            'reference'   => 'GGR-TEST-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'amount'      => $rechargeAmount,
            'date'        => date('Y-m-d'),
            'voucher'     => '',
            'observation' => 'Recarga prueba GGR - spark ggr:test-loss',
            'status'      => 2,
        ]);
        $depositId = (int) $modelDeposits->getInsertID();

        $player = $modelUsers->find($playerId);
        $store  = $modelUsers->find($storeId);

        CLI::write("Recarga OK: {$rechargeAmount} al jugador (deposit #{$depositId})", 'green');
        CLI::write('Saldo jugador recarga: ' . wallet_recharge_balance($player));
        CLI::write('Saldo PV recarga: ' . wallet_recharge_balance($store));

        CLI::newLine();
        CLI::write('=== Paso 2: Simular pérdida (apuesta sin premio) ===', 'cyan');

        $modelGames = new GamesModel();
        $modelGames->insert([
            'description' => 'Test GGR ' . date('Y-m-d H:i:s'),
            'date'        => date('Y-m-d'),
            'time'        => date('H:i:s'),
            'status'      => 3,
            'price'       => $stakeAmount,
            'award'       => 0,
            'type'        => 0,
        ]);
        $gameId = (int) $modelGames->getInsertID();

        bingo_record_ggr_stake($playerId, $gameId, $stakeAmount, 'test_loss', $gameId);
        // Sin payout = pérdida total del jugador → GGR = stake

        CLI::write("Partida test #{$gameId}: apuesta {$stakeAmount}, premio 0 (pérdida)", 'green');

        CLI::newLine();
        CLI::write('=== Paso 3: Liquidar GGR ===', 'cyan');

        $settled = bingo_settle_player_game_ggr_commissions($playerId, $gameId, $storeId);
        CLI::write("Comisiones liquidadas: {$settled}", 'green');

        $commissions = $db->table('affiliate_ggr_commissions')
            ->where('player_id', $playerId)
            ->where('game_id', $gameId)
            ->get()
            ->getResultArray();

        if ($commissions === []) {
            CLI::error('No se generaron comisiones GGR. Revisa cadena de afiliados y tasas.');
            return;
        }

        CLI::newLine();
        CLI::write('=== Resultado GGR ===', 'cyan');
        foreach ($commissions as $row) {
            $affiliate = $modelUsers->find((int) $row['affiliate_id']);
            $name = trim(($affiliate['firstname'] ?? '') . ' ' . ($affiliate['lastname'] ?? ''));
            CLI::write(sprintf(
                '  %s (%s id %d): stake=%.2f payout=%.2f GGR=%.2f tasa=%.4f comisión=%.2f status=%d',
                $name,
                $row['affiliate_type'],
                $row['affiliate_id'],
                (float) $row['total_stake'],
                (float) $row['total_payout'],
                (float) $row['ggr_amount'],
                (float) $row['commission_rate'],
                (float) $row['commission_amount'],
                (int) $row['status'],
            ), 'white');
        }

        $expectedGgr = $stakeAmount;
        $actualGgr   = (float) ($commissions[0]['ggr_amount'] ?? 0);
        if (abs($expectedGgr - $actualGgr) < 0.01) {
            CLI::newLine();
            CLI::write("✓ GGR correcto: {$actualGgr} (= apuesta {$stakeAmount} - premio 0)", 'green');
        } else {
            CLI::newLine();
            CLI::error("✗ GGR inesperado: esperado {$expectedGgr}, obtenido {$actualGgr}");
        }
    }

    private function printUser(string $label, array $user): void
    {
        CLI::write(sprintf(
            '%s id=%d email=%s nombre=%s %s saldo_recarga=%.2f ggr_rate=%s',
            $label,
            (int) $user['id'],
            $user['email'] ?? '',
            $user['firstname'] ?? '',
            $user['lastname'] ?? '',
            wallet_recharge_balance($user),
            $user['ggr_commission_rate'] ?? 'null',
        ));
    }

    private function listStores(UsersModel $modelUsers): void
    {
        CLI::write('PV disponibles:', 'yellow');
        $stores = $modelUsers->where('group', bingo_group_store())->where('deleted', 0)->findAll(20);
        foreach ($stores as $s) {
            CLI::write('  id=' . $s['id'] . ' ' . ($s['firstname'] ?? '') . ' ' . ($s['lastname'] ?? '') . ' (' . ($s['email'] ?? '') . ')');
        }
    }
}
