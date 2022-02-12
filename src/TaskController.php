<?php


class TaskController
{
    /**
     * @param TaskGateway $gateway
     */
    public function __construct(private TaskGateway $gateway,
                                private int $user_id
    ){}

    /**
     * Send request Task
     * @param string $method
     * @param string|null $id
     * @return void
     */
    public function processRequest(string $method, ?string $id) :void
    {
        if ($id===null){
            if ($method === "GET"){
//                echo json_encode($this->gateway->getAll());
                echo json_encode($this->gateway->getAllForUser($this->user_id));
            }elseif ($method === "POST"){
                $data= (array) json_decode(file_get_contents("php://input"),true);
                $errors=$this->getValidationErrors($data);
                if (!empty($errors)){
                    $this->respondUnprocessableEntity($errors);
                    return;
                }
                $id=$this->gateway->createForUser($this->user_id,$data);
                $this->respondCreated($id);
            }else{
                $this->respondMethodNotAllowed("GET,POST");
            }
        }else{
            $task=$this->gateway->getForUser($this->user_id,$id);
            if ($task===false){
                $this->respondNotFound($id);
                return;
            }
            switch ($method){
                case "GET":
                    echo json_encode($task);
                    break;
                case "PATCH":
                    $data= (array) json_decode(file_get_contents("php://input"),true);
                    $errors=$this->getValidationErrors($data,false);
                    if (!empty($errors)){
                        $this->respondUnprocessableEntity($errors);
                        return;
                    }
                    $rows=$this->gateway->updateForUser($this->user_id,$id,$data);
                    echo json_encode(["message"=>"Task update","rows"=>$rows]);
                    break;
                case "DELETE":
                    $rows=$this->gateway->deleteForUser($this->user_id,$id);
                    echo json_encode(["message"=>"Task deleted","rows"=>$rows]);
                    break;
                default:
                    $this->respondMethodNotAllowed("GET,PATCH,DELETE");
            }
        }
    }

    /**
     *
     * @param array $errors
     * @return void
     */
    private function respondUnprocessableEntity(array $errors):void
    {
        http_response_code(422);
        echo json_encode(["errors"=>$errors]);
    }

    /**
     * @param string $allowed_methods
     * @return void
     */
    private function respondMethodNotAllowed(string $allowed_methods):void
    {
        http_response_code(405);
        header("Allow: $allowed_methods");
    }

    /**
     * @param string $id
     * @return void
     */
    private function respondNotFound(string $id):void
    {
        http_response_code(404);
        echo json_encode(["message"=>"Task with ID $id not found"]);
    }

    /**
     * @param string $id
     * @return void
     */
    private function respondCreated(string $id)
    {
        http_response_code(201);
        echo json_encode(["message"=>"Task create","id"=>$id]);
    }

    /**
     * Get Validation Error list
     * New record check $is_new=true
     * @param array $data
     * @param bool $is_new
     * @return array
     */
    private function getValidationErrors(array $data,bool $is_new=true): array
    {
        $errors=[];
        if ($is_new && empty($data["name"])){
            $errors[]="name is required";
        }
        if (!empty($data["priority"])){
            if (filter_var($data["priority"],FILTER_VALIDATE_INT)===false){
                $errors[]="priority must be an integer";
            }
        }
        return $errors;
    }
}