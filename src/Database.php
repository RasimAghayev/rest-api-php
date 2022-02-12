<?php

class Database
{
    /**
     * @var PDO|null
     */
    private ?PDO $conn=null;
    /**
     * @param string $host
     * @param string $name
     * @param string $user
     * @param string $password
     */
    public function __construct(
        private string $host,
        private string $name,
        private string $user,
        private string $password
    ){}

    /**
     * @return PDO
     */
    public function getConnection(): PDO
    {
        if ($this->conn===null) {
            $dsn = "mysql:host={$this->host};dbname={$this->name};charset=utf8";
            $this->conn=new PDO($dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
        }
        return $this->conn;
    }
}