<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/check_login.php';

if (!isset($_POST['recipe_id']) || !isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit();
}

$recipe_id = (int)$_POST['recipe_id'];
$email = $_SESSION['email'];

// Get the user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user_id = $result->fetch_assoc()['id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'User not found.']);
    exit();
}

// Check if the recipe is already saved
$stmt = $conn->prepare("SELECT * FROM saved_recipes WHERE user_id = ? AND recipe_id = ?");
$stmt->bind_param("ii", $user_id, $recipe_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Recipe already saved.']);
    exit();
}

// Save the recipe
$stmt = $conn->prepare("INSERT INTO saved_recipes (user_id, recipe_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $recipe_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save recipe.']);
}
?>