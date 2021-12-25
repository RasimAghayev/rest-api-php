<?php

class TaskGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn=$database->getConnection();
    }

    public function getAll(): array
    {
        $sql="SELECT * FROM `tasks` order by 'name'";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}