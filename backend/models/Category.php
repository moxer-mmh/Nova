<?php
require_once __DIR__ . '/../config/database.php';

class Category {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAllCategories() {
        $sql = "SELECT * FROM CATEGORIES ORDER BY name";
        $stmt = $this->db->executeQuery($sql);
        return $this->db->fetchAll($stmt);
    }
    
    public function getCategoryById($categoryId) {
        $sql = "SELECT * FROM CATEGORIES WHERE category_id = :category_id";
        $stmt = $this->db->executeQuery($sql, [':category_id' => $categoryId]);
        return $this->db->fetchOne($stmt);
    }
    
    public function createCategory($name) {
        $sql = "INSERT INTO CATEGORIES (category_id, name) 
                VALUES (categories_seq.NEXTVAL, :name)";
        $params = [':name' => $name];
        $stmt = $this->db->executeQuery($sql, $params);
        
        // Get the new category ID
        $sql = "SELECT categories_seq.CURRVAL as category_id FROM DUAL";
        $idStmt = $this->db->executeQuery($sql);
        $idResult = $this->db->fetchOne($idStmt);
        
        return $idResult ? $idResult['CATEGORY_ID'] : false;
    }
    
    public function updateCategory($categoryId, $name) {
        $sql = "UPDATE CATEGORIES SET name = :name WHERE category_id = :category_id";
        $params = [':category_id' => $categoryId, ':name' => $name];
        $stmt = $this->db->executeQuery($sql, $params);
        return true;
    }
    
    public function deleteCategory($categoryId) {
        // Check if category is used
        $sql = "SELECT COUNT(*) as count FROM BOOKS WHERE category_id = :category_id";
        $stmt = $this->db->executeQuery($sql, [':category_id' => $categoryId]);
        $result = $this->db->fetchOne($stmt);
        
        if ($result['COUNT'] > 0) {
            return false; // Cannot delete category with books
        }
        
        // Delete category
        $sql = "DELETE FROM CATEGORIES WHERE category_id = :category_id";
        $stmt = $this->db->executeQuery($sql, [':category_id' => $categoryId]);
        return true;
    }
}
