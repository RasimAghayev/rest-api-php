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
        $stmt=$this->conn->query($sql);
//        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $data=[];
        while ($row=$stmt->fetch(PDO::FETCH_ASSOC)){
            $row['is_completed']= (bool) $row['is_completed'];
            $data[]=$row;
        }
        return $data;
    }

    public function get(string $id): array | false
    {
        $sql="SELECT * FROM `tasks` where id=:id";
        $stmt=$this->conn->prepare($sql);
        $stmt->bindValue(":id",$id,PDO::PARAM_INT);
        $stmt->execute();
        $data=$stmt->fetch(PDO::FETCH_ASSOC);
        if ($data!==false){
            $data['is_completed']=(bool) $data['is_completed'];
        }
        return $data;
    }
}