<?php

class Database
{
     private $host = "localhost";
    private $db_name = "reyhanez_reyhane";
    private $username = "reyhanez_mostafa";
    private $password = "E)ZfOLKRS)=q";
    public $conn;


    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8");
        } catch (PDOException $exception) {
            echo "اتصال به دیتابیس ناموفق: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

