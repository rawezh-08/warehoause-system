-- Add is_business_partner flag to customers table
ALTER TABLE customers
ADD COLUMN is_business_partner TINYINT(1) DEFAULT 0,
ADD COLUMN supplier_id INT NULL,
ADD CONSTRAINT fk_customer_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL;

-- Add is_business_partner flag to suppliers table
ALTER TABLE suppliers
ADD COLUMN is_business_partner TINYINT(1) DEFAULT 0,
ADD COLUMN customer_id INT NULL,
ADD CONSTRAINT fk_supplier_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL;

-- Create index for better performance
CREATE INDEX idx_customers_business_partner ON customers(is_business_partner);
CREATE INDEX idx_suppliers_business_partner ON suppliers(is_business_partner); 