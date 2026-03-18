<?php
include 'db.php';

$provider = getGoogleProvider();

// Generate authorization URL
$authUrl = $provider->getAuthorizationUrl([
    'scope' => [
        'openid',
        'email',
        'profile'
    ]
]);

// Store state to prevent CSRF
$_SESSION['oauth2state'] = $provider->getState();

header('Location: ' . $authUrl);
exit();
?>