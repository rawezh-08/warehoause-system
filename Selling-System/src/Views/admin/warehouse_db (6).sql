-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 14, 2025 at 07:11 PM
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_employee_payment` (IN `p_employee_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_payment_type` ENUM('salary','bonus','overtime'), IN `p_date` DATE, IN `p_notes` TEXT, IN `p_created_by` INT)   BEGIN
    -- Insert employee payment record
    INSERT INTO employee_payments (
        employee_id, 
        amount, 
        payment_type, 
        payment_date, 
        notes, 
        created_by
    ) VALUES (
        p_employee_id, 
        p_amount, 
        p_payment_type, 
        p_date, 
        p_notes, 
        p_created_by
    );
    
    SELECT LAST_INSERT_ID() AS payment_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_expense` (IN `p_amount` DECIMAL(10,2), IN `p_date` DATE, IN `p_notes` TEXT, IN `p_created_by` INT)   BEGIN
    -- Insert expense record
    INSERT INTO expenses (
        amount, 
        expense_date, 
        notes, 
        created_by
    ) VALUES (
        p_amount, 
        p_date, 
        p_notes, 
        p_created_by
    );
    
    SELECT LAST_INSERT_ID() AS 'result';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_purchase` (IN `p_invoice_number` VARCHAR(50), IN `p_supplier_id` INT, IN `p_date` TIMESTAMP, IN `p_payment_type` ENUM('cash','credit'), IN `p_discount` DECIMAL(10,2), IN `p_paid_amount` DECIMAL(10,2), IN `p_shipping_cost` DECIMAL(10,2), IN `p_other_cost` DECIMAL(10,2), IN `p_notes` TEXT, IN `p_created_by` INT, IN `p_products` JSON)   BEGIN
    DECLARE purchase_id INT;
    DECLARE i INT DEFAULT 0;
    DECLARE product_count INT;
    DECLARE v_product_id INT;
    DECLARE v_quantity INT;
    DECLARE v_unit_price DECIMAL(10,2);
    DECLARE v_total_price DECIMAL(10,2);
    DECLARE v_purchase_item_id INT;
    DECLARE total_purchase_amount DECIMAL(10,2) DEFAULT 0;
    DECLARE remaining_amount DECIMAL(10,2) DEFAULT 0;
    DECLARE v_supplier_debt DECIMAL(10,2) DEFAULT 0;
    DECLARE v_amount_to_deduct DECIMAL(10,2) DEFAULT 0;
    
    -- Create purchase record with shipping_cost and other_cost
    INSERT INTO purchases (
        invoice_number, supplier_id, date, payment_type, 
        discount, paid_amount, shipping_cost, other_cost, notes, created_by
    ) VALUES (
        p_invoice_number, p_supplier_id, p_date, p_payment_type, 
        p_discount, p_paid_amount, p_shipping_cost, p_other_cost, p_notes, p_created_by
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
    
    -- Apply discount to total amount if applicable
    IF p_discount > 0 THEN
        SET total_purchase_amount = total_purchase_amount - p_discount;
    END IF;
    
    -- Add shipping and other costs to total
    SET total_purchase_amount = total_purchase_amount + p_shipping_cost + p_other_cost;
    
    -- Calculate remaining amount
    IF p_payment_type = 'credit' THEN
        SET remaining_amount = total_purchase_amount - p_paid_amount;
        
        -- Check if supplier owes us money
        SELECT debt_on_supplier INTO v_supplier_debt 
        FROM suppliers 
        WHERE id = p_supplier_id;
        
        -- If supplier owes us money, deduct from it first
        IF v_supplier_debt > 0 THEN
            -- Calculate how much to deduct from supplier's debt
            IF v_supplier_debt >= remaining_amount THEN
                SET v_amount_to_deduct = remaining_amount;
                SET remaining_amount = 0;
            ELSE
                SET v_amount_to_deduct = v_supplier_debt;
                SET remaining_amount = remaining_amount - v_supplier_debt;
            END IF;
            
            -- Record the deduction from supplier's debt
            CALL add_supplier_debt_transaction(
                p_supplier_id,
                v_amount_to_deduct,
                'supplier_return',
                purchase_id,
                CONCAT('کڕین بە قەرز - کەمکردنەوە لە قەرزی دابینکەر'),
                p_created_by
            );
        END IF;
    ELSE
        -- For cash payments, the paid amount should equal the total
        SET remaining_amount = 0;
        SET p_paid_amount = total_purchase_amount;
    END IF;
    
    -- Update purchase with remaining amount and ensure paid amount is set correctly
    UPDATE purchases 
    SET remaining_amount = remaining_amount,
        paid_amount = p_paid_amount
    WHERE id = purchase_id;
    
    -- If there's still remaining amount after deducting from supplier's debt,
    -- create debt record for supplier
    IF p_payment_type = 'credit' AND remaining_amount > 0 THEN
        -- Record debt to supplier
        CALL add_supplier_debt_transaction(
            p_supplier_id, 
            remaining_amount, 
            'purchase', 
            purchase_id, 
            p_notes, 
            p_created_by
        );
    END IF;
    
    SELECT purchase_id AS 'result';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_sale` (IN `p_invoice_number` VARCHAR(50), IN `p_customer_id` INT, IN `p_date` TIMESTAMP, IN `p_payment_type` ENUM('cash','credit'), IN `p_discount` DECIMAL(10,2), IN `p_paid_amount` DECIMAL(10,2), IN `p_price_type` ENUM('single','wholesale'), IN `p_shipping_cost` DECIMAL(10,2), IN `p_other_costs` DECIMAL(10,2), IN `p_notes` TEXT, IN `p_created_by` INT, IN `p_products` JSON)   BEGIN
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
    DECLARE total_sale_amount DECIMAL(10,2) DEFAULT 0;
    DECLARE remaining_amount DECIMAL(10,2) DEFAULT 0;
    
    -- Create sale record
    INSERT INTO sales (
        invoice_number, customer_id, date, payment_type, 
        discount, paid_amount, price_type, shipping_cost, other_costs,
        notes, created_by
    ) VALUES (
        p_invoice_number, p_customer_id, p_date, p_payment_type, 
        p_discount, p_paid_amount, p_price_type, p_shipping_cost, p_other_costs,
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
        SET v_total_price = v_quantity * v_unit_price;
        
        -- Add to total sale amount
        SET total_sale_amount = total_sale_amount + v_total_price;
        
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
    
    -- Add shipping and other costs to total
    SET total_sale_amount = total_sale_amount + IFNULL(p_shipping_cost, 0) + IFNULL(p_other_costs, 0);
    
    -- Apply discount to total amount if applicable
    IF p_discount > 0 THEN
        SET total_sale_amount = total_sale_amount - p_discount;
    END IF;
    
    -- Calculate remaining amount
    IF p_payment_type = 'credit' THEN
        SET remaining_amount = total_sale_amount - p_paid_amount;
    ELSE
        -- For cash payments, the paid amount should equal the total
        SET remaining_amount = 0;
        SET p_paid_amount = total_sale_amount;
    END IF;
    
    -- Update sale with remaining amount and ensure paid amount is correct
    UPDATE sales 
    SET remaining_amount = remaining_amount,
        paid_amount = p_paid_amount
    WHERE id = sale_id;
    
    -- If payment is credit, create debt transaction for customer
    IF p_payment_type = 'credit' AND remaining_amount > 0 THEN
        CALL add_debt_transaction(
            p_customer_id,
            remaining_amount,
            'sale',
            sale_id,
            p_notes,
            p_created_by
        );
    END IF;
    
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_supplier_debt_transaction` (IN `p_supplier_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_transaction_type` ENUM('purchase','payment','return'), IN `p_reference_id` INT, IN `p_notes` TEXT, IN `p_created_by` INT)   BEGIN
    -- Add supplier debt transaction
    INSERT INTO supplier_debt_transactions (
        supplier_id, amount, transaction_type, reference_id, notes, created_by
    ) VALUES (
        p_supplier_id, p_amount, p_transaction_type, p_reference_id, p_notes, p_created_by
    );
    
    -- Update supplier debt based on transaction type
    IF p_transaction_type = 'purchase' THEN
        -- Business owes supplier for purchases
        UPDATE suppliers 
        SET debt_on_myself = debt_on_myself + p_amount 
        WHERE id = p_supplier_id;
    ELSEIF p_transaction_type = 'payment' OR p_transaction_type = 'return' THEN
        -- Business reducing debt to supplier through payment or returns
        UPDATE suppliers 
        SET debt_on_myself = debt_on_myself - p_amount 
        WHERE id = p_supplier_id;
    ELSEIF p_transaction_type = 'supplier_payment' THEN
        -- Supplier pays the business (adding to what supplier owes business)
        UPDATE suppliers 
        SET debt_on_supplier = debt_on_supplier + p_amount 
        WHERE id = p_supplier_id;
    ELSEIF p_transaction_type = 'supplier_return' THEN
        -- Supplier returns products or makes a payment reducing what they owe
        UPDATE suppliers 
        SET debt_on_supplier = debt_on_supplier - p_amount 
        WHERE id = p_supplier_id;
    ELSEIF p_transaction_type = 'manual_adjustment' THEN
        -- Manual adjustment - positive amount increases debt_on_myself, negative increases debt_on_supplier
        IF p_amount >= 0 THEN
            UPDATE suppliers 
            SET debt_on_myself = debt_on_myself + p_amount 
            WHERE id = p_supplier_id;
        ELSE
            UPDATE suppliers 
            SET debt_on_supplier = debt_on_supplier - p_amount 
            WHERE id = p_supplier_id;
        END IF;
    END IF;
    
    SELECT LAST_INSERT_ID() AS 'result';
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `adjust_supplier_balance` (IN `p_supplier_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_notes` TEXT, IN `p_created_by` INT)   BEGIN
    -- Amount can be positive (business owes supplier) or negative (supplier owes business)
    -- Check if supplier exists
    DECLARE supplier_exists INT;
    
    SELECT COUNT(*) INTO supplier_exists FROM suppliers WHERE id = p_supplier_id;
    
    IF supplier_exists = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'دابینکەری داواکراو بوونی نییە';
    END IF;
    
    -- Record transaction in supplier_debt_transactions
    CALL add_supplier_debt_transaction(
        p_supplier_id,
        p_amount,
        'manual_adjustment',
        NULL,
        p_notes,
        p_created_by
    );
    
    SELECT 'success' AS 'result';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `business_pay_supplier` (IN `p_supplier_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_notes` TEXT, IN `p_created_by` INT)   BEGIN
    -- This procedure handles when the business pays money to a supplier (not for specific purchases)
    DECLARE supplier_exists INT;
    SELECT COUNT(*) INTO supplier_exists FROM suppliers WHERE id = p_supplier_id;
    
    IF supplier_exists = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'دابینکەری داواکراو بوونی نییە';
    END IF;
    
    -- Check if payment amount is valid
    IF p_amount <= 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'بڕی پارەی دراو دەبێت گەورەتر بێت لە سفر';
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_supplier_transactions` (IN `p_supplier_id` INT, IN `p_start_date` DATE, IN `p_end_date` DATE)   BEGIN
    -- Check if supplier exists
    DECLARE supplier_exists INT;
    SELECT COUNT(*) INTO supplier_exists FROM suppliers WHERE id = p_supplier_id;
    
    IF supplier_exists = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'دابینکەری داواکراو بوونی نییە';
    END IF;
    
    -- Get transactions within date range if provided
    IF p_start_date IS NOT NULL AND p_end_date IS NOT NULL THEN
        SELECT 
            id,
            amount,
            transaction_type,
            reference_id,
            notes,
            created_at,
            CASE 
                WHEN transaction_type = 'purchase' THEN 'increase_debt_on_myself'
                WHEN transaction_type = 'payment' OR transaction_type = 'return' THEN 'decrease_debt_on_myself'
                WHEN transaction_type = 'supplier_payment' THEN 'increase_debt_on_supplier'
                WHEN transaction_type = 'supplier_return' THEN 'decrease_debt_on_supplier'
                WHEN transaction_type = 'manual_adjustment' AND amount >= 0 THEN 'increase_debt_on_myself'
                WHEN transaction_type = 'manual_adjustment' AND amount < 0 THEN 'increase_debt_on_supplier'
                ELSE 'unknown'
            END AS effect_on_balance
        FROM supplier_debt_transactions
        WHERE supplier_id = p_supplier_id
        AND DATE(created_at) BETWEEN p_start_date AND p_end_date
        ORDER BY created_at DESC;
    ELSE
        -- Get all transactions if no date range
        SELECT 
            id,
            amount,
            transaction_type,
            reference_id,
            notes,
            created_at,
            CASE 
                WHEN transaction_type = 'purchase' THEN 'increase_debt_on_myself'
                WHEN transaction_type = 'payment' OR transaction_type = 'return' THEN 'decrease_debt_on_myself'
                WHEN transaction_type = 'supplier_payment' THEN 'increase_debt_on_supplier'
                WHEN transaction_type = 'supplier_return' THEN 'decrease_debt_on_supplier'
                WHEN transaction_type = 'manual_adjustment' AND amount >= 0 THEN 'increase_debt_on_myself'
                WHEN transaction_type = 'manual_adjustment' AND amount < 0 THEN 'increase_debt_on_supplier'
                ELSE 'unknown'
            END AS effect_on_balance
        FROM supplier_debt_transactions
        WHERE supplier_id = p_supplier_id
        ORDER BY created_at DESC;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `handle_supplier_payment` (IN `p_supplier_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_notes` TEXT, IN `p_created_by` INT)   BEGIN
    -- This procedure handles when a supplier pays money to the business
    -- Check if supplier exists
    DECLARE supplier_exists INT;
    SELECT COUNT(*) INTO supplier_exists FROM suppliers WHERE id = p_supplier_id;
    
    IF supplier_exists = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'دابینکەری داواکراو بوونی نییە';
    END IF;
    
    -- Check if payment amount is valid
    IF p_amount <= 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'بڕی پارەی دراو دەبێت گەورەتر بێت لە سفر';
    END IF;
    
    -- Record payment in supplier debt transactions
    CALL add_supplier_debt_transaction(
        p_supplier_id,
        p_amount,
        'supplier_payment',
        NULL,
        p_notes,
        p_created_by
    );
    
    SELECT 'success' AS 'result';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `handle_supplier_return` (IN `p_supplier_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_notes` TEXT, IN `p_created_by` INT)   BEGIN
    -- This procedure handles when a supplier returns products or items to the business
    -- which reduces the supplier's debt to the business
    
    DECLARE supplier_exists INT;
    DECLARE v_debt_on_supplier DECIMAL(10,2);
    
    -- Check if supplier exists
    SELECT COUNT(*) INTO supplier_exists FROM suppliers WHERE id = p_supplier_id;
    
    IF supplier_exists = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'دابینکەری داواکراو بوونی نییە';
    END IF;
    
    -- Check if return amount is valid
    IF p_amount <= 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'بڕی گەڕاندنەوە دەبێت گەورەتر بێت لە سفر';
    END IF;
    
    -- Check if there's enough balance to return
    SELECT debt_on_supplier INTO v_debt_on_supplier FROM suppliers WHERE id = p_supplier_id;
    
    IF v_debt_on_supplier < p_amount THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'بڕی گەڕاندنەوە گەورەترە لە قەرزی دابینکەر';
    END IF;
    
    -- Record the return in supplier debt transactions
    CALL add_supplier_debt_transaction(
        p_supplier_id,
        p_amount,
        'supplier_return',
        NULL,
        p_notes,
        p_created_by
    );
    
    SELECT 'success' AS 'result';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `pay_customer_debt` (IN `p_customer_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_sale_id` INT, IN `p_notes` TEXT, IN `p_created_by` INT)   BEGIN
    DECLARE current_debt DECIMAL(10,2);
    DECLARE sale_remaining DECIMAL(10,2);
    
    -- Check if paying for a specific sale or general debt
    IF p_sale_id IS NOT NULL THEN
        -- Get current remaining amount for this sale
        SELECT remaining_amount INTO sale_remaining FROM sales WHERE id = p_sale_id;
        
        -- Check if sale exists
        IF sale_remaining IS NULL THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'پسووڵەی داواکراو بوونی نییە';
        END IF;
        
        -- Check if payment amount is valid
        IF p_amount <= 0 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'بڕی پارەی دراو دەبێت گەورەتر بێت لە سفر';
        END IF;
        
        IF p_amount > sale_remaining THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'بڕی پارەی دراو گەورەترە لە قەرزی ماوە';
        END IF;
        
        -- Update the sale record
        UPDATE sales 
        SET paid_amount = paid_amount + p_amount,
            remaining_amount = remaining_amount - p_amount
        WHERE id = p_sale_id;
        
        -- Record payment in debt transactions
        CALL add_debt_transaction(
            p_customer_id,
            p_amount,
            'payment',
            p_sale_id,
            p_notes,
            p_created_by
        );
    ELSE
        -- Get current customer debt
        SELECT debit_on_business INTO current_debt FROM customers WHERE id = p_customer_id;
        
        -- Check if payment amount is valid
        IF p_amount <= 0 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'بڕی پارەی دراو دەبێت گەورەتر بێت لە سفر';
        END IF;
        
        IF p_amount > current_debt THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'بڕی پارەی دراو گەورەترە لە قەرز';
        END IF;
        
        -- Record payment in debt transactions
        CALL add_debt_transaction(
            p_customer_id,
            p_amount,
            'payment',
            NULL,
            p_notes,
            p_created_by
        );
    END IF;
    
    SELECT 'success' AS 'result';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `pay_supplier` (IN `p_supplier_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_purchase_id` INT, IN `p_notes` TEXT, IN `p_created_by` INT)   BEGIN
    DECLARE current_debt DECIMAL(10,2);
    DECLARE payment_amount DECIMAL(10,2);
    DECLARE advance_amount DECIMAL(10,2);
    
    -- Get current supplier debt
    SELECT debt_on_myself INTO current_debt 
    FROM suppliers 
    WHERE id = p_supplier_id;
    
    -- Check if payment amount is valid
    IF p_amount <= 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'بڕی پارە دەبێت گەورەتر بێت لە سفر';
    END IF;
    
    -- Calculate how much goes to debt and how much is advance
    IF p_amount > current_debt THEN
        SET payment_amount = current_debt;
        SET advance_amount = p_amount - current_debt;
    ELSE
        SET payment_amount = p_amount;
        SET advance_amount = 0;
    END IF;
    
    -- If paying for a specific purchase
    IF p_purchase_id IS NOT NULL THEN
        -- Update the purchase record
        UPDATE purchases 
        SET paid_amount = paid_amount + payment_amount,
            remaining_amount = remaining_amount - payment_amount
        WHERE id = p_purchase_id;
    END IF;
    
    -- If there is debt to pay
    IF payment_amount > 0 THEN
        -- Record debt payment
        INSERT INTO supplier_debt_transactions (
            supplier_id,
            amount,
            transaction_type,
            reference_id,
            notes,
            created_by
        ) VALUES (
            p_supplier_id,
            payment_amount,
            'payment',
            p_purchase_id,
            CONCAT(p_notes, IF(advance_amount > 0, ' (پارەدانی قەرز)', '')),
            p_created_by
        );
        
        -- Update supplier debt
        UPDATE suppliers 
        SET debt_on_myself = debt_on_myself - payment_amount 
        WHERE id = p_supplier_id;
    END IF;
    
    -- If there is advance payment
    IF advance_amount > 0 THEN
        -- Record advance payment
        INSERT INTO supplier_debt_transactions (
            supplier_id,
            amount,
            transaction_type,
            reference_id,
            notes,
            created_by
        ) VALUES (
            p_supplier_id,
            advance_amount,
            'advance_payment',
            NULL,
            CONCAT(p_notes, ' (پارەی پێشەکی)'),
            p_created_by
        );
        
        -- Update supplier advance balance
        UPDATE suppliers 
        SET debt_on_supplier = debt_on_supplier + advance_amount 
        WHERE id = p_supplier_id;
    END IF;
    
    -- Return success with details
    SELECT 
        'success' AS 'result',
        payment_amount AS 'debt_payment',
        advance_amount AS 'advance_payment';
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
(8, 'ڕاوێژ ', '07709240894', '', 'جزا ', '07501211541', 'سلێمانی-بەکرەجۆی تازە', 0, '', '2025-04-06 07:51:02', '2025-04-14 13:51:26'),
(9, 'دانا', '07709240897', '', 'جەلال', '', 'پیرەمۆگرون', 0, '', '2025-04-07 08:37:06', '2025-04-14 13:51:33'),
(10, 'دارا ', '07709248251', '', 'عسمان', '07501211541', 'سلێمانی-بەکرەجۆی تازە', 0, '', '2025-04-11 15:43:48', '2025-04-14 13:51:39');

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

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `salary` decimal(10,0) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `phone`, `salary`, `notes`, `created_at`, `updated_at`) VALUES
(7, 'کاروان', '07708540101', 750000, '', '2025-04-10 20:38:27', '2025-04-13 06:01:17');

-- --------------------------------------------------------

--
-- Table structure for table `employee_payments`
--

CREATE TABLE `employee_payments` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `amount` decimal(10,0) NOT NULL DEFAULT 0,
  `payment_type` enum('salary','bonus','overtime') NOT NULL,
  `payment_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `amount` decimal(10,0) DEFAULT 0,
  `expense_date` date NOT NULL,
  `notes` text NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `amount`, `expense_date`, `notes`, `created_by`, `created_at`) VALUES
(6, 50000, '2025-04-12', 'بۆ خۆم ', 1, '2025-04-12 16:54:11');

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
(33, 46, 10, 'purchase', 78, '2025-04-14 13:52:48'),
(34, 47, 4, 'purchase', 79, '2025-04-14 13:52:48'),
(35, 53, 10, 'purchase', 80, '2025-04-14 14:11:22'),
(36, 51, 10, 'purchase', 81, '2025-04-14 14:11:40'),
(37, 46, 20, 'purchase', 82, '2025-04-14 14:23:44'),
(38, 49, 5, 'purchase', 83, '2025-04-14 14:33:14'),
(39, 49, 2, 'purchase', 84, '2025-04-14 16:10:21');

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
(46, 'پیاڵە', 'A488', '1743956793191', 'uploads/products/67f2ab56e219b_1743956822.jpg', '', 1, 3, 20, 10, 1000, 1500, 1250, 10, 595, '2025-04-06 16:27:02', '2025-04-14 14:23:44'),
(47, 'سوراحی', 'A475', '1744104685757', 'uploads/products/67f4ed09d8699_1744104713.png', '', 1, 2, 20, 0, 3000, 3500, 3250, 10, 10, '2025-04-08 09:31:53', '2025-04-14 13:52:48'),
(49, 'test', 'A101', '1744387562014', 'uploads/products/67f940e7140f3_1744388327.jpg', '', 3, 1, 0, 0, 1000, 2000, 1500, 10, 43, '2025-04-11 16:18:47', '2025-04-14 16:10:21'),
(50, 'test', 'A637', '1744388415539', 'uploads/products/67f94146d1a6a_1744388422.jpg', '', 3, 1, 0, 0, 1000, 2000, 1500, 10, 168, '2025-04-11 16:20:22', '2025-04-14 09:02:43'),
(51, 'ژێر پیاڵە', 'A265', '1744388488429', 'uploads/products/67f94195bdaf4_1744388501.png', '', 3, 1, 0, 0, 1000, 1500, 1250, 10, 120, '2025-04-11 16:21:41', '2025-04-14 14:11:40'),
(53, 'کەوچک ', 'A899', '1744477703301', 'uploads/products/67fa9e19dbe4c_1744477721.jpg', '', 1, 1, 0, 0, 1500, 2000, 1750, 10, 50, '2025-04-12 17:08:41', '2025-04-14 14:11:22'),
(54, 'قەنەفە', 'A071', '1744649039110', 'uploads/products/67fd3b7bce1e3_1744649083.jpg', '', 1, 1, 0, 0, 1000, 1500, 1250, 10, 50, '2025-04-14 16:44:43', '2025-04-14 16:44:43');

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
  `shipping_cost` decimal(10,0) NOT NULL DEFAULT 0,
  `other_cost` decimal(10,0) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `paid_amount` decimal(10,0) DEFAULT 0,
  `remaining_amount` decimal(10,0) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `invoice_number`, `supplier_id`, `date`, `payment_type`, `discount`, `shipping_cost`, `other_cost`, `notes`, `created_by`, `created_at`, `updated_at`, `paid_amount`, `remaining_amount`) VALUES
(78, '258', 3, '2025-04-13 21:00:00', 'credit', 500, 2000, 1000, '', 1, '2025-04-14 13:52:48', '2025-04-14 13:52:48', 4000, 27500),
(79, '54', 3, '2025-04-13 21:00:00', 'credit', 0, 0, 0, '', 1, '2025-04-14 14:11:22', '2025-04-14 14:11:22', 0, 20000),
(80, '5', 3, '2025-04-13 21:00:00', 'cash', 0, 0, 0, '', 1, '2025-04-14 14:11:40', '2025-04-14 14:11:40', 15000, 0),
(81, '56', 3, '2025-04-13 21:00:00', 'credit', 0, 0, 0, '', 1, '2025-04-14 14:23:44', '2025-04-14 14:23:44', 0, 30000),
(82, '1564', 3, '2025-04-13 21:00:00', 'credit', 0, 0, 0, '', 1, '2025-04-14 14:33:14', '2025-04-14 14:33:14', 0, 10000),
(83, '4595', 3, '2025-04-13 21:00:00', 'cash', 0, 0, 0, '', 1, '2025-04-14 16:10:21', '2025-04-14 16:10:21', 4000, 0);

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
(78, 78, 46, 10, 1500, 15000),
(79, 78, 47, 4, 3500, 14000),
(80, 79, 53, 10, 2000, 20000),
(81, 80, 51, 10, 1500, 15000),
(82, 81, 46, 20, 1500, 30000),
(83, 82, 49, 5, 2000, 10000),
(84, 83, 49, 2, 2000, 4000);

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `paid_amount` decimal(10,0) DEFAULT 0,
  `remaining_amount` decimal(10,0) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_type` enum('piece','box','set') NOT NULL DEFAULT 'piece',
  `pieces_count` int(11) NOT NULL COMMENT 'Actual number of pieces sold',
  `unit_price` decimal(10,0) NOT NULL,
  `total_price` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `phone1` varchar(20) NOT NULL,
  `phone2` varchar(20) NOT NULL,
  `debt_on_myself` decimal(10,0) DEFAULT 0,
  `debt_on_supplier` decimal(10,0) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `phone1`, `phone2`, `debt_on_myself`, `debt_on_supplier`, `notes`, `created_at`, `updated_at`) VALUES
(2, 'محمد ', '07708542838', '', 0, 0, '', '2025-04-06 07:51:37', '2025-04-14 13:51:02'),
(3, 'ڕاوێژ', '07702183313', '', 40000, 50000, '', '2025-04-06 16:28:52', '2025-04-14 14:33:14');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_debt_transactions`
--

CREATE TABLE `supplier_debt_transactions` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `amount` decimal(10,0) NOT NULL COMMENT 'Positive: debt to supplier increased, Negative: debt to supplier decreased',
  `transaction_type` enum('purchase','payment','return','supplier_payment','manual_adjustment','supplier_return') NOT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID from purchases or manual entry',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_debt_transactions`
--

INSERT INTO `supplier_debt_transactions` (`id`, `supplier_id`, `amount`, `transaction_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES
(104, 3, 20000, 'purchase', 79, '', 1, '2025-04-14 14:11:22'),
(113, 3, 20000, 'payment', NULL, '', 1, '2025-04-14 14:22:44'),
(114, 3, 50000, '', NULL, ' (پارەی پێشەکی)', 1, '2025-04-14 14:23:00'),
(115, 3, 30000, 'purchase', 81, '', 1, '2025-04-14 14:23:44'),
(116, 3, 10000, 'purchase', 82, '', 1, '2025-04-14 14:33:14');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id`