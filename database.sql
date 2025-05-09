-- Create the database
DROP DATABASE IF EXISTS nova_ecommerce;
CREATE DATABASE nova_ecommerce;
USE nova_ecommerce;

-- Create Users table
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Products table
CREATE TABLE Products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image_url VARCHAR(255) DEFAULT 'default.jpg',
    category VARCHAR(50) NOT NULL,
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Cart table
CREATE TABLE Cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
);

-- Create Orders table
CREATE TABLE Orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Create Order_Items table
CREATE TABLE Order_Items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price_per_unit DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE
);

-- Create Cancelled_Orders table for storing history
CREATE TABLE Cancelled_Orders (
    cancel_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    cancel_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) NOT NULL,
    reason TEXT
);

-- Procedure to show order details for a client
DELIMITER //
CREATE PROCEDURE GetOrderDetails(IN p_order_id INT)
BEGIN
    SELECT 
        o.order_id,
        o.order_date,
        u.username,
        u.full_name,
        p.name AS product_name,
        oi.quantity,
        oi.price_per_unit,
        (oi.quantity * oi.price_per_unit) AS item_total,
        o.total_amount
    FROM 
        Orders o
    JOIN 
        Users u ON o.user_id = u.user_id
    JOIN 
        Order_Items oi ON o.order_id = oi.order_id
    JOIN 
        Products p ON oi.product_id = p.product_id
    WHERE 
        o.order_id = p_order_id;
END //
DELIMITER ;

-- Procedure to finalize an order and empty the cart
DELIMITER //
CREATE PROCEDURE FinalizeOrder(IN p_user_id INT, OUT p_order_id INT)
BEGIN
    DECLARE v_total DECIMAL(10, 2);
    
    -- Calculate total amount from cart
    SELECT SUM(c.quantity * p.price) INTO v_total
    FROM Cart c
    JOIN Products p ON c.product_id = p.product_id
    WHERE c.user_id = p_user_id;
    
    -- Create new order
    INSERT INTO Orders (user_id, total_amount)
    VALUES (p_user_id, v_total);
    
    SET p_order_id = LAST_INSERT_ID();
    
    -- Copy items from cart to order_items and update product stock directly
    INSERT INTO Order_Items (order_id, product_id, quantity, price_per_unit)
    SELECT p_order_id, c.product_id, c.quantity, p.price
    FROM Cart c
    JOIN Products p ON c.product_id = p.product_id
    WHERE c.user_id = p_user_id;
    
    -- Update product stock directly in the procedure
    UPDATE Products p
    JOIN Cart c ON p.product_id = c.product_id
    SET p.stock = p.stock - c.quantity
    WHERE c.user_id = p_user_id;
    
    -- Empty the cart
    DELETE FROM Cart WHERE user_id = p_user_id;
END //
DELIMITER ;

-- Remove the problematic trigger
DROP TRIGGER IF EXISTS after_order_items_insert;

-- Procedure to display order history
DELIMITER //
CREATE PROCEDURE GetOrderHistory(IN p_user_id INT)
BEGIN
    SELECT 
        o.order_id,
        o.order_date,
        o.total_amount,
        o.status
    FROM 
        Orders o
    WHERE 
        o.user_id = p_user_id
    ORDER BY 
        o.order_date DESC;
END //
DELIMITER ;

-- Trigger to prevent order if stock is insufficient
DELIMITER //
CREATE TRIGGER before_cart_insert
BEFORE INSERT ON Cart
FOR EACH ROW
BEGIN
    DECLARE available_stock INT;
    
    SELECT stock INTO available_stock
    FROM Products
    WHERE product_id = NEW.product_id;
    
    IF NEW.quantity > available_stock THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock available';
    END IF;
END //
DELIMITER ;

-- Trigger to prevent cart update if stock is insufficient
DELIMITER //
CREATE TRIGGER before_cart_update
BEFORE UPDATE ON Cart
FOR EACH ROW
BEGIN
    DECLARE available_stock INT;
    
    SELECT stock INTO available_stock
    FROM Products
    WHERE product_id = NEW.product_id;
    
    IF NEW.quantity > available_stock THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock available';
    END IF;
END //
DELIMITER ;

-- Trigger to restore stock after order cancellation
DELIMITER //
CREATE TRIGGER after_order_cancel
BEFORE UPDATE ON Orders
FOR EACH ROW
BEGIN
    DECLARE v_reason TEXT DEFAULT 'Order cancelled by user';
    
    IF NEW.status = 'Cancelled' AND OLD.status != 'Cancelled' THEN
        -- Save to cancelled orders table
        INSERT INTO Cancelled_Orders (order_id, user_id, total_amount, reason)
        VALUES (OLD.order_id, OLD.user_id, OLD.total_amount, v_reason);
        
        -- Restore stock for all items in this order
        UPDATE Products p
        JOIN Order_Items oi ON p.product_id = oi.product_id
        SET p.stock = p.stock + oi.quantity
        WHERE oi.order_id = OLD.order_id;
    END IF;
END //
DELIMITER ;

-- Insert sample admin user
INSERT INTO Users (username, password, email, full_name, is_admin) 
VALUES ('admin', '$2y$10$8jnbBCL5xdYrwMHVYCnjMO2lDq.ZSyHFI0TpYw5nQF3gDrP0MMd9K', 'admin@example.com', 'Admin User', 1);
-- Password: admin123

-- Insert sample regular user
INSERT INTO Users (username, password, email, full_name, is_admin) 
VALUES ('user', '$2y$10$qLy6vbsldPhRRuHRGn0kw.o7Z7AQhvYzQMoaUvwkLVcn3pjL96ex6', 'user@example.com', 'Regular User', 0);
-- Password: user123

-- Insert sample products
INSERT INTO Products (name, description, price, stock, image_url, category, featured) VALUES
('Gaming Laptop', 'High-performance gaming laptop with RTX 3080', 1299.99, 10, 'laptop1.jpg', 'Electronics', TRUE),
('Mechanical Keyboard', 'RGB mechanical keyboard with Cherry MX switches', 129.99, 50, 'keyboard1.jpg', 'Accessories', TRUE),
('Wireless Mouse', 'High-precision wireless gaming mouse', 79.99, 30, 'mouse1.jpg', 'Accessories', TRUE),
('Gaming Monitor', '27-inch 4K gaming monitor with 144Hz refresh rate', 499.99, 15, 'monitor1.jpg', 'Electronics', FALSE),
('Gaming Chair', 'Ergonomic gaming chair with lumbar support', 249.99, 20, 'chair1.jpg', 'Furniture', FALSE),
('PC Case', 'Mid-tower gaming PC case with tempered glass', 89.99, 25, 'case1.jpg', 'Components', FALSE),
('Gaming Headset', 'Surround sound gaming headset with noise-cancelling mic', 99.99, 40, 'headset1.jpg', 'Audio', FALSE),
('SSD 1TB', 'High-speed 1TB solid state drive', 149.99, 35, 'ssd1.jpg', 'Storage', FALSE),
('Gaming Controller', 'Wireless gaming controller for PC and consoles', 59.99, 45, 'controller1.jpg', 'Accessories', FALSE),
('Graphics Card', 'High-end gaming graphics card', 699.99, 8, 'gpu1.jpg', 'Components', FALSE);
