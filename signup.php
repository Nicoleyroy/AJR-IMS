<?php

session_start();

?>
<!DOCTYPE html>
<html>
<head>
    <title>SIGNUP</title>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Comapatible" content="IE=edge">
    <meta name="viewport" content="width=device=width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/signup.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    <div class="container">
        <div class="form-box signup">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success" role="alert">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <form action="signup_validate.php" method="POST">
                <h1>Create Account</h1>
                
                <div class="name-fields">
                    <div class="input-box">
                        <input required class="form-control" type="text" placeholder="First name" name="firstname">
                        <i class='bx bxs-user'></i>
                    </div>
                    <div class="input-box">
                        <input required class="form-control" type="text" placeholder="Last name" name="lastname">
                        <i class='bx bxs-user'></i>
                    </div>
                </div>

                <div class="input-box">
                    <input required class="form-control" type="email" placeholder="Email" name="email">
                    <i class='bx bxs-envelope'></i>
                </div>

                <div class="input-box">
                    <input required class="form-control" type="text" placeholder="Username" name="username">
                    <i class='bx bxs-user-circle'></i>
                </div>

                <div class="input-box">
                    <input required class="form-control" type="password" placeholder="Password" name="password">
                    <i class='bx bxs-lock-alt'></i>
                </div>

                <div class="input-box">
                    <input required class="form-control" type="password" placeholder="Re Enter password" name="re-password">
                    <i class='bx bxs-lock-alt'></i>
                </div>

                <button type="submit" class="btn">Create Account</button>

                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>