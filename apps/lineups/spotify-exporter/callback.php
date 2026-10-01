<?php

require_once 'config.php';

if (!isset($_GET['code'])) {
    die('Authorization failed.');
}

$response = spotifyPost(
    'https://accounts.spotify.com/api/token',
    [
        'grant_type' => 'authorization_code',
        'code' => $_GET['code'],
        'redirect_uri' => SPOTIFY_REDIRECT_URI
    ],
    [
        'Authorization: Basic ' .
        base64_encode(
            SPOTIFY_CLIENT_ID .
            ':' .
            SPOTIFY_CLIENT_SECRET
        )
    ]
);

if (!isset($response['access_token'])) {

    echo '<pre>';
    print_r($response);
    exit;
}

$_SESSION['access_token'] = $response['access_token'];

header('Location: playlists.php');
exit;