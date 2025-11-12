<?php

require "password_validator.php";

$token = $_POST["token"];
$token_hash = hash("sha256", $token);

$mysql = require_once __DIR__ . '/config.php';

$sql = "SELECT * FROM user WHERE rest_token_hash = ?";
$stmt = $mysql->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->excute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if($user === NULL){
    die("Token not found");
}

if(strtotime($user["reset_token_expires_at"]) <= time()){
    die("Token has expired");
}

$password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

$sql = "UPDATE user 
        SET password_hash = ?, 
            reset_token_hash = NULL,
            reset_token_expires_at = NULL 
        WHERE id = ?";

$stmt = $mysqli->prepare($sql);

$stmt->bind_param("ss", $password_hash, $user["id"]);
$stmt->excute();

echo "Password has been updated";

?>