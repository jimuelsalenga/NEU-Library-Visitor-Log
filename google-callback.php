<?php
// google-callback.php
require_once 'db.php';
require_once 'vendor/autoload.php';

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri('http://localhost/neu-library/google-callback.php');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // Get user profile info
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();

    // Save to session for use in your dashboard
    $_SESSION['user_id'] = $google_account_info->id;
    $_SESSION['user_name'] = $google_account_info->name;
    $_SESSION['user_email'] = $google_account_info->email;
    $_SESSION['user_pic'] = $google_account_info->picture;

    // Redirect to the Details page first to capture their Program
    header("Location: user-details.php");
    exit();
} else {
    header("Location: index.php?error=auth_failed");
    exit();
}