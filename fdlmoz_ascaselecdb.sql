-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 19-Mar-2026 às 20:20
-- Versão do servidor: 10.6.25-MariaDB-log
-- versão do PHP: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de dados: `fdlmoz_ascaselecdb`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'login', 'user', 1, 'Login de Administrador', '::1', '2026-02-20 09:50:58'),
(2, 1, 'member_created', 'member', 1, 'Novo membro: Mauro Ribeiro', '::1', '2026-02-20 09:59:50'),
(3, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Mauro Ribeiro: 2.000,00 MT', '::1', '2026-02-20 10:01:44'),
(4, 1, 'logout', 'user', 1, 'Logout', '::1', '2026-02-20 10:25:28'),
(5, NULL, 'login', 'user', 2, 'Login de Mauro Ribeiro', '::1', '2026-02-20 10:25:53'),
(6, NULL, 'logout', 'user', 2, 'Logout', '::1', '2026-02-20 10:28:52'),
(7, 1, 'login', 'user', 1, 'Login de Administrador', '::1', '2026-02-20 10:29:05'),
(8, 1, 'login', 'user', 1, 'Login de Administrador', '::1', '2026-02-20 10:47:20'),
(9, 1, 'joia_paid', 'member', 1, 'Jóia paga', '::1', '2026-02-20 10:49:14'),
(10, 1, 'logout', 'user', 1, 'Logout', '::1', '2026-02-20 11:15:35'),
(11, NULL, 'login', 'user', 2, 'Login de Mauro Ribeiro', '::1', '2026-02-20 11:17:45'),
(12, NULL, 'logout', 'user', 2, 'Logout', '::1', '2026-02-20 11:20:05'),
(13, 1, 'login', 'user', 1, 'Login de Administrador', '::1', '2026-02-20 11:20:18'),
(14, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Mauro Ribeiro: 2.000,00 MT', '::1', '2026-02-20 11:35:40'),
(15, 1, 'logout', 'user', 1, 'Logout', '::1', '2026-02-20 11:50:20'),
(16, 1, 'login', 'user', 1, 'Login de Administrador', '::1', '2026-02-20 11:52:16'),
(17, 1, 'member_created', 'member', 2, 'Novo membro: Geraldo Eliseu', '::1', '2026-02-20 11:55:56'),
(18, 1, 'joia_paid', 'member', 2, 'Jóia paga', '::1', '2026-02-20 11:56:56'),
(19, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Geraldo Eliseu: 2.000,00 MT', '::1', '2026-02-20 11:58:07'),
(20, 1, 'loan_created', 'loan', 1, 'Empréstimo de 1.000,00 MT para Mauro Ribeiro', '::1', '2026-02-20 11:59:52'),
(21, 1, 'logout', 'user', 1, 'Logout', '::1', '2026-02-20 12:12:43'),
(22, NULL, 'login', 'user', 3, 'Login de Geraldo Eliseu', '::1', '2026-02-20 12:13:08'),
(23, NULL, 'logout', 'user', 3, 'Logout', '::1', '2026-02-20 12:32:53'),
(24, 1, 'login', 'user', 1, 'Login de Administrador', '::1', '2026-02-20 12:33:10'),
(25, 1, 'logout', 'user', 1, 'Logout', '::1', '2026-02-20 13:22:50'),
(26, 1, 'logout', 'user', 1, 'Logout', '::1', '2026-02-20 14:18:36'),
(27, 1, 'login', 'user', 1, 'Login de Administrador', '::1', '2026-02-20 14:18:54'),
(28, 1, 'logout', 'user', 1, 'Logout', '::1', '2026-02-20 14:19:29'),
(29, 1, 'login', 'user', 1, 'Login de Administrador', '::1', '2026-02-20 16:40:06'),
(30, 1, 'member_created', 'member', 3, 'Novo membro: Valdemar Colimão', '::1', '2026-02-20 18:49:48'),
(31, 1, 'member_deleted', 'member', 3, 'Membro desactivado', '::1', '2026-02-20 18:50:04'),
(32, 1, 'member_deleted', 'member', 3, 'Membro desactivado', '::1', '2026-02-20 19:04:10'),
(33, 1, 'logout', 'user', 1, 'Logout', '::1', '2026-02-20 19:04:20'),
(34, 1, 'login', 'user', 1, 'Login de Administrador', '::1', '2026-02-20 19:04:31'),
(35, 1, 'member_deleted', 'member', 3, 'Membro desactivado', '::1', '2026-02-20 19:04:51'),
(36, 1, 'login', 'user', 1, 'Login de Administrador', '::1', '2026-02-21 18:55:29'),
(37, 1, 'member_deleted', 'member', 1, 'Membro desactivado', '::1', '2026-02-21 18:56:00'),
(38, 1, 'member_deleted', 'member', 2, 'Membro desactivado', '::1', '2026-02-21 18:56:08'),
(39, 1, 'member_created', 'member', 4, 'Novo membro: Mauro Ribeiro', '::1', '2026-02-21 19:25:10'),
(40, 1, 'member_created', 'member', 5, 'Novo membro: Geraldo Eliseu', '::1', '2026-02-21 19:27:34'),
(41, 1, 'member_created', 'member', 6, 'Novo membro: Valdemar Colimão', '::1', '2026-02-21 19:29:30'),
(42, 1, 'member_created', 'member', 7, 'Novo membro: Messias Mouzinho', '::1', '2026-02-21 19:30:38'),
(43, 1, 'joia_paid', 'member', 4, 'Jóia paga', '::1', '2026-02-21 19:31:29'),
(44, 1, 'joia_paid', 'member', 7, 'Jóia paga', '::1', '2026-02-21 19:32:53'),
(45, 1, 'joia_paid', 'member', 6, 'Jóia paga', '::1', '2026-02-21 19:33:25'),
(46, 1, 'joia_paid', 'member', 6, 'Jóia paga', '::1', '2026-02-21 19:33:32'),
(47, 1, 'joia_paid', 'member', 5, 'Jóia paga', '::1', '2026-02-21 19:34:00'),
(48, 1, 'logout', 'user', 1, 'Logout', '::1', '2026-02-21 19:47:23'),
(49, 1, 'login', 'user', 1, 'Login de Administrador', '::1', '2026-02-21 19:47:40'),
(50, 1, 'logout', 'user', 1, 'Logout', '::1', '2026-02-21 20:13:34'),
(51, 1, 'login', 'user', 1, 'Login de Administrador', '::1', '2026-02-21 20:14:24'),
(52, 1, 'login', 'user', 1, 'Login de Administrador', '::1', '2026-02-21 20:32:30'),
(53, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Geraldo Eliseu: 2.000,00 MT', '::1', '2026-02-21 20:39:13'),
(54, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Mauro Ribeiro: 2.000,00 MT', '::1', '2026-02-21 20:40:00'),
(55, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Messias Mouzinho: 2.000,00 MT', '::1', '2026-02-21 20:40:35'),
(56, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Valdemar Colimão: 2.000,00 MT', '::1', '2026-02-21 20:41:03'),
(57, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Geraldo Eliseu: 3.000,00 MT', '::1', '2026-02-21 20:41:59'),
(58, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Mauro Ribeiro: 2.000,00 MT', '::1', '2026-02-21 20:42:24'),
(59, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Messias Mouzinho: 2.000,00 MT', '::1', '2026-02-21 20:43:38'),
(60, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Valdemar Colimão: 2.000,00 MT', '::1', '2026-02-21 20:44:04'),
(61, 1, 'loan_created', 'loan', 2, 'Empréstimo de 2.000,00 MT para Geraldo Eliseu', '::1', '2026-02-21 20:59:08'),
(62, 1, 'loan_repaid', 'loan', 2, 'Pagamento (INTEREST_ONLY) de 300,00 MT', '::1', '2026-02-21 21:49:05'),
(63, 1, 'loan_created', 'loan', 3, 'Empréstimo de 3.000,00 MT para Mauro Ribeiro', '::1', '2026-02-21 22:16:19'),
(64, 1, 'loan_repaid', 'loan', 3, 'Pagamento (INTEREST_ONLY) de 450,00 MT', '::1', '2026-02-21 22:18:02'),
(65, 1, 'loan_created', 'loan', 4, 'Empréstimo de 1.000,00 MT para Mauro Ribeiro', '::1', '2026-02-21 22:18:52'),
(66, 1, 'logout', 'user', 1, 'Logout', '::1', '2026-02-21 22:42:04'),
(67, 1, 'login', 'user', 1, 'Login de Administrador', '::1', '2026-02-21 22:42:31'),
(68, 1, 'member_deleted', 'member', 4, 'Membro apagado permanentemente (Hard Delete)', '::1', '2026-02-22 00:31:49'),
(69, 1, 'member_deleted', 'member', 6, 'Membro apagado permanentemente (Hard Delete)', '::1', '2026-02-22 19:33:55'),
(70, 1, 'member_deleted', 'member', 7, 'Membro apagado permanentemente (Hard Delete)', '::1', '2026-02-22 19:34:39'),
(71, 1, 'member_deleted', 'member', 5, 'Membro apagado permanentemente (Hard Delete)', '::1', '2026-02-22 19:35:37'),
(72, 1, 'member_created', 'member', 8, 'Novo membro: Mauro Ribeiro', '197.218.77.110', '2026-02-22 19:53:18'),
(73, 1, 'member_created', 'member', 9, 'Novo membro: Alburquerque Rimua', '197.218.77.110', '2026-02-22 19:55:37'),
(74, 1, 'member_created', 'member', 10, 'Novo membro: Geraldo Eliseu', '197.218.77.110', '2026-02-22 19:58:09'),
(75, 1, 'member_created', 'member', 11, 'Novo membro: Clementino Hale', '197.218.77.110', '2026-02-22 20:00:56'),
(76, 1, 'member_created', 'member', 12, 'Novo membro: Leonel Jassitene', '197.218.77.110', '2026-02-22 20:09:26'),
(77, 1, 'member_created', 'member', 13, 'Novo membro: Valdemar Colimão', '197.218.77.110', '2026-02-22 20:11:49'),
(78, 1, 'member_created', 'member', 14, 'Novo membro: Messias Mouzinho', '197.218.77.110', '2026-02-22 20:14:10'),
(79, 1, 'member_created', 'member', 15, 'Novo membro: Elisio Chaima', '197.218.77.110', '2026-02-22 20:20:28'),
(80, 1, 'member_created', 'member', 16, 'Novo membro: Ibrahimo Ibrahimo', '197.218.77.110', '2026-02-22 20:33:26'),
(81, 1, 'joia_paid', 'member', 9, 'Jóia paga', '197.218.77.110', '2026-02-22 20:39:19'),
(82, 1, 'joia_paid', 'member', 11, 'Jóia paga', '197.218.77.110', '2026-02-22 20:39:41'),
(83, 1, 'joia_paid', 'member', 15, 'Jóia paga', '197.218.77.110', '2026-02-22 20:40:11'),
(84, 1, 'joia_paid', 'member', 10, 'Jóia paga', '197.218.77.110', '2026-02-22 20:40:35'),
(85, 1, 'joia_paid', 'member', 16, 'Jóia paga', '197.218.77.110', '2026-02-22 20:41:04'),
(86, 1, 'joia_paid', 'member', 12, 'Jóia paga', '197.218.77.110', '2026-02-22 20:41:26'),
(87, 1, 'joia_paid', 'member', 8, 'Jóia paga', '197.218.77.110', '2026-02-22 20:42:12'),
(88, 1, 'joia_paid', 'member', 14, 'Jóia paga', '197.218.77.110', '2026-02-22 20:42:51'),
(89, 1, 'joia_paid', 'member', 13, 'Jóia paga', '197.218.77.110', '2026-02-22 20:43:39'),
(90, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Geraldo Eliseu: 3.000,00 MT', '197.218.77.110', '2026-02-22 20:48:32'),
(91, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Geraldo Eliseu: 3.000,00 MT', '197.218.77.110', '2026-02-22 20:48:58'),
(92, 1, 'settings_updated', 'cycle', 1, 'Definições do ciclo actualizadas', '197.218.77.110', '2026-02-22 20:52:26'),
(93, 1, 'settings_updated', 'cycle', 1, 'Definições do ciclo actualizadas', '197.218.77.110', '2026-02-22 20:53:44'),
(94, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Geraldo Eliseu: 3.000,00 MT', '197.218.77.110', '2026-02-22 21:00:17'),
(95, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Mauro Ribeiro: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:05:55'),
(96, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Mauro Ribeiro: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:06:37'),
(97, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Mauro Ribeiro: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:07:13'),
(98, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Mauro Ribeiro: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:09:43'),
(99, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Clementino Hale: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:10:54'),
(100, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Clementino Hale: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:11:22'),
(101, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Clementino Hale: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:11:47'),
(102, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Ibrahimo Ibrahimo: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:13:17'),
(103, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Ibrahimo Ibrahimo: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:13:45'),
(104, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Leonel Jassitene: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:14:48'),
(105, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Leonel Jassitene: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:15:23'),
(106, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Elisio Chaima: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:16:28'),
(107, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Elisio Chaima: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:17:06'),
(108, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Alburquerque Rimua: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:18:57'),
(109, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Alburquerque Rimua: 3.000,00 MT', '197.218.77.110', '2026-02-22 21:19:23'),
(110, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Valdemar Colimão: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:20:40'),
(111, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Valdemar Colimão: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:21:09'),
(112, 1, 'contribution_created', 'contribution', 0, 'Contribuição de Messias Mouzinho: 2.000,00 MT', '197.218.77.110', '2026-02-22 21:22:01'),
(113, 1, 'loan_created', 'loan', 5, 'Empréstimo de 1.000,00 MT para Messias Mouzinho', '197.218.77.110', '2026-02-22 21:27:37'),
(114, 1, 'loan_repaid', 'loan', 5, 'Pagamento (TOTAL) de 1.150,00 MT', '197.218.77.110', '2026-02-22 21:28:48'),
(115, 1, 'loan_created', 'loan', 6, 'Empréstimo de 1.000,00 MT para Messias Mouzinho', '197.218.77.110', '2026-02-22 21:31:56'),
(116, 1, 'loan_repaid', 'loan', 6, 'Pagamento (INTEREST_ONLY) de 150,00 MT', '197.218.77.110', '2026-02-22 21:32:32'),
(117, 1, 'loan_repaid', 'loan', 6, 'Pagamento (TOTAL) de 1.000,00 MT', '197.218.77.110', '2026-02-22 21:33:17'),
(118, 1, 'logout', 'user', 1, 'Logout', '197.218.77.110', '2026-02-22 21:41:29'),
(119, 9, 'login', 'user', 9, 'Login de Mauro Ribeiro', '197.218.77.110', '2026-02-22 21:41:45'),
(120, 9, 'logout', 'user', 9, 'Logout', '197.218.77.110', '2026-02-22 21:43:04'),
(121, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.77.110', '2026-02-22 21:43:10'),
(122, 10, 'login', 'user', 10, 'Login de Alburquerque Rimua', '197.235.233.138', '2026-02-22 22:02:10'),
(123, 9, 'login', 'user', 9, 'Login de Mauro Ribeiro', '197.218.77.110', '2026-02-22 22:04:29'),
(124, 10, 'password_changed', 'member', 9, 'Membro alterou a sua palavra-passe', '197.235.233.138', '2026-02-22 22:06:24'),
(125, 10, 'member_updated', 'member', 9, 'Membro actualizou seus dados pessoais', '197.235.233.138', '2026-02-22 22:06:54'),
(126, 10, 'member_updated', 'member', 9, 'Membro actualizou seus dados pessoais', '197.235.233.138', '2026-02-22 22:07:12'),
(127, 10, 'member_updated', 'member', 9, 'Membro actualizou seus dados pessoais', '197.235.233.138', '2026-02-22 22:07:40'),
(128, 10, 'member_updated', 'member', 9, 'Membro actualizou seus dados pessoais', '197.235.233.138', '2026-02-22 22:07:41'),
(129, 10, 'login', 'user', 10, 'Login de Albuquerque Rimua', '197.235.233.138', '2026-02-22 22:10:20'),
(130, 9, 'logout', 'user', 9, 'Logout', '197.218.77.110', '2026-02-22 22:20:51'),
(131, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.77.110', '2026-02-22 22:21:20'),
(132, 1, 'logout', 'user', 1, 'Logout', '197.218.77.110', '2026-02-22 22:24:39'),
(133, 17, 'login', 'user', 17, 'Login de Ibrahimo Ibrahimo', '197.218.66.23', '2026-02-23 05:22:38'),
(134, 11, 'login', 'user', 11, 'Login de Geraldo Eliseu', '197.218.74.100', '2026-02-23 05:30:38'),
(135, 11, 'password_changed', 'member', 10, 'Membro alterou a sua palavra-passe', '197.218.74.100', '2026-02-23 05:32:54'),
(136, 11, 'logout', 'user', 11, 'Logout', '197.218.74.100', '2026-02-23 05:33:13'),
(137, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.68.24', '2026-02-23 09:27:17'),
(138, 1, 'loan_created', 'loan', 7, 'Empréstimo de 1.000,00 MT para Messias Mouzinho', '197.218.68.24', '2026-02-23 09:43:01'),
(139, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.68.24', '2026-02-23 11:18:29'),
(140, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.68.24', '2026-02-23 12:10:20'),
(141, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.68.24', '2026-02-23 14:31:02'),
(142, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.68.24', '2026-02-23 15:15:22'),
(143, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.68.24', '2026-02-23 15:18:17'),
(144, 1, 'loan_repaid', 'loan', 7, 'Liquidação Total: 1.300,00 MT', '197.218.68.24', '2026-02-23 15:22:09'),
(145, 1, 'loan_created', 'loan', 8, 'Empréstimo de 4.000,00 MT para Mauro Ribeiro', '197.218.68.24', '2026-02-23 15:24:19'),
(146, 1, 'loan_repaid', 'loan', 8, 'Liquidação Total: 4.600,00 MT', '197.218.68.24', '2026-02-23 15:26:06'),
(147, 1, 'loan_created', 'loan', 9, 'Empréstimo de 1.000,00 MT para Mauro Ribeiro', '197.218.68.24', '2026-02-23 15:28:04'),
(148, 1, 'loan_repaid', 'loan', 9, 'Liquidação Total: 1.150,00 MT', '197.218.68.24', '2026-02-23 15:29:32'),
(149, 1, 'loan_created', 'loan', 10, 'Empréstimo de 1.000,00 MT para Elisio Chaima', '197.218.68.24', '2026-02-23 15:30:43'),
(150, 1, 'loan_repaid', 'loan', 10, 'Liquidação Total: 1.300,00 MT', '197.218.68.24', '2026-02-23 15:34:11'),
(151, 1, 'loan_created', 'loan', 11, 'Empréstimo de 1.000,00 MT para Ibrahimo Ibrahimo', '197.218.68.24', '2026-02-23 15:35:04'),
(152, 1, 'loan_repaid', 'loan', 11, 'Liquidação Total: 1.150,00 MT', '197.218.68.24', '2026-02-23 15:35:54'),
(153, 1, 'loan_created', 'loan', 12, 'Empréstimo de 1.000,00 MT para Messias Mouzinho', '197.218.68.24', '2026-02-23 15:36:48'),
(154, 1, 'loan_repaid', 'loan', 12, 'Liquidação Total: 1.150,00 MT', '197.218.68.24', '2026-02-23 15:37:22'),
(155, 1, 'loan_created', 'loan', 13, 'Empréstimo de 1.000,00 MT para Messias Mouzinho', '197.218.68.24', '2026-02-23 15:38:13'),
(156, 1, 'loan_repaid', 'loan', 13, 'Liquidação Total: 1.150,00 MT', '197.218.68.24', '2026-02-23 15:38:43'),
(157, 1, 'loan_created', 'loan', 14, 'Empréstimo de 3.000,00 MT para Ibrahimo Ibrahimo', '197.218.68.24', '2026-02-23 15:39:32'),
(158, 1, 'loan_repaid', 'loan', 14, 'Liquidação Total: 3.450,00 MT', '197.218.68.24', '2026-02-23 15:39:56'),
(159, 1, 'loan_created', 'loan', 15, 'Empréstimo de 1.000,00 MT para Geraldo Eliseu', '197.218.68.24', '2026-02-23 15:40:46'),
(160, 1, 'loan_repaid', 'loan', 15, 'Liquidação Total: 1.150,00 MT', '197.218.68.24', '2026-02-23 15:41:08'),
(161, 1, 'loan_created', 'loan', 16, 'Empréstimo de 3.000,00 MT para Albuquerque Rimua', '197.218.68.24', '2026-02-23 15:42:03'),
(162, 1, 'loan_repaid', 'loan', 16, 'Liquidação Total: 3.450,00 MT', '197.218.68.24', '2026-02-23 15:42:26'),
(163, 1, 'loan_created', 'loan', 17, 'Empréstimo de 1.000,00 MT para Clementino Hale', '197.218.68.24', '2026-02-23 15:43:08'),
(164, 1, 'loan_repaid', 'loan', 17, 'Liquidação Total: 1.150,00 MT', '197.218.68.24', '2026-02-23 15:43:29'),
(165, 1, 'loan_created', 'loan', 18, 'Empréstimo de 1.500,00 MT para Clementino Hale', '197.218.68.24', '2026-02-23 15:45:03'),
(166, 1, 'loan_repaid', 'loan', 18, 'Liquidação Total: 1.725,00 MT', '197.218.68.24', '2026-02-23 15:46:10'),
(167, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.68.24', '2026-02-23 16:42:51'),
(168, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.145', '2026-02-23 22:19:39'),
(169, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.145', '2026-02-23 22:22:16'),
(170, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.64.93', '2026-02-24 12:45:19'),
(171, 17, 'login', 'user', 17, 'Login de Ibrahimo Ibrahimo', '197.218.65.7', '2026-02-25 15:26:02'),
(172, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.74.62', '2026-02-25 17:43:50'),
(173, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.74.62', '2026-02-25 18:39:25'),
(174, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.74.62', '2026-02-25 18:42:33'),
(175, 1, 'loan_created', 'loan', 19, 'Empréstimo de 500,00 MT para Clementino Hale', '197.218.74.62', '2026-02-25 19:29:39'),
(176, 1, 'loan_repaid', 'loan', 19, 'Liquidação Total: 575,00 MT', '197.218.74.62', '2026-02-25 19:30:21'),
(177, 1, 'loan_created', 'loan', 20, 'Empréstimo de 3.500,00 MT para Valdemar Colimão', '197.218.74.62', '2026-02-25 19:31:23'),
(178, 1, 'loan_repaid', 'loan', 20, 'Liquidação Total: 4.025,00 MT', '197.218.74.62', '2026-02-25 19:32:14'),
(179, 1, 'loan_created', 'loan', 21, 'Empréstimo de 1.500,00 MT para Valdemar Colimão', '197.218.74.62', '2026-02-25 19:33:41'),
(180, 1, 'loan_repaid', 'loan', 21, 'Liquidação Total: 1.725,00 MT', '197.218.74.62', '2026-02-25 19:34:12'),
(181, 1, 'loan_created', 'loan', 22, 'Empréstimo de 1.500,00 MT para Geraldo Eliseu', '197.218.74.62', '2026-02-25 19:34:56'),
(182, 1, 'loan_repaid', 'loan', 22, 'Liquidação Total: 1.725,00 MT', '197.218.74.62', '2026-02-25 19:35:18'),
(183, 1, 'loan_created', 'loan', 23, 'Empréstimo de 1.000,00 MT para Mauro Ribeiro', '197.218.74.62', '2026-02-25 19:36:33'),
(184, 1, 'loan_repaid', 'loan', 23, 'Liquidação Total: 1.150,00 MT', '197.218.74.62', '2026-02-25 19:37:15'),
(185, 1, 'loan_created', 'loan', 24, 'Empréstimo de 500,00 MT para Clementino Hale', '197.218.74.62', '2026-02-25 19:38:36'),
(186, 1, 'loan_repaid', 'loan', 24, 'Liquidação Total: 575,00 MT', '197.218.74.62', '2026-02-25 19:38:53'),
(187, 1, 'loan_created', 'loan', 25, 'Empréstimo de 1.500,00 MT para Geraldo Eliseu', '197.218.74.62', '2026-02-25 19:39:50'),
(188, 1, 'loan_repaid', 'loan', 25, 'Liquidação Total: 1.725,00 MT', '197.218.74.62', '2026-02-25 19:40:16'),
(189, 1, 'loan_created', 'loan', 26, 'Empréstimo de 3.000,00 MT para Leonel Jassitene', '197.218.74.62', '2026-02-25 19:41:04'),
(190, 1, 'loan_created', 'loan', 27, 'Empréstimo de 1.000,00 MT para Ibrahimo Ibrahimo', '197.218.74.62', '2026-02-25 19:42:04'),
(191, 1, 'loan_created', 'loan', 28, 'Empréstimo de 1.500,00 MT para Geraldo Eliseu', '197.218.74.62', '2026-02-25 19:43:02'),
(192, 1, 'loan_created', 'loan', 29, 'Empréstimo de 1.000,00 MT para Albuquerque Rimua', '197.218.74.62', '2026-02-25 19:45:02'),
(193, 1, 'loan_created', 'loan', 30, 'Empréstimo de 1.500,00 MT para Albuquerque Rimua', '197.218.74.62', '2026-02-25 19:46:01'),
(194, 1, 'loan_created', 'loan', 31, 'Empréstimo de 3.000,00 MT para Messias Mouzinho', '197.218.74.62', '2026-02-25 19:46:56'),
(195, 1, 'loan_created', 'loan', 32, 'Empréstimo de 4.000,00 MT para Albuquerque Rimua', '197.218.74.62', '2026-02-25 19:47:39'),
(196, 1, 'loan_created', 'loan', 33, 'Empréstimo de 500,00 MT para Ibrahimo Ibrahimo', '197.218.74.62', '2026-02-25 19:48:13'),
(197, 1, 'loan_created', 'loan', 34, 'Empréstimo de 600,00 MT para Messias Mouzinho', '197.218.74.62', '2026-02-25 19:49:04'),
(198, 1, 'loan_created', 'loan', 35, 'Empréstimo de 5.000,00 MT para Clementino Hale', '197.218.74.62', '2026-02-25 19:49:59'),
(199, 1, 'loan_created', 'loan', 36, 'Empréstimo de 4.000,00 MT para Mauro Ribeiro', '197.218.74.62', '2026-02-25 19:51:21'),
(200, 1, 'loan_created', 'loan', 37, 'Empréstimo de 2.000,00 MT para Mauro Ribeiro', '197.218.74.62', '2026-02-25 19:52:00'),
(201, 1, 'settings_updated', 'cycle', 1, 'Definições do ciclo actualizadas', '197.218.74.62', '2026-02-25 19:53:47'),
(202, 1, 'loan_created', 'loan', 38, 'Empréstimo de 5.500,00 MT para Elisio Chaima', '197.218.74.62', '2026-02-25 19:54:45'),
(203, 1, 'loan_created', 'loan', 39, 'Empréstimo de 3.000,00 MT para Geraldo Eliseu', '197.218.74.62', '2026-02-25 19:55:51'),
(204, 1, 'loan_created', 'loan', 40, 'Empréstimo de 2.000,00 MT para Ibrahimo Ibrahimo', '197.218.74.62', '2026-02-25 19:56:41'),
(205, 1, 'loan_created', 'loan', 41, 'Empréstimo de 3.500,00 MT para Valdemar Colimão', '197.218.74.62', '2026-02-25 19:57:57'),
(206, 1, 'loan_created', 'loan', 42, 'Empréstimo de 1.500,00 MT para Valdemar Colimão', '197.218.74.62', '2026-02-25 19:58:39'),
(207, 1, 'loan_created', 'loan', 43, 'Empréstimo de 2.500,00 MT para Mauro Ribeiro', '197.218.74.62', '2026-02-25 19:59:21'),
(208, 1, 'loan_created', 'loan', 44, 'Empréstimo de 1.100,00 MT para Ibrahimo Ibrahimo', '197.218.74.62', '2026-02-25 20:00:00'),
(209, 1, 'loan_created', 'loan', 45, 'Empréstimo de 500,00 MT para Ibrahimo Ibrahimo', '197.218.74.62', '2026-02-25 20:00:45'),
(210, 1, 'logout', 'user', 1, 'Logout', '197.218.74.62', '2026-02-25 20:39:59'),
(211, 9, 'login', 'user', 9, 'Login de Mauro Ribeiro', '197.218.74.62', '2026-02-25 20:40:05'),
(212, 9, 'logout', 'user', 9, 'Logout', '197.218.74.62', '2026-02-25 20:42:20'),
(213, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.74.62', '2026-02-25 20:42:33'),
(214, 1, 'contribution_updated', 'contribution', 24, 'Contribuição editada: 2.000,00 MT', '197.218.74.62', '2026-02-25 20:43:27'),
(215, 1, 'contribution_updated', 'contribution', 26, 'Contribuição editada: 2.000,00 MT', '197.218.74.62', '2026-02-25 20:43:51'),
(216, 9, 'login', 'user', 9, 'Login de Mauro Ribeiro', '197.218.74.62', '2026-02-25 20:52:57'),
(217, 17, 'login', 'user', 17, 'Login de Ibrahimo Ibrahimo', '197.218.65.7', '2026-02-25 21:08:53'),
(218, 9, 'logout', 'user', 9, 'Logout', '197.218.74.62', '2026-02-25 21:23:58'),
(219, 17, 'login', 'user', 17, 'Login de Ibrahimo Ibrahimo', '197.218.68.47', '2026-02-25 23:05:22'),
(220, 15, 'login', 'user', 15, 'Login de Messias Mouzinho', '197.235.92.106', '2026-02-25 23:27:36'),
(221, 11, 'login', 'user', 11, 'Login de Geraldo Eliseu', '197.218.73.44', '2026-02-26 07:04:39'),
(222, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.74.62', '2026-02-26 08:41:53'),
(223, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.74.62', '2026-02-26 10:53:47'),
(224, 17, 'login', 'user', 17, 'Login de Ibrahimo Ibrahimo', '197.218.68.47', '2026-02-26 11:14:02'),
(225, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.74.62', '2026-02-26 11:14:25'),
(226, 17, 'login', 'user', 17, 'Login de Ibrahimo Ibrahimo', '197.218.72.86', '2026-02-26 13:39:31'),
(227, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.66.125', '2026-02-26 18:33:39'),
(228, 1, 'logout', 'user', 1, 'Logout', '197.218.66.125', '2026-02-26 18:33:46'),
(229, 9, 'login', 'user', 9, 'Login de Mauro Ribeiro', '197.218.66.125', '2026-02-26 18:34:20'),
(230, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.66.125', '2026-02-26 19:05:23'),
(231, 1, 'loan_repaid', 'loan', 27, 'Liquidação Total: 1.300,00 MT', '197.218.66.125', '2026-02-26 19:06:45'),
(232, 1, 'loan_repaid', 'loan', 36, 'Pagamento Apenas Juros: 600,00 MT. Capital 4.000,00 MT → novo empréstimo #46', '197.218.66.125', '2026-02-26 19:07:30'),
(233, 1, 'loan_repaid', 'loan', 34, 'Pagamento Apenas Juros: 90,00 MT. Capital 600,00 MT → novo empréstimo #47', '197.218.66.125', '2026-02-26 19:09:09'),
(234, 1, 'loan_repaid', 'loan', 31, 'Pagamento Apenas Juros: 450,00 MT. Capital 3.000,00 MT → novo empréstimo #48', '197.218.66.125', '2026-02-26 19:09:47'),
(235, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.66.125', '2026-02-26 19:24:12'),
(236, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.66.125', '2026-02-26 20:02:36'),
(237, 15, 'login', 'user', 15, 'Login de Messias Mouzinho', '197.218.72.13', '2026-02-26 20:03:52'),
(238, 1, 'loan_repaid', 'loan', 40, 'Liquidação Total: 2.300,00 MT', '197.218.66.125', '2026-02-26 20:07:40'),
(239, 1, 'loan_repaid', 'loan', 33, 'Liquidação Total: 575,00 MT', '197.218.66.125', '2026-02-26 20:08:39'),
(240, 17, 'login', 'user', 17, 'Login de Ibrahimo Ibrahimo', '197.218.77.95', '2026-02-27 03:08:25'),
(241, 9, 'login', 'user', 9, 'Login de Mauro Ribeiro', '197.218.66.37', '2026-02-27 17:41:06'),
(242, 9, 'logout', 'user', 9, 'Logout', '197.218.66.37', '2026-02-27 18:27:45'),
(243, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.66.37', '2026-02-27 18:27:49'),
(244, 1, 'loan_repaid', 'loan', 35, 'Liquidação Total: 5.750,00 MT', '197.218.66.37', '2026-02-27 18:28:52'),
(245, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.66.37', '2026-02-27 19:40:50'),
(246, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.66.37', '2026-02-27 23:01:21'),
(247, 1, 'logout', 'user', 1, 'Logout', '197.218.66.37', '2026-02-27 23:16:07'),
(248, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.66.37', '2026-02-27 23:18:07'),
(249, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.66.37', '2026-02-27 23:48:57'),
(250, 9, 'login', 'user', 9, 'Login de Mauro Ribeiro', '197.218.66.37', '2026-02-27 23:53:38'),
(251, 1, 'logout', 'user', 1, 'Logout', '197.218.66.37', '2026-02-27 23:54:37'),
(252, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.66.37', '2026-02-27 23:59:08'),
(253, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.66.37', '2026-02-27 23:59:39'),
(254, 1, 'logout', 'user', 1, 'Logout', '197.218.66.37', '2026-02-27 23:59:56'),
(255, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.66.37', '2026-02-28 00:01:46'),
(256, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.70.35', '2026-02-28 09:32:01'),
(257, 1, 'logout', 'user', 1, 'Logout', '197.218.70.35', '2026-02-28 09:35:46'),
(258, 9, 'login', 'user', 9, 'Login de Mauro Ribeiro', '197.218.70.35', '2026-02-28 09:36:17'),
(259, 9, 'login', 'user', 9, 'Login de Mauro Ribeiro', '197.218.66.90', '2026-02-28 13:37:55'),
(260, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.66.90', '2026-02-28 17:30:38'),
(261, 1, 'loan_created', 'loan', 49, 'Empréstimo de 2.000,00 MT para Ibrahimo Ibrahimo', '197.218.66.90', '2026-02-28 17:32:10'),
(262, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.200.212', '2026-03-01 09:43:53'),
(263, 15, 'login', 'user', 15, 'Login de Messias Mouzinho', '197.218.76.56', '2026-03-01 09:51:10'),
(264, 15, 'password_changed', 'member', 14, 'Membro alterou a sua palavra-passe', '197.218.76.56', '2026-03-01 09:52:14'),
(265, 9, 'login', 'user', 9, 'Login de Mauro Ribeiro', '41.220.200.58', '2026-03-01 12:53:02'),
(266, 12, 'login', 'user', 12, 'Login de Clementino Hale', '197.218.75.39', '2026-03-01 12:58:12'),
(267, 17, 'login', 'user', 17, 'Login de Ibrahimo Ibrahimo', '197.218.67.87', '2026-03-01 18:10:50'),
(268, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.72.36', '2026-03-01 19:07:19'),
(269, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.200.153', '2026-03-01 20:28:05'),
(270, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.200.225', '2026-03-02 20:44:54'),
(271, 1, 'loan_updated', 'loan', 49, 'Empréstimo actualizado: 2.500,00 MT', '41.220.200.225', '2026-03-02 20:46:53'),
(272, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.75.111', '2026-03-03 18:31:53'),
(273, 1, 'contribution_created', 'contribution', 33, 'Contribuição de Elisio Chaima: 2.000,00 MT', '197.218.75.111', '2026-03-03 18:32:43'),
(274, 1, 'loan_repaid', 'loan', 38, 'Liquidação Total: 7.150,00 MT', '197.218.75.111', '2026-03-03 18:33:42'),
(275, 1, 'loan_repaid', 'loan', 42, 'Liquidação Total: 1.725,00 MT', '197.218.75.111', '2026-03-03 18:34:28'),
(276, 1, 'loan_repaid', 'loan', 41, 'Liquidação Total: 4.025,00 MT', '197.218.75.111', '2026-03-03 18:34:48'),
(277, 1, 'loan_created', 'loan', 50, 'Empréstimo de 5.000,00 MT para Valdemar Colimão', '197.218.75.111', '2026-03-03 18:35:25'),
(278, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.21', '2026-03-04 08:02:50'),
(279, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.21', '2026-03-04 08:08:21'),
(280, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.21', '2026-03-04 09:23:13'),
(281, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.21', '2026-03-04 09:23:56'),
(282, 1, 'loan_created', 'loan', 51, 'Empréstimo de 9.000,00 MT para Elisio Chaima', '41.220.201.21', '2026-03-04 10:06:42'),
(283, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.21', '2026-03-04 15:18:56'),
(284, 1, 'loan_created', 'loan', 52, 'Empréstimo de 1.000,00 MT para Clementino Hale', '41.220.201.21', '2026-03-04 15:19:28'),
(285, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.21', '2026-03-04 15:54:49'),
(286, 1, 'logout', 'user', 1, 'Logout', '41.220.201.21', '2026-03-04 15:55:41'),
(287, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.244', '2026-03-04 17:49:26'),
(288, 1, 'loan_repaid', 'loan', 37, 'Pagamento Apenas Juros: 300,00 MT. Capital 2.000,00 MT → novo empréstimo #53', '41.220.201.244', '2026-03-04 17:52:14'),
(289, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.59', '2026-03-05 19:01:06'),
(290, 1, 'loan_repaid', 'loan', 32, 'Pagamento Apenas Juros: 600,00 MT. Capital 4.000,00 MT → novo empréstimo #54', '41.220.201.59', '2026-03-05 19:04:03'),
(291, 1, 'loan_repaid', 'loan', 29, 'Pagamento Apenas Juros: 150,00 MT. Capital 1.000,00 MT → novo empréstimo #55', '41.220.201.59', '2026-03-05 19:05:11'),
(292, 1, 'loan_repaid', 'loan', 30, 'Pagamento Apenas Juros: 225,00 MT. Capital 1.500,00 MT → novo empréstimo #56', '41.220.201.59', '2026-03-05 19:06:23'),
(293, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.200.78', '2026-03-07 14:51:49'),
(294, 1, 'logout', 'user', 1, 'Logout', '41.220.200.78', '2026-03-07 14:53:22'),
(295, 9, 'login', 'user', 9, 'Login de Mauro Ribeiro', '41.220.200.78', '2026-03-07 14:53:52'),
(296, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.200.17', '2026-03-08 13:50:30'),
(297, 1, 'loan_created', 'loan', 57, 'Empréstimo de 500,00 MT para Ibrahimo Ibrahimo', '41.220.200.17', '2026-03-08 13:51:09'),
(298, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.200.159', '2026-03-09 14:07:35'),
(299, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.184', '2026-03-10 16:36:11'),
(300, 1, 'logout', 'user', 1, 'Logout', '41.220.201.184', '2026-03-10 16:37:46'),
(301, 9, 'login', 'user', 9, 'Login de Mauro Ribeiro', '41.220.201.184', '2026-03-10 16:38:10'),
(302, 9, 'logout', 'user', 9, 'Logout', '41.220.201.184', '2026-03-10 16:39:52'),
(303, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.184', '2026-03-10 16:39:58'),
(304, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.75', '2026-03-12 18:19:50'),
(305, 1, 'contribution_created', 'contribution', 34, 'Contribuição de Leonel Jassitene: 2.000,00 MT', '41.220.201.75', '2026-03-12 18:22:50'),
(306, 1, 'contribution_created', 'contribution', 35, 'Contribuição de Valdemar Colimão: 2.000,00 MT', '41.220.201.75', '2026-03-12 18:23:46'),
(307, 1, 'loan_created', 'loan', 58, 'Empréstimo de 1.000,00 MT para Valdemar Colimão', '41.220.201.75', '2026-03-12 18:25:38'),
(308, 1, 'loan_created', 'loan', 59, 'Empréstimo de 2.500,00 MT para Valdemar Colimão', '41.220.201.75', '2026-03-12 18:26:13'),
(309, 1, 'loan_repaid', 'loan', 26, 'Liquidação Total: 3.900,00 MT', '41.220.201.75', '2026-03-12 18:27:24'),
(310, 1, 'loan_created', 'loan', 60, 'Empréstimo de 5.400,00 MT para Leonel Jassitene', '41.220.201.75', '2026-03-12 18:28:32'),
(311, 1, 'loan_created', 'loan', 61, 'Empréstimo de 1.000,00 MT para Geraldo Eliseu', '41.220.201.75', '2026-03-12 18:29:18'),
(312, 1, 'loan_created', 'loan', 62, 'Empréstimo de 1.000,00 MT para Albuquerque Rimua', '41.220.201.75', '2026-03-12 18:30:01'),
(313, 1, 'loan_repaid', 'loan', 43, 'Liquidação Total: 3.250,00 MT', '41.220.201.75', '2026-03-12 18:30:59'),
(314, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.65.48', '2026-03-14 13:23:46'),
(315, 1, 'loan_created', 'loan', 63, 'Empréstimo de 700,00 MT para Clementino Hale', '197.218.65.48', '2026-03-14 13:24:15'),
(316, 1, 'loan_created', 'loan', 64, 'Empréstimo de 700,00 MT para Clementino Hale', '197.218.65.48', '2026-03-14 13:24:16'),
(317, 1, 'loan_created', 'loan', 65, 'Empréstimo de 1.500,00 MT para Mauro Ribeiro', '197.218.65.48', '2026-03-14 13:24:32'),
(318, 1, 'loan_created', 'loan', 66, 'Empréstimo de 2.000,00 MT para Leonel Jassitene', '197.218.65.48', '2026-03-14 13:24:45'),
(319, 1, 'loan_created', 'loan', 67, 'Empréstimo de 500,00 MT para Elisio Chaima', '197.218.65.48', '2026-03-14 13:25:15'),
(320, 1, 'loan_deleted', 'loan', 63, 'Empréstimo de Clementino Hale (700,00 MT) eliminado.', '197.218.65.48', '2026-03-14 13:26:53'),
(321, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.20', '2026-03-15 10:46:26'),
(322, 1, 'loan_updated', 'loan', 64, 'Empréstimo actualizado: 1.000,00 MT', '41.220.201.20', '2026-03-15 10:47:41'),
(323, 1, 'login', 'user', 1, 'Login de Administrador', '197.218.76.86', '2026-03-15 14:03:01'),
(324, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.20', '2026-03-15 19:34:30'),
(325, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.152', '2026-03-18 19:42:11'),
(326, 1, 'loan_created', 'loan', 68, 'Empréstimo de 500,00 MT para Clementino Hale', '41.220.201.200', '2026-03-18 19:46:37'),
(327, 1, 'loan_created', 'loan', 69, 'Empréstimo de 500,00 MT para Clementino Hale', '41.220.201.200', '2026-03-18 19:46:37'),
(328, 1, 'login', 'user', 1, 'Login de Administrador', '41.220.201.194', '2026-03-19 14:36:36'),
(329, 1, 'contribution_created', 'contribution', 36, 'Contribuição de Geraldo Eliseu: 3.000,00 MT (multa: 450,00 MT)', '41.220.201.194', '2026-03-19 14:37:39'),
(330, 1, 'loan_repaid', 'loan', 39, 'Liquidação Total: 3.900,00 MT', '41.220.201.194', '2026-03-19 14:38:26'),
(331, 1, 'loan_repaid', 'loan', 28, 'Liquidação Total: 1.950,00 MT', '41.220.201.194', '2026-03-19 14:38:42');

-- --------------------------------------------------------

--
-- Estrutura da tabela `contributions`
--

CREATE TABLE `contributions` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `reference_month` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_date` date NOT NULL,
  `due_date` date NOT NULL,
  `is_late` tinyint(1) DEFAULT 0,
  `late_fee` decimal(12,2) DEFAULT 0.00,
  `payment_method` enum('cash','mpesa','bank_transfer') DEFAULT 'cash',
  `receipt_ref` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `contributions`
--

INSERT INTO `contributions` (`id`, `member_id`, `cycle_id`, `reference_month`, `amount`, `paid_date`, `due_date`, `is_late`, `late_fee`, `payment_method`, `receipt_ref`, `notes`, `created_at`) VALUES
(12, 10, 1, '2026-01-01', 3000.00, '2026-01-05', '2026-02-10', 0, 0.00, 'cash', '', '', '2026-02-22 20:48:32'),
(14, 10, 1, '2025-12-01', 3000.00, '2025-12-30', '2026-01-10', 0, 0.00, 'cash', '', '', '2026-02-22 21:00:17'),
(15, 8, 1, '2025-12-01', 2000.00, '2025-12-30', '2026-01-10', 0, 0.00, 'cash', '', '', '2026-02-22 21:05:55'),
(17, 8, 1, '2026-02-01', 2000.00, '2026-02-05', '2026-03-10', 0, 0.00, 'cash', '', '', '2026-02-22 21:07:13'),
(18, 8, 1, '2026-01-01', 2000.00, '2026-01-05', '2026-02-10', 0, 0.00, 'cash', '', '', '2026-02-22 21:09:43'),
(19, 11, 1, '2025-12-01', 2000.00, '2025-12-30', '2026-01-10', 0, 0.00, 'cash', '', '', '2026-02-22 21:10:54'),
(20, 11, 1, '2026-01-01', 2000.00, '2026-01-05', '2026-02-10', 0, 0.00, 'cash', '', '', '2026-02-22 21:11:22'),
(21, 11, 1, '2026-02-01', 2000.00, '2026-02-05', '2026-03-10', 0, 0.00, 'cash', '', '', '2026-02-22 21:11:47'),
(22, 16, 1, '2025-12-01', 2000.00, '2025-12-30', '2026-01-10', 0, 0.00, 'cash', '', '', '2026-02-22 21:13:17'),
(23, 16, 1, '2026-01-01', 2000.00, '2026-01-05', '2026-02-10', 0, 0.00, 'cash', '', '', '2026-02-22 21:13:45'),
(24, 12, 1, '2025-12-01', 2000.00, '2026-01-11', '2026-01-10', 1, 300.00, 'cash', '', '', '2026-02-22 21:14:48'),
(25, 12, 1, '2026-01-01', 2000.00, '2026-01-05', '2026-02-10', 0, 0.00, 'cash', '', '', '2026-02-22 21:15:23'),
(26, 15, 1, '2025-12-01', 2000.00, '2026-01-11', '2026-01-10', 1, 300.00, 'cash', '', '', '2026-02-22 21:16:28'),
(27, 15, 1, '2026-01-01', 2000.00, '2026-01-05', '2026-02-10', 0, 0.00, 'cash', '', '', '2026-02-22 21:17:06'),
(28, 9, 1, '2025-12-01', 2000.00, '2025-12-30', '2026-01-10', 0, 0.00, 'cash', '', '', '2026-02-22 21:18:57'),
(29, 9, 1, '2026-01-01', 3000.00, '2026-01-05', '2026-02-10', 0, 0.00, 'cash', '', '', '2026-02-22 21:19:23'),
(30, 13, 1, '2025-12-01', 2000.00, '2025-12-30', '2026-01-10', 0, 0.00, 'cash', '', '', '2026-02-22 21:20:40'),
(31, 13, 1, '2026-01-01', 2000.00, '2026-01-05', '2026-02-10', 0, 0.00, 'cash', '', '', '2026-02-22 21:21:09'),
(32, 14, 1, '2025-12-01', 2000.00, '2025-12-30', '2026-01-10', 0, 0.00, 'cash', '', '', '2026-02-22 21:22:01'),
(33, 15, 1, '2026-02-01', 2000.00, '2026-03-03', '2026-03-10', 0, 0.00, 'cash', '', '', '2026-03-03 18:32:43'),
(34, 12, 1, '2026-02-01', 2000.00, '2026-03-09', '2026-03-10', 0, 0.00, 'cash', '', '', '2026-03-12 18:22:50'),
(35, 13, 1, '2026-02-01', 2000.00, '2026-03-10', '2026-03-10', 0, 0.00, 'cash', '', '', '2026-03-12 18:23:46'),
(36, 10, 1, '2026-02-01', 3000.00, '2026-03-19', '2026-03-10', 1, 450.00, 'cash', '', '', '2026-03-19 14:37:39');

-- --------------------------------------------------------

--
-- Estrutura da tabela `cycles`
--

CREATE TABLE `cycles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `joia_amount` decimal(12,2) NOT NULL DEFAULT 1000.00,
  `joia_deadline` date NOT NULL,
  `min_monthly` decimal(12,2) NOT NULL DEFAULT 2000.00,
  `max_monthly` decimal(12,2) NOT NULL DEFAULT 5000.00,
  `monthly_deadline_day` int(11) NOT NULL DEFAULT 10,
  `late_fee_pct` decimal(5,2) NOT NULL DEFAULT 15.00,
  `loan_interest_pct` decimal(5,2) NOT NULL DEFAULT 15.00,
  `loan_repayment_days` int(11) NOT NULL DEFAULT 30,
  `min_loan_movement` decimal(12,2) NOT NULL DEFAULT 50000.00,
  `allow_multiple_loans` tinyint(1) NOT NULL DEFAULT 1,
  `loan_tolerance_margin` decimal(12,2) NOT NULL DEFAULT 0.00,
  `fixed_interest_entitlement` decimal(12,2) NOT NULL DEFAULT 7500.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `cycles`
--

INSERT INTO `cycles` (`id`, `name`, `start_date`, `end_date`, `joia_amount`, `joia_deadline`, `min_monthly`, `max_monthly`, `monthly_deadline_day`, `late_fee_pct`, `loan_interest_pct`, `loan_repayment_days`, `min_loan_movement`, `allow_multiple_loans`, `loan_tolerance_margin`, `fixed_interest_entitlement`, `is_active`, `created_at`) VALUES
(1, 'Ciclo 2025-2026', '2025-12-01', '2026-11-30', 1000.00, '2025-12-30', 2000.00, 5000.00, 10, 15.00, 15.00, 30, 50000.00, 1, 1500.00, 7500.00, 1, '2026-02-20 09:43:56');

-- --------------------------------------------------------

--
-- Estrutura da tabela `distributions`
--

CREATE TABLE `distributions` (
  `id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `type` enum('interest','late_fee','surplus') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `description` text DEFAULT NULL,
  `distributed_at` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `joias`
--

CREATE TABLE `joias` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 1000.00,
  `paid_date` date DEFAULT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `receipt_ref` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `joias`
--

INSERT INTO `joias` (`id`, `member_id`, `cycle_id`, `amount`, `paid_date`, `status`, `receipt_ref`, `notes`, `created_at`) VALUES
(8, 8, 1, 1000.00, '2026-01-01', 'paid', '', NULL, '2026-02-22 19:53:17'),
(9, 9, 1, 1000.00, '2026-01-01', 'paid', '', NULL, '2026-02-22 19:55:37'),
(10, 10, 1, 1000.00, '2026-01-01', 'paid', '', NULL, '2026-02-22 19:58:08'),
(11, 11, 1, 1000.00, '2026-01-01', 'paid', '', NULL, '2026-02-22 20:00:56'),
(12, 12, 1, 1000.00, '2026-01-01', 'paid', '', NULL, '2026-02-22 20:09:26'),
(13, 13, 1, 1000.00, '2026-01-01', 'paid', '', NULL, '2026-02-22 20:11:49'),
(14, 14, 1, 1000.00, '2026-01-01', 'paid', '', NULL, '2026-02-22 20:14:10'),
(15, 15, 1, 1000.00, '2026-01-01', 'paid', '', NULL, '2026-02-22 20:20:27'),
(16, 16, 1, 1000.00, '2026-01-01', 'paid', '', NULL, '2026-02-22 20:33:26');

-- --------------------------------------------------------

--
-- Estrutura da tabela `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `disbursement_date` date NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('active','paid','overdue','defaulted') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `loans`
--

INSERT INTO `loans` (`id`, `member_id`, `cycle_id`, `amount`, `disbursement_date`, `due_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(7, 14, 1, 1000.00, '2025-12-19', '2026-01-18', 'paid', '', '2026-02-23 09:43:01', '2026-02-23 15:22:09'),
(8, 8, 1, 4000.00, '2025-12-26', '2026-01-25', 'paid', '', '2026-02-23 15:24:19', '2026-02-23 15:26:06'),
(9, 8, 1, 1000.00, '2025-12-27', '2026-01-26', 'paid', '', '2026-02-23 15:28:04', '2026-02-23 15:29:32'),
(10, 15, 1, 1000.00, '2025-12-27', '2026-01-26', 'paid', '', '2026-02-23 15:30:43', '2026-02-23 15:34:11'),
(11, 16, 1, 1000.00, '2025-12-27', '2026-01-26', 'paid', '', '2026-02-23 15:35:04', '2026-02-23 15:35:54'),
(12, 14, 1, 1000.00, '2025-12-28', '2026-01-27', 'paid', '', '2026-02-23 15:36:48', '2026-02-23 15:37:22'),
(13, 14, 1, 1000.00, '2025-12-30', '2026-01-29', 'paid', '', '2026-02-23 15:38:13', '2026-02-23 15:38:43'),
(14, 16, 1, 3000.00, '2025-12-30', '2026-01-29', 'paid', '', '2026-02-23 15:39:32', '2026-02-23 15:39:56'),
(15, 10, 1, 1000.00, '2025-12-31', '2026-01-30', 'paid', '', '2026-02-23 15:40:46', '2026-02-23 15:41:08'),
(16, 9, 1, 3000.00, '2025-12-31', '2026-01-30', 'paid', '', '2026-02-23 15:42:03', '2026-02-23 15:42:26'),
(17, 11, 1, 1000.00, '2025-12-31', '2026-01-30', 'paid', '', '2026-02-23 15:43:08', '2026-02-23 15:43:29'),
(18, 11, 1, 1500.00, '2026-01-02', '2026-02-01', 'paid', '', '2026-02-23 15:45:03', '2026-02-23 15:46:10'),
(19, 11, 1, 500.00, '2026-01-04', '2026-02-03', 'paid', '', '2026-02-25 19:29:39', '2026-02-25 19:30:21'),
(20, 13, 1, 3500.00, '2026-01-06', '2026-02-05', 'paid', '', '2026-02-25 19:31:23', '2026-02-25 19:32:14'),
(21, 13, 1, 1500.00, '2026-01-07', '2026-02-06', 'paid', '', '2026-02-25 19:33:41', '2026-02-25 19:34:12'),
(22, 10, 1, 1500.00, '2026-01-08', '2026-02-07', 'paid', '', '2026-02-25 19:34:56', '2026-02-25 19:35:18'),
(23, 8, 1, 1000.00, '2026-01-12', '2026-02-11', 'paid', '', '2026-02-25 19:36:32', '2026-02-25 19:37:15'),
(24, 11, 1, 500.00, '2026-01-16', '2026-02-15', 'paid', '', '2026-02-25 19:38:36', '2026-02-25 19:38:53'),
(25, 10, 1, 1500.00, '2026-01-17', '2026-02-16', 'paid', '', '2026-02-25 19:39:50', '2026-02-25 19:40:16'),
(26, 12, 1, 3000.00, '2026-01-17', '2026-02-16', 'paid', '', '2026-02-25 19:41:04', '2026-03-12 18:27:24'),
(27, 16, 1, 1000.00, '2026-01-25', '2026-02-24', 'paid', '', '2026-02-25 19:42:04', '2026-02-26 19:06:45'),
(28, 10, 1, 1500.00, '2026-01-26', '2026-02-25', 'paid', '', '2026-02-25 19:43:02', '2026-03-19 14:38:42'),
(29, 9, 1, 1000.00, '2026-01-26', '2026-02-25', 'paid', '', '2026-02-25 19:45:02', '2026-03-05 19:05:11'),
(30, 9, 1, 1500.00, '2026-01-26', '2026-02-25', 'paid', '', '2026-02-25 19:46:01', '2026-03-05 19:06:23'),
(31, 14, 1, 3000.00, '2026-01-27', '2026-02-26', 'paid', '', '2026-02-25 19:46:56', '2026-02-26 19:09:47'),
(32, 9, 1, 4000.00, '2026-01-28', '2026-02-27', 'paid', '', '2026-02-25 19:47:39', '2026-03-05 19:04:03'),
(33, 16, 1, 500.00, '2026-01-28', '2026-02-27', 'paid', '', '2026-02-25 19:48:13', '2026-02-26 20:08:39'),
(34, 14, 1, 600.00, '2026-01-28', '2026-02-27', 'paid', '', '2026-02-25 19:49:04', '2026-02-26 19:09:09'),
(35, 11, 1, 5000.00, '2026-01-29', '2026-02-28', 'paid', '', '2026-02-25 19:49:59', '2026-02-27 18:28:52'),
(36, 8, 1, 4000.00, '2026-01-26', '2026-02-25', 'paid', '', '2026-02-25 19:51:21', '2026-02-26 19:07:30'),
(37, 8, 1, 2000.00, '2026-01-29', '2026-02-28', 'paid', '', '2026-02-25 19:52:00', '2026-03-04 17:52:14'),
(38, 15, 1, 5500.00, '2026-01-30', '2026-03-01', 'paid', '', '2026-02-25 19:54:45', '2026-03-03 18:33:42'),
(39, 10, 1, 3000.00, '2026-01-30', '2026-03-01', 'paid', '', '2026-02-25 19:55:51', '2026-03-19 14:38:26'),
(40, 16, 1, 2000.00, '2026-01-30', '2026-03-01', 'paid', '', '2026-02-25 19:56:41', '2026-02-26 20:07:40'),
(41, 13, 1, 3500.00, '2026-02-06', '2026-03-08', 'paid', '', '2026-02-25 19:57:57', '2026-03-03 18:34:48'),
(42, 13, 1, 1500.00, '2026-02-07', '2026-03-09', 'paid', '', '2026-02-25 19:58:39', '2026-03-03 18:34:28'),
(43, 8, 1, 2500.00, '2026-02-07', '2026-03-09', 'paid', '', '2026-02-25 19:59:21', '2026-03-12 18:30:59'),
(44, 16, 1, 1100.00, '2026-02-07', '2026-03-09', 'overdue', '', '2026-02-25 20:00:00', '2026-03-10 16:36:13'),
(45, 16, 1, 500.00, '2026-02-12', '2026-03-14', 'overdue', '', '2026-02-25 20:00:45', '2026-03-15 10:46:29'),
(46, 8, 1, 4000.00, '2026-02-25', '2026-03-27', 'active', 'Renovação automática do empréstimo #36 por pagamento apenas de juros.', '2026-02-26 19:07:30', '2026-02-26 19:07:30'),
(47, 14, 1, 600.00, '2026-02-27', '2026-03-29', 'active', 'Renovação automática do empréstimo #34 por pagamento apenas de juros.', '2026-02-26 19:09:09', '2026-02-26 19:09:09'),
(48, 14, 1, 3000.00, '2026-02-26', '2026-03-28', 'active', 'Renovação automática do empréstimo #31 por pagamento apenas de juros.', '2026-02-26 19:09:47', '2026-02-26 19:09:47'),
(49, 16, 1, 2500.00, '2026-02-28', '2026-03-30', 'active', '', '2026-02-28 17:32:10', '2026-03-02 20:46:53'),
(50, 13, 1, 5000.00, '2026-03-03', '2026-04-02', 'active', '', '2026-03-03 18:35:25', '2026-03-03 18:35:25'),
(51, 15, 1, 9000.00, '2026-03-04', '2026-04-03', 'active', '', '2026-03-04 10:06:42', '2026-03-04 10:06:42'),
(52, 11, 1, 1000.00, '2026-03-04', '2026-04-03', 'active', '', '2026-03-04 15:19:28', '2026-03-04 15:19:28'),
(53, 8, 1, 2000.00, '2026-02-28', '2026-03-30', 'active', 'Renovação automática do empréstimo #37 por pagamento apenas de juros.', '2026-03-04 17:52:14', '2026-03-04 17:52:14'),
(54, 9, 1, 4000.00, '2026-02-27', '2026-03-29', 'active', 'Renovação automática do empréstimo #32 por pagamento apenas de juros.', '2026-03-05 19:04:03', '2026-03-05 19:04:03'),
(55, 9, 1, 1000.00, '2026-02-25', '2026-03-27', 'active', 'Renovação automática do empréstimo #29 por pagamento apenas de juros.', '2026-03-05 19:05:11', '2026-03-05 19:05:11'),
(56, 9, 1, 1500.00, '2026-02-25', '2026-03-27', 'active', 'Renovação automática do empréstimo #30 por pagamento apenas de juros.', '2026-03-05 19:06:23', '2026-03-05 19:06:23'),
(57, 16, 1, 500.00, '2026-03-08', '2026-04-07', 'active', '', '2026-03-08 13:51:09', '2026-03-08 13:51:09'),
(58, 13, 1, 1000.00, '2026-03-10', '2026-04-09', 'active', 'Mauro em Nome do Colimao', '2026-03-12 18:25:38', '2026-03-12 18:25:38'),
(59, 13, 1, 2500.00, '2026-03-10', '2026-04-09', 'active', '', '2026-03-12 18:26:13', '2026-03-12 18:26:13'),
(60, 12, 1, 5400.00, '2026-03-09', '2026-04-08', 'active', '', '2026-03-12 18:28:32', '2026-03-12 18:28:32'),
(61, 10, 1, 1000.00, '2026-03-09', '2026-04-08', 'active', 'Messias Levou em nome de Geraldo', '2026-03-12 18:29:18', '2026-03-12 18:29:18'),
(62, 9, 1, 1000.00, '2026-03-10', '2026-04-09', 'active', '', '2026-03-12 18:30:01', '2026-03-12 18:30:01'),
(64, 11, 1, 1000.00, '2026-03-14', '2026-04-13', 'active', '', '2026-03-14 13:24:16', '2026-03-15 10:47:41'),
(65, 8, 1, 1500.00, '2026-03-14', '2026-04-13', 'active', '', '2026-03-14 13:24:32', '2026-03-14 13:24:32'),
(66, 12, 1, 2000.00, '2026-03-14', '2026-04-13', 'active', '', '2026-03-14 13:24:45', '2026-03-14 13:24:45'),
(67, 15, 1, 500.00, '2026-03-14', '2026-04-13', 'active', '', '2026-03-14 13:25:15', '2026-03-14 13:25:15'),
(68, 11, 1, 500.00, '2026-03-17', '2026-04-16', 'active', '', '2026-03-18 19:46:36', '2026-03-18 19:46:36'),
(69, 11, 1, 500.00, '2026-03-17', '2026-04-16', 'active', '', '2026-03-18 19:46:37', '2026-03-18 19:46:37');

-- --------------------------------------------------------

--
-- Estrutura da tabela `loan_interest`
--

CREATE TABLE `loan_interest` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `reference_month` date NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL DEFAULT 15.00,
  `interest_amount` decimal(12,2) NOT NULL,
  `paid_date` date DEFAULT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `loan_interest`
--

INSERT INTO `loan_interest` (`id`, `loan_id`, `member_id`, `cycle_id`, `reference_month`, `interest_rate`, `interest_amount`, `paid_date`, `status`, `notes`, `created_at`) VALUES
(7, 7, 14, 1, '2025-12-01', 15.00, 150.00, '2026-01-19', 'paid', NULL, '2026-02-23 09:43:01'),
(8, 7, 14, 1, '2026-01-01', 30.00, 150.00, '2026-01-19', 'paid', NULL, '2026-02-23 15:22:09'),
(9, 8, 8, 1, '2025-12-01', 15.00, 600.00, '2026-01-25', 'paid', NULL, '2026-02-23 15:24:19'),
(10, 9, 8, 1, '2025-12-01', 15.00, 150.00, '2026-01-26', 'paid', NULL, '2026-02-23 15:28:04'),
(11, 10, 15, 1, '2025-12-01', 15.00, 150.00, '2026-01-27', 'paid', NULL, '2026-02-23 15:30:43'),
(12, 10, 15, 1, '2026-01-01', 30.00, 150.00, '2026-01-27', 'paid', NULL, '2026-02-23 15:34:11'),
(13, 11, 16, 1, '2025-12-01', 15.00, 150.00, '2026-01-26', 'paid', NULL, '2026-02-23 15:35:04'),
(14, 12, 14, 1, '2025-12-01', 15.00, 150.00, '2025-12-27', 'paid', NULL, '2026-02-23 15:36:48'),
(15, 13, 14, 1, '2025-12-01', 15.00, 150.00, '2026-01-29', 'paid', NULL, '2026-02-23 15:38:13'),
(16, 14, 16, 1, '2025-12-01', 15.00, 450.00, '2026-01-29', 'paid', NULL, '2026-02-23 15:39:32'),
(17, 15, 10, 1, '2025-12-01', 15.00, 150.00, '2026-01-30', 'paid', NULL, '2026-02-23 15:40:46'),
(18, 16, 9, 1, '2025-12-01', 15.00, 450.00, '2026-01-30', 'paid', NULL, '2026-02-23 15:42:03'),
(19, 17, 11, 1, '2025-12-01', 15.00, 150.00, '2026-01-30', 'paid', NULL, '2026-02-23 15:43:08'),
(20, 18, 11, 1, '2026-01-01', 15.00, 225.00, '2026-02-01', 'paid', NULL, '2026-02-23 15:45:03'),
(21, 19, 11, 1, '2026-01-01', 15.00, 75.00, '2026-02-03', 'paid', NULL, '2026-02-25 19:29:39'),
(22, 20, 13, 1, '2026-01-01', 15.00, 525.00, '2026-02-05', 'paid', NULL, '2026-02-25 19:31:23'),
(23, 21, 13, 1, '2026-01-01', 15.00, 225.00, '2026-02-06', 'paid', NULL, '2026-02-25 19:33:41'),
(24, 22, 10, 1, '2026-01-01', 15.00, 225.00, '2026-02-07', 'paid', NULL, '2026-02-25 19:34:56'),
(25, 23, 8, 1, '2026-01-01', 15.00, 150.00, '2026-02-11', 'paid', NULL, '2026-02-25 19:36:32'),
(26, 24, 11, 1, '2026-01-01', 15.00, 75.00, '2026-02-15', 'paid', NULL, '2026-02-25 19:38:36'),
(27, 25, 10, 1, '2026-01-01', 15.00, 225.00, '2026-02-16', 'paid', NULL, '2026-02-25 19:39:50'),
(28, 26, 12, 1, '2026-01-01', 15.00, 450.00, '2026-03-09', 'paid', NULL, '2026-02-25 19:41:04'),
(29, 27, 16, 1, '2026-01-01', 15.00, 150.00, '2026-02-26', 'paid', NULL, '2026-02-25 19:42:04'),
(30, 28, 10, 1, '2026-01-01', 15.00, 225.00, '2026-03-19', 'paid', NULL, '2026-02-25 19:43:02'),
(31, 29, 9, 1, '2026-01-01', 15.00, 150.00, '2026-02-25', 'paid', NULL, '2026-02-25 19:45:02'),
(32, 30, 9, 1, '2026-01-01', 15.00, 225.00, '2026-02-25', 'paid', NULL, '2026-02-25 19:46:01'),
(33, 31, 14, 1, '2026-01-01', 15.00, 450.00, '2026-02-26', 'paid', NULL, '2026-02-25 19:46:56'),
(34, 32, 9, 1, '2026-01-01', 15.00, 600.00, '2026-02-26', 'paid', NULL, '2026-02-25 19:47:39'),
(35, 33, 16, 1, '2026-01-01', 15.00, 75.00, '2026-02-26', 'paid', NULL, '2026-02-25 19:48:13'),
(36, 34, 14, 1, '2026-01-01', 15.00, 90.00, '2026-02-26', 'paid', NULL, '2026-02-25 19:49:04'),
(37, 35, 11, 1, '2026-01-01', 15.00, 750.00, '2026-02-27', 'paid', NULL, '2026-02-25 19:49:59'),
(38, 36, 8, 1, '2026-01-01', 15.00, 600.00, '2026-02-25', 'paid', NULL, '2026-02-25 19:51:21'),
(39, 37, 8, 1, '2026-01-01', 15.00, 300.00, '2026-02-28', 'paid', NULL, '2026-02-25 19:52:00'),
(40, 38, 15, 1, '2026-01-01', 15.00, 825.00, '2026-03-03', 'paid', NULL, '2026-02-25 19:54:45'),
(41, 39, 10, 1, '2026-01-01', 15.00, 450.00, '2026-03-19', 'paid', NULL, '2026-02-25 19:55:51'),
(42, 40, 16, 1, '2026-01-01', 15.00, 300.00, '2026-02-26', 'paid', NULL, '2026-02-25 19:56:41'),
(43, 41, 13, 1, '2026-02-01', 15.00, 525.00, '2026-03-03', 'paid', NULL, '2026-02-25 19:57:57'),
(44, 42, 13, 1, '2026-02-01', 15.00, 225.00, '2026-03-03', 'paid', NULL, '2026-02-25 19:58:39'),
(45, 43, 8, 1, '2026-02-01', 15.00, 375.00, '2026-03-12', 'paid', NULL, '2026-02-25 19:59:21'),
(46, 44, 16, 1, '2026-02-01', 15.00, 165.00, NULL, 'pending', NULL, '2026-02-25 20:00:00'),
(47, 45, 16, 1, '2026-02-01', 15.00, 75.00, NULL, 'pending', NULL, '2026-02-25 20:00:45'),
(48, 27, 16, 1, '2026-02-01', 30.00, 150.00, '2026-02-26', 'paid', NULL, '2026-02-26 19:06:45'),
(49, 46, 8, 1, '2026-02-01', 15.00, 600.00, NULL, 'pending', NULL, '2026-02-26 19:07:30'),
(50, 47, 14, 1, '2026-02-01', 15.00, 90.00, NULL, 'pending', NULL, '2026-02-26 19:09:09'),
(51, 48, 14, 1, '2026-02-01', 15.00, 450.00, NULL, 'pending', NULL, '2026-02-26 19:09:47'),
(52, 49, 16, 1, '2026-02-01', 15.00, 375.00, NULL, 'pending', NULL, '2026-02-28 17:32:10'),
(53, 38, 15, 1, '2026-03-01', 30.00, 825.00, '2026-03-03', 'paid', NULL, '2026-03-03 18:33:42'),
(54, 50, 13, 1, '2026-03-01', 15.00, 750.00, NULL, 'pending', NULL, '2026-03-03 18:35:25'),
(55, 51, 15, 1, '2026-03-01', 15.00, 1350.00, NULL, 'pending', NULL, '2026-03-04 10:06:42'),
(56, 52, 11, 1, '2026-03-01', 15.00, 150.00, NULL, 'pending', NULL, '2026-03-04 15:19:28'),
(57, 53, 8, 1, '2026-02-01', 15.00, 300.00, NULL, 'pending', NULL, '2026-03-04 17:52:14'),
(58, 54, 9, 1, '2026-02-01', 15.00, 600.00, NULL, 'pending', NULL, '2026-03-05 19:04:03'),
(59, 55, 9, 1, '2026-02-01', 15.00, 150.00, NULL, 'pending', NULL, '2026-03-05 19:05:11'),
(60, 56, 9, 1, '2026-02-01', 15.00, 225.00, NULL, 'pending', NULL, '2026-03-05 19:06:23'),
(61, 57, 16, 1, '2026-03-01', 15.00, 75.00, NULL, 'pending', NULL, '2026-03-08 13:51:09'),
(62, 58, 13, 1, '2026-03-01', 15.00, 150.00, NULL, 'pending', NULL, '2026-03-12 18:25:38'),
(63, 59, 13, 1, '2026-03-01', 15.00, 375.00, NULL, 'pending', NULL, '2026-03-12 18:26:13'),
(64, 26, 12, 1, '2026-03-01', 30.00, 450.00, '2026-03-09', 'paid', NULL, '2026-03-12 18:27:24'),
(65, 60, 12, 1, '2026-03-01', 15.00, 810.00, NULL, 'pending', NULL, '2026-03-12 18:28:32'),
(66, 61, 10, 1, '2026-03-01', 15.00, 150.00, NULL, 'pending', NULL, '2026-03-12 18:29:18'),
(67, 62, 9, 1, '2026-03-01', 15.00, 150.00, NULL, 'pending', NULL, '2026-03-12 18:30:01'),
(68, 43, 8, 1, '2026-03-01', 30.00, 375.00, '2026-03-12', 'paid', NULL, '2026-03-12 18:30:59'),
(70, 64, 11, 1, '2026-03-01', 15.00, 150.00, NULL, 'pending', NULL, '2026-03-14 13:24:16'),
(71, 65, 8, 1, '2026-03-01', 15.00, 225.00, NULL, 'pending', NULL, '2026-03-14 13:24:32'),
(72, 66, 12, 1, '2026-03-01', 15.00, 300.00, NULL, 'pending', NULL, '2026-03-14 13:24:45'),
(73, 67, 15, 1, '2026-03-01', 15.00, 75.00, NULL, 'pending', NULL, '2026-03-14 13:25:15'),
(74, 68, 11, 1, '2026-03-01', 15.00, 75.00, NULL, 'pending', NULL, '2026-03-18 19:46:37'),
(75, 69, 11, 1, '2026-03-01', 15.00, 75.00, NULL, 'pending', NULL, '2026-03-18 19:46:37'),
(76, 39, 10, 1, '2026-03-01', 30.00, 450.00, '2026-03-19', 'paid', NULL, '2026-03-19 14:38:26'),
(77, 28, 10, 1, '2026-03-01', 30.00, 225.00, '2026-03-19', 'paid', NULL, '2026-03-19 14:38:42');

-- --------------------------------------------------------

--
-- Estrutura da tabela `loan_repayments`
--

CREATE TABLE `loan_repayments` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_date` date NOT NULL,
  `payment_method` enum('cash','mpesa','bank_transfer') DEFAULT 'cash',
  `receipt_ref` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `loan_repayments`
--

INSERT INTO `loan_repayments` (`id`, `loan_id`, `member_id`, `amount`, `paid_date`, `payment_method`, `receipt_ref`, `notes`, `created_at`) VALUES
(3, 7, 14, 1000.00, '2026-01-19', 'cash', '', NULL, '2026-02-23 15:22:09'),
(4, 8, 8, 4000.00, '2026-01-25', 'cash', '', NULL, '2026-02-23 15:26:06'),
(5, 9, 8, 1000.00, '2026-01-26', 'cash', '', NULL, '2026-02-23 15:29:32'),
(6, 10, 15, 1000.00, '2026-01-27', 'cash', '', NULL, '2026-02-23 15:34:11'),
(7, 11, 16, 1000.00, '2026-01-26', 'cash', '', NULL, '2026-02-23 15:35:54'),
(8, 12, 14, 1000.00, '2025-12-27', 'cash', '', NULL, '2026-02-23 15:37:22'),
(9, 13, 14, 1000.00, '2026-01-29', 'cash', '', NULL, '2026-02-23 15:38:43'),
(10, 14, 16, 3000.00, '2026-01-29', 'cash', '', NULL, '2026-02-23 15:39:56'),
(11, 15, 10, 1000.00, '2026-01-30', 'cash', '', NULL, '2026-02-23 15:41:08'),
(12, 16, 9, 3000.00, '2026-01-30', 'cash', '', NULL, '2026-02-23 15:42:26'),
(13, 17, 11, 1000.00, '2026-01-30', 'cash', '', NULL, '2026-02-23 15:43:29'),
(14, 18, 11, 1500.00, '2026-02-01', 'cash', '', NULL, '2026-02-23 15:46:10'),
(15, 19, 11, 500.00, '2026-02-03', 'cash', '', NULL, '2026-02-25 19:30:21'),
(16, 20, 13, 3500.00, '2026-02-05', 'cash', '', NULL, '2026-02-25 19:32:14'),
(17, 21, 13, 1500.00, '2026-02-06', 'cash', '', NULL, '2026-02-25 19:34:12'),
(18, 22, 10, 1500.00, '2026-02-07', 'cash', '', NULL, '2026-02-25 19:35:18'),
(19, 23, 8, 1000.00, '2026-02-11', 'cash', '', NULL, '2026-02-25 19:37:15'),
(20, 24, 11, 500.00, '2026-02-15', 'cash', '', NULL, '2026-02-25 19:38:53'),
(21, 25, 10, 1500.00, '2026-02-16', 'cash', '', NULL, '2026-02-25 19:40:16'),
(22, 27, 16, 1000.00, '2026-02-26', 'cash', '', NULL, '2026-02-26 19:06:45'),
(23, 40, 16, 2000.00, '2026-02-26', 'cash', '', NULL, '2026-02-26 20:07:40'),
(24, 33, 16, 500.00, '2026-02-26', 'cash', '', NULL, '2026-02-26 20:08:39'),
(25, 35, 11, 5000.00, '2026-02-27', 'cash', '', NULL, '2026-02-27 18:28:52'),
(26, 38, 15, 5500.00, '2026-03-03', 'cash', '', NULL, '2026-03-03 18:33:42'),
(27, 42, 13, 1500.00, '2026-03-03', 'cash', '', NULL, '2026-03-03 18:34:28'),
(28, 41, 13, 3500.00, '2026-03-03', 'cash', '', NULL, '2026-03-03 18:34:48'),
(29, 26, 12, 3000.00, '2026-03-09', 'cash', '', NULL, '2026-03-12 18:27:24'),
(30, 43, 8, 2500.00, '2026-03-12', 'cash', '', NULL, '2026-03-12 18:30:59'),
(31, 39, 10, 3000.00, '2026-03-19', 'cash', '', NULL, '2026-03-19 14:38:26'),
(32, 28, 10, 1500.00, '2026-03-19', 'cash', '', NULL, '2026-03-19 14:38:42');

-- --------------------------------------------------------

--
-- Estrutura da tabela `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `address` varchar(300) DEFAULT NULL,
  `join_date` date NOT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `members`
--

INSERT INTO `members` (`id`, `full_name`, `phone`, `email`, `id_number`, `address`, `join_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(8, 'Mauro Ribeiro', '+258864123111', 'solodinhom@gmail.com', '', 'Bairro Matundo, U.C. Alberto Vaquina', '2026-01-01', 'active', '', '2026-02-22 19:53:17', '2026-02-22 19:53:17'),
(9, 'Albuquerque Rimua', '845380460', 'aleorimua@gmail.com', '', 'Tete', '2026-01-01', 'active', '', '2026-02-22 19:55:37', '2026-02-22 22:06:54'),
(10, 'Geraldo Eliseu', '+258840580341', 'geraldo@gmail.com', '', 'Bairro Matundo, U.C. Alberto Vaquina', '2026-01-01', 'active', '', '2026-02-22 19:58:08', '2026-02-22 19:58:08'),
(11, 'Clementino Hale', '+258849159202', 'hale@gmail.com', '', 'Bairro Matundo', '2026-01-01', 'active', '', '2026-02-22 20:00:56', '2026-02-22 20:00:56'),
(12, 'Leonel Jassitene', '+258844522479', 'leonel@gmail.com', '', 'Bairro Matundo, U.C. Alberto Vaquina', '2026-01-01', 'active', '', '2026-02-22 20:09:26', '2026-02-22 20:09:26'),
(13, 'Valdemar Colimão', '+258879352875', 'colimao@gmail.com', '', 'Bairro Chingodzi', '2026-01-01', 'active', '', '2026-02-22 20:11:49', '2026-02-22 20:11:49'),
(14, 'Messias Mouzinho', '+258842835879', 'messias@gmail.com', '', 'Bairro Matundo', '2026-01-01', 'active', '', '2026-02-22 20:14:10', '2026-02-22 20:14:10'),
(15, 'Elisio Chaima', '+258853365545', 'elisio@gmail.com', '', 'Bairro Matundo', '2026-01-01', 'active', '', '2026-02-22 20:20:27', '2026-02-22 20:20:27'),
(16, 'Ibrahimo Ibrahimo', '+258844105002', 'ibrahimo@gmail.com', '', 'Bairro Matundo, U.C. Alberto Vaquina', '2026-01-01', 'active', '', '2026-02-22 20:33:26', '2026-02-22 20:33:26');

-- --------------------------------------------------------

--
-- Estrutura da tabela `member_cycles`
--

CREATE TABLE `member_cycles` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `enrolled_at` date NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `member_cycles`
--

INSERT INTO `member_cycles` (`id`, `member_id`, `cycle_id`, `enrolled_at`, `status`, `created_at`) VALUES
(8, 8, 1, '2026-01-01', 'active', '2026-02-22 19:53:17'),
(9, 9, 1, '2026-01-01', 'active', '2026-02-22 19:55:37'),
(10, 10, 1, '2026-01-01', 'active', '2026-02-22 19:58:08'),
(11, 11, 1, '2026-01-01', 'active', '2026-02-22 20:00:56'),
(12, 12, 1, '2026-01-01', 'active', '2026-02-22 20:09:26'),
(13, 13, 1, '2026-01-01', 'active', '2026-02-22 20:11:49'),
(14, 14, 1, '2026-01-01', 'active', '2026-02-22 20:14:10'),
(15, 15, 1, '2026-01-01', 'active', '2026-02-22 20:20:27'),
(16, 16, 1, '2026-01-01', 'active', '2026-02-22 20:33:26');

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `role` enum('admin','user','member') DEFAULT 'user',
  `member_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `member_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$KQVduKo3NZRgpS6fujYryeOZT9sUJthmfPNPjV8G8VqdwsZYSUW/2', 'Administrador', 'admin@asca.co.mz', 'admin', NULL, 1, '2026-02-20 09:43:56', '2026-02-20 09:43:56'),
(9, 'mauro.ribeiro', '$2y$10$0TtIyvPZrTfsq1uu.lIEIe5ANxj6eRKAbmUKlUQLgMzogpER40N5K', 'Mauro Ribeiro', 'solodinhom@gmail.com', 'member', 8, 1, '2026-02-22 19:53:18', '2026-02-22 19:53:18'),
(10, 'alburquerque.rimua', '$2y$10$qMQia4TXCDpWN51FshHbyewyx.6TvSuh.3B553IUyYQyEY/szSU8i', 'Albuquerque Rimua', 'aleorimua@gmail.com', 'member', 9, 1, '2026-02-22 19:55:37', '2026-02-22 22:06:54'),
(11, 'geraldo.eliseu', '$2y$10$pk6Bmay6IiELNAvwmuSu0eXqEhH2iVOkQL/tvWqkKiKVoMwm87YLC', 'Geraldo Eliseu', 'geraldo@gmail.com', 'member', 10, 1, '2026-02-22 19:58:09', '2026-02-23 05:32:54'),
(12, 'clementino.hale', '$2y$10$yHeuWqatnKoBiSYNKHwZu.jHqkqppSHZf5Wc5Nzm5XmAi3nqnSdGa', 'Clementino Hale', 'hale@gmail.com', 'member', 11, 1, '2026-02-22 20:00:56', '2026-02-22 20:00:56'),
(13, 'leonel.jassitene', '$2y$10$TNEsGtQCE07uiAGiDEwbKepC2fZogXgqW9o91b4ptKyChsRZlsH1u', 'Leonel Jassitene', 'leonel@gmail.com', 'member', 12, 1, '2026-02-22 20:09:26', '2026-02-22 20:09:26'),
(14, 'valdemar.colimo', '$2y$10$3g8pJllmgWqB9gR8DX6sceQ.vVUKt70YxS0qZp3iZiajY2IY09f8K', 'Valdemar Colimão', 'colimao@gmail.com', 'member', 13, 1, '2026-02-22 20:11:49', '2026-02-22 20:11:49'),
(15, 'messias.mouzinho', '$2y$10$1ApJhCqe8qDJXJ.HqboTDudYrFB0yDeuUq86HAYSkiYBJbKFs5sDm', 'Messias Mouzinho', 'messias@gmail.com', 'member', 14, 1, '2026-02-22 20:14:10', '2026-03-01 09:52:14'),
(16, 'elisio.chaima', '$2y$10$UPg/kA6pKRSXo8PMSe1ThumKeCxshOBE.yvMzD.JDssUcmfDUP2mC', 'Elisio Chaima', 'elisio@gmail.com', 'member', 15, 1, '2026-02-22 20:20:28', '2026-02-22 20:20:28'),
(17, 'ibrahimo.ibrahimo', '$2y$10$w6LFsJCzTJP01nIrmDhhq.zppUULjox/3mQar7PztIcSUBo9p.CtW', 'Ibrahimo Ibrahimo', 'ibrahimo@gmail.com', 'member', 16, 1, '2026-02-22 20:33:26', '2026-02-22 20:33:26');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices para tabela `contributions`
--
ALTER TABLE `contributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `cycle_id` (`cycle_id`);

--
-- Índices para tabela `cycles`
--
ALTER TABLE `cycles`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `distributions`
--
ALTER TABLE `distributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Índices para tabela `joias`
--
ALTER TABLE `joias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_joia` (`member_id`,`cycle_id`),
  ADD KEY `cycle_id` (`cycle_id`);

--
-- Índices para tabela `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `cycle_id` (`cycle_id`);

--
-- Índices para tabela `loan_interest`
--
ALTER TABLE `loan_interest`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `cycle_id` (`cycle_id`);

--
-- Índices para tabela `loan_repayments`
--
ALTER TABLE `loan_repayments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Índices para tabela `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `member_cycles`
--
ALTER TABLE `member_cycles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`member_id`,`cycle_id`),
  ADD KEY `cycle_id` (`cycle_id`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_users_member` (`member_id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=332;

--
-- AUTO_INCREMENT de tabela `contributions`
--
ALTER TABLE `contributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de tabela `cycles`
--
ALTER TABLE `cycles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `distributions`
--
ALTER TABLE `distributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `joias`
--
ALTER TABLE `joias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT de tabela `loan_interest`
--
ALTER TABLE `loan_interest`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de tabela `loan_repayments`
--
ALTER TABLE `loan_repayments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de tabela `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `member_cycles`
--
ALTER TABLE `member_cycles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `contributions`
--
ALTER TABLE `contributions`
  ADD CONSTRAINT `contributions_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contributions_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `distributions`
--
ALTER TABLE `distributions`
  ADD CONSTRAINT `distributions_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `distributions_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `joias`
--
ALTER TABLE `joias`
  ADD CONSTRAINT `joias_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `joias_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `loan_interest`
--
ALTER TABLE `loan_interest`
  ADD CONSTRAINT `loan_interest_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loan_interest_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loan_interest_ibfk_3` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `loan_repayments`
--
ALTER TABLE `loan_repayments`
  ADD CONSTRAINT `loan_repayments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loan_repayments_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `member_cycles`
--
ALTER TABLE `member_cycles`
  ADD CONSTRAINT `member_cycles_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `member_cycles_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
