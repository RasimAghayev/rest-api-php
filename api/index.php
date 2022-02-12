<?php
declare(strict_types=1);
ini_set("display_errors","On");
require_once __DIR__."/bootstrap.php";
$path=parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH);
$parts=explode("/",$path);
$resource=$parts[2];
$id=$parts[3] ?? null;
if($resource !== "tasks"){
    http_response_code (404);
    exit;
}

$database=new Database($_ENV['DB_HOST'],$_ENV["DB_NAME"],$_ENV["DB_USER"],$_ENV["DB_PASS"]);
$user_gatwey=new UserGateway($database);

//var_dump($_SERVER["HTTP_AUTHORIZATION"]);
//$headers=apache_request_headers();
//echo $headers["Authorization"];
//exit();
$codec=new JWTCodec($_ENV["SECRET_KEY"]);
$auth=new Auth($user_gatwey,$codec);
//$auth->authenticateAPIKey() ? : exit();
$auth->authenticateAccessToken() ? : exit();

$user_id=$auth->getUserID();
$task_gateway=new TaskGateway($database);
$controller= new TaskController($task_gateway,$user_id);
$controller->processRequest($_SERVER['REQUEST_METHOD'],$id);