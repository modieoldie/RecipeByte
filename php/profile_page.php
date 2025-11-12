<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/check_login.php';
if (!isset($_SESSION['email'])) {
    exit();
}

// Getting the User ID from users table (database) so that we can use it to 
// know what recipes the user uploaded.
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user_id = $result->fetch_assoc()['id'] ?? null;

// Fetch the user's profile picture path from the database
$profile_picture_path = null;
if ($user_id) {
    $stmt = $conn->prepare("SELECT profile_picture_path FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile_picture_path = $result->fetch_assoc()['profile_picture_path'] ?? null;
    // If the path is set and does not start with 'images/', prepend '../' for relative path
    if ($profile_picture_path && strpos($profile_picture_path, 'images/') === 0) {
        $profile_picture_path = '../' . $profile_picture_path;
    }
}
// Set default if not set (do NOT check file existence)
if (!$profile_picture_path) {
    $profile_picture_path = '../images/profile.png';
}

// Using that User ID, we fetch all the recipes the user uploaded from the database.
$recipes = [];
if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM recipes WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $recipes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// 
$saved_recipes = [];
if ($user_id) {
    $stmt = $conn->prepare("
        SELECT r.* 
        FROM saved_recipes sr
        JOIN recipes r ON sr.recipe_id = r.recipe_id
        WHERE sr.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $saved_recipes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$email = $_SESSION['email'] ?? 'unknown@example.com';
$username = $_SESSION['username'] ?? 'User';
$macros = $_SESSION['macros'] ?? [
    'calories' => 0,
    'protein' => 0,
    'carbs' => 0,
    'fat' => 0
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($username) ?>'s Profile</title>
    <link rel="stylesheet" href="../css/sections/header.css" />
    <link rel="stylesheet" href="../css/profile.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
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
            <h1>PROFILE</h1>
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
                  <img src="<?= htmlspecialchars($profile_picture_path) ?>" alt="Profile" class="icon" />
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
    
    <main class="profile-page">
    <?php
    if(isset($_SESSION['success_message'])){
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px; border: 1px solid #f5c6cb;">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }
    if(isset($_SESSION['error_message'])){
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px; border: 1px solid #f5c6cb;">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }
    ?>
    <div class="sidebar-column">
        <aside class="sidebar">
            <div class="profile-box">
                <img src="<?= htmlspecialchars($profile_picture_path) ?>" alt="Profile Picture" class="profile-pic">
                <h2 class="profile-name"><?= htmlspecialchars($username) ?></h2>
                <div class="macros">
                    <p><strong>Weekly Macronutrients</strong></p>
                    <p>Calories: <?= htmlspecialchars($macros['calories']); ?></p>
                    <p>Protein: <?= htmlspecialchars($macros['protein']); ?></p>
                    <p>Carbs: <?= htmlspecialchars($macros['carbs']); ?></p>
                    <p>Fat: <?= htmlspecialchars($macros['fat']); ?></p>
                </div>
            </div>
        </aside>
        <section class="settings-section">
            <button class="settings-toggle">Settings <i class="material-icons" style="font-size:25px; color:black;">settings</i></button>
            <div class="settings-dropdown">
                <button id="change-picture-btn" class="settings-option">Change Profile Picture</button>
                <button id="change-user-btn" class="settings-option">Change Username</button>
                <button id="change-password-btn" class="settings-option">Change Password</button>
            </div>
        </section>
        <div id="user-modal" class="modal">
            <div class="modal-content">
                <span class="close-btn">&times;</span>
                <h2>Change Username</h2>
                <form action="../php/update_profile.php" method="post">
                    <input type="hidden" name="action" value="update_username">
                    <label for="username">New Username</label>
                    <input type="text" id="username" name="username" required>
                    <button type="submit">Save Username</button>
                </form>
            </div>
        </div>
         <div id="password-modal" class="modal">
            <div class="modal-content">
                <span class="close-btn">&times;</span>
                <h2>Change Password</h2>
                <?php
                if(isset($_SESSION['password_error'])){
                    echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px; border: 1px solid #f5c6cb;">' . htmlspecialchars($_SESSION['password_error']) . '</div>';
                }
                ?>
                <form action="../php/update_profile.php" method="post">
                    <input type="hidden" name="action" value="update_password">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <button type="submit">Save Password</button>
                </form>
            </div>
        </div>
            <form action="../php/logout.php" method="post">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </section>
    </div>
<section class="main-content">
    <div class="recipes-section">
	<div class="section-header">
	  <h2>My Recipes</h2>
	  <button type="button" class="upload-btn">Upload</button>
	</div>
        <!-- This uses the recipes list of all the recipes we fetched from the database to display all the recipes
            the user uploaded. It shows the recipe image as a thumbnail and the title of the recipe. -->
        <div class="recipe-grid">
            <?php if (!empty($recipes)): ?>
                <?php foreach ($recipes as $recipe): ?>
                    <a class="recipe-box"
                        data-id="<?= $recipe['recipe_id'] ?>"
                        href="../php/display_recipe.php?id=<?= $recipe['recipe_id'] ?>">
                        <button class="delete-btn" onclick="deleteRecipe(event, <?= $recipe['recipe_id'] ?>, 'uploaded'); event.preventDefault();">&times;</button>
                        <img src="<?= htmlspecialchars($recipe['image_path'] ?? "https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442aj/website/images/food_icon.png") ?>" alt="<?= htmlspecialchars($recipe['title']) ?>" class="recipe-thumbnail">
                        <h3><?= htmlspecialchars($recipe['title']) ?></h3>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p>You haven't uploaded any recipes yet.</p>
            <?php endif; ?>
        </div>
    </div>
    <!-- This uses the saved_recipes list of all the recipes we fetched from the database to display all the recipes
            the user saved. It shows the recipe image as a thumbnail and the title of the recipe. -->
    <div class="saved-section">
        <h2>Saved Recipes</h2>
        <div class="recipe-grid">
            <?php if (!empty($saved_recipes)): ?>
                <?php foreach ($saved_recipes as $recipe): ?>
                    <a class="recipe-box"
                        data-id="<?= $recipe['recipe_id'] ?>"
                        href="../php/display_recipe.php?id=<?= $recipe['recipe_id'] ?>">
                        <button class="delete-btn" onclick="deleteRecipe(event, <?= $recipe['recipe_id'] ?>, 'saved'); event.preventDefault();">&times;</button>
                        <img src="<?= htmlspecialchars($recipe['image_path'] ?? "https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442aj/website/images/food_icon.png") ?>" alt="<?= htmlspecialchars($recipe['title']) ?>" class="recipe-thumbnail">
                        <h3><?= htmlspecialchars($recipe['title']) ?></h3>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p>You haven't saved any recipes yet.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
    </main>
    <div id="uploadModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h2>Upload Recipe</h2>
        <form id="uploadForm" action="../php/save_recipe.php" method="post" enctype="multipart/form-data">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" placeholder="Enter the title of your recipe" required>
            <label for="recipeImage">Upload a Recipe Image</label>
            <input type="file" id="recipeImage" name="recipeImage" accept="image/*">
            <h3>Ingredients</h3>
            <div id="ingredientContainer">
                <input type="text" name="ingredient[]" placeholder="Enter an ingredient">
            </div>
            <button type="button" onclick="addTextBox(false)">Add Ingredient</button>
            <h3>Directions</h3>
            <div id="stepContainer">
                <input type="text" name="step[]" placeholder="Enter a step">
            </div>
            <button type="button" onclick="addTextBox(true)">Add Step</button>
            <h3>Nutrition Facts (per serving)</h3>
            <label for="calories">Calories:</label>
            <input type="text" id="calories" name="calories">
            <label for="fat">Fat (g):</label>
            <input type="text" id="fat" name="fat">
            <label for="carbs">Carbs (g):</label>
            <input type="text" id="carbs" name="carbs">
            <label for="protein">Protein (g):</label>
            <input type="text" id="protein" name="protein">
            <button type="submit" name="upload">Add Recipe</button>
        </form>
    </div>
    </div>
    <div id="pfp-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('pfp-modal')">&times;</span>
            <h2>Change Profile Picture</h2>
            <form id="profilePicForm" action="upload_profile_picture.php" method="POST" enctype="multipart/form-data">
                <input type="file" id="profilePic" name="profilePic" accept="image/*" required>
                <button type="submit" id="uploadPfpBtn">Upload</button>
            </form>
        </div>
    </div> 
    <script>
        const settingsToggle = document.querySelector('.settings-toggle');
        const settingsDropdown= document.querySelector('.settings-dropdown');
        const pfpModal = document.getElementById('pfp-modal');
        const userModal = document.getElementById('user-modal');
        const passwordModal = document.getElementById('password-modal');
        const allModals = [pfpModal, userModal, passwordModal].filter(m => m !== null);
        const openPfpBtn = document.getElementById('change-picture-btn');
        const openUserBtn = document.getElementById('change-user-btn');
        const openPasswordBtn = document.getElementById('change-password-btn');
        const closeButtons = document.querySelectorAll('.close-btn');
        settingsToggle.addEventListener('click', () => {
            settingsDropdown.classList.toggle('active');
        });
        const closeAllModals = () => {
            allModals.forEach(modal => {
                if(modal) modal.style.display = 'none';
            });
        };
        openPfpBtn.addEventListener('click', () => {
            closeAllModals();
            pfpModal.style.display = 'block'; 
            settingsDropdown.classList.remove('active');
        });
        openUserBtn.addEventListener('click', () => {
            closeAllModals();
            userModal.style.display = 'block'; 
            settingsDropdown.classList.remove('active');
        });
        openPasswordBtn.addEventListener('click', () => {
            closeAllModals();
            passwordModal.style.display = 'block'; 
            settingsDropdown.classList.remove('active');
        });
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                closeAllModals();
            })
        })
        window.addEventListener('click', (event) => {
            if(allModals.includes(event.target)){
                closeAllModals();
            }
        });
        document.querySelector('.upload-btn').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('uploadModal').style.display = 'flex';
        });
        function closeModal() {
            document.getElementById('uploadModal').style.display = 'none';
        }
        window.onclick = function(event) {
            const modal = document.getElementById('uploadModal');
            if (event.target === modal) {
            modal.style.display = 'none';
            }
        }
        function addTextBox(isStep) {
            const container = document.getElementById(isStep ? 'stepContainer' : 'ingredientContainer');
            const newInput = document.createElement('input');
            newInput.type = 'text';
            newInput.name = isStep ? 'step[]' : 'ingredient[]';
            newInput.placeholder = isStep ? 'Enter a step' : 'Enter an ingredient';
            container.appendChild(newInput);
        }
        const profilePicForm = document.getElementById('profilePicForm');
        if (profilePicForm) {
            profilePicForm.addEventListener('submit', function(e) {
            });
        }
        const profilePicInput = document.getElementById('profilePic');
        if (profilePicInput && profilePicForm) {
            profilePicInput.addEventListener('change', function() {
                if (profilePicInput.files.length > 0) {
                    profilePicForm.submit();
                }
            });
            document.querySelector('label[for="profilePic"]').addEventListener('click', function() {
                profilePicInput.click();
            });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            <?php if (isset($_SESSION['password_error'])): ?>
                const passwordModal = document.getElementById('password-modal');
                if(passwordModal) {
                    passwordModal.style.display = 'block';
                }
            <?php
                unset($_SESSION['password_error']);
            endif;
            ?>
        });
    </script>

    <!-- Recipe Popup Modal for When a User Clicks on Recipe That the User Uploaded. -->
    <div id="recipeModal" class="modal">
        <div class="modal-content recipe-modal">
            <span class="close-btn" onclick="closeRecipeModal()">&times;</span>
            <h2 id="modalTitle"></h2>
            <br>
            <img id="modalImage" src="" alt="Recipe Image" class="modal-image">
            <br>
            <h3>Ingredients</h3>
            <p id="modalIngredients"></p>
            <br>
            <h3>Directions</h3>
            <p id="modalDirections"></p>
            <br>
            <h3>Nutrition Facts (per serving)</h3>
            <p>Calories: <span id="modalCalories"></span></p>
            <p>Protein: <span id="modalProtein"></span>g</p>
            <p>Carbs: <span id="modalCarbs"></span>g</p>
            <p>Fat: <span id="modalFat"></span>g</p>
            
        </div>
    </div>

    <script>
        function openRecipeModal(title, image, ingredients, directions, calories, protein, carbs, fat) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalImage').src = image;
            document.getElementById('modalIngredients').innerHTML = ingredients;
            document.getElementById('modalDirections').innerHTML = directions;
            document.getElementById('modalCalories').textContent = calories;
            document.getElementById('modalProtein').textContent = protein;
            document.getElementById('modalCarbs').textContent = carbs;
            document.getElementById('modalFat').textContent = fat;

            document.getElementById('recipeModal').style.display = 'flex';
        }

        function closeRecipeModal() {
            document.getElementById('recipeModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('recipeModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>

    <!-- When the delete recipe button is clicked, make a POST request to the delete_recipe.php to delete the entry
         from the database. Then removes the recipe from the frontend. -->
    <script>
        function deleteRecipe(event, recipeId, type) {
            event.preventDefault(); // Prevent the default behavior of the <a> tag
            event.stopPropagation(); // prevent opening the modal

            fetch("../php/delete_recipe.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "recipe_id=" + encodeURIComponent(recipeId) + "&type=" + encodeURIComponent(type)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Select the recipe card that matches the deleted recipe
                    const recipeBox = document.querySelector(`.recipe-box[data-id='${recipeId}']`);
                    if (recipeBox) {
                        // Fade out for a smooth effect before removing
                        recipeBox.style.transition = "opacity 0.3s ease";
                        recipeBox.style.opacity = "0";
                        setTimeout(() => recipeBox.remove(), 300);
                    }
                } else {
                    alert(data.error || "Error deleting recipe");
                }
            })
            .catch(() => {
                alert("Failed to connect to server.");
            });
        }
    </script>


</body>
</html>