-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 18, 2025 at 02:25 PM
-- Server version: 10.6.20-MariaDB-cll-lve
-- PHP Version: 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tarryn_workplaceportal`
--

-- --------------------------------------------------------

--
-- Table structure for table `mentor_accounts`
--

CREATE TABLE `mentor_accounts` (
  `id` int(11) NOT NULL,
  `employee_id` int(30) NOT NULL,
  `employee_code` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `force_password_change` tinyint(1) DEFAULT 1,
  `account_locked` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `mentor_accounts`
--

INSERT INTO `mentor_accounts` (`id`, `employee_id`, `employee_code`, `password`, `force_password_change`, `account_locked`, `last_login`, `date_created`, `date_updated`) VALUES
(1, 399, '2024-0009', '$2y$10$lAgr7Tc278DImTZBPcUHwONhFckr7FF2B2V5Tm50KcJSVOBi.RCM.', 0, 0, '2025-07-21 15:21:07', '2025-07-21 15:21:07', '2025-07-21 15:21:23'),
(2, 10, '2023-0002', '$2y$10$71aKNzdy/bIZRjnMH.ea0u4VC/ctPLwzjcO7qcNiFyCG6pz1FfHZK', 0, 0, '2025-07-21 15:40:23', '2025-07-21 15:36:20', '2025-07-21 15:40:23'),
(3, 451, '2024-0056', '$2y$10$KigeR4MxfQIiluBEsxDUDO24VWsf0BzMqUyDa1dMr/nBZwwzNFJ9u', 0, 0, '2025-07-22 13:39:19', '2025-07-22 13:38:07', '2025-07-22 13:39:19'),
(4, 213, '2023-0025', '$2y$10$z22L1YDLTBFDSJEXnq7oqOKgk.UNJFdeNSeW.Dtu44KhYCPAz1SrO', 0, 0, '2025-07-28 13:21:38', '2025-07-23 08:44:57', '2025-07-28 13:21:38'),
(5, 232, '2023-0044', '$2y$10$qxmpBVjWphXmysG5.RyzS.7zVmMIXlLqnJt3jY8bS0.ohONvRL.vG', 0, 0, '2025-07-28 13:33:16', '2025-07-28 08:47:19', '2025-07-28 13:33:16'),
(6, 598, '2025-0083', '$2y$10$lS0iXuZW4CP3m9U4Z0x1vuWF1AObnKVYuR6c.wc2R.K04J8SNB8Zy', 0, 0, '2025-07-28 14:51:31', '2025-07-28 14:51:03', '2025-07-28 14:51:31'),
(7, 412, '2024-0015', '$2y$10$gT2ooHRmq0grgekqiDf4Fute3F5eIv9h6enbAvuNVHLkCK4gJ65g6', 0, 0, '2025-08-18 09:48:05', '2025-08-13 12:16:46', '2025-08-18 09:48:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mentor_accounts`
--
ALTER TABLE `mentor_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mentor_accounts`
--
ALTER TABLE `mentor_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
