<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/config.php';
session_start();
header('Content-Type: application/json');
error_log("submit_comments.php called with: " . json_encode($_POST));


// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to post a comment.']);
    exit;
}

// Validate POST inputs
$recipe_id = $_POST['recipe_id'] ?? null;
$star_rating = $_POST['star_rating'] ?? null;
$comment = trim($_POST['comment'] ?? '');
$override    = isset($_POST['override']) ? (bool)$_POST['override'] : false;

if (!$recipe_id || !$star_rating) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

// Allow empty comments
if ($comment === '') {
    $comment = '';
}
if (!filter_var($recipe_id, FILTER_VALIDATE_INT) || !filter_var($star_rating, FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    exit;
}



// ---------------------------------------------
// NEW: Check if user has already rated this recipe
// ---------------------------------------------
$checkSql = "SELECT comment_id, star_rating FROM ratings WHERE recipe_id = ? AND user_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("ii", $recipe_id, $_SESSION['user_id']);
$checkStmt->execute();
$existingRating = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

// If rating exists and override not requested, return 'exists' to frontend
if ($existingRating && !$override) {
    echo json_encode([
        'status' => 'exists',
        'comment_id' => $existingRating['comment_id'],
        'star_rating' => $existingRating['star_rating']
    ]);
    exit;
}

// If rating exists and override requested, update it
if ($existingRating && $override) {
    $updateSql = "UPDATE ratings SET star_rating = ?, comment = ? WHERE comment_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("isi", $star_rating, $comment, $existingRating['comment_id']);
    if ($updateStmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $updateStmt->error]);
    }
    $updateStmt->close();
    $conn->close();
    exit;
}




// If no existing rating, insert new rating
$sql = "INSERT INTO ratings (recipe_id, user_id, star_rating, comment) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiis", $recipe_id, $_SESSION['user_id'], $star_rating, $comment);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}
$stmt->close();
$conn->close();
?>
