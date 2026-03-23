<?php
// google-auth.php
require_once 'db.php';
require_once 'vendor/autoload.php';

$clientID = $_ENV['GOOGLE_CLIENT_ID'];
$clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'];
$redirectUri = 'http://localhost/neu-library/google-callback.php';

$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");

// This generates the URL to Google's login page
$authUrl = $client->createAuthUrl();

// Redirect the user to Google
header("Location: " . $authUrl);
exit();