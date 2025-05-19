<?php

session_start();
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$siteKey = $_ENV['RECAPTCHA_SITE_KEY'];

?>

<!DOCTYPE html>
<html>
<head>
    <title>LOGIN</title>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Comapatible" content="IE=edge">
    <meta name="viewport" content="width=device=width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/login.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>
   <div class="container">
        <div class="form-box">
        <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <i class='bx bx-error-circle'></i>
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success" role="alert">
                    <i class='bx bx-check-circle'></i>
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <form action="login_validate.php" method="POST">
            <h1>Login</h1>   

            <div class="input-box"> 
                    <input required class="form-control" type="text" placeholder="Username" name="username">
                    <i class='bx bxs-user'></i>
                </div>

                <div class="input-box">
                <input required class="form-control" type="password" placeholder="Password" name="password">
                    <i class='bx bxs-lock-alt'></i>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="forgot_password.php">Forgot password?</a>
                </div>

                <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($siteKey)?>" data-theme="light"></div>

                <button type="submit" class="btn">Login</button>
                
                <div class="social-login">
                        <p>or login with social platforms</p>
                        <a href="googleAuth\google-login.php" class="btn-google">
                            <i class='bx bxl-google'></i>
                        </a>
                    </div>

                <div class="register-link">
                    <p>Don't have an account? <a href="signup.php">Register here</a></p>
                </div>
            </form>
        </div>
   </div>

      <script src="https://www.google.com/recaptcha/api.js"></script>
</body>
</html>
