/*
 * Procédures stockées pour Nova Bookstore
 * Ce fichier contient toutes les procédures stockées nécessaires
 * pour le fonctionnement de l'application
 */

-- 1. Procédure pour afficher les détails d'une commande et le total à payer
CREATE OR REPLACE PROCEDURE get_order_details(
    p_order_id IN NUMBER,
    p_user_id IN NUMBER,
    p_details OUT SYS_REFCURSOR,
    p_items OUT SYS_REFCURSOR
)
AS
BEGIN
    -- Vérifier si la commande appartient à l'utilisateur ou si c'est un admin
    -- Ouvrir le curseur pour les détails de la commande
    OPEN p_details FOR
        SELECT o.order_id, o.total_amount, o.order_date, o.status,
               o.shipping_address, o.shipping_city, o.shipping_postal_code, o.shipping_country,
               o.payment_method, u.username, u.email
        FROM ORDERS o
        JOIN USERS u ON o.user_id = u.user_id
        WHERE o.order_id = p_order_id AND (o.user_id = p_user_id OR 
            EXISTS (SELECT 1 FROM USERS WHERE user_id = p_user_id AND is_admin = 1));
    
    -- Ouvrir le curseur pour les items de la commande
    OPEN p_items FOR
        SELECT oi.order_item_id, oi.book_id, oi.quantity, oi.price,
               b.title, b.author, b.image_url
        FROM ORDER_ITEMS oi
        JOIN BOOKS b ON oi.book_id = b.book_id
        WHERE oi.order_id = p_order_id;
END get_order_details;
/

-- 2. Procédure pour finaliser une commande et vider le panier
CREATE OR REPLACE PROCEDURE finalize_order(
    p_user_id IN NUMBER,
    p_cart_id IN NUMBER,
    p_shipping_address IN VARCHAR2,
    p_shipping_city IN VARCHAR2,
    p_shipping_postal_code IN VARCHAR2,
    p_shipping_country IN VARCHAR2,
    p_payment_method IN VARCHAR2,
    p_order_id OUT NUMBER
)
AS
    v_total_amount NUMBER := 0;
    v_insufficient_stock EXCEPTION;
    PRAGMA EXCEPTION_INIT(v_insufficient_stock, -20001);
BEGIN
    -- Calculer le montant total de la commande
    SELECT NVL(SUM(ci.quantity * b.price), 0) INTO v_total_amount
    FROM CART_ITEMS ci
    JOIN BOOKS b ON ci.book_id = b.book_id
    WHERE ci.cart_id = p_cart_id;
    
    -- Vérifier s'il y a des items dans le panier
    IF v_total_amount = 0 THEN
        RAISE_APPLICATION_ERROR(-20002, 'Le panier est vide');
    END IF;
    
    -- Vérifier le stock disponible pour chaque livre
    FOR item IN (SELECT ci.book_id, ci.quantity, b.stock, b.title
                 FROM CART_ITEMS ci
                 JOIN BOOKS b ON ci.book_id = b.book_id
                 WHERE ci.cart_id = p_cart_id) LOOP
        IF item.quantity > item.stock THEN
            RAISE_APPLICATION_ERROR(-20001, 'Stock insuffisant pour ' || item.title);
        END IF;
    END LOOP;
    
    -- Créer la commande
    INSERT INTO ORDERS (
        order_id,
        user_id,
        total_amount,
        order_date,
        status,
        shipping_address,
        shipping_city,
        shipping_postal_code,
        shipping_country,
        payment_method
    ) VALUES (
        orders_seq.NEXTVAL,
        p_user_id,
        v_total_amount,
        CURRENT_TIMESTAMP,
        'pending',
        p_shipping_address,
        p_shipping_city,
        p_shipping_postal_code,
        p_shipping_country,
        p_payment_method
    ) RETURNING order_id INTO p_order_id;
    
    -- Ajouter les items de commande
    INSERT INTO ORDER_ITEMS (
        order_item_id,
        order_id,
        book_id,
        quantity,
        price
    ) 
    SELECT 
        order_items_seq.NEXTVAL,
        p_order_id,
        ci.book_id,
        ci.quantity,
        b.price
    FROM CART_ITEMS ci
    JOIN BOOKS b ON ci.book_id = b.book_id
    WHERE ci.cart_id = p_cart_id;
    
    -- Mettre à jour le stock des livres (fait automatiquement par le trigger update_stock)
    
    -- Vider le panier
    DELETE FROM CART_ITEMS WHERE cart_id = p_cart_id;
    
    COMMIT;
EXCEPTION
    WHEN v_insufficient_stock THEN
        ROLLBACK;
        RAISE;
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END finalize_order;
/

-- 3. Procédure pour afficher l'historique des commandes d'un client
CREATE OR REPLACE PROCEDURE get_user_orders(
    p_user_id IN NUMBER,
    p_is_admin IN NUMBER DEFAULT 0,
    p_target_user_id IN NUMBER DEFAULT NULL,
    p_cursor OUT SYS_REFCURSOR
)
AS
    v_user_id NUMBER;
BEGIN
    -- Si c'est un admin qui fait la requête pour un autre utilisateur
    IF p_is_admin = 1 AND p_target_user_id IS NOT NULL THEN
        v_user_id := p_target_user_id;
    ELSE
        v_user_id := p_user_id;
    END IF;
    
    -- Ouvrir le curseur pour l'historique des commandes
    OPEN p_cursor FOR
        SELECT o.order_id, o.total_amount, o.order_date, o.status,
               o.shipping_address, o.shipping_city, o.shipping_postal_code,
               o.shipping_country, o.payment_method,
               (SELECT COUNT(*) FROM ORDER_ITEMS oi WHERE oi.order_id = o.order_id) as item_count
        FROM ORDERS o
        WHERE o.user_id = v_user_id
        ORDER BY o.order_date DESC;
END get_user_orders;
/
