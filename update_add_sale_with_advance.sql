DELIMITER $$

DROP PROCEDURE IF EXISTS `add_sale_with_advance`$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_sale_with_advance` (
    IN `p_invoice_number` VARCHAR(50), 
    IN `p_customer_id` INT, 
    IN `p_date` TIMESTAMP, 
    IN `p_payment_type` ENUM('cash','credit'), 
    IN `p_discount` DECIMAL(10,2), 
    IN `p_paid_amount` DECIMAL(10,2), 
    IN `p_price_type` ENUM('single','wholesale'), 
    IN `p_shipping_cost` DECIMAL(10,2), 
    IN `p_other_costs` DECIMAL(10,2), 
    IN `p_notes` TEXT, 
    IN `p_created_by` INT, 
    IN `p_products` JSON, 
    IN `p_is_delivery` TINYINT(1), 
    IN `p_delivery_address` TEXT,
    IN `p_phone_number` VARCHAR(20)
)   BEGIN
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
        notes, created_by, is_delivery, delivery_address, phone_number
    ) VALUES (
        p_invoice_number, p_customer_id, p_date, p_payment_type, 
        p_discount, p_paid_amount, p_price_type, p_shipping_cost, p_other_costs,
        p_notes, p_created_by, p_is_delivery, p_delivery_address, p_phone_number
    );
    
    SET sale_id = LAST_INSERT_ID();
    SET product_count = JSON_LENGTH(p_products);
    
    -- Process products
    WHILE i < product_count DO
        -- Extract product data from JSON
        SET v_product_id = JSON_EXTRACT(p_products, CONCAT('$[', i, '].product_id'));
        SET v_quantity = JSON_EXTRACT(p_products, CONCAT('$[', i, '].quantity'));
        SET v_unit_type = JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', i, '].unit_type')));
        SET v_unit_price = JSON_EXTRACT(p_products, CONCAT('$[', i, '].unit_price'));
        
        -- Get product details
        SELECT 
            pieces_per_box, 
            boxes_per_set,
            current_quantity 
        INTO 
            v_pieces_per_box, 
            v_boxes_per_set,
            v_available_quantity 
        FROM products 
        WHERE id = v_product_id;
        
        -- Calculate pieces count based on unit type
        IF v_unit_type = 'piece' THEN
            SET v_pieces_count = v_quantity;
        ELSEIF v_unit_type = 'box' THEN
            SET v_pieces_count = v_quantity * v_pieces_per_box;
        ELSEIF v_unit_type = 'set' THEN
            SET v_pieces_count = v_quantity * v_boxes_per_set * v_pieces_per_box;
        END IF;
        
        -- Calculate total price
        SET v_total_price = v_unit_price * v_quantity;
        SET total_sale_amount = total_sale_amount + v_total_price;
        
        -- Check if enough quantity is available
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

DELIMITER ; 