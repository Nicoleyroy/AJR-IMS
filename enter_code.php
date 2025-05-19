<?php

session_start();

require 'includes/db.php';

if($_SERVER['REQUEST_METHOD'] === "POST"){
    $enteredCode = $_POST['code'];
    $email = $_SESSION['email'];

    if(!isset($_SESSION['email'])){
      $_SESSION['error'] = "No email session found, please try again.";
      header("Location: forgot_password.php");
      exit();
    }

    $stmt  = $pdo->prepare("SELECT reset_code FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user){
      if($enteredCode === $user['reset_code']){
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_code_verified'] = true;
        
        header("Location: reset_password.php");
        exit();

      } else {
        $_SESSION['error'] = "Invalid code. Please try again.";
      }
    } else {
      $_SESSION['error'] = "No user found with that email.";
    }
}

?>


<!DOCTYPE html>
<html>
<head>
    <!-- title -->
    <title>Enter code</title>
    <!-- css -->
    <link rel="stylesheet" href="styles\enter_code.css">
    <meta charset="UTF-8">
    

</head>

<body>
  <div class="container">
    <div class="card">
      <h3 style="text-align: center">Enter code</h3>
      <?php if (isset($_SESSION['error'])): ?>
        <p id="alertMessage" style="border: 1px solid #F74141; padding: 0.5rem 1rem !important; border-radius: 0.25rem; display: block; color: #F74141;">
          <?= $_SESSION['error'];
          unset($_SESSION['error']); ?>
        </p>
      <?php endif; ?>

      <?php if (isset($_SESSION['success'])): ?>
        <p id="alertMessage" style="border: 1px solid #77DD77; padding: 0.5rem 1rem !important; border-radius: 0.25rem; display: block; color: #77DD77;">
          <?= $_SESSION['success'];
          unset($_SESSION['success']); ?>
        </p>
      <?php endif; ?>
      <form action="enter_code.php" method="POST">
        <input type="number" placeholder="Enter code" maxlength="6" name="code"/>
        <button>Submit</button>
      </form>
    </div>
  </div>
</body>

</html>