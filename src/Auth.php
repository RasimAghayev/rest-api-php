<?php

class Auth
{
    /**
     * @var int
     */
    private int $user_id;

    /**
     * Set UserGateway & JWTCodec
     * @param UserGateway $userGateway
     * @param JWTCodec $codec
     */
    public function __construct(private UserGateway $userGateway,
                                private JWTCodec $codec){}

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

    /**
     * Check access token true or false
     * @return bool
     */
    public function authenticateAccessToken(): bool
    {
        if(!preg_match("/^Bearer\s+(.*)$/",$_SERVER["HTTP_AUTHORIZATION"],$matches))
        {
            http_response_code(400);
            echo json_encode(["message"=>"Incomplete authorization header"]);
            return false;
        }
//        $plain_text=base64_decode($matches[1],true);
//        if($plain_text===false)
//        {
//            http_response_code(400);
//            echo json_encode(["message"=>"Incomplete authorization header"]);
//            return false;
//        }
//        $data=json_decode($plain_text,true);
//        if($data===null)
//        {
//            http_response_code(400);
//            echo json_encode(["message"=>"Incomplete authorization header"]);
//            return false;
//        }
        try {
            $data=$this->codec->decode($matches[1]);
        }catch (InvalidSignatureException){
            http_response_code(401);
            echo json_encode(["message"=>"Invalid signature"]);
            return false;
        }catch (TokenExpiredException){
            http_response_code(401);
            echo json_encode(["message"=>"Token has expired"]);
            return false;
        }
        catch (Exception $e){
            http_response_code(400);
            echo json_encode(["message"=>$e->getMessage()]);
            return false;
        }
        $this->user_id=$data["sub"];
//        var_dump( $plain_text);
        return true;
    }
}