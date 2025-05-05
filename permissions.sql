-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2025 at 08:55 AM
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
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `group` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `code`, `description`, `group`) VALUES
(1, 'بینینی کارمەندەکان', 'view_employees', 'توانای بینینی لیستی کارمەندەکان', 'کارمەندەکان'),
(2, 'زیادکردنی کارمەند', 'add_employee', 'توانای زیادکردنی کارمەندی نوێ', 'کارمەندەکان'),
(3, 'دەستکاریکردنی کارمەند', 'edit_employee', 'توانای دەستکاریکردنی زانیاری کارمەندەکان', 'کارمەندەکان'),
(4, 'سڕینەوەی کارمەند', 'delete_employee', 'توانای سڕینەوەی کارمەندەکان', 'کارمەندەکان'),
(5, 'بەڕێوەبردنی هەژمارەکان', 'manage_accounts', 'توانای زیادکردن و دەستکاریکردنی هەژماری بەکارهێنەران', 'کارگێڕی'),
(6, 'بەڕێوەبردنی دەسەڵاتەکان', 'manage_roles', 'توانای دەستکاریکردنی ڕۆڵەکان و دەسەڵاتەکان', 'کارگێڕی'),
(7, 'بینینی کڕینەکان', 'view_purchases', 'توانای بینینی پسولەکانی کڕین', 'کڕین'),
(8, 'زیادکردنی کڕین', 'add_purchase', 'توانای زیادکردنی پسولەی کڕین', 'کڕین'),
(9, 'دەستکاریکردنی کڕین', 'edit_purchase', 'توانای دەستکاریکردنی پسولەکانی کڕین', 'کڕین'),
(10, 'سڕینەوەی کڕین', 'delete_purchase', 'توانای سڕینەوەی پسولەکانی کڕین', 'کڕین'),
(11, 'بینینی فرۆشتنەکان', 'view_sales', 'توانای بینینی پسولەکانی فرۆشتن', 'فرۆشتن'),
(12, 'زیادکردنی فرۆشتن', 'add_sale', 'توانای زیادکردنی پسولەی فرۆشتن', 'فرۆشتن'),
(13, 'دەستکاریکردنی فرۆشتن', 'edit_sale', 'توانای دەستکاریکردنی پسولەکانی فرۆشتن', 'فرۆشتن'),
(14, 'سڕینەوەی فرۆشتن', 'delete_sale', 'توانای سڕینەوەی پسولەکانی فرۆشتن', 'فرۆشتن'),
(15, 'بینینی کاڵاکان', 'view_products', 'توانای بینینی لیستی کاڵاکان', 'کاڵاکان'),
(16, 'زیادکردنی کاڵا', 'add_product', 'توانای زیادکردنی کاڵای نوێ', 'کاڵاکان'),
(17, 'دەستکاریکردنی کاڵا', 'edit_product', 'توانای دەستکاریکردنی زانیاری کاڵاکان', 'کاڵاکان'),
(18, 'سڕینەوەی کاڵا', 'delete_product', 'توانای سڕینەوەی کاڵاکان', 'کاڵاکان'),
(19, 'بینینی موشتەرییەکان', 'view_customers', 'توانای بینینی لیستی موشتەرییەکان', 'موشتەرییەکان'),
(20, 'زیادکردنی موشتەری', 'add_customer', 'توانای زیادکردنی موشتەری نوێ', 'موشتەرییەکان'),
(21, 'دەستکاریکردنی موشتەری', 'edit_customer', 'توانای دەستکاریکردنی زانیاری موشتەرییەکان', 'موشتەرییەکان'),
(22, 'سڕینەوەی موشتەری', 'delete_customer', 'توانای سڕینەوەی موشتەرییەکان', 'موشتەرییەکان'),
(23, 'بینینی دابینکەران', 'view_suppliers', 'توانای بینینی لیستی دابینکەران', 'دابینکەران'),
(24, 'زیادکردنی دابینکەر', 'add_supplier', 'توانای زیادکردنی دابینکەری نوێ', 'دابینکەران'),
(25, 'دەستکاریکردنی دابینکەر', 'edit_supplier', 'توانای دەستکاریکردنی زانیاری دابینکەران', 'دابینکەران'),
(26, 'سڕینەوەی دابینکەر', 'delete_supplier', 'توانای سڕینەوەی دابینکەران', 'دابینکەران'),
(27, 'بینینی ڕاپۆرتەکان', 'view_reports', 'توانای بینینی ڕاپۆرتەکانی سیستەم', 'ڕاپۆرتەکان'),
(28, 'بینینی ڕاپۆرتی دارایی', 'view_financial_reports', 'توانای بینینی ڕاپۆرتە داراییەکان', 'ڕاپۆرتەکان'),
(29, 'بینینی ڕاپۆرتی کۆگا', 'view_inventory_reports', 'توانای بینینی ڕاپۆرتەکانی کۆگا', 'ڕاپۆرتەکان');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
