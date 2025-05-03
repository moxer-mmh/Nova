<?php
require_once __DIR__ . '/../config/database.php';

class Book {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAllBooks($limit = 12, $offset = 0) {
        try {
            // Oracle doesn't support LIMIT/OFFSET directly, use ROWNUM
            if ($offset > 0) {
                $sql = "SELECT * FROM (
                            SELECT a.*, ROWNUM rnum FROM (
                                SELECT b.*, c.name as category_name 
                                FROM books b 
                                JOIN categories c ON b.category_id = c.category_id 
                                ORDER BY b.book_id DESC
                            ) a 
                            WHERE ROWNUM <= :upper_limit
                        ) WHERE rnum > :lower_limit";
                        
                $params = [
                    ':upper_limit' => $offset + $limit,
                    ':lower_limit' => $offset
                ];
            } else {
                // Simpler query when no offset needed
                $sql = "SELECT * FROM (
                            SELECT b.*, c.name as category_name 
                            FROM books b 
                            JOIN categories c ON b.category_id = c.category_id 
                            ORDER BY b.book_id DESC
                        ) WHERE ROWNUM <= :limit";
                        
                $params = [':limit' => $limit];
            }
            
            $stmt = $this->db->executeQuery($sql, $params);
            return $this->db->fetchAll($stmt);
        } catch (Exception $e) {
            error_log('Error in getAllBooks: ' . $e->getMessage());
            // Return empty array if there's an error
            return [];
        }
    }
    
    /**
     * Get book by ID
     */
    public function getBookById($bookId) {
        try {
            $sql = "SELECT b.*, c.name as category_name 
                    FROM books b
                    LEFT JOIN categories c ON b.category_id = c.category_id
                    WHERE b.book_id = :book_id";
            $stmt = $this->db->executeQuery($sql, [':book_id' => $bookId]);
            return $this->db->fetchOne($stmt);
        } catch (Exception $e) {
            error_log('Error in getBookById: ' . $e->getMessage());
            return false;
        }
    }
    
    public function searchBooks($query, $categoryId = null) {
        $params = [];
        $sql = "SELECT b.*, c.name as category_name FROM BOOKS b 
                LEFT JOIN CATEGORIES c ON b.category_id = c.category_id
                WHERE 1=1";
        
        if (!empty($query)) {
            $sql .= " AND (UPPER(b.title) LIKE UPPER(:query) OR UPPER(b.author) LIKE UPPER(:query) OR UPPER(b.description) LIKE UPPER(:query))";
            $params[':query'] = '%' . $query . '%';
        }
        
        if (!empty($categoryId)) {
            $sql .= " AND b.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }
        
        $sql .= " ORDER BY b.title";
        
        $stmt = $this->db->executeQuery($sql, $params);
        return $this->db->fetchAll($stmt);
    }
    
    public function getFeaturedBooks($limit = 4) {
        try {
            // Use a query that works with the FEATURED column
            $sql = "SELECT * FROM (
                        SELECT b.*, c.name as category_name 
                        FROM books b 
                        JOIN categories c ON b.category_id = c.category_id 
                        WHERE b.featured = 1
                        ORDER BY b.book_id DESC
                    ) WHERE ROWNUM <= :limit";
                    
            $params = [':limit' => $limit];
            
            $stmt = $this->db->executeQuery($sql, $params);
            $results = $this->db->fetchAll($stmt);
            
            // Fall back to regular books if no featured books found
            if (empty($results)) {
                return $this->getAllBooks($limit, 0);
            }
            
            return $results;
        } catch (Exception $e) {
            // Try a simpler query without the FEATURED filter in case of error
            error_log('Error in getFeaturedBooks: ' . $e->getMessage());
            
            try {
                $sql = "SELECT * FROM (
                            SELECT b.*, c.name as category_name 
                            FROM books b 
                            JOIN categories c ON b.category_id = c.category_id 
                            ORDER BY b.book_id DESC
                        ) WHERE ROWNUM <= :limit";
                        
                $params = [':limit' => $limit];
                
                $stmt = $this->db->executeQuery($sql, $params);
                return $this->db->fetchAll($stmt);
            } catch (Exception $e2) {
                error_log('Error in fallback getFeaturedBooks: ' . $e2->getMessage());
                return [];
            }
        }
    }
    
    public function getCategories() {
        $sql = "SELECT * FROM CATEGORIES ORDER BY name";
        $stmt = $this->db->executeQuery($sql);
        return $this->db->fetchAll($stmt);
    }
    
    /**
     * Create a new book
     */
    public function createBook($bookData) {
        try {
            $sql = "INSERT INTO BOOKS (
                        book_id,
                        title,
                        author,
                        category_id,
                        isbn,
                        publisher,
                        publication_date,
                        description,
                        price,
                        stock,
                        image_url,
                        featured,
                        created_at,
                        updated_at
                    ) VALUES (
                        books_seq.NEXTVAL,
                        :title,
                        :author,
                        :category_id,
                        :isbn,
                        :publisher,
                        TO_DATE(:publication_date, 'YYYY-MM-DD'),
                        :description,
                        :price,
                        :stock,
                        :image_url,
                        :featured,
                        CURRENT_TIMESTAMP,
                        CURRENT_TIMESTAMP
                    )";
                    
            $params = [
                ':title' => $bookData['title'],
                ':author' => $bookData['author'],
                ':category_id' => $bookData['category_id'],
                ':isbn' => $bookData['isbn'],
                ':publisher' => $bookData['publisher'],
                ':publication_date' => $bookData['publication_date'],
                ':description' => $bookData['description'],
                ':price' => $bookData['price'],
                ':stock' => $bookData['stock'],
                ':image_url' => $bookData['image_url'],
                ':featured' => $bookData['featured']
            ];
            
            $stmt = $this->db->executeQuery($sql, $params);
            
            // Get the new book ID
            $sql = "SELECT books_seq.CURRVAL as book_id FROM DUAL";
            $idStmt = $this->db->executeQuery($sql);
            $idResult = $this->db->fetchOne($idStmt);
            
            return $idResult ? $idResult['BOOK_ID'] : false;
        } catch (Exception $e) {
            error_log('Error creating book: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update an existing book
     */
    public function updateBook($bookId, $bookData) {
        try {
            // Build the SQL query dynamically to handle nullable fields
            $sqlParts = [];
            $params = [':book_id' => $bookId];
            
            // Add each field to be updated
            $fields = [
                'title' => 'title',
                'author' => 'author',
                'category_id' => 'category_id',
                'isbn' => 'isbn',
                'publisher' => 'publisher',
                'price' => 'price',
                'stock' => 'stock', 
                'image_url' => 'image_url',
                'featured' => 'featured',
                'description' => 'description'
            ];
            
            foreach ($fields as $field => $paramName) {
                if (isset($bookData[$field])) {
                    $sqlParts[] = "{$field} = :{$paramName}";
                    $params[":{$paramName}"] = $bookData[$field];
                }
            }
            
            // Handle publication date separately due to Oracle date format
            if (!empty($bookData['publication_date'])) {
                $sqlParts[] = "publication_date = TO_DATE(:publication_date, 'YYYY-MM-DD')";
                $params[':publication_date'] = $bookData['publication_date'];
            }
            
            // Always update updated_at timestamp
            $sqlParts[] = "updated_at = CURRENT_TIMESTAMP";
            
            // Build the final SQL
            $sql = "UPDATE BOOKS SET " . implode(", ", $sqlParts) . " WHERE book_id = :book_id";
            
            $stmt = $this->db->executeQuery($sql, $params);
            return true;
        } catch (Exception $e) {
            error_log('Error updating book: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a book
     */
    public function deleteBook($bookId) {
        try {
            // Check if book is in any orders
            $sql = "SELECT COUNT(*) as count FROM ORDER_ITEMS WHERE book_id = :book_id";
            $stmt = $this->db->executeQuery($sql, [':book_id' => $bookId]);
            $result = $this->db->fetchOne($stmt);
            
            if ($result['COUNT'] > 0) {
                // Book is in orders, don't delete but set stock to 0
                $sql = "UPDATE BOOKS SET stock = 0 WHERE book_id = :book_id";
                $stmt = $this->db->executeQuery($sql, [':book_id' => $bookId]);
                return 'inactive';
            }
            
            // Safe to delete
            $sql = "DELETE FROM BOOKS WHERE book_id = :book_id";
            $stmt = $this->db->executeQuery($sql, [':book_id' => $bookId]);
            return true;
        } catch (Exception $e) {
            error_log('Error deleting book: ' . $e->getMessage());
            return false;
        }
    }
    
    public function addBook($title, $author, $categoryId, $isbn, $publisher, $publicationDate, $description, $price, $stock, $imageUrl) {
        $sql = "INSERT INTO BOOKS (
                    book_id, title, author, category_id, isbn, publisher, 
                    publication_date, description, price, stock, image_url
                ) VALUES (
                    books_seq.NEXTVAL, :title, :author, :category_id, :isbn, :publisher,
                    TO_DATE(:pub_date, 'YYYY-MM-DD'), :description, :price, :stock, :image_url
                ) RETURNING book_id INTO :book_id";
        
        $bookId = 0;
        $params = [
            ':title' => $title,
            ':author' => $author,
            ':category_id' => $categoryId,
            ':isbn' => $isbn,
            ':publisher' => $publisher,
            ':pub_date' => $publicationDate,
            ':description' => $description,
            ':price' => $price,
            ':stock' => $stock,
            ':image_url' => $imageUrl,
            ':book_id' => &$bookId
        ];
        
        $this->db->executeQuery($sql, $params);
        return $bookId;
    }
    
    public function updateBookStock($bookId, $quantity) {
        $sql = "UPDATE BOOKS SET stock = stock + :quantity WHERE book_id = :book_id";
        $this->db->executeQuery($sql, [
            ':book_id' => $bookId,
            ':quantity' => $quantity
        ]);
    }

    public function getTotalBooksCount() {
        $stmt = $this->db->executeQuery("SELECT COUNT(*) as total FROM BOOKS");
        $result = $this->db->fetchOne($stmt);
        return $result['TOTAL'];
    }
}
?>
