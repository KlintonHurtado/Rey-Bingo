-- Fase 1: ejecutar en MySQL si no usas php spark migrate
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `wallet_recharge` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `wallet`,
  ADD COLUMN IF NOT EXISTS `wallet_withdraw` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `wallet_recharge`,
  ADD COLUMN IF NOT EXISTS `wallet_bonus` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `wallet_withdraw`,
  ADD COLUMN IF NOT EXISTS `kyc_status` VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER `wallet_bonus`,
  ADD COLUMN IF NOT EXISTS `kyc_front` VARCHAR(255) NULL AFTER `kyc_status`,
  ADD COLUMN IF NOT EXISTS `kyc_back` VARCHAR(255) NULL AFTER `kyc_front`,
  ADD COLUMN IF NOT EXISTS `kyc_observations` TEXT NULL AFTER `kyc_back`;

UPDATE `users` SET `wallet_recharge` = `wallet`
WHERE (`wallet_recharge` + `wallet_withdraw` + `wallet_bonus`) = 0 AND `wallet` > 0;
