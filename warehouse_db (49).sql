-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 25, 2025 at 01:03 PM
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
    DECLARE v_unit_type VARCHAR(10);
    DECLARE v_pieces_count INT;
    DECLARE v_pieces_per_box INT;
    DECLARE v_boxes_per_set INT;
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
        SET v_unit_type = JSON_UNQUOTE(IFNULL(JSON_EXTRACT(p_products, CONCAT('$[', i, '].unit_type')), '"piece"'));
        SET v_quantity = JSON_EXTRACT(p_products, CONCAT('$[', i, '].quantity'));
        SET v_unit_price = JSON_EXTRACT(p_products, CONCAT('$[', i, '].unit_price'));
        SET v_total_price = v_quantity * v_unit_price;
        
        -- Get product information for unit conversion
        SELECT IFNULL(pieces_per_box, 1) AS pieces_per_box, 
               IFNULL(boxes_per_set, 1) AS boxes_per_set 
        INTO v_pieces_per_box, v_boxes_per_set 
        FROM products WHERE id = v_product_id;
        
        -- Calculate actual pieces count based on unit type
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
        END IF;
        
        -- Add to total purchase amount
        SET total_purchase_amount = total_purchase_amount + v_total_price;
        
        -- Add to purchase items
        INSERT INTO purchase_items (
            purchase_id, product_id, quantity, unit_type, unit_price, total_price
        ) VALUES (
            purchase_id, v_product_id, v_quantity, v_unit_type, v_unit_price, v_total_price
        );
        
        SET v_purchase_item_id = LAST_INSERT_ID();
        
        -- Update current quantity with the calculated pieces count
        UPDATE products 
        SET current_quantity = current_quantity + v_pieces_count 
        WHERE id = v_product_id;
        
        -- Record in inventory table with the calculated pieces count
        INSERT INTO inventory (
            product_id, quantity, reference_type, reference_id
        ) VALUES (
            v_product_id, v_pieces_count, 'purchase', v_purchase_item_id
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_sale` (IN `p_invoice_number` VARCHAR(50), IN `p_customer_id` INT, IN `p_date` TIMESTAMP, IN `p_payment_type` ENUM('cash','credit'), IN `p_discount` DECIMAL(10,2), IN `p_paid_amount` DECIMAL(10,2), IN `p_price_type` ENUM('single','wholesale'), IN `p_shipping_cost` DECIMAL(10,2), IN `p_other_costs` DECIMAL(10,2), IN `p_notes` TEXT, IN `p_created_by` INT, IN `p_products` JSON, IN `p_is_delivery` TINYINT(1), IN `p_delivery_address` TEXT)   BEGIN
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
        notes, created_by, is_delivery, delivery_address
    ) VALUES (
        p_invoice_number, p_customer_id, p_date, p_payment_type, 
        p_discount, p_paid_amount, p_price_type, p_shipping_cost, p_other_costs,
        p_notes, p_created_by, p_is_delivery, p_delivery_address
    );
    
    SET sale_id = LAST_INSERT_ID();
    SET product_count = JSON_LENGTH(p_products);
    
    -- Process products
    WHILE i < product_count DO
        -- Extract product data
        SET v_product_id = JSON_EXTRACT(p_products, CONCAT('$[', i, '].product_id'));
        SET v_quantity = JSON_EXTRACT(p_products, CONCAT('$[', i, '].quantity'));
        SET v_unit_type = JSON_UNQUOTE(IFNULL(JSON_EXTRACT(p_products, CONCAT('$[', i, '].unit_type')), '"piece"'));
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
        
        -- Calculate actual pieces count based on unit type
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_sale_with_advance` (IN `p_invoice_number` VARCHAR(50), IN `p_customer_id` INT, IN `p_date` TIMESTAMP, IN `p_payment_type` ENUM('cash','credit'), IN `p_discount` DECIMAL(10,2), IN `p_paid_amount` DECIMAL(10,2), IN `p_price_type` ENUM('single','wholesale'), IN `p_shipping_cost` DECIMAL(10,2), IN `p_other_costs` DECIMAL(10,2), IN `p_notes` TEXT, IN `p_created_by` INT, IN `p_products` JSON, IN `p_is_delivery` TINYINT(1), IN `p_delivery_address` TEXT)   BEGIN
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
    DECLARE customer_advance DECIMAL(10,2);
    
    -- Get customer's advance payment amount
    SELECT debt_on_customer INTO customer_advance FROM customers WHERE id = p_customer_id;
    
    -- Create sale record
    INSERT INTO sales (
        invoice_number, customer_id, date, payment_type, 
        discount, paid_amount, price_type, shipping_cost, other_costs,
        notes, created_by, is_delivery, delivery_address
    ) VALUES (
        p_invoice_number, p_customer_id, p_date, p_payment_type, 
        p_discount, p_paid_amount, p_price_type, p_shipping_cost, p_other_costs,
        p_notes, p_created_by, p_is_delivery, p_delivery_address
    );
    
    SET sale_id = LAST_INSERT_ID();
    SET product_count = JSON_LENGTH(p_products);
    
    -- Process products
    WHILE i < product_count DO
        -- Extract product data
        SET v_product_id = JSON_EXTRACT(p_products, CONCAT('$[', i, '].product_id'));
        SET v_quantity = JSON_EXTRACT(p_products, CONCAT('$[', i, '].quantity'));
        SET v_unit_type = JSON_UNQUOTE(IFNULL(JSON_EXTRACT(p_products, CONCAT('$[', i, '].unit_type')), '"piece"'));
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
        
        -- Calculate actual pieces count based on unit type
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_supplier_advance_payment` (IN `p_supplier_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_notes` TEXT, IN `p_created_by` INT)   BEGIN
    -- This procedure handles when the business gives advance payment to a supplier
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
    
    -- Record advance payment in supplier debt transactions
    CALL add_supplier_debt_transaction(
        p_supplier_id,
        p_amount,
        'supplier_advance',
        NULL,
        p_notes,
        p_created_by
    );
    
    SELECT 'success' AS 'result';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_supplier_debt_transaction` (IN `p_supplier_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_transaction_type` ENUM('purchase','payment','return','supplier_payment','manual_adjustment','supplier_return','supplier_advance','advance_used'), IN `p_reference_id` INT, IN `p_notes` TEXT, IN `p_created_by` INT)   BEGIN
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
    ELSEIF p_transaction_type = 'supplier_advance' THEN
        -- Supplier advances money to the business
        UPDATE suppliers 
        SET debt_on_supplier = debt_on_supplier + p_amount 
        WHERE id = p_supplier_id;
    ELSEIF p_transaction_type = 'advance_used' THEN
        -- Business uses advance payment
        UPDATE suppliers 
        SET debt_on_supplier = debt_on_supplier - p_amount 
        WHERE id = p_supplier_id;
    END IF;
    
    SELECT LAST_INSERT_ID() AS 'result';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_supplier_return` (IN `p_supplier_id` INT, IN `p_notes` TEXT, IN `p_created_by` INT, IN `p_return_items` JSON)   BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE item_count INT;
    DECLARE v_product_id INT;
    DECLARE v_quantity INT;
    DECLARE v_unit_price DECIMAL(10,2);
    DECLARE v_unit_type VARCHAR(10);
    DECLARE v_original_quantity INT;
    DECLARE v_returned_quantity INT;
    DECLARE v_pieces_per_box INT;
    DECLARE v_boxes_per_set INT;
    DECLARE v_pieces_count INT;
    DECLARE total_return_amount DECIMAL(10,2) DEFAULT 0;
    
    SET item_count = JSON_LENGTH(p_return_items);
    
    -- Start transaction
    START TRANSACTION;
    
    -- Process return items
    WHILE i < item_count DO
        -- Extract item data
        SET v_product_id = JSON_EXTRACT(p_return_items, CONCAT('$[', i, '].product_id'));
        SET v_quantity = JSON_EXTRACT(p_return_items, CONCAT('$[', i, '].quantity'));
        SET v_unit_price = JSON_EXTRACT(p_return_items, CONCAT('$[', i, '].unit_price'));
        SET v_unit_type = JSON_UNQUOTE(JSON_EXTRACT(p_return_items, CONCAT('$[', i, '].unit_type')));
        
        -- Get product details for unit conversion
        SELECT pieces_per_box, boxes_per_set 
        INTO v_pieces_per_box, v_boxes_per_set
        FROM products 
        WHERE id = v_product_id;

        -- Calculate pieces count based on unit type
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
        END IF;
        
        -- Get original purchase quantity and returned quantity in pieces
        SELECT 
            SUM(CASE 
                WHEN pi.unit_type = 'piece' THEN pi.quantity
                WHEN pi.unit_type = 'box' THEN pi.quantity * p2.pieces_per_box
                WHEN pi.unit_type = 'set' THEN pi.quantity * p2.pieces_per_box * p2.boxes_per_set
            END),
            SUM(CASE 
                WHEN pi.unit_type = 'piece' THEN COALESCE(pi.returned_quantity, 0)
                WHEN pi.unit_type = 'box' THEN COALESCE(pi.returned_quantity, 0) * p2.pieces_per_box
                WHEN pi.unit_type = 'set' THEN COALESCE(pi.returned_quantity, 0) * p2.pieces_per_box * p2.boxes_per_set
            END)
        INTO v_original_quantity, v_returned_quantity
        FROM purchase_items pi
        JOIN purchases p ON pi.purchase_id = p.id
        JOIN products p2 ON pi.product_id = p2.id
        WHERE p.supplier_id = p_supplier_id 
        AND pi.product_id = v_product_id;
        
        -- Check if return quantity is valid
        IF (v_pieces_count > (v_original_quantity - COALESCE(v_returned_quantity, 0))) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'بڕی گەڕاندنەوە زیاترە لە بڕی کڕدراو';
        END IF;
        
        -- Add to total return amount
        SET total_return_amount = total_return_amount + (v_quantity * v_unit_price);
        
        -- Update product quantity
        UPDATE products 
        SET current_quantity = current_quantity - v_pieces_count 
        WHERE id = v_product_id;
        
        -- Update returned quantity in purchase_items
        UPDATE purchase_items pi
        JOIN purchases p ON pi.purchase_id = p.id
        SET pi.returned_quantity = COALESCE(pi.returned_quantity, 0) + 
            CASE 
                WHEN pi.unit_type = v_unit_type THEN v_quantity
                WHEN pi.unit_type = 'piece' AND v_unit_type IN ('box', 'set') THEN v_pieces_count
                WHEN pi.unit_type = 'box' AND v_unit_type = 'piece' THEN v_quantity / v_pieces_per_box
                WHEN pi.unit_type = 'set' AND v_unit_type = 'piece' THEN v_quantity / (v_pieces_per_box * v_boxes_per_set)
            END
        WHERE p.supplier_id = p_supplier_id 
        AND pi.product_id = v_product_id
        AND pi.unit_type = v_unit_type
        LIMIT 1;
        
        -- Record in inventory table
        INSERT INTO inventory (
            product_id, 
            quantity, 
            reference_type, 
            reference_id,
            notes
        ) VALUES (
            v_product_id, 
            -v_pieces_count, 
            'return', 
            p_supplier_id,
            CONCAT('گەڕاندنەوە بۆ دابینکەر - ', IFNULL(p_notes, ''))
        );
        
        SET i = i + 1;
    END WHILE;
    
    -- Update supplier debt
    UPDATE suppliers 
    SET debt_on_myself = GREATEST(0, debt_on_myself - total_return_amount)
    WHERE id = p_supplier_id;
    
    -- Record in supplier debt transactions
    INSERT INTO supplier_debt_transactions (
        supplier_id,
        amount,
        transaction_type,
        notes,
        created_by
    ) VALUES (
        p_supplier_id,
        total_return_amount,
        'return',
        p_notes,
        p_created_by
    );
    
    -- Commit transaction
    COMMIT;
    
    SELECT 'success' AS 'result';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_wasting` (IN `p_invoice_number` VARCHAR(50), IN `p_date` TIMESTAMP, IN `p_payment_type` ENUM('cash','credit'), IN `p_paid_amount` DECIMAL(10,2), IN `p_notes` TEXT, IN `p_created_by` INT, IN `p_products` JSON)   BEGIN
    DECLARE wasting_id INT;
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
    DECLARE v_wasting_item_id INT;
    DECLARE total_amount DECIMAL(10,2) DEFAULT 0;
    DECLARE remaining_amount DECIMAL(10,2) DEFAULT 0;
    
    -- Create wasting record
    INSERT INTO wastings (
        invoice_number, date, payment_type, notes, created_by
    ) VALUES (
        p_invoice_number, p_date, p_payment_type, p_notes, p_created_by
    );
    
    SET wasting_id = LAST_INSERT_ID();
    SET product_count = JSON_LENGTH(p_products);
    
    -- Process products
    WHILE i < product_count DO
        -- Extract product data
        SET v_product_id = JSON_EXTRACT(p_products, CONCAT('$[', i, '].product_id'));
        SET v_quantity = JSON_EXTRACT(p_products, CONCAT('$[', i, '].quantity'));
        SET v_unit_type = JSON_UNQUOTE(IFNULL(JSON_EXTRACT(p_products, CONCAT('$[', i, '].unit_type')), '"piece"'));
        SET v_unit_price = JSON_EXTRACT(p_products, CONCAT('$[', i, '].unit_price'));
        SET v_total_price = v_quantity * v_unit_price;
        
        -- Add to total amount
        SET total_amount = total_amount + v_total_price;
        
        -- Get product information
        SELECT current_quantity, 
               IFNULL(pieces_per_box, 1) AS pieces_per_box, 
               IFNULL(boxes_per_set, 1) AS boxes_per_set 
        INTO v_available_quantity, v_pieces_per_box, v_boxes_per_set 
        FROM products WHERE id = v_product_id;
        
        -- Calculate actual pieces count based on unit type
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
        END IF;
        
        -- Check stock availability
        IF v_available_quantity < v_pieces_count THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'بڕی پێویست لە کۆگا بەردەست نییە';
        END IF;
        
        -- Add to wasting items
        INSERT INTO wasting_items (
            wasting_id, product_id, quantity, unit_type, pieces_count,
            unit_price, total_price
        ) VALUES (
            wasting_id, v_product_id, v_quantity, v_unit_type, v_pieces_count,
            v_unit_price, v_total_price
        );
        
        SET v_wasting_item_id = LAST_INSERT_ID();
        
        -- Update current quantity
        UPDATE products 
        SET current_quantity = current_quantity - v_pieces_count 
        WHERE id = v_product_id;
        
        -- Record in inventory table
        INSERT INTO inventory (
            product_id, quantity, reference_type, reference_id
        ) VALUES (
            v_product_id, -v_pieces_count, 'adjustment', v_wasting_item_id
        );
        
        SET i = i + 1;
    END WHILE;
    
    -- Calculate remaining amount for credit payment
    IF p_payment_type = 'credit' THEN
        SET remaining_amount = total_amount - p_paid_amount;
    ELSE
        -- For cash payments, paid amount should equal total
        SET remaining_amount = 0;
        SET p_paid_amount = total_amount;
    END IF;
    
    -- Update wasting with payment information
    UPDATE wastings 
    SET paid_amount = p_paid_amount,
        remaining_amount = remaining_amount
    WHERE id = wasting_id;
    
    SELECT wasting_id AS 'result';
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteDraftReceipt` (IN `p_receipt_id` INT)   BEGIN
    -- Check if the draft receipt exists using our function
    IF NOT DoesDraftReceiptExist(p_receipt_id) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'ڕەشنووسی پسووڵە نەدۆزرایەوە';
    ELSE
        -- Begin transaction
        START TRANSACTION;
        
        -- Delete related sale items
        DELETE FROM sale_items WHERE sale_id = p_receipt_id;
        
        -- Delete the sale record
        DELETE FROM sales WHERE id = p_receipt_id AND is_draft = 1;
        
        -- Commit transaction
        COMMIT;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteWastingRecord` (IN `p_wasting_id` INT)   BEGIN
    -- Check if the wasting record exists using our function
    IF NOT DoesWastingExist(p_wasting_id) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'بەفیڕۆچوو نەدۆزرایەوە';
    ELSE
        -- Begin transaction
        START TRANSACTION;
        
        -- Restore product quantities
        UPDATE products p
        JOIN wasting_items wi ON p.id = wi.product_id
        SET p.current_quantity = p.current_quantity + wi.quantity
        WHERE wi.wasting_id = p_wasting_id;
        
        -- Delete related wasting items
        DELETE FROM wasting_items WHERE wasting_id = p_wasting_id;
        
        -- Delete the wasting record
        DELETE FROM wastings WHERE id = p_wasting_id;
        
        -- Commit transaction
        COMMIT;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `finalize_draft_receipt` (IN `p_receipt_id` INT, IN `p_created_by` INT)   BEGIN
    DECLARE v_customer_id INT;
    DECLARE v_payment_type VARCHAR(10);
    DECLARE v_total_amount DECIMAL(10,0);
    DECLARE v_draft_exists INT;
    
    -- Check if the draft exists
    SELECT COUNT(*) INTO v_draft_exists
    FROM sales
    WHERE id = p_receipt_id AND is_draft = 1;
    
    IF v_draft_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ڕەشنووسی پسووڵە نەدۆزرایەوە یان پێشتر پەسەند کراوە';
    ELSE
        -- Get receipt information
        SELECT customer_id, payment_type, 
               (COALESCE(SUM(si.total_price), 0) + s.shipping_cost + s.other_costs - s.discount) as total_amount
        INTO v_customer_id, v_payment_type, v_total_amount
        FROM sales s
        LEFT JOIN sale_items si ON s.id = si.sale_id
        WHERE s.id = p_receipt_id
        GROUP BY s.id;
        
        -- Begin transaction
        START TRANSACTION;
        
        -- Update the draft to finalize it
        UPDATE sales 
        SET is_draft = 0, 
            created_by = p_created_by, 
            updated_at = NOW()
        WHERE id = p_receipt_id;
        
        -- If payment type is credit, update customer debt
        IF v_payment_type = 'credit' THEN
            -- Insert debt transaction record
            INSERT INTO debt_transactions (
                customer_id, 
                amount, 
                transaction_type, 
                reference_id, 
                created_by, 
                created_at
            ) VALUES (
                v_customer_id, 
                v_total_amount, 
                'sale', 
                p_receipt_id, 
                p_created_by, 
                NOW()
            );
            
            -- Update customer debit_on_business amount (CORRECT column name)
            UPDATE customers 
            SET debit_on_business = debit_on_business + v_total_amount
            WHERE id = v_customer_id;
        END IF;
        
        -- Commit transaction
        COMMIT;
    END IF;
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
    DECLARE current_debt DECIMAL(10,2);
    
    -- Get current supplier debt
    SELECT debt_on_supplier INTO current_debt
    FROM suppliers 
    WHERE id = p_supplier_id;
    
    -- Validate amount against current debt
    IF current_debt < p_amount THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'بڕی پارەی داواکراو زیاترە لە قەرزی دابینکەر';
    END IF;
    
    -- Insert transaction record
    INSERT INTO supplier_debt_transactions (
        supplier_id,
        amount,
        transaction_type,
        notes,
        created_by
    ) VALUES (
        p_supplier_id,
        p_amount,
        'supplier_payment',
        p_notes,
        p_created_by
    );
    
    -- Update supplier balance
    UPDATE suppliers 
    SET debt_on_supplier = debt_on_supplier - p_amount
    WHERE id = p_supplier_id;
    
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `use_supplier_advance_payment` (IN `p_supplier_id` INT, IN `p_purchase_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_notes` TEXT, IN `p_created_by` INT)   BEGIN
    -- This procedure handles when the business uses supplier advance payment for a purchase
    DECLARE supplier_exists INT;
    DECLARE advance_amount DECIMAL(10,2);
    DECLARE purchase_exists INT;
    DECLARE remaining_amount DECIMAL(10,2);
    DECLARE amount_to_use DECIMAL(10,2);
    
    -- Check if supplier exists
    SELECT COUNT(*), debt_on_supplier INTO supplier_exists, advance_amount 
    FROM suppliers 
    WHERE id = p_supplier_id;
    
    IF supplier_exists = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'دابینکەری داواکراو بوونی نییە';
    END IF;
    
    -- Check if there is advance payment available
    IF advance_amount <= 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'پارەی پێشەکی بەردەست نییە';
    END IF;
    
    -- Check if purchase exists
    SELECT COUNT(*), remaining_amount INTO purchase_exists, remaining_amount 
    FROM purchases 
    WHERE id = p_purchase_id;
    
    IF purchase_exists = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'کڕینی داواکراو بوونی نییە';
    END IF;
    
    -- Calculate amount to use
    SET amount_to_use = LEAST(advance_amount, p_amount, remaining_amount);
    
    IF amount_to_use <= 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'بڕی پارەی بەکارهاتوو نابێت سفر یان کەمتر بێت';
    END IF;
    
    -- Record the use of advance payment
    CALL add_supplier_debt_transaction(
        p_supplier_id,
        amount_to_use,
        'advance_used',
        p_purchase_id,
        p_notes,
        p_created_by
    );
    
    -- Update purchase's remaining amount and paid amount
    UPDATE purchases 
    SET remaining_amount = remaining_amount - amount_to_use,
        paid_amount = paid_amount + amount_to_use
    WHERE id = p_purchase_id;
    
    SELECT 'success' AS 'result', amount_to_use AS 'amount_used';
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `DoesDraftReceiptExist` (`p_receipt_id` INT) RETURNS TINYINT(1) READS SQL DATA BEGIN
    DECLARE record_count INT;
    
    SELECT COUNT(*) INTO record_count 
    FROM sales 
    WHERE id = p_receipt_id AND is_draft = 1;
    
    IF record_count > 0 THEN
        RETURN TRUE;
    ELSE
        RETURN FALSE;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `DoesWastingExist` (`p_wasting_id` INT) RETURNS TINYINT(1) READS SQL DATA BEGIN
    DECLARE record_count INT;
    
    SELECT COUNT(*) INTO record_count 
    FROM wastings 
    WHERE id = p_wasting_id;
    
    IF record_count > 0 THEN
        RETURN TRUE;
    ELSE
        RETURN FALSE;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admin_accounts`
--

CREATE TABLE `admin_accounts` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_accounts`
--

INSERT INTO `admin_accounts` (`id`, `username`, `password_hash`, `created_at`, `updated_at`) VALUES
(1, 'Ashkan@5678', '$2y$12$J6heQa6au7qgqr.bS7Kvu.lMg/Le2rNRTb1Sn4dZCHGqY0OcSa94G', '2025-04-16 10:14:42', '2025-04-16 10:19:59');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cash_management` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` decimal(10,2) NOT NULL COMMENT 'Positive for deposits, negative for withdrawals',
  `transaction_type` enum('initial_balance','deposit','withdrawal','adjustment') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'ناوماڵ', 'کاڵا ناوماڵەکان'),
(4, 'شووشەوات', ''),
(5, 'قوماش', ''),
(6, 'شتومەک', ''),
(7, 'n', '');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `debt_on_customer` decimal(10,0) DEFAULT 0 COMMENT 'Amount customer owes to us'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone1`, `phone2`, `guarantor_name`, `guarantor_phone`, `address`, `debit_on_business`, `notes`, `created_at`, `updated_at`, `debt_on_customer`) VALUES
(23, 'ڕاوێژ2', '07709240894', '', '', '', '', 910500, '', '2025-04-20 09:26:03', '2025-04-25 07:21:48', 0),
(24, 'کاروان', '07709240897', '', '', '', 'سلێمانی بەکرەجۆی تازە', 76000, '', '2025-04-21 06:29:43', '2025-04-24 18:07:05', 0),
(25, 'موسا', '07709240895', '', '', '', '', 44000, '', '2025-04-22 13:46:52', '2025-04-24 21:20:02', 0);

-- --------------------------------------------------------

--
-- Table structure for table `debt_transactions`
--

CREATE TABLE `debt_transactions` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(10,0) NOT NULL COMMENT 'Positive: customer debt increased, Negative: customer debt decreased',
  `transaction_type` enum('sale','purchase','payment','collection','prepayment_used','advance_payment') NOT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID from sales, purchases, or manual entry',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `debt_transactions`
--

INSERT INTO `debt_transactions` (`id`, `customer_id`, `amount`, `transaction_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES
(225, 23, 900000, 'sale', 194, '', 1, '2025-04-24 17:34:59'),
(226, 23, -50000, '', 109, 'گەڕاندنەوەی کاڵا - ', NULL, '2025-04-24 17:36:16'),
(227, 23, 40000, 'sale', 196, '', 1, '2025-04-24 18:04:51'),
(228, 23, -4000, '', 114, 'گەڕاندنەوەی کاڵا - ', NULL, '2025-04-24 18:04:59'),
(229, 24, 80000, 'sale', 197, '', 1, '2025-04-24 18:06:54'),
(230, 24, -4000, '', 115, 'گەڕاندنەوەی کاڵا - ', NULL, '2025-04-24 18:07:05'),
(231, 23, 16000, 'sale', 199, '', 1, '2025-04-24 18:09:39'),
(233, 23, 1000, 'sale', NULL, 'Test debt transaction', 1, '2025-04-24 19:10:49'),
(234, 25, 16000, 'sale', 221, NULL, 1, '2025-04-24 20:53:01'),
(236, 23, -2500, '', 118, 'گەڕاندنەوەی کاڵا - ', NULL, '2025-04-24 21:11:09'),
(237, 25, 28000, 'sale', 225, '', NULL, '2025-04-24 21:20:02'),
(238, 23, 10000, 'sale', 229, '', 1, '2025-04-25 07:21:48');

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
(13, 'ڕامیار', '07709240854', 750000, '', '2025-04-22 14:44:06', '2025-04-22 14:44:14'),
(14, 'd', '07502656656', 5000, '', '2025-04-22 17:19:57', '2025-04-22 17:20:38');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `product_id`, `quantity`, `reference_type`, `reference_id`, `created_at`, `notes`) VALUES
(434, 69, -100, 'sale', 304, '2025-04-24 17:34:59', NULL),
(435, 68, -200, 'sale', 305, '2025-04-24 17:34:59', NULL),
(436, 68, 100, 'purchase', 139, '2025-04-24 17:35:30', NULL),
(437, 69, 200, 'purchase', 140, '2025-04-24 17:35:30', NULL),
(438, 68, 1, 'return', 109, '2025-04-24 17:36:16', 'گەڕاندنەوە: 1 box (ئەسڵی: 1 box)'),
(439, 68, -1, 'return', 110, '2025-04-24 17:42:59', 'گەڕاندنەوە: 1 piece (ئەسڵی: 1 piece)'),
(440, 68, -1, 'return', 111, '2025-04-24 17:44:08', 'گەڕاندنەوە: 1 piece (ئەسڵی: 1 piece)'),
(441, 69, -1, 'return', 112, '2025-04-24 17:44:50', 'گەڕاندنەوە: 1 box (ئەسڵی: 1 box)'),
(442, 68, -1, 'return', 113, '2025-04-24 17:46:20', 'گەڕاندنەوە: 1 piece (ئەسڵی: 1 piece)'),
(443, 69, -10, 'adjustment', 11, '2025-04-24 17:51:15', NULL),
(444, 69, -10, 'sale', 307, '2025-04-24 18:04:51', NULL),
(445, 69, 1, 'return', 114, '2025-04-24 18:04:59', 'گەڕاندنەوە: 1 piece (ئەسڵی: 1 piece)'),
(446, 69, -20, 'sale', 308, '2025-04-24 18:06:54', NULL),
(447, 69, 1, 'return', 115, '2025-04-24 18:07:05', 'گەڕاندنەوە: 1 piece (ئەسڵی: 1 piece)'),
(448, 69, -4, 'sale', 310, '2025-04-24 18:09:39', NULL),
(449, 69, 1, 'purchase', 141, '2025-04-24 18:15:49', NULL),
(450, 69, 2, 'purchase', 142, '2025-04-24 18:16:02', NULL),
(451, 77, 1, 'purchase', 143, '2025-04-24 18:16:27', NULL),
(452, 69, -1, 'return', 116, '2025-04-24 18:17:06', 'گەڕاندنەوە: 1 piece (ئەسڵی: 1 piece)'),
(453, 69, -1, 'return', 117, '2025-04-24 18:17:28', 'گەڕاندنەوە: 1 piece (ئەسڵی: 1 piece)'),
(455, 68, -1, 'sale', 318, '2025-04-24 19:10:24', NULL),
(456, 69, -2, 'adjustment', 12, '2025-04-24 19:46:58', NULL),
(457, 69, -4, 'adjustment', 13, '2025-04-24 19:49:41', NULL),
(458, 69, -40, 'adjustment', 14, '2025-04-24 20:26:55', NULL),
(460, 69, -4, 'adjustment', 15, '2025-04-24 20:50:06', NULL),
(461, 69, -4, 'adjustment', 16, '2025-04-24 20:50:12', NULL),
(462, 69, -2, 'sale', 335, '2025-04-24 21:08:58', NULL),
(463, 68, 1, 'return', 118, '2025-04-24 21:11:09', 'گەڕاندنەوە: 1 piece (ئەسڵی: 1 piece)'),
(464, 69, 4, 'purchase', 144, '2025-04-24 21:17:15', NULL),
(465, 69, -7, 'sale', 336, '2025-04-24 21:19:20', NULL),
(466, 69, 2, 'return', 119, '2025-04-24 21:19:46', 'گەڕاندنەوە: 2 piece (ئەسڵی: 2 piece)'),
(467, 69, -5, 'sale', 337, '2025-04-24 22:01:37', NULL),
(468, 77, -4, 'sale', 338, '2025-04-24 22:01:37', NULL),
(469, 69, -40, 'sale', 339, '2025-04-25 07:19:20', NULL),
(470, 69, -4, 'sale', 340, '2025-04-25 07:20:33', NULL),
(471, 68, -4, 'sale', 341, '2025-04-25 07:21:48', NULL),
(472, 68, -4, 'sale', 342, '2025-04-25 07:22:20', NULL),
(473, 69, -1, 'return', 120, '2025-04-25 10:45:49', 'گەڕاندنەوە: 1 piece (ئەسڵی: 1 piece)'),
(474, 69, -1, 'return', 121, '2025-04-25 11:02:56', 'گەڕاندنەوە: 1 piece (ئەسڵی: 1 piece)');

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
(68, 'پیاڵە', 'A018', '1745142214785', 'uploads/products/6804c1f59ca92_1745142261.jpg', '', 4, 3, 20, 10, 1500, 2500, 2000, 10, 540, '2025-04-20 09:44:21', '2025-04-25 07:22:20'),
(69, 'کەوچک ', 'A474', '1745225304356', 'uploads/products/68060675a6e60_1745225333.png', '', 4, 3, 10, 20, 3000, 4000, 3500, 5, 57, '2025-04-21 08:48:53', '2025-04-25 11:02:56'),
(77, 'حاجی فاروق', 'A458', '1745342209677', 'uploads/products/6807cf0c79fc7_1745342220.jpg', '', 6, 1, 0, 0, 1000, 1500, 1250, 10, 0, '2025-04-22 17:17:00', '2025-04-24 22:01:37');

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
(109, 194, '', '2025-04-24 20:36:16', 0.00, 'damaged', '', '2025-04-24 17:36:16', '2025-04-24 17:36:16'),
(110, 131, '', '2025-04-24 20:42:59', 0.00, 'damaged', '', '2025-04-24 17:42:59', '2025-04-24 17:42:59'),
(111, 131, '', '2025-04-24 20:44:08', 0.00, 'damaged', '', '2025-04-24 17:44:08', '2025-04-24 17:44:08'),
(112, 131, '', '2025-04-24 20:44:50', 0.00, 'damaged', '', '2025-04-24 17:44:50', '2025-04-24 17:44:50'),
(113, 131, '', '2025-04-24 20:46:20', 0.00, 'damaged', '', '2025-04-24 17:46:20', '2025-04-24 17:46:20'),
(114, 196, '', '2025-04-24 21:04:59', 0.00, 'damaged', '', '2025-04-24 18:04:59', '2025-04-24 18:04:59'),
(115, 197, '', '2025-04-24 21:07:05', 0.00, 'damaged', '', '2025-04-24 18:07:05', '2025-04-24 18:07:05'),
(116, 132, '', '2025-04-24 21:17:06', 0.00, 'damaged', '', '2025-04-24 18:17:06', '2025-04-24 18:17:06'),
(117, 133, '', '2025-04-24 21:17:28', 0.00, 'damaged', '', '2025-04-24 18:17:28', '2025-04-24 18:17:28'),
(118, 207, '', '2025-04-25 00:11:09', 0.00, 'damaged', '', '2025-04-24 21:11:09', '2025-04-24 21:11:09'),
(119, 225, '', '2025-04-25 00:19:46', 0.00, 'damaged', '', '2025-04-24 21:19:46', '2025-04-24 21:19:46'),
(120, 135, '', '2025-04-25 13:45:49', 0.00, 'damaged', '', '2025-04-25 10:45:49', '2025-04-25 10:45:49'),
(121, 135, '', '2025-04-25 14:02:56', 0.00, 'damaged', '', '2025-04-25 11:02:56', '2025-04-25 11:02:56');

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
(135, 'B-0001', 9, '2025-04-22 21:00:00', 'credit', 0, 0, 0, '', 1, '2025-04-24 21:17:15', '2025-04-24 21:17:32', 0, 12000);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_type` enum('piece','box','set') NOT NULL DEFAULT 'piece',
  `unit_price` decimal(10,0) NOT NULL,
  `total_price` decimal(10,0) NOT NULL,
  `returned_quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `product_id`, `quantity`, `unit_type`, `unit_price`, `total_price`, `returned_quantity`) VALUES
(144, 135, 69, 4, 'piece', 3000, 12000, 2);

-- --------------------------------------------------------

--
-- Table structure for table `return_items`
--

CREATE TABLE `return_items` (
  `id` int(11) NOT NULL,
  `return_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `unit_type` enum('piece','box','set') DEFAULT 'piece',
  `original_unit_type` enum('piece','box','set') DEFAULT 'piece',
  `original_quantity` decimal(10,2) DEFAULT 0.00,
  `reason` enum('damaged','wrong_product','customer_request','other') DEFAULT 'other',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `return_items`
--

INSERT INTO `return_items` (`id`, `return_id`, `product_id`, `quantity`, `unit_price`, `unit_type`, `original_unit_type`, `original_quantity`, `reason`, `notes`, `created_at`, `total_price`) VALUES
(113, 109, 68, 1.00, 50000.00, 'box', 'box', 1.00, 'damaged', NULL, '2025-04-24 17:36:16', 50000.00),
(114, 110, 68, 1.00, 1500.00, 'piece', 'piece', 1.00, 'damaged', NULL, '2025-04-24 17:42:59', 1500.00),
(115, 111, 68, 1.00, 1500.00, 'piece', 'piece', 1.00, 'damaged', NULL, '2025-04-24 17:44:08', 1500.00),
(116, 112, 69, 1.00, 30000.00, 'box', 'box', 1.00, 'damaged', NULL, '2025-04-24 17:44:50', 30000.00),
(117, 113, 68, 1.00, 1500.00, 'piece', 'piece', 1.00, 'damaged', NULL, '2025-04-24 17:46:20', 1500.00),
(118, 114, 69, 1.00, 4000.00, 'piece', 'piece', 1.00, 'damaged', NULL, '2025-04-24 18:04:59', 4000.00),
(119, 115, 69, 1.00, 4000.00, 'piece', 'piece', 1.00, 'damaged', NULL, '2025-04-24 18:07:05', 4000.00),
(120, 116, 69, 1.00, 3000.00, 'piece', 'piece', 1.00, 'damaged', NULL, '2025-04-24 18:17:06', 3000.00),
(121, 117, 69, 1.00, 3000.00, 'piece', 'piece', 1.00, 'damaged', NULL, '2025-04-24 18:17:28', 3000.00),
(122, 118, 68, 1.00, 2500.00, 'piece', 'piece', 1.00, 'damaged', NULL, '2025-04-24 21:11:09', 2500.00),
(123, 119, 69, 2.00, 4000.00, 'piece', 'piece', 2.00, 'damaged', NULL, '2025-04-24 21:19:46', 8000.00),
(124, 120, 69, 1.00, 3000.00, 'piece', 'piece', 1.00, 'damaged', NULL, '2025-04-25 10:45:49', 3000.00),
(125, 121, 69, 1.00, 3000.00, 'piece', 'piece', 1.00, 'damaged', NULL, '2025-04-25 11:02:56', 3000.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_type` enum('cash','credit') NOT NULL,
  `discount` decimal(10,0) DEFAULT 0,
  `price_type` enum('single','wholesale') NOT NULL DEFAULT 'single',
  `shipping_cost` decimal(10,0) DEFAULT 0,
  `other_costs` decimal(10,0) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `paid_amount` decimal(10,0) DEFAULT 0,
  `remaining_amount` decimal(10,0) DEFAULT 0,
  `is_draft` tinyint(1) DEFAULT 0,
  `is_delivery` tinyint(1) DEFAULT 0,
  `delivery_address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `invoice_number`, `customer_id`, `date`, `payment_type`, `discount`, `price_type`, `shipping_cost`, `other_costs`, `notes`, `created_by`, `created_at`, `updated_at`, `paid_amount`, `remaining_amount`, `is_draft`, `is_delivery`, `delivery_address`) VALUES
(198, 'A-0004', 23, '2025-04-23 21:00:00', 'credit', 0, 'single', 0, 0, '', 1, '2025-04-24 18:09:22', '2025-04-24 18:25:11', 0, 16000, 0, 0, NULL),
(199, 'A-0005', 23, '2025-04-23 21:00:00', 'credit', 0, 'single', 0, 0, '', 1, '2025-04-24 18:09:39', '2025-04-24 18:09:39', 0, 16000, 0, 0, NULL),
(205, 'A-0011', 24, '2025-04-23 21:00:00', 'credit', 0, 'single', 0, 0, '', 1, '2025-04-24 19:06:25', '2025-04-24 19:06:38', 0, 16000, 0, 0, NULL),
(206, 'A-0012', 23, '2025-04-23 21:00:00', 'credit', 0, 'single', 0, 0, '', 1, '2025-04-24 19:07:59', '2025-04-24 19:12:32', 0, 25000, 0, 0, NULL),
(209, 'A-0002', 25, '2025-04-23 21:00:00', 'credit', 0, 'single', 0, 0, '', 1, '2025-04-24 19:23:00', '2025-04-24 19:23:25', 0, 25000, 0, 0, NULL),
(210, 'A-0003', 25, '2025-04-23 21:00:00', 'credit', 0, 'single', 0, 0, '', 1, '2025-04-24 19:24:45', '2025-04-24 19:24:52', 0, 25000, 0, 0, NULL),
(211, 'A-0004', 25, '2025-04-23 21:00:00', 'credit', 0, 'single', 0, 0, '', 1, '2025-04-24 19:27:50', '2025-04-24 19:27:59', 0, 16000, 0, 0, NULL),
(212, 'A-0005', 25, '2025-04-23 21:00:00', 'credit', 0, 'single', 0, 0, '', 1, '2025-04-24 19:31:31', '2025-04-24 19:31:46', 0, 180000, 0, 0, NULL),
(213, 'A-0006', 25, '2025-04-23 21:00:00', 'credit', 0, 'single', 0, 0, '', 1, '2025-04-24 19:38:48', '2025-04-24 19:38:56', 0, 16000, 0, 0, NULL),
(220, 'A-0007', 25, '2025-04-23 21:00:00', 'credit', 0, 'single', 0, 0, '', 1, '2025-04-24 20:37:03', '2025-04-24 20:49:01', 0, 40000, 0, 0, NULL),
(221, 'A-0008', 25, '2025-04-23 21:00:00', 'credit', 0, 'single', 0, 0, '', 1, '2025-04-24 20:49:59', '2025-04-24 20:53:01', 0, 16000, 0, 0, NULL),
(224, 'A-0010', 25, '2025-04-21 21:00:00', 'cash', 0, 'single', 0, 0, '', 1, '2025-04-24 21:08:58', '2025-04-24 21:10:33', 8000, 0, 0, 0, NULL),
(225, 'A-0011', 25, '2025-04-22 21:00:00', 'credit', 0, 'single', 0, 0, '', 1, '2025-04-24 21:19:20', '2025-04-24 21:20:02', 0, 28000, 0, 0, NULL),
(226, 'A-0012', 23, '2025-04-23 21:00:00', 'cash', 0, 'single', 0, 0, '', 1, '2025-04-24 22:01:37', '2025-04-24 22:01:37', 26000, 0, 0, 0, NULL),
(227, 'A-0013', 23, '2025-04-24 21:00:00', 'cash', 0, 'single', 0, 0, '', 1, '2025-04-25 07:19:20', '2025-04-25 07:19:20', 160000, 0, 0, 0, NULL),
(228, 'A-0014', 23, '2025-04-24 21:00:00', 'cash', 0, 'single', 0, 0, '', 1, '2025-04-25 07:20:33', '2025-04-25 07:20:33', 16000, 0, 0, 1, 'هەولێر'),
(229, 'A-0014', 23, '2025-04-24 21:00:00', 'credit', 0, 'single', 0, 0, '', 1, '2025-04-25 07:21:48', '2025-04-25 07:21:48', 0, 10000, 0, 0, NULL),
(230, 'A-0015', 24, '2025-04-24 21:00:00', 'cash', 0, 'single', 0, 0, '', 1, '2025-04-25 07:22:20', '2025-04-25 07:22:20', 10000, 0, 0, 1, 'هەولێڕ');

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
  `total_price` decimal(10,0) NOT NULL,
  `returned_quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_type`, `pieces_count`, `unit_price`, `total_price`, `returned_quantity`) VALUES
(309, 198, 69, 4, 'piece', 4, 4000, 16000, 0),
(310, 199, 69, 4, 'piece', 4, 4000, 16000, 0),
(316, 205, 69, 4, 'piece', 4, 4000, 16000, 0),
(317, 206, 68, 10, 'piece', 10, 2500, 25000, 0),
(320, 209, 68, 10, 'piece', 10, 2500, 25000, 0),
(321, 210, 68, 10, 'piece', 10, 2500, 25000, 0),
(322, 211, 69, 4, 'piece', 4, 4000, 16000, 0),
(323, 212, 69, 45, 'piece', 45, 4000, 180000, 0),
(324, 213, 69, 4, 'piece', 4, 4000, 16000, 0),
(331, 220, 69, 10, 'piece', 10, 4000, 40000, 0),
(332, 221, 69, 4, 'piece', 4, 4000, 16000, 0),
(335, 224, 69, 2, 'piece', 2, 4000, 8000, 0),
(336, 225, 69, 7, 'piece', 7, 4000, 28000, 2),
(337, 226, 69, 5, 'piece', 5, 4000, 20000, 0),
(338, 226, 77, 4, 'piece', 4, 1500, 6000, 0),
(339, 227, 69, 4, 'box', 40, 40000, 160000, 0),
(340, 228, 69, 4, 'piece', 4, 4000, 16000, 0),
(341, 229, 68, 4, 'piece', 4, 2500, 10000, 0),
(342, 230, 68, 4, 'piece', 4, 2500, 10000, 0);

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
(8, 'دارا ', '07709240894', '', 735000, 0, '', '2025-04-20 09:26:09', '2025-04-24 17:38:53'),
(9, 'قەیوان', '07708540838', '', 13000, 0, '', '2025-04-22 17:17:43', '2025-04-24 21:17:32');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_debt_transactions`
--

CREATE TABLE `supplier_debt_transactions` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `amount` decimal(10,0) NOT NULL DEFAULT 0 COMMENT 'Positive: debt to supplier increased, Negative: debt to supplier decreased',
  `transaction_type` enum('purchase','payment','return','supplier_payment','manual_adjustment','supplier_return','supplier_advance','advance_used') NOT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID from purchases or manual entry',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_debt_transactions`
--

INSERT INTO `supplier_debt_transactions` (`id`, `supplier_id`, `amount`, `transaction_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES
(100, 8, 750000, 'purchase', 131, '', 1, '2025-04-24 17:35:30'),
(101, 8, -15000, 'return', 1, 'Test return - Test return', NULL, '2025-04-24 17:38:53'),
(102, 9, 1000, 'purchase', 134, '', 1, '2025-04-24 18:16:27'),
(103, 9, 12000, 'purchase', 135, '', NULL, '2025-04-24 21:17:32');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `is_piece` tinyint(1) DEFAULT 1,
  `is_box` tinyint(1) DEFAULT 0,
  `is_set` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`, `is_piece`, `is_box`, `is_set`) VALUES
(1, 'دانە', 1, 0, 0),
(2, 'دانە و کارتۆن', 1, 1, 0),
(3, 'دانە و کارتۆن و سێت', 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `wastings`
--

CREATE TABLE `wastings` (
  `id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wastings`
--

INSERT INTO `wastings` (`id`, `date`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(16, '2025-04-23 21:00:00', '', 1, '2025-04-24 20:50:12', '2025-04-24 20:50:12');

-- --------------------------------------------------------

--
-- Table structure for table `wasting_items`
--

CREATE TABLE `wasting_items` (
  `id` int(11) NOT NULL,
  `wasting_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_type` enum('piece','box','set') NOT NULL DEFAULT 'piece',
  `pieces_count` int(11) NOT NULL COMMENT 'Actual number of pieces wasted',
  `unit_price` decimal(10,0) NOT NULL,
  `total_price` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wasting_items`
--

INSERT INTO `wasting_items` (`id`, `wasting_id`, `product_id`, `quantity`, `unit_type`, `pieces_count`, `unit_price`, `total_price`) VALUES
(16, 16, 69, 4, 'piece', 4, 3000, 12000);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_accounts`
--
ALTER TABLE `admin_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `debt_transactions`
--
ALTER TABLE `debt_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `employee_payments`
--
ALTER TABLE `employee_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_count`
--
ALTER TABLE `inventory_count`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_count_items`
--
ALTER TABLE `inventory_count_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `count_id` (`count_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `product_returns`
--
ALTER TABLE `product_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receipt_id` (`receipt_id`),
  ADD KEY `receipt_type` (`receipt_type`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_id` (`purchase_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `return_items`
--
ALTER TABLE `return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `return_id` (`return_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier_debt_transactions`
--
ALTER TABLE `supplier_debt_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wastings`
--
ALTER TABLE `wastings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wasting_items`
--
ALTER TABLE `wasting_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wasting_id` (`wasting_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_accounts`
--
ALTER TABLE `admin_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `debt_transactions`
--
ALTER TABLE `debt_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=239;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `employee_payments`
--
ALTER TABLE `employee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=475;

--
-- AUTO_INCREMENT for table `inventory_count`
--
ALTER TABLE `inventory_count`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_count_items`
--
ALTER TABLE `inventory_count_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `product_returns`
--
ALTER TABLE `product_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `return_items`
--
ALTER TABLE `return_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=231;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=343;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `supplier_debt_transactions`
--
ALTER TABLE `supplier_debt_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wastings`
--
ALTER TABLE `wastings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `wasting_items`
--
ALTER TABLE `wasting_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `debt_transactions`
--
ALTER TABLE `debt_transactions`
  ADD CONSTRAINT `debt_transactions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `employee_payments`
--
ALTER TABLE `employee_payments`
  ADD CONSTRAINT `employee_payments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `inventory_count_items`
--
ALTER TABLE `inventory_count_items`
  ADD CONSTRAINT `inventory_count_items_ibfk_1` FOREIGN KEY (`count_id`) REFERENCES `inventory_count` (`id`),
  ADD CONSTRAINT `inventory_count_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`);

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`),
  ADD CONSTRAINT `purchase_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `return_items`
--
ALTER TABLE `return_items`
  ADD CONSTRAINT `return_items_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `product_returns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `return_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `supplier_debt_transactions`
--
ALTER TABLE `supplier_debt_transactions`
  ADD CONSTRAINT `supplier_debt_transactions_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `wasting_items`
--
ALTER TABLE `wasting_items`
  ADD CONSTRAINT `wasting_items_ibfk_1` FOREIGN KEY (`wasting_id`) REFERENCES `wastings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wasting_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
