<?php
// Simple DB connection helper
$config = include('config.php');
$conn = new mysqli(
    $config['db_host'],
    $config['db_user'],
    $config['db_pass'],
    $config['db_name']
);
if ($conn->connect_error) die("DB connection failed");
?>