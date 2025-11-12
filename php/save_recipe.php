<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/check_login.php';
if (!isset($_SESSION['email'])) {
    header("Location: https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442aj/website/html/login.html");
    exit(); // Safety: no HTML should render if not logged in
}


if(isset($_POST['upload'])){
    $email = $_SESSION['email'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    $user_id = null;
    if($result->num_rows > 0){
        $user = $result->fetch_assoc();
        $user_id = $user["id"];
    }

    if ($user_id === null) {
        die("User not found in database.");
    }    

    $title = $_POST['title'] ?? '';
    $ingredients = $_POST['ingredient'] ?? [];
    $steps = $_POST['step'] ?? [];
    $calories = (int)($_POST['calories'] ?? 0);
    $fat = (int)($_POST['fat'] ?? 0);
    $carbs = (int)($_POST['carbs'] ?? 0);
    $protein = (int)($_POST['protein'] ?? 0);

    $ingredient_text = implode("\n", array_filter($ingredients));
    $step_text = implode("\n", array_filter($steps));


    $stmt = $conn->prepare("
        INSERT INTO recipes (user_id, title, ingredients, directions, calories, fat, carbs, protein)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "isssiiii",
        $user_id,
        $title,
        $ingredient_text,
        $step_text,
        $calories,
        $fat,
        $carbs,
        $protein
    );

    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }


    if (isset($_FILES['recipeImage']) && $_FILES['recipeImage']['error'] === UPLOAD_ERR_OK) {
        $targetDir = __DIR__ . "/../uploads/";
        $fileType = strtolower(pathinfo($_FILES["recipeImage"]["name"], PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }        
    
        if (in_array($fileType, $allowedTypes)) {
            $uniqueName = uniqid('recipe_', true) . '.' . $fileType;
            $targetFilePath = $targetDir . $uniqueName;

            $webPath = "/CSE442/2025-Fall/cse-442aj/website/uploads/" . $uniqueName;
    
            if (move_uploaded_file($_FILES["recipeImage"]["tmp_name"], $targetFilePath)) {

                chmod($targetFilePath, 0644);

                // Update the recipe row with image_path
                $recipe_id = $conn->insert_id;
                $stmt = $conn->prepare("UPDATE recipes SET image_path = ? WHERE recipe_id = ?");
                $stmt->bind_param("si", $webPath, $recipe_id);
                $stmt->execute();
            } else {
                $_SESSION['error_message'] = "Error uploading file.";
            }
        } else {
            $_SESSION['error_message'] = "Invalid file type.";
        }
    }

    var_dump($user_id, $title, $ingredient_text, $step_text, $calories, $fat, $carbs, $protein);
    header("Location: ../php/profile_page.php");
    exit();
}

?>

