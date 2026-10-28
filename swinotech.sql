-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 28, 2026 at 10:50 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `swinotech`
--

-- --------------------------------------------------------

--
-- Table structure for table `device_settings`
--

CREATE TABLE `device_settings` (
  `setting_id` tinyint UNSIGNED NOT NULL,
  `device_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ESP32-01',
  `device_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `device_settings`
--

INSERT INTO `device_settings` (`setting_id`, `device_name`, `device_token`) VALUES
(1, 'ESP32', 'f87df2ad6070331a9aa5795402b9c36330a3801c7f331bb1');

-- --------------------------------------------------------

--
-- Table structure for table `farm_profile`
--

CREATE TABLE `farm_profile` (
  `profile_id` tinyint UNSIGNED NOT NULL,
  `farm_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PHP',
  `heat_threshold` decimal(5,2) NOT NULL DEFAULT '32.00',
  `due_soon_days` tinyint UNSIGNED NOT NULL DEFAULT '7'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `farm_profile`
--

INSERT INTO `farm_profile` (`profile_id`, `farm_name`, `owner_name`, `location`, `currency`, `heat_threshold`, `due_soon_days`) VALUES
(1, 'Banilad Piggery', 'Bambi Batumbakalhahahaha', 'Batangas, Philippines', 'PHP', 32.00, 7);

-- --------------------------------------------------------

--
-- Table structure for table `pigs`
--

CREATE TABLE `pigs` (
  `pig_id` int UNSIGNED NOT NULL,
  `tag_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('boar','fattener','sow') COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` decimal(10,2) NOT NULL,
  `pen_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','sold','dead') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `date_added` date NOT NULL DEFAULT (curdate())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pigs`
--

INSERT INTO `pigs` (`pig_id`, `tag_number`, `category`, `weight`, `pen_number`, `status`, `date_added`) VALUES
(101, 'B-001', 'boar', 184.00, 'Pen 1', 'active', '2026-06-03'),
(102, 'B-002', 'boar', 195.00, 'Pen 2', 'sold', '2026-06-04'),
(103, 'B-003', 'boar', 206.00, 'Pen 3', 'active', '2026-06-05'),
(104, 'F-001', 'fattener', 96.00, 'Pen 4', 'active', '2026-06-06'),
(105, 'F-002', 'fattener', 99.00, 'Pen 5', 'active', '2026-06-07'),
(106, 'I-001', 'sow', 18.00, 'Pen 6', 'active', '2026-06-08'),
(107, 'I-002', 'sow', 21.00, 'Pen 7', 'active', '2026-06-09'),
(108, 'S-001', 'fattener', 140.00, 'Pen 8', 'active', '2026-06-10'),
(109, 'S-002', 'fattener', 142.00, 'Pen 8', 'active', '2026-06-11'),
(110, 'S-003', 'fattener', 145.00, 'Pen 8', 'active', '2026-06-12'),
(111, 'F-003', 'fattener', 101.00, 'Pen 9', 'active', '2026-06-13'),
(112, 'F-004', 'fattener', 104.00, 'Pen 9', 'active', '2026-06-14'),
(113, 'F-005', 'fattener', 107.00, 'Pen 9', 'active', '2026-06-15'),
(114, 'F-006', 'fattener', 109.00, 'Pen 9', 'active', '2026-06-16'),
(115, 'F-007', 'fattener', 112.00, 'Pen 10', 'active', '2026-06-17'),
(116, 'F-008', 'fattener', 115.00, 'Pen 10', 'active', '2026-06-18'),
(117, 'I-003', 'sow', 19.00, 'Pen 11', 'active', '2026-06-19'),
(118, 'I-004', 'sow', 20.00, 'Pen 11', 'active', '2026-06-20'),
(119, 'I-005', 'sow', 22.00, 'Pen 11', 'active', '2026-06-21'),
(120, 'I-006', 'sow', 23.00, 'Pen 11', 'active', '2026-06-22'),
(121, 'I-007', 'sow', 24.00, 'Pen 12', 'active', '2026-06-23'),
(122, 'I-008', 'sow', 25.00, 'Pen 12', 'active', '2026-06-24'),
(123, 'I-009', 'sow', 26.00, 'Pen 12', 'active', '2026-06-25'),
(124, 'I-010', 'sow', 27.00, 'Pen 12', 'dead', '2026-06-26'),
(125, 'S-004', 'fattener', 138.00, 'Pen 13', 'active', '2026-06-27'),
(126, 'S-005', 'fattener', 141.00, 'Pen 13', 'dead', '2026-06-28'),
(127, 'S-006', 'sow', 143.00, 'Pen 13', 'active', '2026-06-29'),
(128, 'S-007', 'sow', 146.00, 'Pen 13', 'active', '2026-06-30'),
(129, 'S-008', 'sow', 148.00, 'Pen 1', 'active', '2026-07-01'),
(130, 'S-009', 'fattener', 150.00, 'Pen 13', 'dead', '2026-07-02'),
(131, 'S-010', 'sow', 178.00, 'Pen 1', 'active', '2026-10-28');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int UNSIGNED NOT NULL,
  `buyer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sale_date` date NOT NULL DEFAULT (curdate()),
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sale_id`, `buyer_name`, `sale_date`, `total_amount`) VALUES
(1031, 'Northgate Meat Traders', '2026-10-26', 35911.00),
(1032, 'Rene Baterbonia', '2026-10-28', 23400.00);

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `sale_item_id` int UNSIGNED NOT NULL,
  `sale_id` int UNSIGNED NOT NULL,
  `pig_id` int UNSIGNED NOT NULL,
  `sold_weight` decimal(10,2) NOT NULL,
  `price_per_kg` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`sale_item_id`, `sale_id`, `pig_id`, `sold_weight`, `price_per_kg`) VALUES
(1, 1032, 102, 195.00, 120.00);

-- --------------------------------------------------------

--
-- Table structure for table `sensor_logs`
--

CREATE TABLE `sensor_logs` (
  `log_id` int UNSIGNED NOT NULL,
  `device_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ESP32-01',
  `temperature` decimal(6,2) NOT NULL,
  `humidity` decimal(6,2) NOT NULL,
  `recorded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sensor_logs`
--

INSERT INTO `sensor_logs` (`log_id`, `device_id`, `temperature`, `humidity`, `recorded_at`) VALUES
(4001, 'ESP32-01', 33.40, 71.20, '2026-10-28 08:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `sow_records`
--

CREATE TABLE `sow_records` (
  `sow_record_id` int UNSIGNED NOT NULL,
  `pig_id` int UNSIGNED NOT NULL,
  `mating_date` date NOT NULL,
  `expected_farrowing_date` date DEFAULT NULL,
  `status` enum('pregnant','farrowed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pregnant',
  `piglets_born` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sow_records`
--

INSERT INTO `sow_records` (`sow_record_id`, `pig_id`, `mating_date`, `expected_farrowing_date`, `status`, `piglets_born`) VALUES
(901, 101, '2026-06-20', '2026-10-12', 'farrowed', 0),
(902, 117, '2026-07-08', '2026-10-30', 'farrowed', 8),
(903, 103, '2026-07-06', '2026-10-28', 'farrowed', 0),
(906, 123, '2026-07-08', '2026-10-30', 'pregnant', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int UNSIGNED NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','staff') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'staff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`) VALUES
(1, 'admin67', '$2y$10$PTMySUDeOqIVH8b2bBXBj.sh0Qjr8zyzfGLg3eonE9vgyufOzUSJG', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `device_settings`
--
ALTER TABLE `device_settings`
  ADD PRIMARY KEY (`setting_id`);

--
-- Indexes for table `farm_profile`
--
ALTER TABLE `farm_profile`
  ADD PRIMARY KEY (`profile_id`);

--
-- Indexes for table `pigs`
--
ALTER TABLE `pigs`
  ADD PRIMARY KEY (`pig_id`),
  ADD UNIQUE KEY `uq_pigs_tag_number` (`tag_number`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`sale_item_id`),
  ADD UNIQUE KEY `uq_sale_items_pig` (`pig_id`),
  ADD KEY `fk_sale_items_sale` (`sale_id`);

--
-- Indexes for table `sensor_logs`
--
ALTER TABLE `sensor_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `sow_records`
--
ALTER TABLE `sow_records`
  ADD PRIMARY KEY (`sow_record_id`),
  ADD KEY `fk_sow_pig` (`pig_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pigs`
--
ALTER TABLE `pigs`
  MODIFY `pig_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1033;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `sale_item_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sensor_logs`
--
ALTER TABLE `sensor_logs`
  MODIFY `log_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4002;

--
-- AUTO_INCREMENT for table `sow_records`
--
ALTER TABLE `sow_records`
  MODIFY `sow_record_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=907;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `fk_sale_items_pig` FOREIGN KEY (`pig_id`) REFERENCES `pigs` (`pig_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sale_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `sow_records`
--
ALTER TABLE `sow_records`
  ADD CONSTRAINT `fk_sow_pig` FOREIGN KEY (`pig_id`) REFERENCES `pigs` (`pig_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
