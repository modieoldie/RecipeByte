<?php
session_start();
require_once __DIR__ . '/config.php';

//Prevent Caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");


// 1. Check if the session is already active
if (!isset($_SESSION['user_id'])) // if user_id is not set
{ 



// 2. Check for a remember_me cookie
if (isset($_COOKIE['remember_me'])) {
    // Decode cookie
    $data = json_decode(base64_decode($_COOKIE['remember_me']), true);
    $email = $data['email'] ?? '';
    $token = $data['token'] ?? '';

    // Validate email and token format before querying DB
    if ($email && $token) {
        $stmt = $conn->prepare("SELECT id, session_token_hash, session_token_expires_at FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // 3. Verify token
        if (
            $user &&
            $user['session_token_expires_at'] > date('Y-m-d H:i:s') &&
            password_verify($token, $user['session_token_hash'])
        ) {
            // âœ… Token valid, rebuild session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $email;
        }
    }

    // âŒ Invalid or expired cookie â€” cleanup
    setcookie("remember_me", "", time() - 3600, "/");
    header("Location: /CSE442/2025-Fall/cse-442aj/website/html/login.html");
    exit();
}

// 4. Neither session nor valid cookie
header("Location: /CSE442/2025-Fall/cse-442aj/website/html/login.html");
exit();

}

?>
