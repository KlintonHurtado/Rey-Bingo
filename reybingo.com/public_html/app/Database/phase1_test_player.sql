-- Jugador de prueba (group = 0) para validar ocultar P2P en pantalla de usuario
-- Contraseña: jugador123
-- Ejecutar solo si no usas: php spark player:create-test

INSERT INTO users (
    `code`, `group`, `wallet`, `wallet_recharge`, `wallet_withdraw`, `wallet_bonus`,
    `document`, `firstname`, `lastname`, `username`, `password`, `email`, `phone`,
    `referred_code`, `verified_email`, `status`, `deleted`, `sounds`, `narration`, `autodial`, `roulette`,
    `created_at`, `updated_at`
)
SELECT
    'BGC-A99999', 0, 100, 100, 0, 0,
    '99999999', 'Jugador', 'Prueba', 'jugador',
    '$2y$10$jZtQchki9nSBrId1bufSqOYDyijLVFJgeZmECXpWwRofhNEzriGNi',
    'jugador.prueba@reybingo.local', '5800000000',
    'PRUEBA01', 1, 1, 0, 1, 0, 0, 1,
    NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'jugador');

UPDATE users SET `code` = CONCAT('BGC-A', LPAD(id, 5, '0')) WHERE username = 'jugador';

INSERT INTO `system` (`key`, `value`) SELECT 'p2p_active', '0' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `system` WHERE `key` = 'p2p_active');
UPDATE `system` SET `value` = '0' WHERE `key` = 'p2p_active';
