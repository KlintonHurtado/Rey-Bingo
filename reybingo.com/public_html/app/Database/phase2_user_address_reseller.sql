-- Ejecutar si no corres migraciones CI4
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `address_line` VARCHAR(255) NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `city` VARCHAR(120) NULL AFTER `address_line`,
  ADD COLUMN IF NOT EXISTS `state` VARCHAR(120) NULL AFTER `city`,
  ADD COLUMN IF NOT EXISTS `is_reseller` TINYINT(1) NOT NULL DEFAULT 0 AFTER `state`;
