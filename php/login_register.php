<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/config.php';

if(isset($_POST['sign_up'])){

    $stmt = $conn->prepare("SELECT email FROM users WHERE email =?");
    $stmt->bind_param("s", $_POST['email']);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $_SESSION['sign_up_error'] = 'Email is already registered';
        $_SESSION['active_form'] = 'register';
        
        header("Location: https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442aj/website/html/login.html?error=register&form=register");
        exit();
    } else {

        if($_POST['password'] !== $_POST['confirm_password']) {
        	$errorMessage = urldecode("Passwords do not match");
        
        	header("Location: https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442aj/website/html/login.html?error=passwordmismatch&form=register");
        	exit();
    	}

        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $email = $_POST['email'];
    
        $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();

    header("Location: https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442aj/website/html/login.html?form=login");
    exit();
    }   
}



if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email =?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0){
        $user = $result->fetch_assoc();
        if(password_verify($password, $user['password'])){
            $_SESSION['email'] = $user['email'];

        header("Location: https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442aj/website/php/user_page.php");
        exit();
        }
    } 

    $_SESSION['login_error'] = 'Incorrect email or password';
    $_SESSION['active_form'] = 'login';

    header("Location: https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442aj/website/html/login.html?error=login&form=login");
    exit();
}

?>