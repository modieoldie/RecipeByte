<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';

$recipe_id = $_GET['recipe_id'] ?? null;

if (!$recipe_id || !filter_var($recipe_id, FILTER_VALIDATE_INT)) {
    exit('<p>Invalid recipe ID.</p>');
}

$sql = "SELECT c.comment_id, c.comment, c.star_rating, u.username, u.profile_picture_path AS profile_pic
        FROM ratings c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.recipe_id = ?
        ORDER BY c.comment_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>No comments yet. Be the first to comment!</p>";
} else {
    while ($row = $result->fetch_assoc()) {

        // --- Determine star image ---
        switch ($row['star_rating']) {
            case 1:
                $starImg = "../images/one_stars.png";      
                break;
            case 2:
                $starImg = "../images/two_stars.png";     
                break;
            case 3:
                $starImg = "../images/three_stars.png";   
                break;
            case 4:
                $starImg = "../images/four_stars.png";    
                break;
            case 5:
                $starImg = "../images/five_stars.png";    
                break;
            default:
                // Should never happen, but just in case
                $starImg = "../images/unknown_star.png"; // optional neutral icon
        }
        $profile_pic = !empty($row['profile_pic'])
    ? '../' . htmlspecialchars($row['profile_pic'])  // prepend ../ to go up one directory
    : '../images/logo-13.svg';        
	echo '<div class="comment-box">';
	echo '  <img src="' . $profile_pic . '" alt="User avatar" class="comment-pfp">';
        echo '  <div class="comment-content">';
        echo '      <p class="comment-text">' . htmlspecialchars($row['comment']) . '</p>';
        echo '      <img src="' . htmlspecialchars($starImg) . '" alt="Rating: ' . htmlspecialchars($row['star_rating']) . ' stars" class="comment-rating">';
        echo '  </div>';
        echo '</div>';
    }
}

$stmt->close();
$conn->close();
?>
