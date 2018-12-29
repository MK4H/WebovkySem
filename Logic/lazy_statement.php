<?php
class LazyStatement extends PDOStatement {
    private $conn;
    private $query;
    private $stmt;

    public function __construct(PDO $conn, string $query) {
        $this->conn = $conn;
        $this->query = $query;
    }
}