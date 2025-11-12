update_profile.php

<?php

session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../php/login.php");
    exit();
}

require_once __DIR__ . '/config.php';

$user_id = $_SESSION['user_id'];

//process the form based on the 'action'
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])){

    //action: upate user name
    if($_POST['action'] === 'update_username'){
        if(empty(trim($_POST['username']))){
            $_SESSION['error_message'] = "Username cannot be empty";
            header("Location: ../php/profile_page.php");
            exit();
        }

        $new_username = trim($_POST['username']);
    
        $sql_check= "SELECT id FROM users WHERE username = ? AND id != ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("si", $new_username, $user_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if($result->num_rows > 0){
            $_SESSION['error_message'] = "Username is already taken";
        } else {

            //proceed with update
            $sql_update = "UPDATE users SET username = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $new_username, $user_id);

            if($stmt_update->execute()){
                $_SESSION['username'] = $new_username;
                $_SESSION['success_message'] = "Username updated successfully";
            } else {
                $_SESSION['error_message'] = "Error updating username";
            }
        }

    //action: update password
    } else if($_POST['action'] === 'update_password'){
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        $passRegex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";

        if($new_password !== $confirm_password){
            $_SESSION['password_error'] = "Passwords do not match";
        } else if(!preg_match($passRegex, $new_password)){
            $_SESSION['password_error'] = "Password must meet requirements";
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $password_hash, $user_id);

            if($stmt->execute()){
                $_SESSION['success_message'] = "Password changed successfully";
            } else {
                $_SESSION['password_error'] = "Error changing password";
            }
        } 
    }
}

header("Location: ../php/profile_page.php");
exit();

?>