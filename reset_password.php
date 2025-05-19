<?php

session_start(); 

require 'includes/db.php';
 

if(!isset($_SESSION['email']) || !isset($_SESSION['reset_code_verified']) ||  !$_SESSION['reset_code_verified']){
    header("Location: forgot_password.php");
    exit();
  }

  if($_SERVER['REQUEST_METHOD'] === "POST"){
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];

    if($newPassword === $confirmPassword){
      $hashedPassword = md5($newPassword);

      $stmt = $pdo->prepare("UPDATE users SET PASSWORD = ? WHERE email = ?");
      $stmt->execute([$hashedPassword, $_SESSION['email']]); 

      unset($_SESSION['reset_email']);
      unset($_SESSION['reset_code_verified']);

      $_SESSION['success'] = "Your password has been reset successfully. You can now log in with your new password.";
      header("Location: login.php");
      exit();
    } else {
      $_SESSION['error'] = "Passwords do not match. Please try again.";
      header("Location: reset_password.php");
      exit();
    }
  }


?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Comapatible" content="IE=edge">
    <meta name="viewport" content="width=device=width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/reset_password.css">
    <link rel="stylesheet" href="styles/notifications.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    <div class="container">
        <div class="form-box">
            <form action="reset_password.php" method="POST">
                <h1>Reset Password</h1>
                <p class="subtitle">Enter your new password below</p>

                <div class="input-box">
                    <input type="password" name="password" class="form-control" placeholder="New Password" required>
                    <i class='bx bxs-lock-alt'></i>
                </div>

                <div class="input-box">
                    <input type="password" name="confirm-password" class="form-control" placeholder="Confirm Password" required>
                    <i class='bx bxs-lock-alt'></i>
                </div>

                <button type="submit" class="btn">Reset Password</button>

                <div class="back-to-login">
                    <p>Remember your password? <a href="login.php">Back to login</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="js/notifications.js"></script>
    
    <?php if (isset($_SESSION['error'])): ?>
    <script>
        showNotification('error', 'Error', '<?= htmlspecialchars($_SESSION['error']); ?>');
    </script>
    <?php unset($_SESSION['error']); endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
    <script>
        showNotification('success', 'Success', '<?= htmlspecialchars($_SESSION['success']); ?>');
    </script>
    <?php unset($_SESSION['success']); endif; ?>
</body>
</html>