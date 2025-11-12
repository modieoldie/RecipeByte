<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/check_login.php';
if (!isset($_SESSION['email'])) {
    header("Location: ../html/login.html");
    exit();
}
if (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === UPLOAD_ERR_OK) {
    $email = $_SESSION['email'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_id = null;
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user["id"];
    }
    if ($user_id === null) {
        $_SESSION['error_message'] = "User not found in database.";
        header("Location: profile_page.php");
        exit();
    }
    $targetDir = __DIR__ . "/../images/";
    $fileType = strtolower(pathinfo($_FILES["profilePic"]["name"], PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    if (in_array($fileType, $allowedTypes)) {
        $uniqueName = uniqid('profile_', true) . '.' . $fileType;
        $targetFilePath = $targetDir . $uniqueName;
        $webPath = "images/" . $uniqueName; // Store as relative path only
        if (move_uploaded_file($_FILES["profilePic"]["tmp_name"], $targetFilePath)) {
            chmod($targetFilePath, 0644);
            $stmt = $conn->prepare("UPDATE users SET profile_picture_path = ? WHERE id = ?");
            $stmt->bind_param("si", $webPath, $user_id);
            $stmt->execute();
            $_SESSION['success_message'] = "Profile picture updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error uploading file.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid file type.";
    }
} else {
    $_SESSION['error_message'] = "No file uploaded or upload error.";
}
header("Location: profile_page.php");
exit();
?>