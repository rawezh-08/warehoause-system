-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 07, 2025 at 08:33 PM
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

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_debt_transaction` (IN `p_customer_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_transaction_type` ENUM('sale','purchase','payment','collection'), IN `p_reference_id` INT, IN `p_notes` TEXT, IN `p_created_by` INT)   BEGIN
    -- Add debt transaction
    INSERT INTO debt_transactions (
        customer_id, amount, transaction_type, reference_id, notes, created_by
    ) VALUES (
        p_customer_id, p_amount, p_transaction_type, p_reference_id, p_notes, p_created_by
    );
    
    -- Update customer debt
    IF p_transaction_type IN ('sale', 'purchase') THEN
        UPDATE customers 
        SET debit_on_business = debit_on_business + p_amount 
        WHERE id = p_customer_id;
    ELSE
        UPDATE customers 
        SET debit_on_business = debit_on_business - p_amount 
        WHERE id = p_customer_id;
    END IF;
    
    SELECT LAST_INSERT_ID() AS 'result';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_supplier_debt_transaction` (IN `p_supplier_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_transaction_type` ENUM('purchase','payment','return'), IN `p_reference_id` INT, IN `p_notes` TEXT, IN `p_created_by` INT)   BEGIN
    -- Add supplier debt transaction
    INSERT INTO supplier_debt_transactions (
        supplier_id, amount, transaction_type, reference_id, notes, created_by
    ) VALUES (
        p_supplier_id, p_amount, p_transaction_type, p_reference_id, p_notes, p_created_by
    );
    
    -- Update supplier debt
    IF p_transaction_type = 'purchase' THEN
        UPDATE suppliers 
        SET debt_on_myself = debt_on_myself + p_amount 
        WHERE id = p_supplier_id;
    ELSE
        UPDATE suppliers 
        SET debt_on_myself = debt_on_myself - p_amount 
        WHERE id = p_supplier_id;
    END IF;
    
    SELECT LAST_INSERT_ID() AS 'result';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `pay_supplier` (IN `p_supplier_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_notes` TEXT, IN `p_created_by` INT)   BEGIN
    DECLARE current_debt DECIMAL(10,2);
    
    -- Get current supplier debt
    SELECT debt_on_myself INTO current_debt FROM suppliers WHERE id = p_supplier_id;
    
    -- Check if payment amount is valid
    IF p_amount <= 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'بڕی پارەی دراو دەبێت گەورەتر بێت لە سفر';
    END IF;
    
    IF p_amount > current_debt THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'بڕی پارەی دراو گەورەترە لە قەرز';
    END IF;
    
    -- Record payment in supplier debt transactions
    CALL add_supplier_debt_transaction(
        p_supplier_id,
        p_amount,
        'payment',
        NULL,
        p_notes,
        p_created_by
    );
    
    SELECT 'success' AS 'result';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_purchase` (IN `p_invoice_number` VARCHAR(50), IN `p_supplier_id` INT, IN `p_date` TIMESTAMP, IN `p_payment_type` ENUM('cash','credit'), IN `p_discount` DECIMAL(10,2), IN `p_notes` TEXT, IN `p_created_by` INT, IN `p_products` JSON)   BEGIN
    DECLARE purchase_id INT;
    DECLARE i INT DEFAULT 0;
    DECLARE product_count INT;
    DECLARE v_product_id INT;
    DECLARE v_quantity INT;
    DECLARE v_unit_price DECIMAL(10,2);
    DECLARE v_total_price DECIMAL(10,2);
    DECLARE v_purchase_item_id INT;
    DECLARE total_purchase_amount DECIMAL(10,2) DEFAULT 0;
    
    -- Create purchase record
    INSERT INTO purchases (
        invoice_number, supplier_id, date, payment_type, 
        discount, notes, created_by
    ) VALUES (
        p_invoice_number, p_supplier_id, p_date, p_payment_type, 
        p_discount, p_notes, p_created_by
    );
    
    SET purchase_id = LAST_INSERT_ID();
    SET product_count = JSON_LENGTH(p_products);
    
    -- Process products
    WHILE i < product_count DO
        -- Extract product data
        SET v_product_id = JSON_EXTRACT(p_products, CONCAT('$[', i, '].product_id'));
        SET v_quantity = JSON_EXTRACT(p_products, CONCAT('$[', i, '].quantity'));
        SET v_unit_price = JSON_EXTRACT(p_products, CONCAT('$[', i, '].unit_price'));
        SET v_total_price = v_quantity * v_unit_price;
        
        -- Add to total purchase amount
        SET total_purchase_amount = total_purchase_amount + v_total_price;
        
        -- Add to purchase items
        INSERT INTO purchase_items (
            purchase_id, product_id, quantity, unit_price, total_price
        ) VALUES (
            purchase_id, v_product_id, v_quantity, v_unit_price, v_total_price
        );
        
        SET v_purchase_item_id = LAST_INSERT_ID();
        
        -- Update current quantity
        UPDATE products 
        SET current_quantity = current_quantity + v_quantity 
        WHERE id = v_product_id;
        
        -- Record in inventory table
        INSERT INTO inventory (
            product_id, quantity, reference_type, reference_id
        ) VALUES (
            v_product_id, v_quantity, 'purchase', v_purchase_item_id
        );
        
        SET i = i + 1;
    END WHILE;
    
    -- If payment is credit, create debt record for supplier
    IF p_payment_type = 'credit' THEN
        -- Apply discount to total amount if applicable
        IF p_discount > 0 THEN
            SET total_purchase_amount = total_purchase_amount - p_discount;
        END IF;
        
        -- Record debt to supplier
        CALL add_supplier_debt_transaction(
            p_supplier_id, 
            total_purchase_amount, 
            'purchase', 
            purchase_id, 
            p_notes, 
            p_created_by
        );
    END IF;
    
    SELECT purchase_id AS 'result';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_sale` (IN `p_invoice_number` VARCHAR(50), IN `p_customer_id` INT, IN `p_date` TIMESTAMP, IN `p_payment_type` ENUM('cash','credit'), IN `p_discount` DECIMAL(10,2), IN `p_price_type` ENUM('single','wholesale'), IN `p_shipping_cost` DECIMAL(10,2), IN `p_other_costs` DECIMAL(10,2), IN `p_notes` TEXT, IN `p_created_by` INT, IN `p_products` JSON)   BEGIN
    DECLARE sale_id INT;
    DECLARE i INT DEFAULT 0;
    DECLARE product_count INT;
    DECLARE v_product_id INT;
    DECLARE v_quantity INT;
    DECLARE v_unit_type VARCHAR(10);
    DECLARE v_pieces_count INT;
    DECLARE v_unit_price DECIMAL(10,2);
    DECLARE v_total_price DECIMAL(10,2);
    DECLARE v_available_quantity INT;
    DECLARE v_pieces_per_box INT;
    DECLARE v_boxes_per_set INT;
    DECLARE v_sale_item_id INT;
    
    -- Create sale record
    INSERT INTO sales (
        invoice_number, customer_id, date, payment_type, 
        discount, price_type, shipping_cost, other_costs,
        notes, created_by
    ) VALUES (
        p_invoice_number, p_customer_id, p_date, p_payment_type, 
        p_discount, p_price_type, p_shipping_cost, p_other_costs,
        p_notes, p_created_by
    );
    
    SET sale_id = LAST_INSERT_ID();
    SET product_count = JSON_LENGTH(p_products);
    
    -- Process products
    WHILE i < product_count DO
        -- Extract product data
        SET v_product_id = JSON_EXTRACT(p_products, CONCAT('$[', i, '].product_id'));
        SET v_quantity = JSON_EXTRACT(p_products, CONCAT('$[', i, '].quantity'));
        
        -- Get unit_type with default fallback to 'piece'
        SET v_unit_type = JSON_UNQUOTE(IFNULL(JSON_EXTRACT(p_products, CONCAT('$[', i, '].unit_type')), '"piece"'));
        IF v_unit_type IS NULL OR v_unit_type = '' OR v_unit_type = 'null' THEN
            SET v_unit_type = 'piece';
        END IF;
        
        SET v_unit_price = JSON_EXTRACT(p_products, CONCAT('$[', i, '].unit_price'));
        
        -- Get product information
        SELECT current_quantity, 
               IFNULL(pieces_per_box, 1) AS pieces_per_box, 
               IFNULL(boxes_per_set, 1) AS boxes_per_set 
        INTO v_available_quantity, v_pieces_per_box, v_boxes_per_set 
        FROM products WHERE id = v_product_id;
        
        -- Calculate actual pieces count based on unit type with fallbacks for NULL values
        IF v_unit_type = 'piece' THEN
            SET v_pieces_count = v_quantity;
        ELSEIF v_unit_type = 'box' THEN
            IF v_pieces_per_box IS NULL OR v_pieces_per_box <= 0 THEN
                SIGNAL SQLSTATE '45000' 
                SET MESSAGE_TEXT = 'هەڵەیەک هەیە لە ژمارەی دانەکان لە کارتۆن بۆ ئەم کاڵایە';
            END IF;
            SET v_pieces_count = v_quantity * v_pieces_per_box;
        ELSEIF v_unit_type = 'set' THEN
            IF v_pieces_per_box IS NULL OR v_pieces_per_box <= 0 OR v_boxes_per_set IS NULL OR v_boxes_per_set <= 0 THEN
                SIGNAL SQLSTATE '45000' 
                SET MESSAGE_TEXT = 'هەڵەیەک هەیە لە ژمارەی دانەکان/کارتۆنەکان بۆ ئەم کاڵایە';
            END IF;
            SET v_pieces_count = v_quantity * v_pieces_per_box * v_boxes_per_set;
        ELSE
            -- Default to piece count if unit type is unexpected
            SET v_pieces_count = v_quantity;
        END IF;
        
        -- Ensure pieces_count is never NULL
        IF v_pieces_count IS NULL THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'ژمارەی پارچەکان ناتوانرێت بەتاڵ بێت';
        END IF;
        
        SET v_total_price = v_quantity * v_unit_price;
        
        -- Check stock availability
        IF v_available_quantity < v_pieces_count THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'بڕی پێویست لە کۆگا بەردەست نییە';
        END IF;
        
        -- Add to sale items
        INSERT INTO sale_items (
            sale_id, product_id, quantity, unit_type, pieces_count, 
            unit_price, total_price
        ) VALUES (
            sale_id, v_product_id, v_quantity, v_unit_type, v_pieces_count, 
            v_unit_price, v_total_price
        );
        
        SET v_sale_item_id = LAST_INSERT_ID();
        
        -- Update current quantity
        UPDATE products 
        SET current_quantity = current_quantity - v_pieces_count 
        WHERE id = v_product_id;
        
        -- Record in inventory table
        INSERT INTO inventory (
            product_id, quantity, reference_type, reference_id
        ) VALUES (
            v_product_id, -v_pieces_count, 'sale', v_sale_item_id
        );
        
        SET i = i + 1;
    END WHILE;
    
    SELECT sale_id AS 'result';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_sale_return` (IN `p_sale_id` INT, IN `p_notes` TEXT, IN `p_created_by` INT, IN `p_return_items` JSON)   BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE item_count INT;
    DECLARE v_product_id INT;
    DECLARE v_quantity INT;
    DECLARE v_unit_type VARCHAR(10);
    DECLARE v_pieces_count INT;
    DECLARE v_pieces_per_box INT;
    DECLARE v_boxes_per_set INT;
    
    SET item_count = JSON_LENGTH(p_return_items);
    
    -- Process return items
    WHILE i < item_count DO
        -- Extract item data
        SET v_product_id = JSON_EXTRACT(p_return_items, CONCAT('$[', i, '].product_id'));
        SET v_quantity = JSON_EXTRACT(p_return_items, CONCAT('$[', i, '].quantity'));
        SET v_unit_type = JSON_EXTRACT(p_return_items, CONCAT('$[', i, '].unit_type'));
        
        -- Calculate actual pieces count based on unit type
        IF v_unit_type = 'piece' THEN
            SET v_pieces_count = v_quantity;
        ELSEIF v_unit_type = 'box' THEN
            SELECT pieces_per_box INTO v_pieces_per_box FROM products WHERE id = v_product_id;
            SET v_pieces_count = v_quantity * v_pieces_per_box;
        ELSEIF v_unit_type = 'set' THEN
            SELECT pieces_per_box, boxes_per_set INTO v_pieces_per_box, v_boxes_per_set 
            FROM products WHERE id = v_product_id;
            SET v_pieces_count = v_quantity * v_pieces_per_box * v_boxes_per_set;
        END IF;
        
        -- Update product quantity
        UPDATE products 
        SET current_quantity = current_quantity + v_pieces_count 
        WHERE id = v_product_id;
        
        -- Record in inventory table
        INSERT INTO inventory (
            product_id, quantity, reference_type, reference_id
        ) VALUES (
            v_product_id, v_pieces_count, 'return', p_sale_id
        );
        
        SET i = i + 1;
    END WHILE;
    
    SELECT 'success' AS 'result';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `create_inventory_count` (IN `p_notes` TEXT, IN `p_created_by` INT, IN `p_count_items` JSON)   BEGIN
    DECLARE count_id INT;
    DECLARE i INT DEFAULT 0;
    DECLARE item_count INT;
    DECLARE v_product_id INT;
    DECLARE v_expected_quantity INT;
    DECLARE v_actual_quantity INT;
    DECLARE v_difference INT;
    
    -- Create inventory count record
    INSERT INTO inventory_count (
        notes, created_by
    ) VALUES (
        p_notes, p_created_by
    );
    
    SET count_id = LAST_INSERT_ID();
    SET item_count = JSON_LENGTH(p_count_items);
    
    -- Process count items
    WHILE i < item_count DO
        -- Extract item data
        SET v_product_id = JSON_EXTRACT(p_count_items, CONCAT('$[', i, '].product_id'));
        SET v_actual_quantity = JSON_EXTRACT(p_count_items, CONCAT('$[', i, '].actual_quantity'));
        
        -- Get expected quantity from products table
        SELECT current_quantity INTO v_expected_quantity 
        FROM products WHERE id = v_product_id;
        
        -- Calculate difference
        SET v_difference = v_actual_quantity - v_expected_quantity;
        
        -- Add to inventory count items
        INSERT INTO inventory_count_items (
            count_id, product_id, expected_quantity, actual_quantity, difference
        ) VALUES (
            count_id, v_product_id, v_expected_quantity, v_actual_quantity, v_difference
        );
        
        -- Update product quantity directly
        UPDATE products 
        SET current_quantity = v_actual_quantity 
        WHERE id = v_product_id;
        
        -- Record adjustment in inventory if there's a difference
        IF v_difference != 0 THEN
            INSERT INTO inventory (
                product_id, quantity, reference_type, reference_id
            ) VALUES (
                v_product_id, v_difference, 'adjustment', count_id
            );
        END IF;
        
        SET i = i + 1;
    END WHILE;
    
    SELECT count_id AS 'result';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_supplier_return` (IN `p_supplier_id` INT, IN `p_notes` TEXT, IN `p_created_by` INT, IN `p_return_items` JSON)   BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE item_count INT;
    DECLARE v_product_id INT;
    DECLARE v_quantity INT;
    DECLARE v_unit_price DECIMAL(10,2);
    DECLARE total_return_amount DECIMAL(10,2) DEFAULT 0;
    
    SET item_count = JSON_LENGTH(p_return_items);
    
    -- Process return items
    WHILE i < item_count DO
        -- Extract item data
        SET v_product_id = JSON_EXTRACT(p_return_items, CONCAT('$[', i, '].product_id'));
        SET v_quantity = JSON_EXTRACT(p_return_items, CONCAT('$[', i, '].quantity'));
        SET v_unit_price = JSON_EXTRACT(p_return_items, CONCAT('$[', i, '].unit_price'));
        
        -- Add to total return amount
        SET total_return_amount = total_return_amount + (v_quantity * v_unit_price);
        
        -- Update product quantity
        UPDATE products 
        SET current_quantity = current_quantity - v_quantity 
        WHERE id = v_product_id;
        
        -- Record in inventory table
        INSERT INTO inventory (
            product_id, quantity, reference_type, reference_id
        ) VALUES (
            v_product_id, -v_quantity, 'return', p_supplier_id
        );
        
        SET i = i + 1;
    END WHILE;
    
    -- Record in supplier debt transactions
    CALL add_supplier_debt_transaction(
        p_supplier_id,
        total_return_amount,
        'return',
        NULL,
        p_notes,
        p_created_by
    );
    
    SELECT 'success' AS 'result';
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'ناوماڵ', 'کاڵا ناوماڵەکان'),
(2, 'ئەلیکترۆنیات', 'کاڵا ئەلیکترۆنیەکان'),
(3, 'جلوبەرگ', 'جلوبەرگەکان');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `phone1` varchar(20) NOT NULL,
  `phone2` varchar(20) DEFAULT NULL,
  `guarantor_name` varchar(200) DEFAULT NULL,
  `guarantor_phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `debit_on_business` decimal(10,0) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone1`, `phone2`, `guarantor_name`, `guarantor_phone`, `address`, `debit_on_business`, `notes`, `created_at`, `updated_at`) VALUES
(10, 'ڕاوێژ ', '07709240894', '', 'جەزا ', '', 'سلێمانی-بەکرەجۆ', 60000, '', '2025-04-07 17:29:56', '2025-04-07 17:35:43');

-- --------------------------------------------------------

--
-- Table structure for table `debt_transactions`
--

CREATE TABLE `debt_transactions` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(10,0) NOT NULL COMMENT 'Positive: customer debt increased, Negative: customer debt decreased',
  `transaction_type` enum('sale','purchase','payment','collection') NOT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID from sales, purchases, or manual entry',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `debt_transactions`
--

INSERT INTO `debt_transactions` (`id`, `customer_id`, `amount`, `transaction_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES
(45, 10, 60000, 'sale', 24, '', 1, '2025-04-07 17:35:43');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL COMMENT 'Positive for additions, negative for removals',
  `reference_type` enum('purchase','sale','adjustment','return') NOT NULL,
  `reference_id` int(11) NOT NULL COMMENT 'ID reference to the source table',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `product_id`, `quantity`, `reference_type`, `reference_id`, `created_at`) VALUES
(19, 46, 100, 'purchase', 8, '2025-04-07 17:31:33'),
(20, 46, -40, 'sale', 13, '2025-04-07 17:35:43');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_count`
--

CREATE TABLE `inventory_count` (
  `id` int(11) NOT NULL,
  `count_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_count_items`
--

CREATE TABLE `inventory_count_items` (
  `id` int(11) NOT NULL,
  `count_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `expected_quantity` int(11) NOT NULL,
  `actual_quantity` int(11) NOT NULL,
  `difference` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `notes` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `pieces_per_box` int(11) DEFAULT NULL,
  `boxes_per_set` int(11) DEFAULT NULL,
  `purchase_price` decimal(10,0) NOT NULL,
  `selling_price_single` decimal(10,0) NOT NULL,
  `selling_price_wholesale` decimal(10,0) DEFAULT NULL,
  `min_quantity` int(11) DEFAULT 0,
  `current_quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `code`, `barcode`, `image`, `notes`, `category_id`, `unit_id`, `pieces_per_box`, `boxes_per_set`, `purchase_price`, `selling_price_single`, `selling_price_wholesale`, `min_quantity`, `current_quantity`, `created_at`, `updated_at`) VALUES
(46, 'پیاڵە', 'A488', '1743956793191', 'uploads/products/67f2ab56e219b_1743956822.jpg', '', 1, 3, 20, 10, 1000, 1500, 1250, 10, 1004, '2025-04-06 16:27:02', '2025-04-07 17:35:43');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_type` enum('cash','credit') NOT NULL,
  `discount` decimal(10,0) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `invoice_number`, `supplier_id`, `date`, `payment_type`, `discount`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(4, '1245', 3, '2025-06-03 21:00:00', 'cash', 0, '', 1, '2025-04-06 16:41:50', '2025-04-06 18:08:38'),
(5, '135', 3, '2025-04-05 21:00:00', 'credit', 0, '', 1, '2025-04-06 18:17:43', '2025-04-06 18:17:43'),
(6, '84', 3, '2025-04-05 21:00:00', 'credit', 0, '', 1, '2025-04-06 20:46:00', '2025-04-06 20:46:00'),
(7, '', 3, '2025-04-05 21:00:00', 'credit', 0, '', 1, '2025-04-06 20:46:42', '2025-04-06 20:46:42'),
(8, '121', 4, '2025-04-06 21:00:00', 'credit', 0, '', 1, '2025-04-07 17:31:33', '2025-04-07 17:31:33');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,0) NOT NULL,
  `total_price` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `product_id`, `quantity`, `unit_price`, `total_price`) VALUES
(8, 8, 46, 100, 2000, 200000);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_type` enum('cash','credit') NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `price_type` enum('single','wholesale') NOT NULL DEFAULT 'single',
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `other_costs` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `invoice_number`, `customer_id`, `date`, `payment_type`, `discount`, `price_type`, `shipping_cost`, `other_costs`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(24, '46456', 10, '2025-04-06 21:00:00', 'credit', 0.00, 'single', 0.00, 0.00, '', 1, '2025-04-07 17:35:43', '2025-04-07 17:35:43');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_type` enum('piece','box','set') NOT NULL,
  `pieces_count` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_type`, `pieces_count`, `unit_price`, `total_price`) VALUES
(1, 1, 1, 10, 'piece', 10, 1000, 10000);