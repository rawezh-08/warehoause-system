-- Drop existing procedures
DROP PROCEDURE IF EXISTS `add_sale`;
DROP PROCEDURE IF EXISTS `add_sale_with_advance`;

-- Recreate add_sale procedure with phone_number parameter
DELIMITER $$
CREATE PROCEDURE `add_sale`(
    IN p_invoice_number VARCHAR(50),
    IN p_customer_id INT,
    IN p_date VARCHAR(50),
    IN p_payment_type ENUM('cash', 'credit'),
    IN p_discount DECIMAL(10,0),
    IN p_paid_amount DECIMAL(10,0),
    IN p_price_type ENUM('single', 'wholesale'),
    IN p_shipping_cost DECIMAL(10,0),
    IN p_other_costs DECIMAL(10,0),
    IN p_notes TEXT,
    IN p_created_by INT,
    IN p_products JSON,
    IN p_is_delivery TINYINT(1),
    IN p_delivery_address TEXT,
    IN p_phone_number VARCHAR(20)
)
BEGIN
    DECLARE v_sale_id INT;
    DECLARE i INT DEFAULT 0;
    DECLARE v_product_count INT;
    
    -- Insert sale record
    INSERT INTO sales (
        invoice_number, customer_id, date, payment_type, 
        discount, price_type, shipping_cost, other_costs,
        notes, created_by, is_delivery, delivery_address, phone_number
    ) VALUES (
        p_invoice_number, p_customer_id, p_date, p_payment_type, 
        p_discount, p_price_type, p_shipping_cost, p_other_costs,
        p_notes, p_created_by, p_is_delivery, p_delivery_address, p_phone_number
    );
    
    SET v_sale_id = LAST_INSERT_ID();
    
    -- Get number of products
    SET v_product_count = JSON_LENGTH(p_products);
    
    -- Process each product
    WHILE i < v_product_count DO
        CALL process_sale_item(
            v_sale_id,
            JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', i, '].product_id'))),
            JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', i, '].quantity'))),
            JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', i, '].unit_type'))),
            JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', i, '].unit_price')))
        );
        
        SET i = i + 1;
    END WHILE;
    
    -- Return the sale ID
    SELECT v_sale_id AS result;
END$$
DELIMITER ;

-- Recreate add_sale_with_advance procedure with phone_number parameter
DELIMITER $$
CREATE PROCEDURE `add_sale_with_advance`(
    IN p_invoice_number VARCHAR(50),
    IN p_customer_id INT,
    IN p_date VARCHAR(50),
    IN p_payment_type ENUM('cash', 'credit'),
    IN p_discount DECIMAL(10,0),
    IN p_paid_amount DECIMAL(10,0),
    IN p_price_type ENUM('single', 'wholesale'),
    IN p_shipping_cost DECIMAL(10,0),
    IN p_other_costs DECIMAL(10,0),
    IN p_notes TEXT,
    IN p_created_by INT,
    IN p_products JSON,
    IN p_is_delivery TINYINT(1),
    IN p_delivery_address TEXT,
    IN p_phone_number VARCHAR(20)
)
BEGIN
    DECLARE v_sale_id INT;
    DECLARE i INT DEFAULT 0;
    DECLARE v_product_count INT;
    DECLARE v_total_amount DECIMAL(10,0) DEFAULT 0;
    DECLARE v_remaining_amount DECIMAL(10,0);
    
    -- Insert sale record
    INSERT INTO sales (
        invoice_number, customer_id, date, payment_type, 
        discount, price_type, shipping_cost, other_costs,
        notes, created_by, paid_amount, is_delivery, delivery_address, phone_number
    ) VALUES (
        p_invoice_number, p_customer_id, p_date, p_payment_type, 
        p_discount, p_price_type, p_shipping_cost, p_other_costs,
        p_notes, p_created_by, p_paid_amount, p_is_delivery, p_delivery_address, p_phone_number
    );
    
    SET v_sale_id = LAST_INSERT_ID();
    
    -- Get number of products
    SET v_product_count = JSON_LENGTH(p_products);
    
    -- Process each product
    WHILE i < v_product_count DO
        CALL process_sale_item(
            v_sale_id,
            JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', i, '].product_id'))),
            JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', i, '].quantity'))),
            JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', i, '].unit_type'))),
            JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', i, '].unit_price')))
        );
        
        SET v_total_amount = v_total_amount + (
            JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', i, '].quantity'))) * 
            JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', i, '].unit_price')))
        );
        
        SET i = i + 1;
    END WHILE;
    
    -- Calculate final amount
    SET v_total_amount = v_total_amount + p_shipping_cost + p_other_costs - p_discount;
    SET v_remaining_amount = v_total_amount - p_paid_amount;
    
    -- Update remaining amount
    UPDATE sales 
    SET remaining_amount = v_remaining_amount 
    WHERE id = v_sale_id;
    
    -- Add to debt transactions if needed
    IF v_remaining_amount > 0 AND p_customer_id IS NOT NULL THEN
        INSERT INTO debt_transactions (
            customer_id, amount, transaction_type, reference_id, notes, created_by
        ) VALUES (
            p_customer_id, v_remaining_amount, 'sale', v_sale_id, 'قەرزی فرۆشتن', p_created_by
        );
        
        -- Update customer debt
        UPDATE customers 
        SET debt_amount = debt_amount + v_remaining_amount 
        WHERE id = p_customer_id;
    END IF;
    
    -- Return the sale ID
    SELECT v_sale_id AS result;
END$$
DELIMITER ; 