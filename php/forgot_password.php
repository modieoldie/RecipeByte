<?php
if (isset($_POST['action']) && $_POST['action'] === 'goback') {
    header("Location: ../html/login.html");
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/config.php';

$logFile = __DIR__ . '/reset_log.txt';
$isDebug = isset($_GET['debug']) && $_GET['debug'] === '1';
$maxLines = 100;

// Write a log entry and trim file if too long
function log_event($message) {
    global $logFile, $maxLines;
    $timestamp = date('[Y-m-d H:i:s]');
    $entry = "$timestamp $message\n";
    file_put_contents($logFile, $entry, FILE_APPEND);

    // Trim old lines if file grows too long
    $lines = file($logFile, FILE_IGNORE_NEW_LINES);
    $total = count($lines);
    if ($total > $maxLines) {
        $trimmed = array_slice($lines, $total - $maxLines);
        file_put_contents($logFile, implode("\n", $trimmed) . "\n");
    }
}

// Safe redirect that works even if headers are already sent
function safe_redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        echo "<script>window.location.href='$url';</script>";
        exit();
    }
}

if ($isDebug) {
    // Live debug mode viewer
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
    echo "<meta http-equiv='refresh' content='5'>"; // auto-refresh every 5 seconds
    echo "<title>🔍 Password Reset Debug Log</title>";
    echo "<style>
            body { font-family: monospace; background: #111; color: #0f0; padding: 10px; }
            h2 { color: #6ff; }
            pre { background: #000; padding: 10px; border-radius: 5px; overflow-y: auto; max-height: 90vh; }
          </style></head><body>";
    echo "<h2>🚀 Password Reset Debug Log (auto-refresh every 5s)</h2>";

    if (file_exists($logFile)) {
        echo "<pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
    } else {
        echo "<p>No log entries yet...</p>";
    }

    echo "</body></html>";
    exit();
}

// ===================
// PASSWORD RESET FLOW
// ===================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    log_event("❌ Invalid request method (expected POST).");
    exit();
}

$email = isset($_POST['email']) ? trim($_POST['email']) : (isset($_POST['identifier']) ? trim($_POST['identifier']) : '');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);

if (!$email) {
    log_event("❌ No email provided.");
    safe_redirect("../html/login.html");
}

// Find user
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
if (!$stmt) {
    log_event("❌ SQL prepare failed: " . $conn->error);
    safe_redirect("../html/login.html");
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    log_event("❌ No account found for $email.");
    safe_redirect("../html/login.html");
}

// Generate token
$token = bin2hex(random_bytes(32));
$token_hash = hash('sha256', $token);
$expires = date('Y-m-d H:i:s', time() + 3600);

// Update database
$stmt = $conn->prepare("UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE email = ?");
if (!$stmt) {
    log_event("❌ SQL update failed: " . $conn->error);
    safe_redirect("../html/login.html");
}
$stmt->bind_param("sss", $token_hash, $expires, $email);
$stmt->execute();

// Build reset link
$resetLink = "https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442aj/website/html/password_reset.html?token=$token&email=" . urlencode($email);

// Send email
$subject = "RecipeByte Password Reset";
$message = "
<html><body>
<p>Hello,</p>
<p>You requested to reset your password for your RecipeByte account.</p>
<p>Click the link below to reset your password (valid for 1 hour):</p>
<p><a href='$resetLink'>$resetLink</a></p>
<p>If you didn’t request this, you can safely ignore this email.</p>
</body></html>
";
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type:text/html;charset=UTF-8\r\n";
$headers .= "From: RecipeByte <no-reply@recipebyte.com>\r\n";

$mailSuccess = mail($email, $subject, $message, $headers);

// Log results
if ($mailSuccess) {
    log_event("✅ Reset email sent to $email");
} else {
    log_event("⚠️ Failed to send reset email to $email");
}

log_event("🔗 Token generated for $email (expires $expires)");

safe_redirect("../html/login.html");
?>
