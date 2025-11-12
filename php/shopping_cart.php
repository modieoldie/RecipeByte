<?php

	require_once __DIR__ . '/check_login.php';
	require_once __DIR__ . '/config.php';

	// Prevent cached sessions from persisting
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");

	$profile_picture_path = '../images/profile.png';
	
	if (isset($_SESSION['email'])) {
    		$stmt = $conn->prepare("SELECT id, profile_picture_path FROM users WHERE email = ?");
    		$stmt->bind_param("s", $_SESSION['email']);
    		$stmt->execute();
    		$result = $stmt->get_result();
    		$user = $result->fetch_assoc();

    		if ($user && !empty($user['profile_picture_path']) && file_exists($user['profile_picture_path'])) {
       	 		$profile_picture_path = $user['profile_picture_path'];
    		}
	}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Grocery list</title>
    <link rel="stylesheet" href="../css/sections/header.css" />
    <link rel="stylesheet" href="../css/grocery.css" />

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
            <h1>GROCERY LIST</h1>
          </div>
          <div class="header__right">
            <ul class="header__menu header__menu--desktop">
              <li class="header__item">
                <a href="../html/shopping_cart.html" class="button">
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
                <li><a href="../html/shopping_cart.html">Cart</a></li>
              </ul>
            </div>
          </div>
        </div>
      </nav>
    </header>

    <main class="cart-container">
        <section class="cart-items-list">
            <div class="cart-item">
                <div class="cart-item-image">
                    <img src="../images/Baked-seabass-ec17e28.jpg" alt="recipe 1"/>
                </div>
                <div class="cart-item-details">
                    <h3>Baked Seabass</h3>
                </div>
                <div class="cart-item-quantity">
                    <button class="quantity-btn" aria-label="Decrease quantity">-</button>
                    <span class="quantity-value">1</span>
                    <button class="quantity-btn" aria-label="Increase quantity">+</button>
                </div>
            </div>

            <div class="cart-item">
                <div class="cart-item-image">
                    <img src="../images/Chicken-Fettuccine-Alfredo-4.jpg" alt="recipe 2"/>
                </div>
                <div class="cart-item-details">
                    <h3>Chicken Alfredo Fettuccine</h3>
                </div>
                <div class="cart-item-quantity">
                    <button class="quantity-btn" aria-label="Decrease quantity">-</button>
                    <span class="quantity-value">1</span>
                    <button class="quantity-btn" aria-label="Increase quantity">+</button>
                </div>
            </div>

            <div class="cart-item">
                <div class="cart-item-image">
                    <img src="../images/steak.jpg" alt="recipe 3"/>
                </div>
                <div class="cart-item-details">
                    <h3>Steak</h3>
                </div>
                <div class="cart-item-quantity">
                    <button class="quantity-btn" aria-label="Decrease quantity">-</button>
                    <span class="quantity-value">1</span>
                    <button class="quantity-btn" aria-label="Increase quantity">+</button>
                </div>
            </div>

            <div class="cart-item">
                <div class="cart-item-image">
                    <img src="../images/vegan-chicken-noodle-soup-feature.jpg" alt="recipe 4"/>
                </div>
                <div class="cart-item-details">
                    <h3>Vegan Chicken Noodle Soup</h3>
                </div>
                <div class="cart-item-quantity">
                    <button class="quantity-btn" aria-label="Decrease quantity">-</button>
                    <span class="quantity-value">1</span>
                    <button class="quantity-btn" aria-label="Increase quantity">+</button>
                </div>
            </div>
        </section>

        <section class="cart-summary">
            <div class="total-ingredients-box">
                <h4>Total Ingredients</h4>
                <ul id="ingredients-list-placeholder">
                    <li>Calculated here</li>
                </ul>
            </div>
            <div class="cart-summary-actions">
                <button class="save-file-btn">Save file</button>
            </div>
        </section>
    </main>
    <script src="../js/script.js" type="module"></script>
  </body>
</html>
