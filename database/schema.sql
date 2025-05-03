/*
 * Schéma de la base de données Nova Bookstore
 * Ce fichier contient la structure complète de la base de données
 * avec les relations entre les tables
 */

-- Suppression des tables si elles existent déjà
BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE ORDER_ITEMS CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE CART_ITEMS CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE CANCELLED_ORDERS CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE ORDERS CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE CARTS CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE BOOKS CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE CATEGORIES CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE USERS CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

-- Suppression des séquences
BEGIN
   EXECUTE IMMEDIATE 'DROP SEQUENCE users_seq';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP SEQUENCE categories_seq';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP SEQUENCE books_seq';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP SEQUENCE carts_seq';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP SEQUENCE cart_items_seq';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP SEQUENCE orders_seq';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP SEQUENCE order_items_seq';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP SEQUENCE cancelled_orders_seq';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

-- Création des séquences
CREATE SEQUENCE users_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE categories_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE books_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE carts_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE cart_items_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE orders_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE order_items_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE cancelled_orders_seq START WITH 1 INCREMENT BY 1;

-- Création des tables
-- Table des utilisateurs
CREATE TABLE USERS (
    user_id NUMBER PRIMARY KEY,
    username VARCHAR2(50) NOT NULL UNIQUE,
    password VARCHAR2(255) NOT NULL,
    email VARCHAR2(100) NOT NULL UNIQUE,
    first_name VARCHAR2(50),
    last_name VARCHAR2(50),
    address VARCHAR2(200),
    city VARCHAR2(50),
    postal_code VARCHAR2(20),
    country VARCHAR2(50),
    phone VARCHAR2(20),
    is_admin NUMBER(1) DEFAULT 0 NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des catégories
CREATE TABLE CATEGORIES (
    category_id NUMBER PRIMARY KEY,
    name VARCHAR2(50) NOT NULL UNIQUE
);

-- Table des livres
CREATE TABLE BOOKS (
    book_id NUMBER PRIMARY KEY,
    title VARCHAR2(200) NOT NULL,
    author VARCHAR2(100) NOT NULL,
    category_id NUMBER,
    isbn VARCHAR2(20),
    publisher VARCHAR2(100),
    publication_date DATE,
    description CLOB,
    price NUMBER(10,2) NOT NULL,
    stock NUMBER DEFAULT 0,
    image_url VARCHAR2(255),
    featured NUMBER(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_book_category FOREIGN KEY (category_id) REFERENCES CATEGORIES(category_id)
);

-- Table des paniers
CREATE TABLE CARTS (
    cart_id NUMBER PRIMARY KEY,
    user_id NUMBER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES USERS(user_id)
);

-- Table des éléments du panier
CREATE TABLE CART_ITEMS (
    cart_item_id NUMBER PRIMARY KEY,
    cart_id NUMBER NOT NULL,
    book_id NUMBER NOT NULL,
    quantity NUMBER NOT NULL,
    CONSTRAINT fk_cart_item_cart FOREIGN KEY (cart_id) REFERENCES CARTS(cart_id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_item_book FOREIGN KEY (book_id) REFERENCES BOOKS(book_id)
);

-- Table des commandes
CREATE TABLE ORDERS (
    order_id NUMBER PRIMARY KEY,
    user_id NUMBER,
    total_amount NUMBER(10,2),
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR2(20) DEFAULT 'pending',
    shipping_address VARCHAR2(200),
    shipping_city VARCHAR2(50),
    shipping_postal_code VARCHAR2(20),
    shipping_country VARCHAR2(50),
    payment_method VARCHAR2(50),
    CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES USERS(user_id)
);

-- Table des éléments de commande
CREATE TABLE ORDER_ITEMS (
    order_item_id NUMBER PRIMARY KEY,
    order_id NUMBER NOT NULL,
    book_id NUMBER NOT NULL,
    quantity NUMBER NOT NULL,
    price NUMBER(10,2) NOT NULL,
    CONSTRAINT fk_order_item_order FOREIGN KEY (order_id) REFERENCES ORDERS(order_id) ON DELETE CASCADE,
    CONSTRAINT fk_order_item_book FOREIGN KEY (book_id) REFERENCES BOOKS(book_id)
);

-- Table des commandes annulées (historique)
CREATE TABLE CANCELLED_ORDERS (
    cancel_id NUMBER PRIMARY KEY,
    order_id NUMBER,
    user_id NUMBER,
    cancel_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason VARCHAR2(500),
    total_amount NUMBER(10,2),
    CONSTRAINT fk_cancel_order FOREIGN KEY (order_id) REFERENCES ORDERS(order_id),
    CONSTRAINT fk_cancel_user FOREIGN KEY (user_id) REFERENCES USERS(user_id)
);

-- Commentaire sur les relations entre tables
/*
RELATIONS:
1. Un UTILISATEUR peut avoir plusieurs PANIERS (relation 1:N)
2. Un PANIER appartient à un seul UTILISATEUR (relation N:1)
3. Un PANIER contient plusieurs ARTICLES DE PANIER (relation 1:N)
4. Un ARTICLE DE PANIER appartient à un seul PANIER (relation N:1)
5. Un ARTICLE DE PANIER référence un seul LIVRE (relation N:1)
6. Un UTILISATEUR peut avoir plusieurs COMMANDES (relation 1:N)
7. Une COMMANDE appartient à un seul UTILISATEUR (relation N:1)
8. Une COMMANDE contient plusieurs ARTICLES DE COMMANDE (relation 1:N)
9. Un ARTICLE DE COMMANDE appartient à une seule COMMANDE (relation N:1)
10. Un ARTICLE DE COMMANDE référence un seul LIVRE (relation N:1)
11. Un LIVRE appartient à une seule CATÉGORIE (relation N:1)
12. Une COMMANDE ANNULÉE référence une seule COMMANDE (relation 1:1)
13. Une COMMANDE ANNULÉE est liée à un seul UTILISATEUR (relation N:1)
*/
