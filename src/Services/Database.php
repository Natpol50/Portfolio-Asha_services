<?php

namespace App\Services;

use App\Config\ConfigManager;
use App\Exceptions\DatabaseException;

/**
 * Database - Database connection and query service
 * 
 * This class manages the database connection and provides
 * methods for executing SQL queries.
 */
class Database
{
    private ?\PDO $connection = null;
    private array $config;
    
    /**
     * Create a new Database instance
     */
    public function __construct()
    {
        // Get database configuration
        $configManager = ConfigManager::getInstance();
        $this->config = $configManager->getConfigFor($this)->all();
    }
    
    /**
     * Get database connection
     * 
     * @return \PDO Database connection
     * @throws DatabaseException If connection fails
     */
    public function getConnection(): \PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        
        return $this->connection;
    }
    
    /**
     * Connect to the database
     * 
     * @throws DatabaseException If connection fails
     */
    private function connect(): void
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $this->config['DB_HOST'],
                $this->config['DB_PORT'],
                $this->config['DB_NAME']
            );
            
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new \PDO(
                $dsn,
                $this->config['DB_USER'],
                $this->config['DB_PASSWORD'],
                $options
            );
        } catch (\PDOException $e) {
            throw new DatabaseException('Database connection error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Execute a query with parameters
     * 
     * @param string $sql SQL query
     * @param array $params Parameters for the query
     * @return \PDOStatement PDO statement
     * @throws DatabaseException If query fails
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            throw new DatabaseException('Query error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Get a single row from a query
     * 
     * @param string $sql SQL query
     * @param array $params Parameters for the query
     * @return object|null Single row or null if not found
     * @throws DatabaseException If query fails
     */
    public function fetchOne(string $sql, array $params = []): ?object
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }
    
    /**
     * Get multiple rows from a query
     * 
     * @param string $sql SQL query
     * @param array $params Parameters for the query
     * @return array Array of result objects
     * @throws DatabaseException If query fails
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insert data into a table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value pairs
     * @return int Last insert ID
     * @throws DatabaseException If insert fails
     */
    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);
        
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        $this->query($sql, $data);
        
        return (int) $this->getConnection()->lastInsertId();
    }
    
    /**
     * Update data in a table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value pairs
     * @param string $where WHERE clause (without the WHERE keyword)
     * @param array $whereParams Parameters for the WHERE clause
     * @return int Number of affected rows
     * @throws DatabaseException If update fails
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setClauses = array_map(function ($col) {
            return "$col = :$col";
        }, array_keys($data));
        
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $setClauses),
            $where
        );
        
        $params = array_merge($data, $whereParams);
        
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Delete data from a table
     * 
     * @param string $table Table name
     * @param string $where WHERE clause (without the WHERE keyword)
     * @param array $params Parameters for the WHERE clause
     * @return int Number of affected rows
     * @throws DatabaseException If delete fails
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Begin a transaction
     * 
     * @throws DatabaseException If transaction fails to start
     */
    public function beginTransaction(): void
    {
        try {
            $this->getConnection()->beginTransaction();
        } catch (\PDOException $e) {
            throw new DatabaseException('Failed to start transaction: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Commit a transaction
     * 
     * @throws DatabaseException If commit fails
     */
    public function commit(): void
    {
        try {
            $this->getConnection()->commit();
        } catch (\PDOException $e) {
            throw new DatabaseException('Failed to commit transaction: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Rollback a transaction
     * 
     * @throws DatabaseException If rollback fails
     */
    public function rollback(): void
    {
        try {
            $this->getConnection()->rollBack();
        } catch (\PDOException $e) {
            throw new DatabaseException('Failed to rollback transaction: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
