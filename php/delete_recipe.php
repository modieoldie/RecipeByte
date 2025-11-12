<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/check_login.php';

// Code to delete a recipe entry from the database when the delete recipe button is clicked.

if (!isset($_SESSION['email'])) {
    http_response_code(403);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipe_id = $_POST['recipe_id'] ?? null;
    $type = $_POST['type'];

    if (!$recipe_id) {
        http_response_code(400);
        echo json_encode(["error" => "Missing recipe ID"]);
        exit();
    }

    // Verify that the recipe belongs to this user
    $email = $_SESSION['email'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        http_response_code(404);
        echo json_encode(["error" => "User not found"]);
        exit();
    }

    $user_id = $user['id'];

    // Determine the table to delete from
    if ($type === 'uploaded') {
        $stmt = $conn->prepare("DELETE FROM recipes WHERE user_id = ? AND recipe_id = ?");
    } elseif ($type === 'saved') {
        $stmt = $conn->prepare("DELETE FROM saved_recipes WHERE user_id = ? AND recipe_id = ?");
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid type.']);
        exit();
    }

    $stmt->bind_param("ii", $user_id, $recipe_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to delete recipe"]);
    }

    exit();
}
?>
