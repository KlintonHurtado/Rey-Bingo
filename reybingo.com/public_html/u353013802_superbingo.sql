reybingo-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 09-10-2025 a las 15:49:30
-- Versión del servidor: 11.8.3-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u353013802_superbingo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `achievements`
--

CREATE TABLE `achievements` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `icon` varchar(255) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `requirement_type` varchar(50) NOT NULL COMMENT 'games_played, wins, consecutive_days, etc',
  `requirement_value` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `achievements`
--

INSERT INTO `achievements` (`id`, `name`, `description`, `icon`, `points`, `requirement_type`, `requirement_value`, `created_at`, `updated_at`) VALUES
(1, 'Primer Juego', 'Participa en tu primera partida de bingo', 'achievement-first-game.png', 10, 'games_played', 1, '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(2, 'Jugador Dedicado', 'Participa en 10 partidas de bingo', 'achievement-dedicated.png', 50, 'games_played', 10, '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(3, 'Jugador Veterano', 'Participa en 50 partidas de bingo', 'achievement-veteran.png', 200, 'games_played', 50, '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(4, 'Primera Victoria', 'Gana tu primera partida de bingo', 'achievement-first-win.png', 100, 'wins', 1, '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(5, 'Ganador Frecuente', 'Gana 5 partidas de bingo', 'achievement-frequent-winner.png', 300, 'wins', 5, '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(6, 'Maestro del Bingo', 'Gana 20 partidas de bingo', 'achievement-master.png', 1000, 'wins', 20, '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(7, 'Jugador Constante', 'Inicia sesiu00f3n durante 7 du00edas consecutivos', 'achievement-consistent.png', 100, 'consecutive_days', 7, '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(8, 'Jugador Leal', 'Inicia sesiu00f3n durante 30 du00edas consecutivos', 'achievement-loyal.png', 500, 'consecutive_days', 30, '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(9, 'Coleccionista', 'Compra 20 cartones diferentes', 'achievement-collector.png', 200, 'cartons_bought', 20, '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(10, 'Referente', 'Invita a 5 amigos que se registren', 'achievement-referrer.png', 300, 'referrals', 5, '2025-09-16 05:47:26', '2025-09-16 05:47:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `awards`
--

CREATE TABLE `awards` (
  `id` int(11) NOT NULL,
  `game` int(11) NOT NULL,
  `modality` int(11) NOT NULL,
  `observation` longtext NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `banks`
--

CREATE TABLE `banks` (
  `id` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `account` varchar(255) NOT NULL,
  `holder` varchar(255) NOT NULL,
  `document` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `logo` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `banks`
--

INSERT INTO `banks` (`id`, `code`, `name`, `account`, `holder`, `document`, `phone`, `logo`, `created_at`, `updated_at`, `status`) VALUES
(1, '', '0108 - BANCO PROVINCIAL', '01082419250100121306', 'Marwin Silva', '20641426', '04226410753', '68b1f989f3c2e.png', '2025-08-29 15:03:37', '2025-08-29 15:03:37', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `boards`
--

CREATE TABLE `boards` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `game` int(11) NOT NULL,
  `number` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `isCRON` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cartons`
--

CREATE TABLE `cartons` (
  `id` int(11) NOT NULL,
  `serial` varchar(255) NOT NULL,
  `user` int(11) NOT NULL,
  `game` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ci_sessions`
--

CREATE TABLE `ci_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `data` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `charge` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `phone`, `charge`, `created_at`, `updated_at`, `status`) VALUES
(1, 'Luis Perez', '+584226410763', 'Soporte de Ventas', '2025-01-26 17:29:51', '2025-08-25 04:31:42', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `deposits`
--

CREATE TABLE `deposits` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `account` varchar(255) NOT NULL,
  `method` varchar(255) NOT NULL,
  `bank` varchar(255) NOT NULL,
  `document` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `reference` varchar(255) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `date` date NOT NULL,
  `voucher` varchar(255) NOT NULL,
  `observation` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `email_campaigns`
--

CREATE TABLE `email_campaigns` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `template_id` varchar(255) DEFAULT NULL,
  `segment` varchar(255) DEFAULT NULL COMMENT 'all, pro, level_X, inactive, etc',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft' COMMENT 'draft, scheduled, sending, sent, cancelled',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `email_stats`
--

CREATE TABLE `email_stats` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL COMMENT 'sent, delivered, opened, clicked, bounced, etc',
  `sent_at` datetime DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `clicked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `firebase_tokens`
--

CREATE TABLE `firebase_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` text NOT NULL,
  `device` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `room` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `modalities` varchar(255) NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `award` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 0,
  `url` longtext NOT NULL,
  `video` varchar(255) NOT NULL,
  `reset` int(11) NOT NULL,
  `cover` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `game_rooms`
--

CREATE TABLE `game_rooms` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `game_rooms`
--

INSERT INTO `game_rooms` (`id`, `name`, `description`, `created_at`, `updated_at`, `status`) VALUES
(1, 'Sala diamante', '', '2025-09-04 03:27:55', '2025-09-04 03:27:55', 1),
(2, 'Sala oro', '', '2025-09-04 03:29:27', '2025-09-04 03:29:27', 1),
(3, 'Sala plata', '', '2025-09-04 03:29:27', '2025-09-04 03:29:27', 1),
(4, 'Sala bronce', '', '2025-09-04 03:29:27', '2025-09-04 03:29:27', 1),
(5, 'Sala rubí', '', '2025-09-04 03:29:27', '2025-09-04 03:29:27', 1),
(6, 'Sala zafiro', '', '2025-09-04 03:29:27', '2025-09-04 03:29:27', 1),
(7, 'Sala esmeralda', '', '2025-09-04 03:29:27', '2025-09-04 03:29:27', 1),
(8, 'Sala platino', '', '2025-09-04 03:29:27', '2025-09-04 03:29:27', 1),
(9, 'Sala estrella', '', '2025-09-04 03:29:27', '2025-09-04 03:29:27', 1),
(10, 'Sala fortuna', '', '2025-09-04 03:29:27', '2025-09-04 03:29:27', 1),
(11, 'Sala vip', '', '2025-09-04 03:29:27', '2025-09-04 03:29:27', 1),
(12, 'Sala family', '', '2025-09-04 03:29:27', '2025-09-04 03:29:27', 1),
(13, 'Sala premium', '', '2025-09-04 03:29:27', '2025-09-04 03:29:27', 1),
(14, 'Sala mágica', '', '2025-09-04 03:29:27', '2025-09-04 03:29:27', 1),
(15, 'Sala leyenda', '', '2025-09-04 03:29:27', '2025-09-04 03:29:27', 1),
(16, 'Sala campeones', '', '2025-09-04 03:29:27', '2025-09-04 03:29:27', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `levels`
--

CREATE TABLE `levels` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `required_points` int(11) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `benefits` longtext NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `free_cartons_per_day` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `levels`
--

INSERT INTO `levels` (`id`, `name`, `description`, `required_points`, `icon`, `benefits`, `discount_percentage`, `free_cartons_per_day`, `created_at`, `updated_at`) VALUES
(1, 'Principiante', 'Nivel inicial para todos los jugadores', 0, 'level-1.png', 'Acceso a partidas bu00e1sicas', 0.00, 0, '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(2, 'Aficionado', 'Has demostrado interu00e9s en el juego', 100, 'level-2.png', 'Acceso a partidas bu00e1sicas y 5% de descuento en cartones', 5.00, 0, '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(3, 'Entusiasta', 'Eres un jugador regular', 500, 'level-3.png', 'Acceso a partidas bu00e1sicas y especiales, 10% de descuento en cartones', 10.00, 1, '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(4, 'Experto', 'Tienes experiencia considerable', 1500, 'level-4.png', 'Acceso a todas las partidas, 15% de descuento en cartones', 15.00, 1, '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(5, 'Maestro', 'Eres un maestro del bingo', 5000, 'level-5.png', 'Acceso a todas las partidas, 20% de descuento en cartones, 1 cartu00f3n gratis diario', 20.00, 2, '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(6, 'Leyenda', 'Has alcanzado el nivel mu00e1ximo', 10000, 'level-6.png', 'Acceso a todas las partidas, 25% de descuento en cartones, 2 cartones gratis diarios', 25.00, 3, '2025-09-16 05:47:26', '2025-09-16 05:47:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs`
--

CREATE TABLE `logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_user` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `data` longtext NOT NULL,
  `ip_address` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `user_agent` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `game` int(11) NOT NULL,
  `message` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modalities`
--

CREATE TABLE `modalities` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `positions` varchar(255) NOT NULL,
  `observations` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `modalities`
--

INSERT INTO `modalities` (`id`, `name`, `positions`, `observations`, `created_at`, `updated_at`, `status`) VALUES
(1, 'Full Carton', '1,2,3,4,5,6,7,8,9,10,11,12,14,15,16,17,18,19,20,21,22,23,24,25', 'Winner or winners with all numbers in positions (1 to 25).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(2, 'Four Corners', '1,5,21,25', 'Winner or winners with all numbers in positions (1, 5, 21, and 25).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(3, 'Large Cross', '3,8,11,12,14,15,18,23', 'Winner or winners with all numbers in positions (3, 8, 11, 12, 14, 15, 18, and 23).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(4, 'Large X', '1,5,7,9,17,19,21,25', 'Winner or winners with all numbers in positions (1, 5, 7, 9, 17, 19, 21, and 25).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(5, 'Large Square', '1,2,3,4,5,6,10,11,15,16,20,21,22,23,24,25', 'Winner or winners with all numbers in positions (1, 2, 3, 4, 5, 6, 10, 11, 15, 16, 20, 21, 22, 23, 24, and 25).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(6, 'Large Diamond', '3,7,9,11,15,17,19,23', 'Winner or winners with all numbers in positions (3, 7, 9, 11, 15, 17, 19, and 23).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(7, 'Line 1', '1,2,3,4,5', 'Winner or winners with all numbers in row 1 (positions 1, 2, 3, 4, and 5).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(8, 'Line 2', '6,7,8,9,10', 'Winner or winners with all numbers in row 2 (positions 6, 7, 8, 9, and 10).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(9, 'Line 3', '11,12,14,15', 'Winner or winners with all numbers in row 3 (positions 11, 12, 14, and 15).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(10, 'Line 4', '16,17,18,19,20', 'Winner or winners with all numbers in row 4 (positions 16, 17, 18, 19, and 20).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(11, 'Line 5', '21,22,23,24,25', 'Winner or winners with all numbers in row 5 (positions 21, 22, 23, 24, and 25).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(12, 'Column B', '1,6,11,16,21', 'Winner or winners with all numbers in column B (positions 1, 6, 11, 16, and 21).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(13, 'Column I', '2,7,12,17,22', 'Winner or winners with all numbers in column I (positions 2, 7, 12, 17, and 22).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(14, 'Column N', '3,8,18,23', 'Winner or winners with all numbers in column N (positions 3, 8, 18, and 23).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(15, 'Column G', '4,9,14,19,24', 'Winner or winners with all numbers in column G (positions 4, 9, 14, 19, and 24).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(16, 'Column O', '5,10,15,20,25', 'Winner or winners with all numbers in column O (positions 5, 10, 15, 20, and 25).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(17, 'Diagonal 1', '1,7,19,25', 'Winner or winners with all numbers in diagonal 1 (positions 1, 7, 19, and 25).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(18, 'Diagonal 2', '5,9,17,21', 'Winner or winners with all numbers in diagonal 2 (positions 5, 9, 17, and 21).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(19, 'Leter A', '1,2,3,4,5,6,10,11,12,14,15,16,20,21,25', 'Winner or winners with all numbers in diagonal 2 (positions 1, 2, 3, 4, 5, 6, 10, 11, 12, 14, 15, 16, 20, 21, and 25).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(20, 'Leter E', '1,2,3,4,5,6,11,12,14,15,16,21,22,23,24,25', 'Winner or winners with all numbers in diagonal 2 (positions 1, 2, 3, 4, 5, 6, 11, 12, 14, 15, 16, 21, 22, 23, 24, and 25).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(21, 'Leter I', '1,2,3,4,5,8,18,21,22,23,24,25', 'Winner or winners with all numbers in diagonal 2 (positions 1, 2, 3, 4, 5, 8, 18, 21, 22, 23, 24, and 25).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1),
(22, 'Leter U', '1,5,6,10,11,15,16,20,21,22,23,24,25', 'Winner or winners with all numbers in diagonal 2 (positions 1, 5, 6, 10, 11, 15, 16, 20, 21, 22, 23, 24, and 25).', '2024-09-28 15:21:19', '2024-09-28 15:21:19', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `from` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `type_id` int(11) NOT NULL,
  `game` int(11) NOT NULL,
  `carton` int(11) NOT NULL,
  `modality` int(11) NOT NULL,
  `numbers` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` longtext NOT NULL,
  `template_id` varchar(255) DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notification_templates`
--

CREATE TABLE `notification_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `title_template` varchar(255) NOT NULL,
  `message_template` longtext NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `action_url_template` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `notification_templates`
--

INSERT INTO `notification_templates` (`id`, `name`, `title_template`, `message_template`, `image_url`, `action_url_template`, `created_at`, `updated_at`) VALUES
(1, 'Nueva Partida', '🎮 Nueva partida: {{game_name}}', '¡No te pierdas la partida {{game_name}} el {{game_date}} a las {{game_time}}! Precio del cartón: {{carton_price}}', '/assets/img/notifications/new-game.png', '/games/view/{{game_id}}', '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(2, 'Recordatorio Partida', '⏰ Recordatorio: {{game_name}}', 'La partida {{game_name}} comenzará en {{time_left}}. ¡No te la pierdas!', '/assets/img/notifications/reminder.png', '/games/view/{{game_id}}', '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(3, 'Victoria', '🏆 ¡Felicidades por tu victoria!', 'Has ganado {{prize_amount}} en la modalidad {{modality_name}} de la partida {{game_name}}', '/assets/img/notifications/win.png', '/games/results/{{game_id}}', '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(4, 'Nuevo Nivel', '⭐ ¡Has subido al nivel {{level_name}}!', 'Felicidades por alcanzar el nivel {{level_name}}. Disfruta de tus nuevos beneficios: {{level_benefits}}', '/assets/img/notifications/level-up.png', '/users/profile', '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(5, 'Logro Desbloqueado', '🏅 ¡Nuevo logro desbloqueado!', 'Has desbloqueado el logro \"{{achievement_name}}\". {{achievement_description}}', '/assets/img/notifications/achievement.png', '/users/achievements', '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(6, 'Oferta Especial', '💰 ¡Oferta especial!', '{{offer_description}}. Válido hasta {{offer_end_date}}', '/assets/img/notifications/special-offer.png', '/offers/{{offer_id}}', '2025-09-16 05:47:26', '2025-09-16 05:47:26'),
(7, 'Bono Diario', '🎁 ¡Tu bono diario está disponible!', 'Inicia sesión hoy para reclamar tu bono diario de {{daily_bonus}} puntos', '/assets/img/notifications/daily-bonus.png', '/users/daily-bonus', '2025-09-16 05:47:26', '2025-09-16 05:47:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `numbers`
--

CREATE TABLE `numbers` (
  `id` int(11) NOT NULL,
  `carton` int(11) NOT NULL,
  `number` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `benefits` longtext NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `free_cartons` int(11) NOT NULL DEFAULT 0,
  `daily_points` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `packages`
--

INSERT INTO `packages` (`id`, `name`, `description`, `price`, `duration_days`, `benefits`, `discount_percentage`, `free_cartons`, `daily_points`, `created_at`, `updated_at`, `status`) VALUES
(1, 'Pro Mensual', 'Suscripción mensual al paquete Pro', 19.99, 30, 'Acceso a partidas exclusivas, descuentos en cartones, puntos diarios', 15.00, 5, 10, '2025-09-16 05:47:26', '2025-09-16 07:08:27', 1),
(2, 'Pro Trimestral', 'Suscripción trimestral al paquete Pro', 49.99, 90, 'Acceso a partidas exclusivas, descuentos en cartones, puntos diarios', 20.00, 20, 15, '2025-09-16 05:47:26', '2025-09-16 07:08:31', 1),
(3, 'Pro Anual', 'Suscripción anual al paquete Pro', 149.99, 365, 'Acceso a partidas exclusivas, descuentos en cartones, puntos diarios', 25.00, 100, 20, '2025-09-16 05:47:26', '2025-09-16 07:08:34', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `type_id` int(11) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paypal_orders`
--

CREATE TABLE `paypal_orders` (
  `id` int(11) NOT NULL,
  `paypal_order_id` varchar(255) NOT NULL,
  `local_order_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `paypal_response` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `points`
--

CREATE TABLE `points` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'earned, spent, expired',
  `source` varchar(50) NOT NULL COMMENT 'game, package, daily, level, referral, etc',
  `source_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `endpoint` text NOT NULL,
  `p256dh_key` varchar(255) NOT NULL,
  `auth_key` varchar(255) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `referrals`
--

CREATE TABLE `referrals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_referred` bigint(20) UNSIGNED NOT NULL,
  `id_referrer` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `amount` decimal(18,2) NOT NULL,
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `retires`
--

CREATE TABLE `retires` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `bank` varchar(255) NOT NULL,
  `account` varchar(255) NOT NULL,
  `document` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `observation` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roulettes`
--

CREATE TABLE `roulettes` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `cartons` int(11) NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `status` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sings`
--

CREATE TABLE `sings` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `game` int(11) NOT NULL,
  `carton` int(11) NOT NULL,
  `modality` int(11) NOT NULL,
  `numbers` varchar(255) NOT NULL,
  `lastnumber` int(11) NOT NULL,
  `notified` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `status` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `processing` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `system`
--

CREATE TABLE `system` (
  `id` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `system`
--

INSERT INTO `system` (`id`, `key`, `value`) VALUES
(1, 'name', 'BINGO FAMILY'),
(2, 'contact', 'Maria Mendoza'),
(3, 'phone', '04160300910'),
(4, 'currency', 'Bs'),
(5, 'language', 'spanish'),
(6, 'bank', '1'),
(7, 'method', 'mobile payment'),
(8, 'logo', '688d2222f30c7.png'),
(9, 'paypal', '[{\"active\":\"1\",\"mode\":\"production\",\"sandbox_client_id\":\"AZEN_0_FwMV3439s8ZVKNYKIbQ4Qmd2GHUlxrSCB-Su7ZDpy9dAMaZYlbjOpP3JlRMO87uERhvPqpT_X\",\"sandbox_secret_key\":\"EO0vzL-OP1Y8rdAgNuZcXerMm5s0yhla1wyHoc1jssh1fCx31PguS4h1syJiid-hUvSJZtGcf_RuTaMS\",\"production_client_id\":\"AZHtGiX4hM43RsdpJpS4-PY6uK9XqyLB5PSl_pw41V8F_893MV63kByZDECUI83la5nYk6twg-FwwAyl\",\"production_secret_key\":\"AZHtGiX4hM43RsdpJpS4-PY6uK9XqyLB5PSl_pw41V8F_893MV63kByZDECUI83la5nYk6twg-FwwAyl\"}]'),
(10, 'numberSings', '3'),
(11, 'singBall', '5000-2500'),
(12, 'activatePayPal', '1'),
(13, 'idPayPal', 'AbmWLXiy_m2u3c_PNxQ5xVm1X54QLchlO3Lbsfv0-MuYqcXgrqxnnp8k2v9J_f-SvQL1zcqGS82OH0Io'),
(14, 'secretPayPal', 'EKIC6pkcYzMuC_uINJSunBt0lktKZn-uQAeNuidQs0a9uPAyB9gHQj5fqdudvu2L6zPHY19K7pFkBWl0'),
(15, 'maxCartons', '30'),
(16, 'email', 'info@bingo.hubbills.com'),
(17, 'address', 'Avenida Nicolas Torrelles, C/C 3'),
(18, 'city', 'Sarare'),
(19, 'zipcode', '3015'),
(21, 'country', 'Venezuela'),
(22, 'rateExchange', '139.40'),
(23, 'valueBGC', '0.10'),
(24, 'rateEarnings', '0.3'),
(25, 'rateBGC', '0.025'),
(26, 'rateReferrals', '0.1'),
(27, 'activateAlgorithm', '0'),
(28, 'activateMinimumDeposit', '1'),
(29, 'generateCartons', '10'),
(30, 'activateCron', '1'),
(31, 'activateRoomCards', '1'),
(32, 'accountInstagram', 'bingobgc'),
(33, 'activateShareGame', '1'),
(34, 'activateRoulette', '1'),
(35, 'activateJoinGroup', '1'),
(36, 'linkGroup', 'https://chat.whatsapp.com/GrcplbidMpgHF64pcUaBCm?mode=ac_t'),
(37, 'activateCompleteProfile', '1'),
(38, 'activateInstallPWA', '1'),
(39, 'minimumDeposit', '50'),
(40, 'maximumDeposit', '1000'),
(41, 'minimumRetire', '1000'),
(42, 'maximumRetire', '25000'),
(43, 'minimumTransfer', '500'),
(44, 'maximumTransfer', '5000'),
(45, 'activateDeposit', '1'),
(46, 'activateRetire', '1'),
(47, 'activateTransfer', '1'),
(48, 'bingoType', '2'),
(49, 'activateAddGames', '1'),
(50, 'addGamesTime', '15'),
(51, 'singBingoOnlyLastBall', '1'),
(52, 'priceRanges', '1'),
(53, 'addGamesFrom', '08:00'),
(54, 'addGamesTo', '22:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temp_cartons`
--

CREATE TABLE `temp_cartons` (
  `id` int(11) NOT NULL,
  `game` int(11) NOT NULL,
  `carton` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transfers`
--

CREATE TABLE `transfers` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `from` int(11) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `note` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `group` int(11) NOT NULL,
  `wallet` decimal(18,2) NOT NULL,
  `document` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `bank` varchar(255) NOT NULL,
  `account` varchar(255) NOT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sounds` int(11) NOT NULL DEFAULT 1,
  `narration` int(11) NOT NULL DEFAULT 1,
  `autodial` int(11) NOT NULL DEFAULT 1,
  `image` varchar(255) NOT NULL,
  `verified_email` int(11) NOT NULL,
  `verification_token` varchar(255) NOT NULL,
  `restore_code` varchar(255) NOT NULL,
  `restore_token` varchar(255) NOT NULL,
  `referred_code` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `deleted` int(11) NOT NULL,
  `roulette` int(11) NOT NULL,
  `level_id` int(11) NOT NULL DEFAULT 1,
  `total_points` int(11) NOT NULL DEFAULT 0,
  `current_points` int(11) NOT NULL DEFAULT 0,
  `consecutive_days` int(11) NOT NULL DEFAULT 0,
  `last_daily_check` date DEFAULT NULL,
  `is_pro` tinyint(1) NOT NULL DEFAULT 0,
  `pro_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `code`, `group`, `wallet`, `document`, `firstname`, `lastname`, `username`, `password`, `email`, `phone`, `bank`, `account`, `remember_token`, `created_at`, `updated_at`, `sounds`, `narration`, `autodial`, `image`, `verified_email`, `verification_token`, `restore_code`, `restore_token`, `referred_code`, `status`, `deleted`, `roulette`, `level_id`, `total_points`, `current_points`, `consecutive_days`, `last_daily_check`, `is_pro`, `pro_until`) VALUES
(1, 'BGC-A00001', 1, 0.00, '012345678', 'Administrator 1', 'SUPER BINGO', 'admin', '$2y$10$pjwkgD/PwzZnsQGbe1By7.6qn.MoojjKAtVSKa9/HBK6g9kSdRrgm', 'administrator@superbingo.com', '18295696352', '', '', '9155e169db42e57259381a83da88a73d', '2025-09-06 23:02:48', '2025-10-08 14:15:10', 1, 1, 1, '', 1, '', '', '', '921b9284e496555c72f5fccb6f2cf551', 1, 0, 1, 1, 0, 0, 0, NULL, 0, NULL),
(2, 'BGC-A00002', 0, 4150.00, '12345678', 'Player 1', 'SUPER BINGO', 'player1', '$2y$10$pjwkgD/PwzZnsQGbe1By7.6qn.MoojjKAtVSKa9/HBK6g9kSdRrgm', 'player1@superbingo.com', '18295698745', '', '', '38c597b275e35f728751e726cf2ead5d', '2025-08-01 17:49:39', '2025-10-08 05:51:32', 1, 1, 1, '', 1, '', '', '', '87b8a6f5cbdbd45b486d1085540abcfe', 1, 0, 1, 1, 0, 0, 0, NULL, 0, NULL),
(3, 'BGC-A00003', 0, 5000.00, '87654321', 'Player 2', 'SUPER BINGO', 'player2', '$2y$10$pjwkgD/PwzZnsQGbe1By7.6qn.MoojjKAtVSKa9/HBK6g9kSdRrgm', 'player2@superbingo.com', '18297854142', '', '', NULL, '2025-08-01 21:39:33', '2025-10-08 00:46:52', 1, 1, 1, '', 1, '', '', '', '75e59c010ac862f1f2b8b501bfbc2a05', 1, 0, 1, 1, 0, 0, 0, NULL, 0, NULL),
(4, 'BGC-A00004', 0, 56.00, '11042239', 'Junior ', 'Nunes', 'Junior1104', '$2y$10$UV0CNKn.gW6WFYMtyHR/j.l/9Dj4QBhKaSsIOG2S66mza7Gm8zPLO', 'junior110476@gmail.com', '48984091655', '', '', '209e95dd52aadd87a4542215d09a9299', '2025-10-09 06:13:26', '2025-10-09 08:04:57', 0, 0, 1, '', 0, '3d82ccb4613f96fb4ce41693468f7d91', '', '', 'XAVZ3TRT', 1, 0, 1, 1, 0, 0, 0, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_achievements`
--

CREATE TABLE `user_achievements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `achievement_id` int(11) NOT NULL,
  `progress` int(11) NOT NULL DEFAULT 0,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_packages`
--

CREATE TABLE `user_packages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `payment_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `awards`
--
ALTER TABLE `awards`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `banks`
--
ALTER TABLE `banks`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `boards`
--
ALTER TABLE `boards`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cartons`
--
ALTER TABLE `cartons`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `ci_sessions`
--
ALTER TABLE `ci_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ci_sessions_timestamp` (`timestamp`);

--
-- Indices de la tabla `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `deposits`
--
ALTER TABLE `deposits`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `email_campaigns`
--
ALTER TABLE `email_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `email_stats`
--
ALTER TABLE `email_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `firebase_tokens`
--
ALTER TABLE `firebase_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `game_rooms`
--
ALTER TABLE `game_rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indices de la tabla `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `modalities`
--
ALTER TABLE `modalities`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `notification_templates`
--
ALTER TABLE `notification_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `numbers`
--
ALTER TABLE `numbers`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `paypal_orders`
--
ALTER TABLE `paypal_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `points`
--
ALTER TABLE `points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_referred` (`id_referred`),
  ADD KEY `id_referrer` (`id_referrer`);

--
-- Indices de la tabla `retires`
--
ALTER TABLE `retires`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `roulettes`
--
ALTER TABLE `roulettes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `sings`
--
ALTER TABLE `sings`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `system`
--
ALTER TABLE `system`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `temp_cartons`
--
ALTER TABLE `temp_cartons`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `transfers`
--
ALTER TABLE `transfers`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_achievement` (`user_id`,`achievement_id`),
  ADD KEY `achievement_id` (`achievement_id`);

--
-- Indices de la tabla `user_packages`
--
ALTER TABLE `user_packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `awards`
--
ALTER TABLE `awards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `banks`
--
ALTER TABLE `banks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `boards`
--
ALTER TABLE `boards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cartons`
--
ALTER TABLE `cartons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `deposits`
--
ALTER TABLE `deposits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `email_campaigns`
--
ALTER TABLE `email_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `email_stats`
--
ALTER TABLE `email_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `firebase_tokens`
--
ALTER TABLE `firebase_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `game_rooms`
--
ALTER TABLE `game_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `levels`
--
ALTER TABLE `levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `logs`
--
ALTER TABLE `logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modalities`
--
ALTER TABLE `modalities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notification_templates`
--
ALTER TABLE `notification_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `numbers`
--
ALTER TABLE `numbers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `paypal_orders`
--
ALTER TABLE `paypal_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `points`
--
ALTER TABLE `points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `referrals`
--
ALTER TABLE `referrals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `retires`
--
ALTER TABLE `retires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roulettes`
--
ALTER TABLE `roulettes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sings`
--
ALTER TABLE `sings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `system`
--
ALTER TABLE `system`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de la tabla `temp_cartons`
--
ALTER TABLE `temp_cartons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `transfers`
--
ALTER TABLE `transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `user_achievements`
--
ALTER TABLE `user_achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `user_packages`
--
ALTER TABLE `user_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
