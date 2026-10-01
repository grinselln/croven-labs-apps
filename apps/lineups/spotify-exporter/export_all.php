<?php

require_once 'config.php';

if (!isset($_SESSION['access_token'])) {
    die('Not logged in');
}

$token = $_SESSION['access_token'];

$tempDir =
sys_get_temp_dir() .
'/spotify_export_' .
time();

mkdir($tempDir,0777,true);

$playlists = [];

$url = 'https://api.spotify.com/v1/me/playlists?limit=50';

while($url){

    $data = spotifyGet($url,$token);

    foreach($data['items'] as $playlist){
        $playlists[] = $playlist;
    }

    $url = $data['next'];
}

foreach($playlists as $playlist){

    $safeName = preg_replace(
        '/[^A-Za-z0-9 _-]/',
        '',
        $playlist['name']
    );

    if(empty($safeName)){
        $safeName = 'playlist_' . $playlist['id'];
    }

    $csvFile = $tempDir . '/' . $safeName . '.csv';

    $fp = fopen($csvFile,'w');

    fputcsv($fp,[
        'Track Name',
        'Artist',
        'Album',
        'Duration Seconds',
        'Spotify URL'
    ]);

    $trackUrl =
    'https://api.spotify.com/v1/playlists/' .
    $playlist['id'] .
    '/items?limit=100';

    while($trackUrl){

        $trackData = spotifyGet($trackUrl,$token);

        foreach($trackData['items'] as $item){

            if(empty($item['track'])){
                continue;
            }

            $track = $item['track'];

            $artists = [];

            foreach($track['artists'] as $artist){
                $artists[] = $artist['name'];
            }

            fputcsv($fp,[
                $track['name'],
                implode(', ',$artists),
                $track['album']['name'],
                round($track['duration_ms']/1000),
                $track['external_urls']['spotify']
            ]);
        }

        $trackUrl = $trackData['next'];
    }

    fclose($fp);
}

$zipFile =
$tempDir .
'/spotify_playlists.zip';

$zip = new ZipArchive();

$zip->open(
    $zipFile,
    ZipArchive::CREATE | ZipArchive::OVERWRITE
);

foreach(glob($tempDir . '/*.csv') as $csv){

    $zip->addFile(
        $csv,
        basename($csv)
    );
}

$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="spotify_playlists.zip"');
header('Content-Length: ' . filesize($zipFile));

readfile($zipFile);

foreach(glob($tempDir . '/*') as $file){
    unlink($file);
}

rmdir($tempDir);

exit;