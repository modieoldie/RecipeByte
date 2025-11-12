<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/check_login.php';
require_once __DIR__ . '/config.php';

// Prevent cached sessions from persisting
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

//get recipe id from URL
$recipe_id = $_GET['id'] ?? null;

if(!$recipe_id || !filter_var($recipe_id, FILTER_VALIDATE_INT)){
  
  //no id or valid id. send back to home page.
  header('Location: home_page.php');
  exit;
}

//prepare to fetch data
$recipe = null;

if($conn && !$conn->connect_error){
  //JOIN with the 'users' table to get authors username
  $sql = "SELECT r.*, u.username FROM recipes r LEFT JOIN users u ON r.user_id = u.id WHERE r.recipe_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $recipe_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if($result && $result->num_rows > 0){
    $recipe = $result->fetch_assoc();
  }

// Fetch average rating for this recipe
$average_rating = 0;
$total_ratings = 0;
$star_names = [
    0 => 'no_stars',
    1 => 'one_stars',
    2 => 'two_stars',
    3 => 'three_stars',
    4 => 'four_stars',
    5 => 'five_stars'
];
if ($conn && !$conn->connect_error) {
    $sql_avg = "SELECT AVG(star_rating) AS avg_rating, COUNT(*) AS total_ratings FROM ratings WHERE recipe_id = ?";
    $stmt_avg = $conn->prepare($sql_avg);

    if ($stmt_avg) {
        $stmt_avg->bind_param("i", $recipe_id);
        $stmt_avg->execute();

        $result_avg = $stmt_avg->get_result();
        if ($result_avg && $result_avg->num_rows > 0) {
            $row_avg = $result_avg->fetch_assoc();
            $average_rating = round($row_avg['avg_rating'] ?? 0, 1);
            $total_ratings = (int)($row_avg['total_ratings'] ?? 0);
        }


	// Round the average rating to the nearest integer
	$rounded_rating = (int) round($average_rating);

	// --- Update the average rating in the recipes table ---
	$update_sql = "UPDATE recipes SET average_rating = ? WHERE recipe_id = ?";
	$update_stmt = $conn->prepare($update_sql);
	if ($update_stmt) {
		$update_stmt->bind_param("di", $average_rating, $recipe_id);
		if (!$update_stmt->execute()) {
			error_log("Failed to update average rating for recipe_id $recipe_id: " . $update_stmt->error);
		}
		$update_stmt->close();
	} else {
		error_log("Failed to prepare update statement: " . $conn->error);
	}


	// Build the path to the star image
	$star_image_path = "../images/" . ($star_names[$rounded_rating] ?? 'no_stars') . ".png";


        $stmt_avg->close();
    } else {
        error_log("Failed to prepare AVG query: " . $conn->error);
    }
}


  $stmt->close();
}

//handle "Recipe Not Found"
if(!$recipe){
  echo "Recipe not found";
  exit;
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($recipe['title']); ?></title>
    <link rel="stylesheet" href="../css/sections/header.css" />
    <link rel="stylesheet" href="../css/sections/hero.css" />
    <link rel="stylesheet" href="../css/sections/product.css" />
    <link rel="stylesheet" href="../css/sections/filter.css" />

    <style>
        body {
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

	#toast {
	  position: fixed;
	  top: 20px;            /* top-center placement */
	  left: 50%;
	  transform: translateX(-50%);
	  background-color: #4CAF50; /* friendly green */
	  color: white;
	  padding: 15px 25px;
	  border-radius: 8px;
	  font-size: 1.2rem;
	  font-weight: bold;
	  text-align: center;
	  z-index: 10001;
	  opacity: 0;
	  pointer-events: none; /* allows clicks to pass through */
	  box-shadow: 0 4px 15px rgba(0,0,0,0.3);
	  transition: opacity 0.5s ease, transform 0.5s ease;
	}

	/* Warning toast */
	#toast.warning {
	  background-color: #FF9800; /* friendly orange warning */
	}

        #image-container {
	    position: relative;
            overflow: hidden; /* Hide overflow */
	    text-align: center;
   	    background: linear-gradient(to right, black, darkgray, darkgray, black); /* Symentric gradient */
	    margin: 0 auto;

        }

        img {
            width: 50%; /* Make image responsive */
            transition: transform 0.3s; /* Smooth zoom transition */
	    margin-top: 0px; /* Space above image */
            height: auto;
        }



	.overlay {
    	    position: absolute; /* Position overlay on top of the image */
    	    top: 30%; /* Center vertically */
    	    left: 50%; /* Center horizontally */
    	    transform: translate(-50%, -50%); /* Adjust transform to center */
    	    color: white; /* White font color */
    	    font-size: 8vw; /* Font size 10 times the view width ( for mobile ) */
    	    text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.8); /* Optional shadow for better visibility */
	}

	.author {
	    position: absolute; /* Position overlay on top of the image */
    	    top: 50%; /* Center vertically */
    	    left: 50%; /* Center horizontally */
    	    transform: translate(-50%, -50%); /* Adjust transform to center */
    	    color: white; /* White font color */
    	    font-size: 4vw; /* Font size 10 times the view width ( for mobile ) */
    	    text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.8); /* Optional shadow for better visibility */
	}

	#ingredient-section {
	    
	    padding: 20px; /* Add padding for space around the content */
	    background-color: white; /* Optional: Background color to distinguish the section */
	    color: black; /* Text color */
	    text-align: left; /* Center text for aesthetics */
	    margin-left: 5vw;
	    margin-top: 2vw;
	}

	#title-section {
	    padding: 20px; /* Add padding for space around the content */
	    background-color: white; /* Optional: Background color to distinguish the section */
	    color: black; /* Text color */
	    text-align: left; /* Center text for aesthetics */
	    margin-left: 5vw;
	    margin-top: 2vw;
	}

		/* Desktop layout: side-by-side */
	@media (min-width: 1080px) {
	  #ingredient-section {
		display: flex;
		justify-content: space-between;
		align-items: flex-start;
		gap: 40px;
	  }

	  .ingredients-container {
		flex: 1;
	  }

	.title-container {
		flex: 1;
	}

	  .nutrition-container {
		flex: 1;
		max-width: 400px; /* optional to control table width */
	  }
	}

	#instruction-section {
	    padding: 20px; /* Add padding for space around the content */
	    background-color: white; /* Optional: Background color to distinguish the section */
	    color: black; /* Text color */
	    text-align: left; /* Center text for aesthetics */
	    margin-left: 5vw;
	}

	#ratings-section {
	    padding: 20px; /* Add padding for space around the content */
	    background-color: white; /* Optional: Background color to distinguish the section */
	    color: black; /* Text color */
	    text-align: center; /* Center text for aesthetics */
	}

	#star-rating {
 	   display: flex;
 	   justify-content: center;
 	   align-items: center;
 	   margin-bottom: 10px; /* space between stars and heading */
	}

	#star-rating img {
  	  width: 150px; /* or whatever size looks good */
  	  height: auto;
	}

	.comments-container {
	  display: flex;
	  flex-direction: column;
	  gap: 20px;
	  margin-top: 20px;
	}

	.comment-box {
	  display: flex;
	  align-items: flex-start;
	  background-color: #f0f0f0;
	  padding: 15px;
	  border-radius: 10px;
	  position: relative;
	  width: 700px;
	  max-width: 90%;
	  margin: 0 auto 20px auto;
	  box-sizing: border-box;
	}

	.comment-pfp {
	  width: 60px;
	  height: 60px;
	  border-radius: 50%;
	  object-fit: cover;
	  margin-right: 15px;
	}

/* Comment content container */
.comment-content {
  flex: 1;
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: flex-start; /* ensure left alignment on desktop */
  text-align: left;
}
	.comment-text {
	  font-size: 1rem;
	  color: #333;
	  min-height: 1.2em; /* ensures consistent spacing even if empty */
	  margin-bottom: 40px; /* leave space for star rating */
	}

	.comment-rating {
	  position: static;
	  //bottom: 5px;
	  //right: 5px;
	  width: 100px;
	  height: auto;
	  align-self: flex-end;
	}


/* Container for sort + input + post */
.comment-input-container {
  display: flex;
  align-items: flex-start;
  background-color: #f0f0f0;
  padding: 15px;
  border-radius: 10px;
  position: relative;
  width: 700px;
  max-width: 90%;
  margin: 0 auto 20px auto;
  box-sizing: border-box;
  gap: 10px;
}

/* Sort button on the left */
.sort-button {
  background-color: black;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px 15px;
  cursor: pointer;
  height: 45px;
  flex-shrink: 0;
  font-weight: 500;
  transition: background-color 0.3s;
}
.sort-button:hover {
  background-color: #333;
}

/* Text area for comment input */
.comment-textarea {
  flex: 1;
  height: 45px;
  border: 1px solid #ccc;
  border-radius: 8px;
  padding: 10px;
  resize: none;
  font-size: 1rem;
  box-sizing: border-box;
}

/* Post button on right */
.post-button {
  background-color: black;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px 20px;
  cursor: pointer;
  height: 45px;
  flex-shrink: 0;
  font-weight: 500;
  transition: background-color 0.3s;
}
.post-button:hover {
  background-color: #333;
}




/* Popup overlay */
.popup-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.6);
  display: none; /* hidden by default */
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

/* Popup content box */
.popup-content {
  background: white;
  border-radius: 15px;
  padding: 30px 20px;
  width: 90%;
  max-width: 400px;
  text-align: center;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
  animation: fadeIn 0.2s ease-in-out;
}

/* Heading */
.popup-content h3 {
  margin-bottom: 20px;
  color: black;
  font-size: 1.4rem;
}

/* Stars */
.stars {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-bottom: 20px;
}

.star {
  font-size: 2rem;
  color: #ccc;
  cursor: pointer;
  transition: color 0.2s;
}

.star.selected {
  color: gold;
}


/* Buttons inside popup */
.popup-content button {
  background-color: black;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px 20px;
  cursor: pointer;
  margin: 5px;
  font-weight: 500;
  transition: background-color 0.3s;
}

.popup-content button:hover {
  background-color: #333;
}

/* Fade animation */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}



/* Calculate Nutrition button */
.calc-btn {
  background-color: black;
  color: white;
  border: none;
  border-radius: 10px;
  padding: 12px 24px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.3s ease;
  margin-top: 20px;
}

.calc-btn:hover {
  background-color: #333;
}

/* Nutrition facts container */
.nutrition-container {
  margin-top: 0px;
  background-color: #f7f7f7;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  max-width: 600px;
  margin-left: auto;
  margin-right: 5vw;

}

@media (max-width: 600px) {
  .nutrition-container {
      margin-top: 20px;
      margin-right: auto;

  }
}


/* Nutrition facts heading */
.nutrition-container h3 {
  text-align: center;
  color: black;
  margin-bottom: 15px;
}

/* Nutrition facts table */
.nutrition-table {
  width: 100%;
  border-collapse: collapse;
}

.nutrition-table th,
.nutrition-table td {
  padding: 10px;
  border-bottom: 1px solid #ddd;
  text-align: left;
  color: #333;
}

.nutrition-table th {
  background-color: #eaeaea;
  font-weight: bold;
}



/* Style button for Save Recipe */

.save-recipe-btn {
    position: absolute; /* Position the button relative to the container */
    bottom: 10px; /* Place it 10px from the bottom of the container */
    right: 10px; /* Place it 10px from the right of the container */
    background-color: black;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 10px 20px;
    font-size: 1rem;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.save-recipe-btn:hover {
    transform: scale(1.05);
}

.save-recipe-btn:active {
    transform: scale(0.95);
}






	/* Responsive adjustments */
	@media (max-width: 600px) {
	  .comment-box {
		flex-direction: column;
		align-items: center;
		text-align: center;
	  }

	  .comment-pfp {
		margin: 0 0 10px 0;
	  }

	  .comment-rating {
		position: static;
		margin-top: 5px;
	  }

	  .comment-text {
		margin-bottom: 10px;
	  }
	}

/* Mobile layout */
@media (max-width: 600px) {
  .comment-input-container {
    flex-direction: column;
    align-items: stretch;
    width: 95%;
  }

  .sort-button,
  .post-button {
    width: 100%;
    height: auto;
  }

  .comment-textarea {
    width: 100%;
    height: 80px;
  }
}

/* Responsive for mobile */
@media (max-width: 600px) {
  .nutrition-container {
    width: 90%;
    padding: 15px;
  }

  .calc-btn {
    width: 100%;
    font-size: 1rem;
  }
}




    </style>

  </head>
  <body>


<!-- Simple "Toast" Notification upon submitting a rating -->
<div id="toast"></div>


    <header>
      <nav class="header__nav">
        <div class="header__container">
          <div class="header__left">
            <a href="../php/home_page.php" id="header__logo">
              <img
                src="../images/logo-13.png"
                alt="Company Logo"
                class="logo"
              />
            </a>
            <a href="../php/home_page.php" id="header__companyName">RecipeByte</a>
          </div>

          <div class="header__pageTitle" id="header__page">
            <h1>RECIPES</h1>
          </div>

          <div class="header__right">
            <ul class="header__menu header__menu--desktop">
              <li class="header__item">
                <a href="../php/shopping_cart.php" class="button">
                  <img
                    src="../images/shopping_cart.png"
                    alt="Shopping Cart"
                    class="icon"
                  />
                </a>
              </li>
              <li class="header__item">
                <a href="../php/profile_page.php" class="button">
                  <img src="../images/profile.png" alt="Profile" class="icon" />
                </a>
              </li>
            </ul>

            <div class="header__menu header__menu--mobile">
              <button
                class="menu-toggle"
                id="menuToggle"
                aria-label="Open navigation menu"
              >
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
              </button>
              <ul class="menu-dropdown" id="menuDropdown">
                <li><a href="../php/profile_page.php">Profile</a></li>
                <li><a href="../php/shopping_cart.php">Cart</a></li>
              </ul>
            </div>
          </div>
        </div>
      </nav>
    </header>
<!-- Display Image Of Recipe-->
<div id="image-container">
    <img id="dynamic-image" src="<?php echo htmlspecialchars($recipe['image_path'] ?? '../uploads/food_icon.png'); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>">

  <button id="saveRecipeBtn" class="save-recipe-btn" onclick="saveRecipe(<?= $recipe['recipe_id'] ?>)">Save Recipe</button>
</div>

<!-- New Section for Recipe Name-->
<section id="title-section">
<div class ="title-container">
	<h1><?php echo htmlspecialchars($recipe['title']); ?></h1>	
	<h2>By <?php echo htmlspecialchars($recipe['username'] ?? 'Unknow Chef'); ?></h2>
</div>
</section>




<!-- New Section for Ingredients -->

<section id="ingredient-section">
  <div class ="ingredients-container">
    <h2>Ingredients</h2>
    <p>What you'll need to begin:</p>

    <ol>
      <?php 
        //split ingredients string by new lines
        $ingredients = explode("\n", $recipe['ingredients']);
        foreach($ingredients as $ingredient){
          $ingredient = trim($ingredient);
          if(!empty($ingredient)){
            echo "<li>" . htmlspecialchars($ingredient) . "</li>";
          }
        }
      ?>
    </ol>
    </div>


<!-- Placeholder nutrition facts section (hidden until button pressed) -->
<div id="nutrition-facts" class="nutrition-container">
  <h3>Nutrition Facts</h3>
  <table class="nutrition-table">
    <tr><th>Nutrient</th><th>Amount</th></tr>
    <tr><td>Calories</td><td><?php echo htmlspecialchars($recipe['calories']); ?> kcal</td></tr>
    <tr><td>Protein</td><td><?php echo htmlspecialchars($recipe['protein']); ?> g</td></tr>
    <tr><td>Carbohydrates</td><td><?php echo htmlspecialchars($recipe['carbs']); ?> g</td></tr>
    <tr><td>Fat</td><td><?php echo htmlspecialchars($recipe['fat']); ?> g</td></tr>
    <tr><td>Fiber</td><td> </td></tr>
    <tr><td>Sugar</td><td> </td></tr>

  </table>
</div>


</section>



<!-- New Section for Instructions-->

<section id="instruction-section">
    <h2>Directions</h2>
    <p>Steps to follow:</p>

    <ol>
      <?php 
        //split ingredients string by new lines
        $directions = explode("\n", $recipe['directions']);
        foreach($directions as $step){
          $step = trim($step);
          if(!empty($step)){
            echo "<li>" . htmlspecialchars($step) . "</li>";
          }
        }
      ?>
    </ol>

</section>

<!-- New Section for Comments/Ratings-->


<section id="ratings-section">
    <div id="star-rating">
        <img src="<?php echo htmlspecialchars($star_image_path); ?>" alt="rating">
        <?php 
            $review_text = ($total_ratings === 1) ? "review" : "reviews";
        ?>
        <span id="average-rating" style="font-size: 1.2rem; font-weight: bold; color: black;">
            <?php echo htmlspecialchars($average_rating); ?> / 5 (<?php echo $total_ratings; ?> <?php echo $review_text; ?>)
        </span>
    </div>
    <h2>Comments</h2>


<!-- Comment input and sort area -->
<div class="comment-input-container">
  <button class="sort-button">Sort by Rating</button>
  <textarea
    class="comment-textarea"
    placeholder="Write a comment..."
  ></textarea>
  <button class="post-button">Post</button>
</div>

<!-- Inject PHP here -->
<div id="comments-container"></div>

</section>



<!-- Star rating popup (hidden by default) -->
<div id="rating-popup" class="popup-overlay">
  <div class="popup-content">
    <h3>Rate this recipe</h3>
    <div class="stars" id="star-selection">
      <span class="star" data-value="1">&#9733;</span>
      <span class="star" data-value="2">&#9733;</span>
      <span class="star" data-value="3">&#9733;</span>
      <span class="star" data-value="4">&#9733;</span>
      <span class="star" data-value="5">&#9733;</span>
    </div>
    <button id="submit-rating">Submit</button>
    <button id="cancel-rating">Cancel</button>
  </div>
</div>


<script>
function showToast(message, type = 'success', duration = 3000, onClick = null) {
    const toast = document.getElementById('toast');
    if (!toast) return; // safety check

    toast.textContent = message;

    // Reset styling
    toast.className = '';
    toast.classList.add('toast');
    if (type === 'warning') toast.classList.add('warning');

    // Remove previous click handler
    toast.onclick = null;

    // Clickable handler
    if (onClick) {
        toast.style.cursor = 'pointer';
        toast.style.pointerEvents = 'auto';
        toast.onclick = () => {
            onClick();
            hideToast();
        };
    } else {
        toast.style.cursor = 'default';
        toast.style.pointerEvents = 'none';
    }

    // Show toast
    toast.style.opacity = '1';
    toast.style.transform = 'translateX(-50%) translateY(0)';

    // Hide function
    function hideToast() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(-50%) translateY(-20px)';
        toast.onclick = null;
    }

    // Auto-hide
    setTimeout(hideToast, duration);
}
</script>







<!-- Script for loading the comments from database -->

<script>
// --------------------
// Fetch and display comments dynamically
// --------------------
document.addEventListener('DOMContentLoaded', () => {
  // Extract recipe_id from URL (e.g. recipe.php?recipe_id=14)
  const urlParams = new URLSearchParams(window.location.search);
  const recipeId = urlParams.get('id');

  if (!recipeId) {
    console.error("No recipe_id found in URL.");
    document.getElementById('comments-container').innerHTML = "<p>Error: Missing recipe ID.</p>";
    return;
  }

  // Fetch comments for the current recipe
  fetch(`fetch_comments.php?recipe_id=${recipeId}`)
    .then(response => {
      if (!response.ok) throw new Error("Network response was not ok");
      return response.text();
    })
    .then(html => {
      // Inject fetched comments into the comments container
      document.getElementById('comments-container').innerHTML = html;
    })
    .catch(error => {
      console.error("Error fetching comments:", error);
      document.getElementById('comments-container').innerHTML = "<p>Failed to load comments.</p>";
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const postButton = document.querySelector('.post-button');
    const commentBox = document.querySelector('.comment-textarea');
    const popup = document.getElementById('rating-popup');
    const stars = document.querySelectorAll('#star-selection .star');
    const submitRatingBtn = document.getElementById('submit-rating');
    const cancelRatingBtn = document.getElementById('cancel-rating');
    let selectedRating = 0;

    const recipeId = new URLSearchParams(window.location.search).get('id');

    if (!recipeId) {
        console.error("No recipe_id found in URL.");
        return;
    }

    // ----- Functions -----
    const resetStars = () => {
        stars.forEach(s => s.classList.remove('selected'));
    };

    const updateStarsVisual = () => {
        stars.forEach((s, i) => {
            s.style.color = i < selectedRating ? 'gold' : '#ccc';
            s.classList.toggle('selected', i < selectedRating);
        });
    };

    const refreshComments = () => {
        fetch(`fetch_comments.php?recipe_id=${recipeId}`)
            .then(r => r.text())
            .then(html => {
                document.getElementById('comments-container').innerHTML = html;
            });
    };

    // ----- Event Listeners -----
    // Show popup when clicking Post
    postButton.addEventListener('click', () => {
        popup.style.display = 'flex';
    });

    // Star hover + click
    stars.forEach((star, index) => {
        star.addEventListener('mouseenter', () => {
            stars.forEach((s, i) => s.style.color = i <= index ? 'gold' : '#ccc');
        });
        star.addEventListener('mouseleave', updateStarsVisual);
        star.addEventListener('click', () => {
            selectedRating = index + 1;
            updateStarsVisual();
        });
    });

    // Cancel button
    cancelRatingBtn.addEventListener('click', () => {
        popup.style.display = 'none';
        selectedRating = 0;
        resetStars();
    });

    // Submit rating + comment
    submitRatingBtn.addEventListener('click', () => {
        if (!selectedRating) {
            showToast("Please select a rating!", 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('recipe_id', recipeId);
        formData.append('star_rating', selectedRating);
        formData.append('comment', commentBox.value.trim());

        fetch('submit_comments.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    commentBox.value = '';
                    popup.style.display = 'none';
                    selectedRating = 0;
                    resetStars();
                    showToast("Comment submitted!");
                    refreshComments();
                } else if (data.status === 'exists') {
                    // Ask user if they want to override
                    showToast(
    "You already rated this recipe. Click here to override.",
    'warning',
    6000, // duration
    () => {
        const overrideData = new FormData();
        overrideData.append('recipe_id', recipeId);
        overrideData.append('star_rating', selectedRating);
        overrideData.append('comment', commentBox.value.trim());
        overrideData.append('override', 1);

        fetch('submit_comments.php', { method: 'POST', body: overrideData })
            .then(r => r.json())
            .then(rData => {
                if (rData.status === 'success') {
                    commentBox.value = '';
                    popup.style.display = 'none';
                    selectedRating = 0;
                    resetStars();
                    showToast("Rating overridden!");
                    refreshComments();
                } else {
                    showToast("Error overriding rating", 'warning');
                }
            });
    }
);                } else {
                    showToast("Error posting comment: " + (data.message || 'Unknown error'), 'warning');
                }
            })
            .catch(err => {
                console.error(err);
                showToast("Network error posting comment", 'warning');
            });
    });
});
</script>
<script src="../js/script.js" type="module"></script>


<!-- Handles when the Save Recipe button. It makes a POST request to the save_recipe_to_profile.php
     file to save the recipeId along with the user to the database. -->
<script>
    function saveRecipe(recipeId) {
        fetch("../php/save_recipe_to_profile.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "recipe_id=" + encodeURIComponent(recipeId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Recipe saved successfully!");
            } else {
                alert(data.error || "Error saving recipe.");
            }
        })
        .catch(() => {
            alert("Failed to connect to server.");
        });
    }

</script>



</body>





</html>
