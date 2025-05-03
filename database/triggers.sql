/*
 * Triggers pour Nova Bookstore
 * Ce fichier contient tous les triggers nécessaires
 * pour le fonctionnement de l'application
 */

-- 1. Trigger pour mettre à jour le stock après validation d'une commande
CREATE OR REPLACE TRIGGER update_stock
AFTER INSERT ON ORDER_ITEMS
FOR EACH ROW
BEGIN
    -- Mettre à jour le stock du livre
    UPDATE BOOKS
    SET stock = stock - :NEW.quantity
    WHERE book_id = :NEW.book_id;
END;
/

-- 2. Trigger pour empêcher l'insertion d'une commande si la quantité demandée dépasse le stock disponible
CREATE OR REPLACE TRIGGER check_stock
BEFORE INSERT ON ORDER_ITEMS
FOR EACH ROW
DECLARE
    v_available_stock NUMBER;
BEGIN
    -- Récupérer le stock disponible
    SELECT stock INTO v_available_stock
    FROM BOOKS
    WHERE book_id = :NEW.book_id;
    
    -- Vérifier si le stock est suffisant
    IF :NEW.quantity > v_available_stock THEN
        RAISE_APPLICATION_ERROR(-20001, 'Stock insuffisant pour le livre ' || :NEW.book_id);
    END IF;
END;
/

-- 3. Trigger pour restaurer le stock après l'annulation d'une commande
CREATE OR REPLACE TRIGGER restore_stock_cancel
AFTER UPDATE OF status ON ORDERS
FOR EACH ROW
BEGIN
    IF :OLD.status != 'cancelled' AND :NEW.status = 'cancelled' THEN
        -- Ajouter à la table des commandes annulées
        INSERT INTO CANCELLED_ORDERS (
            cancel_id,
            order_id, 
            user_id, 
            cancel_date, 
            total_amount
        ) VALUES (
            cancelled_orders_seq.NEXTVAL,
            :NEW.order_id, 
            :NEW.user_id, 
            CURRENT_TIMESTAMP, 
            :NEW.total_amount
        );
        
        -- Restaurer le stock pour chaque livre dans la commande
        FOR item IN (SELECT book_id, quantity FROM ORDER_ITEMS WHERE order_id = :NEW.order_id) LOOP
            UPDATE BOOKS
            SET stock = stock + item.quantity
            WHERE book_id = item.book_id;
        END LOOP;
    END IF;
END;
/
