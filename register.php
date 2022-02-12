<?php
require __DIR__."/vendor/autoload.php";
if ($_SERVER["REQUEST_METHOD"]==="POST"){
    $dotenv=Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $database=new Database($_ENV["DB_HOST"],
                           $_ENV["DB_NAME"],
                           $_ENV["DB_USER"],
                           $_ENV["DB_PASS"]);
    $user_gatwey=new UserGateway($database);
    $username=$user_gatwey->getByUsername($_POST["username"]);
//    var_dump($user_gatwey);die();
    (empty($username))?:die("User already yes ".$_POST["username"]) ;
    $api_key=$user_gatwey->createUsername($_POST["name"],$_POST["username"],$_POST["password"]);
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
            <!-- Grid -->
            <div class="grid">
                <!-- Markup example 1: input is inside label -->
                <label for="name">
                    Full name
                    <input type="text" id="name" name="name" placeholder="First name" required>
                </label>

                <label for="name">
                    User name
                    <input type="text" id="username" name="username" placeholder="User name" required>
                </label>

            </div>

            <!-- Markup example 2: input is after label -->
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Password" required>

            <!-- Button -->
            <button type="submit">Register</button>

        </form>
    </main>
</body>
</html>