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

    /**
     * HTTP_X_API_KEY request Check
     * @param string $key
     * @return array|false
     */
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

    public function getByUsername(string $username): array | false
    {
        $sql="SELECT * FROM users where username=:username";
        $stmt=$this->conn->prepare($sql);
        $stmt->bindValue(":username",$username,PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

}