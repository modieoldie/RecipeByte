<?php
// get_profile_pic.php: Serves the logged-in user's profile picture from the database as an image response
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Not logged in.');
}

$user_id = $_SESSION['user_id'];
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection failed.');
}

$sql = "SELECT profile_picture_path FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($profile_picture_path);
    $stmt->fetch();
    if ($profile_picture_path && file_exists($profile_picture_path)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($profile_picture_path);
        if (!$mime) {
            $mime = 'image/jpeg'; // fallback
        }
        // Only serve if it's an image
        if (strpos($mime, 'image/') === 0) {
            header('Content-Type: ' . $mime);
            readfile($profile_picture_path);
            exit;
        }
        // If not an image, fall through to default
    } else {
        // If null or file missing, set to default and update DB
        $defaultPath = 'images/profile.png';
        $update = $conn->prepare("UPDATE users SET profile_picture_path = ? WHERE id = ?");
        $update->bind_param("si", $defaultPath, $user_id);
        $update->execute();
        $update->close();
        header('Content-Type: image/png');
        readfile(__DIR__ . '/../images/profile.png');
        exit;
    }
}
// If no image, serve a default
header('Content-Type: image/png');
readfile(__DIR__ . '/../images/profile.png');
exit;
?>
