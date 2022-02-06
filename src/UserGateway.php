<?php

class UserGateway
{
    /**
     * @var PDO
     */
    private PDO $conn;

    /**
     * @param Database $database
     */
    public function __construct(Database $database)
    {
        $this->conn=$database->getConnection();
    }

    public function getByAPIKey(string $key):array|false
    {
        $sql="SELECT *
              FROM users
              WHERE rest_api_php.users.api_key=:api_key";
        $stmt=$this->conn->prepare($sql);
        $stmt->bindValue(":api_key",$key,PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);

    }

}