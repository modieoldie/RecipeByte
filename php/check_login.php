<?php
// check_login.php — secure version with DB session validation

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

function cl_log($msg) {
    error_log("[check_login] " . $msg);
}

function redirectToLogin($note = '') {
    $loc = "https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442aj/website/html/login.html?form=login";
    if ($note) cl_log("redirectToLogin: $note");
    if (!headers_sent()) {
        header("Location: $loc");
        exit();
    } else {
        cl_log("Cannot send header — headers already sent. Note: $note");
        exit();
    }
}

// --- 1️⃣ Check active session
if (!empty($_SESSION['email'])) {
    $email = $_SESSION['email'];

    // Verify against database that session_token_hash exists and not expired
    $stmt = $conn->prepare("SELECT session_token_hash, session_token_expires_at FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        cl_log("No user found for session email $email");
        redirectToLogin("invalid user in session");
    }

    // Optional but safer: verify that session is still valid by comparing token hash
    if (!empty($_COOKIE['remember_me'])) {
        $cookie_raw = $_COOKIE['remember_me'];
        $cookie_json = base64_decode($cookie_raw, true);
        $cookie_data = json_decode($cookie_json, true);

        if (!empty($cookie_data['token'])) {
            $token_valid = password_verify($cookie_data['token'], $user['session_token_hash'] ?? '');
            if (!$token_valid) {
                cl_log("Session token invalid for $email");
                redirectToLogin("session token invalid");
            }
        }
    }

    if (!empty($user['session_token_expires_at']) && strtotime($user['session_token_expires_at']) < time()) {
        cl_log("Session expired for $email");
        redirectToLogin("session expired");
    }

    cl_log("Authenticated via valid session: $email");
    return;
}

// --- 2️⃣ Fallback: Check remember_me cookie
if (empty($_COOKIE['remember_me'])) {
    cl_log("No session and no remember_me cookie present.");
    redirectToLogin('no remember_me cookie');
}

$cookie_raw = $_COOKIE['remember_me'];
$cookie_json = base64_decode($cookie_raw, true);
$cookie_data = json_decode($cookie_json, true);

if (!$cookie_data || empty($cookie_data['email']) || empty($cookie_data['token'])) {
    cl_log("Invalid remember_me cookie format. raw:" . substr($cookie_raw, 0, 80));
    redirectToLogin('invalid cookie format');
}

$email = $cookie_data['email'];
$token = $cookie_data['token'];

// Validate token from DB
$stmt = $conn->prepare("SELECT session_token_hash, session_token_expires_at FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    cl_log("User not found for cookie email: $email");
    redirectToLogin("user not found");
}

if (empty($user['session_token_hash']) || !password_verify($token, $user['session_token_hash'])) {
    cl_log("Invalid session token for $email");
    redirectToLogin("invalid token");
}

if (strtotime($user['session_token_expires_at']) < time()) {
    cl_log("Expired session token for $email");
    redirectToLogin("expired token");
}

// ✅ Valid remember_me -> restore session
$_SESSION['email'] = $email;
cl_log("Authenticated via remember_me cookie: $email");
