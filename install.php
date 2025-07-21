<?php
// Guided installer for initial setup

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $db_name = $_POST['db_name'];

    $conn = new mysqli($db_host, $db_user, $db_pass);
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    // Create database if not exists
    $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name`");
    $conn->select_db($db_name);

    // Create tables
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(64) UNIQUE,
        password VARCHAR(256),
        hardware_id VARCHAR(128),
        subscription_expiry DATETIME,
        trial_expiry DATETIME,
        is_admin TINYINT DEFAULT 0,
        banned TINYINT DEFAULT 0
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS playlists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(128),
        url TEXT,
        uploaded_by INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS channels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        playlist_id INT,
        name VARCHAR(128),
        stream_url TEXT
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS admin_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message TEXT,
        active TINYINT DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Save config file
    $config = "<?php\nreturn [\n'db_host' => '$db_host',\n'db_user' => '$db_user',\n'db_pass' => '$db_pass',\n'db_name' => '$db_name'\n];\n";
    file_put_contents('config.php', $config);

    echo "Install successful. <a href='index.php'>Go to Portal</a>";
    exit;
}
?>

<form method="post">
    <h2>IPTV BOX Installer</h2>
    <label>DB Host: <input name="db_host" required></label><br>
    <label>DB User: <input name="db_user" required></label><br>
    <label>DB Pass: <input name="db_pass"></label><br>
    <label>DB Name: <input name="db_name" required></label><br>
    <button type="submit">Install</button>
</form>