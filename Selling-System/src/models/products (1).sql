-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 03, 2025 at 11:29 PM
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
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `barcode` varchar(50) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `shelf` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `pieces_per_box` int(11) DEFAULT NULL,
  `boxes_per_set` int(11) DEFAULT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `selling_price_single` decimal(10,2) NOT NULL,
  `selling_price_wholesale` decimal(10,2) DEFAULT NULL,
  `current_quantity` int(11) DEFAULT 0,
  `min_quantity` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `code`, `barcode`, `image`, `shelf`, `notes`, `category_id`, `unit_id`, `pieces_per_box`, `boxes_per_set`, `purchase_price`, `selling_price_single`, `selling_price_wholesale`, `current_quantity`, `min_quantity`, `created_at`, `updated_at`) VALUES
(1, 'ژێر پیاڵە', '252', '123', 'uploads/products/67eef3b0c8a11_1743713200.jpg', NULL, NULL, 1, 1, 0, 0, 1000.00, 2000.00, 1500.00, 0, 10, '2025-04-03 20:46:40', '2025-04-03 20:46:40'),
(2, 'ژێر پیاڵە', '54', '5', 'uploads/products/67eef605e8f47_1743713797.jpg', NULL, NULL, 1, 2, 20, 0, 1000.00, 2000.00, 2500.00, 0, 5, '2025-04-03 20:56:37', '2025-04-03 20:56:37'),
(3, 'ژێر پیاڵە', '54', '123', 'uploads/products/67eef6a51936a_1743713957.jpg', NULL, NULL, 1, 1, 0, 0, 1000.00, 1500.00, 1250.00, 0, 4, '2025-04-03 20:59:17', '2025-04-03 20:59:17'),
(4, 'ژێرپیاڵە ', '2552', '123', 'uploads/products/67eef7ac2433b_1743714220.jpg', 'ڕەفی شووشەوات', NULL, 1, 1, 0, 0, 1500.00, 2000.00, 1750.00, 20, 5, '2025-04-03 21:03:40', '2025-04-03 21:03:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
