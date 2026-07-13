<?php
// public/create_test_players.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');
echo "TEST START\n";

try {
    $dsn = 'mysql:host=localhost;dbname=reybingo;charset=utf8mb4';
    $user = 'root';
    $password = '';
    
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "Connected successfully to database!\n";
    
    $players = [
        ['firstname' => 'Pedro', 'lastname' => 'Gomez', 'username' => 'pedro_test', 'email' => 'pedro_test@example.com'],
        ['firstname' => 'Maria', 'lastname' => 'Rodriguez', 'username' => 'maria_test', 'email' => 'maria_test@example.com'],
        ['firstname' => 'Juan', 'lastname' => 'Perez', 'username' => 'juan_test', 'email' => 'juan_test@example.com'],
        ['firstname' => 'Ana', 'lastname' => 'Martinez', 'username' => 'ana_test', 'email' => 'ana_test@example.com'],
        ['firstname' => 'Carlos', 'lastname' => 'Sanchez', 'username' => 'carlos_test', 'email' => 'carlos_test@example.com']
    ];
    
    foreach ($players as $p) {
        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$p['username']]);
        $row = $stmt->fetch();
        
        if ($row) {
            $uid = $row['id'];
            echo "User {$p['username']} already exists (ID: $uid). Crediting balance...\n";
        } else {
            // Insert
            $group = 0; // JUGADOR
            $passHash = password_hash('12345678', PASSWORD_DEFAULT);
            $document = rand(10000000, 99999999);
            $phone = rand(60000000, 79999999);
            $status = 1;
            $autodial = 1;
            
            $stmtInsert = $pdo->prepare("INSERT INTO users (`group`, firstname, lastname, document, username, phone, email, password, status, autodial) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtInsert->execute([$group, $p['firstname'], $p['lastname'], $document, $p['username'], $phone, $p['email'], $passHash, $status, $autodial]);
            $uid = $pdo->lastInsertId();
            
            $code = 'BGC-A' . str_pad($uid, 5, '0', STR_PAD_LEFT);
            $pdo->prepare("UPDATE users SET code = ? WHERE id = ?")->execute([$code, $uid]);
            echo "Created user {$p['username']} (ID: $uid).\n";
        }
        
        // Reset/credit wallet to 5.00
        $pdo->prepare("UPDATE users SET wallet_withdraw = 5.00, wallet_recharge = 0.00, wallet_bonus = 0.00, wallet = 5.00 WHERE id = ?")->execute([$uid]);
        echo "Credited Bs 5.00 to user ID $uid.\n";
    }
    
    echo "TEST END - SUCCESS\n";
    
} catch (PDOException $e) {
    echo "PDO Connection/Query failed: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General error: " . $e->getMessage() . "\n";
}
