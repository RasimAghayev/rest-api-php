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
              WHERE api_key=:api_key";
        $stmt=$this->conn->prepare($sql);
        $stmt->bindValue(":api_key",$key,PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);

    }

    /**
     * Get By UserName
     * @param string $username
     * @return array|false
     */
    public function getByUsername(string $username): array | false
    {
        $sql="SELECT * FROM users where username=:username";
        $stmt=$this->conn->prepare($sql);
        $stmt->bindValue(":username",$username,PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get By ID
     * @param int $id
     * @return array|false
     */
    public function getByID(int $id): array | false
    {
        $sql="SELECT * FROM users WHERE id=:id";
        $stmt=$this->conn->prepare($sql);
        $stmt->bindValue(":id",$id,PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $name
     * @param string $username
     * @param string $password_hash
     * @return string
     * @throws Exception
     */
    public function createUsername(array $data): string
    {
        $sql="INSERT INTO users (name,username,password_hash,api_key)
          VALUES (:name,:username,:password_hash,:api_key)";
        $stmt=$this->conn->prepare($sql);
        $password_hash=password_hash($data["password_hash"],PASSWORD_DEFAULT);
        $api_key=bin2hex(random_bytes(16));
        $stmt->bindValue(":name",$data["name"],PDO::PARAM_STR);
        $stmt->bindValue(":username",$data["username"],PDO::PARAM_STR);
        $stmt->bindValue(":password_hash",$data["password_hash"],PDO::PARAM_STR);
        $stmt->bindValue(":api_key",$api_key,PDO::PARAM_STR);
        $stmt->execute();
        return $api_key;
    }

}