<?php

require_once 'config.php';

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}

$token = $_SESSION['access_token'];

// Define the artists you want to group by
$targetArtists = ['AFROJACK', 'MARTIN GARRIX'];

$allTracks = [];
$skippedPlaylists = [];

$url = 'https://api.spotify.com/v1/me/playlists?limit=50';

// 1. Fetch all tracks from all accessible playlists
while ($url) {
    $data = spotifyGet($url, $token);
    if (isset($data['error'])) die('<pre>' . print_r($data, true) . '</pre>');
    if (!isset($data['items'])) die('Unable to load playlists.');

    foreach ($data['items'] as $playlist) {
        $trackUrl = 'https://api.spotify.com/v1/playlists/' . $playlist['id'] . '/items?limit=100';
        while ($trackUrl) {
            $trackData = spotifyGet($trackUrl, $token);
            if (isset($trackData['error'])) {
                if (!isset($skippedPlaylists[$playlist['id']])) {
                    $skippedPlaylists[] = ['name' => $playlist['name'], 'owner' => $playlist['owner']['display_name'] ?? 'Unknown'];
                }
                break;
            }
            foreach ($trackData['items'] as $item) {
                if (!empty($item['track']) && is_array($item['track'])) {
                    $allTracks[] = $item['track'];
                }
            }
            $trackUrl = $trackData['next'] ?? null;
        }
    }
    $url = $data['next'] ?? null;
}

// 2. Group tracks by target artists (Case-Insensitive)
$groupedTracks = array_fill_keys($targetArtists, []);

foreach ($allTracks as $track) {
    $trackArtistNames = array_map(function($a) { return strtoupper($a['name']); }, $track['artists'] ?? []);
    
    foreach ($targetArtists as $target) {
        if (in_array(strtoupper($target), $trackArtistNames)) {
            $groupedTracks[$target][] = $track;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Spotify Playlist Exporter</title>
    <style>
        body{ font-family:Arial,sans-serif; margin:20px; }
        .artist-header { color: #1DB954; margin-top: 40px; border-bottom: 2px solid #1DB954; }
        .controls{ margin-bottom:20px; }
        .btn{ display:inline-block; background:#1DB954; color:#fff; text-decoration:none; padding:10px 15px; border-radius:4px; margin-right:10px; }
        .logout{ background:#cc0000; }
        .warning{ background:#fff3cd; border:1px solid #ffe69c; padding:12px; margin-top:20px; }
        table{ width:100%; border-collapse:collapse; margin-bottom: 20px; }
        th,td{ border:1px solid #ddd; padding:8px; text-align:left; }
        th{ background:#f5f5f5; }
    </style>
</head>
<body>

<h1>My Spotify Library</h1>
<div class="controls">
    <a class="btn" href="export_all.php">Export All</a>
    <a class="btn logout" href="logout.php">Logout</a>
</div>

<?php foreach ($groupedTracks as $artistName => $tracks): ?>
    <?php if (!empty($tracks)): ?>
        <h2 class="artist-header"><?= htmlspecialchars($artistName) ?> (<?= count($tracks) ?> tracks)</h2>
        <table>
            <tr>
                <th>Track</th>
                <th>Artist(s)</th>
                <th>Album</th>
            </tr>
            <?php foreach ($tracks as $track): ?>
                <tr>
                    <td><?= htmlspecialchars($track['name'] ?? '') ?></td>
                    <td><?= htmlspecialchars(implode(', ', array_column($track['artists'], 'name'))) ?></td>
                    <td><?= htmlspecialchars($track['album']['name'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
<?php endforeach; ?>

<?php if (!empty($skippedPlaylists)): ?>
    <div class="warning">
        <strong>Skipped Playlists:</strong>
        <ul>
            <?php foreach ($skippedPlaylists as $p): ?>
                <li><?= htmlspecialchars($p['name']) ?> (Owner: <?= htmlspecialchars($p['owner']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

</body>
</html>