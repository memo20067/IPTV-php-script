<?php
// Parse M3U/M3U8 files and insert channels into DB
include('db.php');
function parse_m3u($file, $playlist_id) {
    $lines = file($file);
    $name = ''; $url = '';
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#EXTINF:') === 0) {
            preg_match('/#EXTINF:.*,(.*)/', $line, $m);
            $name = $m[1] ?? '';
        } else if ($line && !str_starts_with($line, '#')) {
            $url = $line;
            if ($name && $url) {
                global $conn;
                $stmt = $conn->prepare("INSERT INTO channels (playlist_id, name, stream_url) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $playlist_id, $name, $url);
                $stmt->execute();
                $name = ''; $url = '';
            }
        }
    }
}
?>