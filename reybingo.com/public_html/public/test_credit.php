<?php
/**
 * SCRIPT TEMPORAL DE PRUEBA - ELIMINAR DESPUÉS
 * Acredita 60 de saldo retirable al usuario con grupo=0 (JUGADOR) para pruebas
 */

$host     = 'localhost';
$user     = 'root';
$pass     = '';
$dbname   = 'reybingo';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// Buscar usuarios con rol JUGADOR (group=0)
$result = $conn->query("SELECT id, firstname, lastname, username, wallet, wallet_recharge, wallet_withdraw, wallet_bonus FROM users WHERE `group` = 0 AND deleted = 0 ORDER BY id ASC");

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$message = '';
$error   = '';

// Procesar la acción
if (isset($_GET['action']) && $_GET['action'] === 'credit' && isset($_GET['uid'])) {
    $uid    = (int) $_GET['uid'];
    $amount = 60.00;

    // Leer valores actuales
    $r = $conn->query("SELECT wallet_recharge, wallet_withdraw, wallet_bonus FROM users WHERE id = $uid LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) {
        $newWithdraw = round((float)$row['wallet_withdraw'] + $amount, 2);
        $newTotal    = round((float)$row['wallet_recharge'] + $newWithdraw + (float)$row['wallet_bonus'], 2);

        $stmt = $conn->prepare("UPDATE users SET wallet_withdraw = ?, wallet = ? WHERE id = ?");
        $stmt->bind_param('ddi', $newWithdraw, $newTotal, $uid);
        $stmt->execute();
        $message = "✅ Se acreditaron Bs 60.00 de saldo retirable al usuario ID $uid. Nuevo wallet_withdraw = $newWithdraw";
        $stmt->close();
    } else {
        $error = '❌ Usuario no encontrado.';
    }

    // Recargar la lista
    $result2 = $conn->query("SELECT id, firstname, lastname, username, wallet, wallet_recharge, wallet_withdraw, wallet_bonus FROM users WHERE `group` = 0 AND deleted = 0 ORDER BY id ASC");
    $users = [];
    while ($row = $result2->fetch_assoc()) {
        $users[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Test - Acreditar saldo retirable</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #1a1a2e; color: #eee; }
    h1 { color: #e94560; }
    .alert-success { background: #0f5132; border: 1px solid #198754; padding: 10px 15px; border-radius: 8px; margin-bottom: 15px; }
    .alert-error   { background: #842029; border: 1px solid #dc3545; padding: 10px 15px; border-radius: 8px; margin-bottom: 15px; }
    table { width: 100%; border-collapse: collapse; background: #16213e; border-radius: 8px; overflow: hidden; }
    th { background: #0f3460; padding: 10px; text-align: left; }
    td { padding: 10px; border-top: 1px solid #333; }
    a.btn { background: #e94560; color: white; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; }
    a.btn:hover { background: #c73652; }
    .warn { background: #664d03; border: 1px solid #ffc107; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; }
</style>
</head>
<body>
<h1>🔧 Test: Acreditar saldo retirable (wallet_withdraw)</h1>
<div class="warn">⚠️ <strong>Este archivo es solo para pruebas.</strong> Elimínalo cuando termines. Ubicación: <code>public/test_credit.php</code></div>

<?php if ($message): ?>
    <div class="alert-success"><?= $message ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert-error"><?= $error ?></div>
<?php endif; ?>

<p>Usuarios JUGADOR encontrados:</p>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Usuario</th>
            <th>wallet (total)</th>
            <th>wallet_recharge</th>
            <th>wallet_withdraw</th>
            <th>wallet_bonus</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname']) ?></td>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td><?= number_format($u['wallet'], 2) ?></td>
            <td><?= number_format($u['wallet_recharge'], 2) ?></td>
            <td><strong style="color:#4ade80"><?= number_format($u['wallet_withdraw'], 2) ?></strong></td>
            <td><?= number_format($u['wallet_bonus'], 2) ?></td>
            <td>
                <a class="btn" href="?action=credit&uid=<?= $u['id'] ?>" onclick="return confirm('¿Acreditar Bs 60 de ganancias a este usuario?')">
                    +60 Retirable
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
