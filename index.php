<?php
// Main UI: tabbed interface for playlist, channels, local file, login, ticker
include('db.php');
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>IPTV BOX Portal</title>
    <meta name="viewport" content="width=device-width">
    <style>
        body { font-family:sans-serif; max-width:600px; margin:auto;}
        .tab { display:inline-block; margin:10px; cursor:pointer;}
        .panel { display:none; }
        .panel.active { display:block; }
        #ticker { position:fixed; bottom:0; width:100%; background:#222; color:#fff; padding:5px; }
    </style>
    <script>
        function showTab(tab) {
            document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
            document.getElementById(tab).classList.add('active');
        }
        window.onload = () => showTab('playlists');
    </script>
</head>
<body>
    <h1>IPTV BOX Portal</h1>
    <div>
        <span class="tab" onclick="showTab('playlists')">Downloaded Playlists</span>
        <span class="tab" onclick="showTab('channels')">All Channels</span>
        <span class="tab" onclick="showTab('local')">Local File</span>
        <span class="tab" onclick="showTab('login')">Login</span>
    </div>

    <div id="playlists" class="panel">
        <h2>Downloaded Playlists</h2>
        <!-- List playlists and video player logic here -->
    </div>
    <div id="channels" class="panel">
        <h2>All Channels</h2>
        <!-- List all channels -->
    </div>
    <div id="local" class="panel">
        <h2>Local File Playback</h2>
        <!-- Upload and parse M3U/M3U8 files -->
    </div>
    <div id="login" class="panel">
        <h2>Login</h2>
        <form method="post" action="user.php">
            <input name="username" placeholder="Username" required>
            <input name="password" type="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
        <h3>Or Register</h3>
        <form method="post" action="user.php">
            <input name="username" placeholder="Username" required>
            <input name="password" type="password" placeholder="Password" required>
            <button type="submit" name="register">Register</button>
        </form>
    </div>

    <?php
    // Show admin ticker if active
    $msg = $conn->query("SELECT message FROM admin_messages WHERE active=1 ORDER BY created_at DESC LIMIT 1")->fetch_row();
    if ($msg && $msg[0]) echo "<div id='ticker'>{$msg[0]}</div>";
    ?>

</body>
</html>