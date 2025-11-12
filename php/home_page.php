<?php
require_once __DIR__ . '/check_login.php';
require_once __DIR__ . '/config.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
$email = $_SESSION['email'] ?? 'unknown@example.com';
$username = $_SESSION['username'] ?? 'User';
$recipes = [];
$search_query = $_GET['search_query'] ?? '';
$search_type = $_GET['search_type'] ?? 'recipe';
$sort_options = ['calories', 'protein', 'fat', 'carbs', 'average_rating'];
$sort = $_GET['sort'] ?? '';
$order_by = '';
if (in_array($sort, $sort_options)) {
    $order_by = "ORDER BY $sort DESC";
} else {
    $order_by = "ORDER BY recipe_id DESC";
}
if($conn && !$conn->connect_error){
  if($search_type === 'recipe' && !empty($search_query)){
    $sql = "SELECT recipe_id, title, image_path, average_rating, calories, protein, fat, carbs FROM recipes WHERE title LIKE ? $order_by";
    $stmt = $conn->prepare($sql);
    $like_query = "%" . $search_query . "%";
    $stmt->bind_param("s", $like_query);
    $stmt->execute();
    $result = $stmt->get_result();
  } else {
    $sql = "SELECT recipe_id, title, image_path, average_rating, calories, protein, fat, carbs FROM recipes $order_by";
    $result = $conn->query($sql);
  }
  if($result && $result->num_rows > 0){
    $recipes = $result->fetch_all(MYSQLI_ASSOC);
  } else if($result === false){
      error_log("Database query error: " . $conn->error);
  }
} else {
    error_log("Database connection error: " . $conn->connect_error);
}
$profile_picture_path = '../images/profile.png';
if (isset($_SESSION['email'])) {
    $stmt = $conn->prepare("SELECT id, profile_picture_path FROM users WHERE email = ?");
    $stmt->bind_param("s", $_SESSION['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if ($user && !empty($user['profile_picture_path']) && file_exists($user['profile_picture_path'])) {
        $profile_picture_path = '../' . ltrim($user['profile_picture_path'], '/');
    }
}
function gen_stars($rating, $maxStars = 5){
    $stars_html = '';
    $rounded_rating = round($rating * 2) / 2;
    $filled_stars_count = floor($rounded_rating);
    $has_half_stars = ($rounded_rating - $filled_stars_count) == 0.5;
    $empty_stars_count = $maxStars - $filled_stars_count - ($has_half_stars ? 1 : 0);
    for($i = 0; $i < $filled_stars_count; $i++){
        $stars_html .= '<span class="star filled">&#9733;</span>';
    }
    if($has_half_stars){
        $stars_html .= '<span class="star half">&#9733;</span>';
    }
    for($i = 0; $i < $empty_stars_count; $i++){
      $stars_html .= '<span class="star">&#9734;</span>';
    }
  return $stars_html;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recipe browsing</title>
    <link rel="stylesheet" href="../css/sections/header.css" />
    <link rel="stylesheet" href="../css/sections/hero.css" />
    <link rel="stylesheet" href="../css/sections/product.css" />
    <link rel="stylesheet" href="../css/sections/filter.css" />
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
    <section class="hero">
      <div class="hero-image">
        <img src="../images/cooking.webp" alt="Cooking" />
        <div class="hero-image__overlay"></div>
        <div class="hero-search-container">
          <form action="home_page.php" method="GET" id="heroSearchForm" style="display:flex; align-items:center; gap:10px;">
            <img
              src="../images/search_bar.png"
              alt="search"
              class="search-icon"
            />
            <input
              type="text"
              id="searchInput"
              name="search_query"
              class="search-input"
              placeholder="Search recipes"
              value="<?php echo htmlspecialchars($search_query); ?>"
            />
            <input type="hidden" id="searchType" name="search_type" value="recipe">
            <div class="search-toggle-icons">
              <img
                src="<?= htmlspecialchars($profile_picture_path) ?>"
                id="profileSearchIcon"
                class="toggle-icon"
                title="Search users"
              />
              <img
                src="../images/food_icon.png"
                alt="Search Recipes"
                id="recipeSearchIcon"
                class="toggle-icon hidden"
                title="Search recipes"
              />
            </div>
          </form>
        </div>
      </div>
    </section>
    <div class="sort-dropdown-container" style="width:100%;display:flex;justify-content:flex-end;margin:16px 24px 8px 0;">
      <form action="home_page.php" method="GET" style="margin:0;display:inline;">
        <input type="hidden" name="search_query" value="<?php echo htmlspecialchars($search_query); ?>">
        <input type="hidden" name="search_type" value="recipe">
        <select name="sort" id="sort-dropdown" onchange="this.form.submit()" style="height:36px; border-radius:5px; border:1px solid #ccc; padding:0 8px; min-width:140px;">
          <option value="" <?= $sort==''?'selected':''; ?>>All Recipes</option>
          <option value="average_rating" <?= $sort=="average_rating"?'selected':''; ?>>Rating</option>
          <option value="calories" <?= $sort=="calories"?'selected':''; ?>>Calories</option>
          <option value="protein" <?= $sort=="protein"?'selected':''; ?>>Protein</option>
          <option value="fat" <?= $sort=="fat"?'selected':''; ?>>Fat</option>
          <option value="carbs" <?= $sort=="carbs"?'selected':''; ?>>Carbs</option>
        </select>
      </form>
    </div>
    <section class="products">
        <div class="product-container">
            <?php if (empty($recipes)): ?>
                <p>No recipes found.</p>    
            <?php else : ?>
                <?php foreach($recipes as $recipe): ?>
                    <div class="product-card">
                        <div class="product-box">
                            <a href="../php/display_recipe.php?id=<?php echo htmlspecialchars($recipe['recipe_id']); ?>" id="product__page">
                                <img src="<?php echo htmlspecialchars($recipe['image_path'] ?? "https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442aj/website/images/food_icon.png"); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>" />
                            </a>
                            <div class="product-label">
                                <p><?php echo htmlspecialchars($recipe['title']); ?></p>
                            </div>
                        </div>
                        <div class="product-rating">
                            <?php echo gen_stars($recipe['average_rating']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
    <script src="../js/script.js" type="module"></script>
  </body>
</html>

