<?php

//php version of account.html

session_start();

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['sign_up_error'] ?? ''
];

$activeForm = $_SESSION['active_form'] ?? 'login';

function displayError($error){
    return !empty($error) ? "<p class='error-message'>$error</p>" : '';
}

function isActiveForm($formName, $activeForm){
    return $formName === $activeForm ? 'active' : '';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="/css/sections/login.css">
    <link rel="stylesheet" href="/css/sections/header.css">
<body>

    <!-- Header -->
     <header>
        <nav class="header__nav">
            <div class="header__container">
                <a href="/" id="header__logo">
                    <img src="/Images/company_logo.jpg" alt="Company Logo" class="logo"\>
                </a>
                <a href="/" id="header__companyName">RecipeByte</a>
                <img src="get_profile_pic.php" alt="Profile Picture" class="profile-pic">
            </div>
        </nav>
    </header>

    <!-- Box -->
    <section class="box">
        <div class="container">
            <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login-form">
                <form action="login_register.php" method="post">
                    <h2>Login</h2>
                    <?= displayError($errors['login']); ?>
                    <input type="email" name="email" placeholder="Email or Phone number" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <p><a href="#">Forgot Password</a></p>
                    <div class="button-group">
                        <button type="submit" name="login" id="login-btn">Login</button>
                        <button type="button" name="sign_up" id="signup-btn" onclick="showForm('register-form')" formnovalidate>Sign Up</button>
                    </div>
                </form>
            </div>

            <div class="form-box <?= isActiveForm('register', $activeForm); ?>" id="register-form">
                <form action="login_register.php" method="post">
                    <h2>Sign Up</h2>
                    <?= displayError($errors['register']); ?>
                    <input type="email" name="email" placeholder="Email or Phone number" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                    <p>Already have an account? <a href="#" onclick="showForm('login-form')">Login</a></p>
                    <div class="button-group">
                        <button type="submit" name="sign_up" id="signup-btn">Sign Up</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script src="/js/script.js"></script>
</body>
</html>

<?php 

session_unset();

?>