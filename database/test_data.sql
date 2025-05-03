/*
 * Données de test pour Nova Bookstore
 * Ce fichier contient des données de test pour l'application
 */

-- Insertion des catégories
INSERT INTO CATEGORIES (category_id, name) VALUES (categories_seq.NEXTVAL, 'Fiction');
INSERT INTO CATEGORIES (category_id, name) VALUES (categories_seq.NEXTVAL, 'Non-Fiction');
INSERT INTO CATEGORIES (category_id, name) VALUES (categories_seq.NEXTVAL, 'Science Fiction');
INSERT INTO CATEGORIES (category_id, name) VALUES (categories_seq.NEXTVAL, 'Mystery');
INSERT INTO CATEGORIES (category_id, name) VALUES (categories_seq.NEXTVAL, 'Biography');

-- Insertion des livres
INSERT INTO BOOKS (
    book_id, title, author, category_id, isbn, 
    publisher, publication_date, description, 
    price, stock, image_url, featured
) VALUES (
    books_seq.NEXTVAL, 
    'Le Petit Prince', 
    'Antoine de Saint-Exupéry', 
    1, 
    '9783140464086', 
    'Gallimard', 
    TO_DATE('1943-04-06', 'YYYY-MM-DD'), 
    'Le Petit Prince est une œuvre de langue française, la plus connue d''Antoine de Saint-Exupéry. Publié en 1943 à New York simultanément en anglais et en français, c''est un conte poétique et philosophique sous l''apparence d''un conte pour enfants.',
    12.99, 
    50, 
    'petit_prince.jpg',
    1
);

INSERT INTO BOOKS (
    book_id, title, author, category_id, isbn, 
    publisher, publication_date, description, 
    price, stock, image_url, featured
) VALUES (
    books_seq.NEXTVAL, 
    'L''Étranger', 
    'Albert Camus', 
    1, 
    '9782070360024', 
    'Gallimard', 
    TO_DATE('1942-05-19', 'YYYY-MM-DD'), 
    'L''Étranger est un roman d''Albert Camus, paru en 1942. Il prend place dans la tétralogie que Camus nommera « cycle de l''absurde » qui décrit les fondements de la philosophie camusienne : l''absurde.',
    10.50, 
    35, 
    'etranger.jpg',
    0
);

INSERT INTO BOOKS (
    book_id, title, author, category_id, isbn, 
    publisher, publication_date, description, 
    price, stock, image_url, featured
) VALUES (
    books_seq.NEXTVAL, 
    'Dune', 
    'Frank Herbert', 
    3, 
    '9782253011422', 
    'Robert Laffont', 
    TO_DATE('1965-08-01', 'YYYY-MM-DD'), 
    'Dune est un roman de science-fiction de Frank Herbert, publié en 1965. Il s''agit du premier roman du cycle de Dune. En 1966, ce livre a obtenu le prix Hugo et en 1965 le prix Nebula du meilleur roman.',
    14.90, 
    20, 
    'dune.jpg',
    1
);

-- Insertion d'un utilisateur administrateur (mot de passe: admin123)
INSERT INTO USERS (
    user_id, 
    username, 
    password, 
    email, 
    first_name, 
    last_name,
    is_admin
) VALUES (
    users_seq.NEXTVAL, 
    'admin', 
    '$2y$10$9YAIqmCsFtmLRwOiGk4OwOh4Xfc4HB8q6VZBOVJEjEZQnbNX.LM86', -- hashed version of 'admin123'
    'admin@nova-books.com', 
    'Admin', 
    'User',
    1
);

COMMIT;
