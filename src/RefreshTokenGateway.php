<?php

class RefreshTokenGateway
{
    /**
     * @var PDO
     */
    private PDO $conn;
    /**
     * @var string
     */
    private string $key;

    /**
     * Connect DataBase
     * @param Database $database
     * @param string $key
     */
    public function __construct(Database $database,string $key)
    {
        $this->conn=$database->getConnection();
        $this->key=$key;
    }

    /**
     * Create Token
     * @param string $token
     * @param int $expiry
     * @return bool
     */
    public function create(string $token,int $expiry)
    {
        $hash=hash_hmac("sha256",$token,$this->key);
        $sql="INSERT INTO refresh_token(token_hash,expires_at) values (:token_hash,:expires_at)";

        $stmt=$this->conn->prepare($sql);
        $stmt->bindValue(":token_hash",$hash,PDO::PARAM_STR);
        $stmt->bindValue(":expires_at",$expiry,PDO::PARAM_STR);

        return $stmt->execute();
    }

    /**
     * Delete Token
     * @param string $token
     * @return int
     */
    public function delete(string $token):int
    {
        $hash=hash_hmac("sha256",$token,$this->key);
        $sql="DELETE FROM refresh_token WHERE token_hash=:token_hash";

        $stmt=$this->conn->prepare($sql);
        $stmt->bindValue(":token_hash",$hash,PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Get Token
     * @param string $token
     * @return array|false
     */
    public function getByToken(string $token) : array | false
    {
        $hash=hash_hmac("sha256",$token,$this->key);
        $sql="SELECT * FROM refresh_token WHERE token_hash=:token_hash";

        $stmt=$this->conn->prepare($sql);
        $stmt->bindValue(":token_hash",$hash,PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Delete all Expired Token
     * @return int
     */
    public function deleteExpired(): int
    {
        $sql="DELETE FROM refresh_token WHERE expires_at<UNIX_TIMESTAMP()";
        $stmt=$this->conn->query($sql);
        return $stmt->rowCount();
    }
}