<?php
// User registration/login & fingerprinting
include('db.php');

function hardware_id() {
    // Simple browser fingerprint (for demo)
    return sha1($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $u = $_POST['username'];
    $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $hw = hardware_id();
    $now = date('Y-m-d H:i:s');
    $trial_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $conn->prepare("INSERT INTO users (username,password,hardware_id,trial_expiry) VALUES (?,?,?,?)")
        ->bind_param("ssss", $u, $p, $hw, $trial_expiry)
        ->execute();
    header('Location: login.php');
    exit;
}
?>