<?php
class Database {
    private $conn;
    private static $instance = null;
    
    private function __construct() {
        $this->connect();
    }
    
    // Singleton pattern
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $username = "Nova";
            $password = "nova1234";
            $connection_string = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=XE)))";
            
            // Connexion à Oracle avec OCI
            $this->conn = oci_connect($username, $password, $connection_string, 'UTF8');
            
            if (!$this->conn) {
                $e = oci_error();
                throw new Exception("Connection failed: " . $e['message']);
            }
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function executeQuery($sql, $params = []) {
        try {
            // Prepare the SQL statement
            $stmt = oci_parse($this->conn, $sql);
            
            if (!$stmt) {
                $error = oci_error($this->conn);
                throw new Exception("Failed to prepare SQL: " . $error['message']);
            }
            
            // Bind parameters
            foreach ($params as $param => $value) {
                // Make sure to use proper parameter names for Oracle
                $paramName = ltrim($param, ':'); // Remove colon from parameter names
                oci_bind_by_name($stmt, $param, $params[$param]);
            }
            
            // Execute the statement
            $result = oci_execute($stmt);
            
            if (!$result) {
                $error = oci_error($stmt);
                throw new Exception("Failed to execute SQL: " . $error['message']);
            }
            
            return $stmt;
        } catch (Exception $e) {
            // Log the error
            error_log("Database Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function fetchAll($stmt) {
        $rows = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    public function fetchOne($stmt) {
        return oci_fetch_assoc($stmt);
    }
    
    public function executeStoredProcedure($procedureName, $params = [], $outCursor = false) {
        $sql = "BEGIN $procedureName(";
        
        $bindParams = [];
        $paramPlaceholders = [];
        
        foreach ($params as $key => $value) {
            $paramPlaceholders[] = ":" . $key;
            $bindParams[$key] = $value;
        }
        
        if ($outCursor) {
            $paramPlaceholders[] = ":p_cursor";
        }
        
        $sql .= implode(", ", $paramPlaceholders);
        $sql .= "); END;";
        
        $stmt = oci_parse($this->conn, $sql);
        
        if (!$stmt) {
            $e = oci_error($this->conn);
            throw new Exception("Failed to parse procedure: " . $e['message']);
        }
        
        foreach ($bindParams as $key => $value) {
            oci_bind_by_name($stmt, ":" . $key, $bindParams[$key]);
        }
        
        if ($outCursor) {
            $cursor = oci_new_cursor($this->conn);
            oci_bind_by_name($stmt, ":p_cursor", $cursor, -1, OCI_B_CURSOR);
        }
        
        $success = oci_execute($stmt);
        
        if (!$success) {
            $e = oci_error($stmt);
            throw new Exception("Failed to execute procedure: " . $e['message']);
        }
        
        if ($outCursor) {
            oci_execute($cursor);
            $results = [];
            while ($row = oci_fetch_assoc($cursor)) {
                $results[] = $row;
            }
            oci_free_statement($cursor);
            oci_free_statement($stmt);
            return $results;
        }
        
        oci_free_statement($stmt);
        return true;
    }
}
?>
