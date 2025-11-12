<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $token_hash = hash('sha256', $token);

    //server-side password validation
    if($password !== $confirmPassword){
        die("Passwords do not match");
    }

    $passRegex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";

    if(!preg_match($passRegex, $password)){
        die("Password does not meet the requirements");
    }

    // Find user by token
    $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token_hash = ?");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        die("Invalid or expired token.");
    }

    // Check if token is expired
    if (strtotime($user['reset_token_expires_at']) < time()) {
        die("Token expired. Please request a new password reset.");
    }

    // Update password securely
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users 
                            SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL 
                            WHERE id = ?");
    $stmt->bind_param("si", $password_hash, $user['id']);
    $stmt->execute();

    echo "Password successfully reset.";
}
?>