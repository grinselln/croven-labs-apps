<?php

session_start();

define('SPOTIFY_CLIENT_ID', '49fb848606304b638b90065a34c8071d');
define('SPOTIFY_CLIENT_SECRET', 'cc8efadc16f442a6b68f3836ee75bf32');
define('SPOTIFY_REDIRECT_URI', 'https://127.0.0.1/croven-labs-apps/apps/lineups/spotify-exporter/callback.php');

function spotifyGet($url, $token)
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token
        ]
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }

    curl_close($ch);

    return json_decode($response, true);
}

function spotifyPost($url, $data, $headers = [])
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => $headers
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }

    curl_close($ch);

    return json_decode($response, true);
}