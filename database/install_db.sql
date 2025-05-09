-- Run this script to initialize the Nova Gaming database with tables and sample data

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
        o.user_id,
        o.order_date,
        u.username,
        u.full_name,
        p.name AS product_name,
        oi.quantity,
        oi.price_per_unit,
        (oi.quantity * oi.price_per_unit) AS item_total,
        o.total_amount,
        o.status
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

-- Drop the procedure if it exists first
DROP PROCEDURE IF EXISTS FinalizeOrder;

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
VALUES ('admin', '$2y$10$0Yh9FAGeP77hi6HQnFjc6OFFYXRpWyGvKHX6ujTOBeDf3IXJ6az6S', 'admin@example.com', 'Admin User', 1);
-- Password: admin123