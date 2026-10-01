<?php
require_once 'config.php';

$scope = implode(' ', [
    'playlist-read-private',
    'playlist-read-collaborative'
]);

$authUrl = 'https://accounts.spotify.com/authorize?' .
http_build_query([
    'client_id' => SPOTIFY_CLIENT_ID,
    'response_type' => 'code',
    'redirect_uri' => SPOTIFY_REDIRECT_URI,
    'scope' => $scope,
    'show_dialog' => 'true'
]);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Spotify Playlist Exporter</title>

    <style>
        body{
            font-family:Arial;
            text-align:center;
            margin-top:100px;
        }

        .btn{
            background:#1DB954;
            color:white;
            padding:15px 25px;
            border:none;
            border-radius:5px;
            text-decoration:none;
            font-size:18px;
        }
    </style>
</head>
<body>

<h1>Spotify Playlist Exporter</h1>

<p>Login with Spotify to view and export playlists.</p>

<a class="btn" href="<?= htmlspecialchars($authUrl) ?>">
    Login With Spotify
</a>

</body>
</html>