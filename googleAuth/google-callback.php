<?php

require_once '../vendor/autoload.php'; 

session_start();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();


$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT']);
$client->addScope('email');
$client->addScope('profile');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);

        $oauth2 = new Google_Service_Oauth2($client);
        $userInfo = $oauth2->userinfo->get();

        $_SESSION['user_type'] = 'google';
        $_SESSION['user_name'] = $userInfo->name;
        $_SESSION['user_email'] = $userInfo->email;
        $_SESSION['user_image'] = $userInfo->picture;

        $_SESSION['success'] = 'Login with Google!';
        header('Location: ../dashboard.php');
        exit();
    } else {
        $_SESSION['error'] = 'Login failed!';
        header('Location: ../login.php');
        exit();
    }
} else {
    $_SESSION['error'] = 'Invalid login attempt!';
    header('Location: ../login.php');
    exit();
}
?>