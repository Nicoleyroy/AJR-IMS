<?php

session_start();
require 'includes/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor\autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $reset_code = rand(100000, 999999);

        $update = $pdo->prepare("UPDATE users SET reset_code = ? WHERE email = ?");
        $update->execute([$reset_code, $email]);

        $_SESSION['email'] = $email;


        $mail = new PHPMailer(true);

          try{

          $mail->isSMTP();
          $mail->Host = 'smtp.gmail.com';
          $mail->SMTPAuth = true;
          $mail->Username = 'jnicole11121024@gmail.com';
          $mail->Password = 'ejkg nuil zunb ajmb';
          $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
          $mail->Port = 587;

          $mail->setFrom('jnicole11121024@gmail.com', 'Joanna Nicole Yroy');
          $mail->addAddress($email, "THIS IS YOUR CLIENT");

          $mail->isHTML(true);
          $mail->Subject = "Password Reset Code";


          $mail->Body = "
              <p>Hello, This is your password reset code: {$reset_code}</p>";

                $mail->AltBody = "Hello, This is your password reset code:\n {$reset_code}\n\n";
                 $mail->send();
              
                  $_SESSION['email_sent'] = true;
                  $_SESSION['success'] = "Verification code has been sent to your email";
                 header("Location: enter_code.php");
                exit();
                 } catch (Exception $e) {
                 $_SESSION['Error'] = "Message could not be sent";
                 header("Location: forgot_password.php");
                 exit();
                 }


    } else {
        $_SESSION['error'] = "No user found with that email";
        header('Location: forgot_password.php');
        exit();
    }
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Comapatible" content="IE=edge">
    <meta name="viewport" content="width=device=width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/forgot_password.css">
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

            <form action="forgot_password.php" method="POST">
                <h1>Forgot Password</h1>
                <p class="subtitle">Enter your email to receive a verification code</p>

                <div class="input-box">
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                    <i class='bx bxs-envelope'></i>
                </div>

                <button type="submit" class="btn">Send Verification Code</button>

                <div class="back-to-login">
                    <p>Remember your password? <a href="login.php">Back to login</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
