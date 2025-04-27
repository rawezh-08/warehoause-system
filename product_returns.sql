-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 27, 2025 at 07:57 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `warehouse_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `product_returns`
--

CREATE TABLE `product_returns` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) NOT NULL,
  `receipt_type` enum('selling','buying') NOT NULL,
  `return_date` datetime NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reason` enum('damaged','wrong_product','customer_request','other') DEFAULT 'other',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_returns`
--

INSERT INTO `product_returns` (`id`, `receipt_id`, `receipt_type`, `return_date`, `total_amount`, `reason`, `notes`, `created_at`, `updated_at`) VALUES
(128, 137, '', '2025-04-25 15:01:35', 0.00, 'damaged', '', '2025-04-25 12:01:35', '2025-04-25 12:01:35'),
(129, 235, '', '2025-04-25 15:02:43', 0.00, 'damaged', '', '2025-04-25 12:02:43', '2025-04-25 12:02:43'),
(130, 137, '', '2025-04-25 15:05:31', 0.00, 'damaged', '', '2025-04-25 12:05:31', '2025-04-25 12:05:31'),
(131, 251, '', '2025-04-25 17:26:53', 0.00, 'wrong_product', '', '2025-04-25 14:26:53', '2025-04-25 14:26:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `product_returns`
--
ALTER TABLE `product_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receipt_id` (`receipt_id`),
  ADD KEY `receipt_type` (`receipt_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `product_returns`
--
ALTER TABLE `product_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
