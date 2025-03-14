<?php
class Database {
    private static $instance = null;
    private $db;

    private function __construct() {
        try {
            $this->db = new SQLite3(__DIR__ . '/daycare.db');
            $this->db->enableExceptions(true);
        } catch (Exception $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->db;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            
            if ($stmt === false) {
                throw new Exception('Failed to prepare statement: ' . $this->db->lastErrorMsg());
            }
            
            foreach ($params as $param => $value) {
                $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
                if (is_null($value)) {
                    $type = SQLITE3_NULL;
                } elseif (is_float($value)) {
                    $type = SQLITE3_FLOAT;
                }
                
                if ($stmt->bindValue($param, $value, $type) === false) {
                    throw new Exception('Failed to bind parameter ' . $param);
                }
            }
            
            $result = $stmt->execute();
            if ($result === false) {
                throw new Exception('Failed to execute statement: ' . $this->db->lastErrorMsg());
            }
            
            return $result;
        } catch (Exception $e) {
            throw new Exception('Query failed: ' . $e->getMessage());
        }
    }

    public function lastInsertRowID() {
        return $this->db->lastInsertRowID();
    }

    public function escapeString($string) {
        return $this->db->escapeString($string);
    }
}
?>