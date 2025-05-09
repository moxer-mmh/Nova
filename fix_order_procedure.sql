-- First drop the existing procedure
DROP PROCEDURE IF EXISTS FinalizeOrder;

-- Then recreate it with the updated implementation
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
    
    -- Copy items from cart to order_items
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
