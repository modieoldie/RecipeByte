<?php

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
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['username'] = $user['username'];

            // --- Remember Me Token Creation ---
            $token = bin2hex(random_bytes(32)); // raw token
            $token_hash = password_hash($token, PASSWORD_DEFAULT); // store hashed version
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

            // Store hashed token and expiration in DB
            $stmt = $conn->prepare("UPDATE users SET session_token_hash=?, session_token_expires_at=? WHERE email=?");
            $stmt->bind_param("sss", $token_hash, $expires, $email);
            $stmt->execute();

            // Set the cookie (store email + token)
            $cookie_value = base64_encode(json_encode([
                'email' => $email,
                'token' => $token
            ]));
            setcookie('remember_me', $cookie_value, time() + (30*24*60*60), "/", "", false, true);

            // âœ… Redirect AFTER cookie + token setup
            header("Location: https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442aj/website/php/home_page.php");
            exit();
        } else {
            $_SESSION['login_error'] = 'Incorrect email or password';
            header("Location: https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442aj/website/html/login.html?error=login&form=login");
            exit();
        }
    } else {
        $_SESSION['login_error'] = 'Email not registered';
        header("Location: https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442aj/website/html/login.html?error=emailnotfound&form=login");
        exit();
    }
}


?>
