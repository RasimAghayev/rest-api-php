<?php
require __DIR__."/vendor/autoload.php";
if ($_SERVER["REQUEST_METHOD"]==="POST"){
    $dotenv=Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $database=new Database($_ENV["DB_HOST"],
                           $_ENV["DB_NAME"],
                           $_ENV["DB_USER"],
                           $_ENV["DB_PASS"]);
    $conn=$database->getConnection();
    $sql_checkuser="SELECT username FROM users WHERE username=:username";
    $stmt_checkuser=$conn->prepare($sql_checkuser);
    $stmt_checkuser->bindValue(":username",$_POST["username"],PDO::PARAM_STR);
    $stmt_checkuser->execute();
    $data=$stmt_checkuser->fetch(PDO::FETCH_ASSOC);
    (!empty($data))?die("User already yes ".$_POST["username"]):'';

    $sql="INSERT INTO users (name,username,password_hash,api_key)
          VALUES (:name,:username,:password_hash,:api_key)";
    $stmt=$conn->prepare($sql);
    $password_hash=password_hash($_POST["password"],PASSWORD_DEFAULT);
    $api_key=bin2hex(random_bytes(16));
    $stmt->bindValue(":name",$_POST["name"],PDO::PARAM_STR);
    $stmt->bindValue(":username",$_POST["username"],PDO::PARAM_STR);
    $stmt->bindValue(":password_hash",$password_hash,PDO::PARAM_STR);
    $stmt->bindValue(":api_key",$api_key,PDO::PARAM_STR);
    $stmt->execute();
    echo "Thank you for registering. Your API key is ".$api_key;
    exit();
}
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@latest/css/pico.min.css">
</head>
<body>
    <main class="container">
        <h1>Register</h1>
        <form action="" method="post">
            <label for="name">
                Name
                <input type="text" name="name" id="name">
            </label>
            <label for="username">
                Username
                <input type="text" name="username" id="username">
            </label>
            <label for="password">
                Password
                <input type="text" name="password" id="password">
            </label>
            <button>Register</button>
        </form>
    </main>
</body>
</html>