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
                $type = $this->getParamType($value);
                if (is_int($value)) {
                    $stmt->bindValue($param, $value, SQLITE3_INTEGER);
                } elseif (is_float($value)) {
                    $stmt->bindValue($param, $value, SQLITE3_FLOAT);
                } else {
                    $stmt->bindValue($param, $value, SQLITE3_TEXT);
                }
            }

            $result = $stmt->execute();
            
            if ($result === false) {
                throw new Exception('Failed to execute statement: ' . $this->db->lastErrorMsg());
            }

            return $result;
        } catch (Exception $e) {
            error_log('Database query error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getParamType($value) {
        if (is_int($value)) return SQLITE3_INTEGER;
        if (is_float($value)) return SQLITE3_FLOAT;
        return SQLITE3_TEXT;
    }

    public function lastInsertRowID() {
        return $this->db->lastInsertRowID();
    }

    public function close() {
        if ($this->db) {
            $this->db->close();
        }
    }
}
?>