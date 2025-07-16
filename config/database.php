<?php
require_once __DIR__ . '/env.php';

class Database
{
    private $host;
    private $db_name;
    private $db_port;
    private $username;
    private $password;
    private $conn;

    public function __construct()
    {
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->db_port = DB_PORT;
        $this->username = DB_USER;
        $this->password = DB_PASS;
    }

    // Vulnerable database connection - no prepared statements
    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=" . DB_PORT . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            // Expose sensitive error information
            echo "Connection error: " . $exception->getMessage();
            echo "<br>Host: " . $this->host;
            echo "<br>Database: " . $this->db_name;
            echo "<br>Username: " . $this->username;
        }
        return $this->conn;
    }

    // Direct query execution without sanitization
    public function executeQuery($query)
    {
        $result = $this->conn->query($query);
        return $result;
    }

    // Vulnerable query method
    public function vulnerableQuery($query)
    {
        return mysqli_query($this->getConnection(), $query);
    }
}
