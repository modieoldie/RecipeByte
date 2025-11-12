<?php

$servername = "localhost";
$username = "bli49";
$password = "50535766";
$dbname = "cse442_2025_fall_team_aj_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if($conn->connect_error){
    die("Connection failed: ". $conn->connect_error);
}

?>

