<?php
// Simple DB connection helper
$config = include('config.php');
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli(
    $config['db_host'],
    $config['db_user'],
    $config['db_pass'],
    $config['db_name']
);
if ($conn->connect_error) {
    // If connection fails, keep $conn as false
    $conn = false;
} else {
    // Ensure admin_messages table has the required columns if table exists
    if ($result = $conn->query("SHOW TABLES LIKE 'admin_messages'")) {
        if ($result->num_rows > 0) {
            $columns = [];
            $res = $conn->query("SHOW COLUMNS FROM admin_messages");
            while ($row = $res->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            if (!in_array('type', $columns)) {
                $conn->query("ALTER TABLE admin_messages ADD COLUMN type VARCHAR(32) DEFAULT 'info'");
            }
            if (!in_array('bg_color', $columns)) {
                $conn->query("ALTER TABLE admin_messages ADD COLUMN bg_color VARCHAR(32) DEFAULT NULL");
            }
            if (!in_array('target_user_id', $columns)) {
                $conn->query("ALTER TABLE admin_messages ADD COLUMN target_user_id INT DEFAULT NULL");
            }
        }
    }
}
?>