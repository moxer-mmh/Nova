<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getUserById($userId) {
        $sql = "SELECT * FROM USERS WHERE user_id = :user_id";
        $stmt = $this->db->executeQuery($sql, [':user_id' => $userId]);
        return $this->db->fetchOne($stmt);
    }
    
    public function getUserByUsername($username) {
        $sql = "SELECT * FROM USERS WHERE username = :username";
        $stmt = $this->db->executeQuery($sql, [':username' => $username]);
        return $this->db->fetchOne($stmt);
    }
    
    public function getUserByEmail($email) {
        $sql = "SELECT * FROM USERS WHERE email = :email";
        $stmt = $this->db->executeQuery($sql, [':email' => $email]);
        return $this->db->fetchOne($stmt);
    }
    
    public function createUser($username, $email, $password, $firstName = '', $lastName = '', $isAdmin = false) {
        try {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Modified to use executeQuery instead of executeProcedure
            $sql = "INSERT INTO USERS (
                        user_id, 
                        username, 
                        password, 
                        email, 
                        first_name, 
                        last_name,
                        is_admin
                    ) VALUES (
                        users_seq.NEXTVAL, 
                        :username, 
                        :password, 
                        :email, 
                        :first_name, 
                        :last_name,
                        :is_admin
                    )";
            
            $params = [
                ':username' => $username,
                ':password' => $hashedPassword,
                ':email' => $email,
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':is_admin' => $isAdmin ? 1 : 0
            ];
            
            $stmt = $this->db->executeQuery($sql, $params);
            
            // Get the newly created user ID
            $sql = "SELECT users_seq.CURRVAL as user_id FROM DUAL";
            $idStmt = $this->db->executeQuery($sql);
            $idResult = $this->db->fetchOne($idStmt);
            
            return $idResult ? $idResult['USER_ID'] : false;
        } catch (Exception $e) {
            error_log('Error creating user: ' . $e->getMessage());
            return false;
        }
    }
    
    public function validateLogin($username, $password) {
        $user = $this->getUserByUsername($username);
        
        if (!$user) {
            error_log("User not found: $username");
            return false;
        }
        
        // Debug info - typically you'd remove this in production
        error_log("Validating login for: $username, password hash: " . substr($user['PASSWORD'], 0, 10) . "...");
        
        if (password_verify($password, $user['PASSWORD'])) {
            return $user;
        }
        
        error_log("Invalid password for: $username");
        return false;
    }
    
    public function updateProfile($userId, $firstName, $lastName, $address, $city, $postalCode, $country, $phone) {
        $sql = "UPDATE USERS SET
                first_name = :first_name,
                last_name = :last_name,
                address = :address,
                city = :city,
                postal_code = :postal_code,
                country = :country,
                phone = :phone,
                updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id";
                
        $params = [
            ':user_id' => $userId,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':address' => $address,
            ':city' => $city,
            ':postal_code' => $postalCode,
            ':country' => $country,
            ':phone' => $phone
        ];
        
        $this->db->executeQuery($sql, $params);
        return true;
    }
    
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $sql = "UPDATE USERS SET 
                password = :password,
                updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id";
                
        $this->db->executeQuery($sql, [
            ':user_id' => $userId,
            ':password' => $hashedPassword
        ]);
        
        return true;
    }

    /**
     * Get all users with pagination
     */
    public function getAllUsers($limit = 10, $offset = 0) {
        $sql = "SELECT * FROM (
                    SELECT a.*, ROWNUM rnum FROM (
                        SELECT * FROM USERS ORDER BY user_id
                    ) a WHERE ROWNUM <= :upper_limit
                ) WHERE rnum > :lower_limit";
                
        $params = [
            ':upper_limit' => $offset + $limit,
            ':lower_limit' => $offset
        ];
        
        $stmt = $this->db->executeQuery($sql, $params);
        return $this->db->fetchAll($stmt);
    }

    /**
     * Get total users count
     */
    public function getTotalUsersCount() {
        $sql = "SELECT COUNT(*) as total FROM USERS";
        $stmt = $this->db->executeQuery($sql);
        $result = $this->db->fetchOne($stmt);
        return $result['TOTAL'];
    }

    /**
     * Update user admin status
     */
    public function updateAdminStatus($userId, $isAdmin) {
        $sql = "UPDATE USERS SET is_admin = :is_admin WHERE user_id = :user_id";
        $params = [':user_id' => $userId, ':is_admin' => $isAdmin];
        $stmt = $this->db->executeQuery($sql, $params);
        return true;
    }
}
?>
