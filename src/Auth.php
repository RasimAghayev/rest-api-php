<?php

class Auth
{
    /**
     * @var int
     */
    private int $user_id;

    /**
     * @param UserGateway $userGateway
     */
    public function __construct(private UserGateway $userGateway){}

    /**
     * Check API_KEY value true or false
     * @return bool
     */
    public function authenticateAPIKey():bool
    {
        if(empty($_SERVER["HTTP_X_API_KEY"])){
            http_response_code(400);
            echo json_encode(["message"=>"Missing API key"]);
            return false;
        }
        $api_key=$_SERVER["HTTP_X_API_KEY"];
        $user=$this->userGateway->getByAPIKey($api_key);
        if ($user===false){
            http_response_code(401);
            echo json_encode(["message"=>"Invalid API key"]);
            return false;
        }
        $this->user_id=$user["id"];
        return true;
    }

    /**
     * Get API_KEY User ID
     * @return int
     */
    public function getUserID():int
    {
        return $this->user_id;
    }
}