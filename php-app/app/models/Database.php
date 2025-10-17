<?php
class Database {
    private $pdo;
    private $stmt;
    private static $instance = null;
    private $transactionLevel = 0;
    
    private function __construct() {
        try {
            $config = require_once __DIR__ . '/../../config/database.php';
            
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $options);
            
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function query($sql, $params = []) {
        try {
            $this->stmt = $this->pdo->prepare($sql);
            $this->stmt->execute($params);
            return $this;
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage() . " - SQL: " . $sql);
        }
    }
    
    public function fetchAll() {
        return $this->stmt->fetchAll();
    }
    
    public function fetch() {
        return $this->stmt->fetch();
    }
    
    public function fetchColumn($column = 0) {
        return $this->stmt->fetchColumn($column);
    }
    
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    public function beginTransaction() {
        if ($this->transactionLevel === 0) {
            $this->pdo->beginTransaction();
        }
        $this->transactionLevel++;
        return true;
    }
    
    public function commit() {
        if ($this->transactionLevel > 0) {
            $this->transactionLevel--;
            if ($this->transactionLevel === 0) {
                return $this->pdo->commit();
            }
        }
        return false;
    }
    
    public function rollBack() {
        if ($this->transactionLevel > 0) {
            $this->transactionLevel = 0;
            return $this->pdo->rollBack();
        }
        return false;
    }
    
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return $this->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        $this->query($sql, $params);
        return $this->rowCount();
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $this->query($sql, $params);
        return $this->rowCount();
    }
    
    public function select($table, $columns = '*', $where = '', $params = [], $orderBy = '', $limit = '') {
        $sql = "SELECT {$columns} FROM {$table}";
        
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if (!empty($limit)) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function find($table, $id, $idColumn = 'id') {
        return $this->query("SELECT * FROM {$table} WHERE {$idColumn} = ?", [$id])->fetch();
    }
    
    public function exists($table, $where, $params = []) {
        $result = $this->query("SELECT 1 FROM {$table} WHERE {$where} LIMIT 1", $params)->fetch();
        return !empty($result);
    }
    
    public function count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$table}";
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        
        return $this->query($sql, $params)->fetchColumn();
    }
    
    public function paginate($table, $page = 1, $perPage = 15, $where = '', $params = [], $orderBy = 'id DESC') {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$table}";
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        $sql .= " ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";
        
        $data = $this->query($sql, $params)->fetchAll();
        $total = $this->count($table, $where, $params);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }
    
    public function getColumns($table) {
        $stmt = $this->pdo->query("DESCRIBE {$table}");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    public function close() {
        $this->pdo = null;
        self::$instance = null;
    }
}