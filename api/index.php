<?php
$path=parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH);
$parts=explode("/",$path);
$resource=$parts[2];
$id=$parts[3] ?? null;
if($resource !== "tasks"){
//    header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
    http_response_code(404);
    exit;

}
//echo $resource ,", ", $id;
//echo $_SERVER["REQUEST_METHOD"],"\n";



require_once dirname(__DIR__)."/src/TaskController.php";
$controller= new TaskController;
$controller->processRequest($_SERVER['REQUEST_METHOD'],$id);