<?php

session_start();

require 'includes/db.php';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm = $_POST['re-password'];

    if($password !== $confirm) {
        $_SESSION['error'] = "Passwords do not match.";
        header('Location: signup.php');
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt-> rowCount() > 0 ) {
        $_SESSION['error'] = "Username already exists.";
        header('Location: signup.php');
        exit();
    }       

    $hashedPassword = md5($password);


    $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, username, email, password) VALUES (?,?,?,?,?)");
    $stmt->execute([$firstname, $lastname, $username, $email, $hashedPassword]);

    $_SESSION['success'] = "Your Account has been created. You can now Login.";
    header('Location: login.php');
    exit();
    
}
