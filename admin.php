<?php
// Admin Control Panel for IPTV Web Player
session_start();
include('db.php');

$msg = '';
$msg_type = 'success';

// Handle ticker creation / updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ticker'])) {
    $message = trim($_POST['message'] ?? '');
    $type = trim($_POST['type'] ?? 'info');
    $custom_bg = trim($_POST['bg_color'] ?? '');
    $target_user_id = !empty($_POST['target_user_id']) ? intval($_POST['target_user_id']) : NULL;
    $active = isset($_POST['active']) ? 1 : 0;

    // Deactivate previous active messages if this one is set active
    if ($active) {
        $conn->query("UPDATE admin_messages SET active = 0 WHERE target_user_id " . ($target_user_id ? "= $target_user_id" : "IS NULL OR target_user_id = 0"));
    }

    $stmt = $conn->prepare("INSERT INTO admin_messages (message, type, bg_color, target_user_id, active) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $message, $type, $custom_bg, $target_user_id, $active);
    if ($stmt->execute()) {
        $msg = "Ticker broadcast message saved successfully!";
    } else {
        $msg = "Error saving ticker: " . $conn->error;
        $msg_type = 'error';
    }
}

// Toggle ticker status
if (isset($_GET['toggle_ticker'])) {
    $tid = intval($_GET['toggle_ticker']);
    $conn->query("UPDATE admin_messages SET active = NOT active WHERE id = $tid");
    header("Location: admin.php");
    exit;
}

// Delete ticker
if (isset($_GET['delete_ticker'])) {
    $tid = intval($_GET['delete_ticker']);
    $conn->query("DELETE FROM admin_messages WHERE id = $tid");
    header("Location: admin.php");
    exit;
}

// Fetch all messages
$all_messages = [];
$res = $conn->query("SELECT m.*, u.username as target_username FROM admin_messages m LEFT JOIN users u ON m.target_user_id = u.id ORDER BY m.id DESC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $all_messages[] = $r;
    }
}

// Fetch all users for targeting
$users_list = [];
$res_u = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
if ($res_u) {
    while ($ru = $res_u->fetch_assoc()) {
        $users_list[] = $ru;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPTV Admin Panel - Message & Ticker Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen p-4 sm:p-8">

    <div class="max-w-5xl mx-auto space-y-6">

        <!-- Top Header -->
        <div class="flex items-center justify-between border-b border-gray-800 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600 bg-indigo-600 flex items-center justify-center text-white font-bold shadow-lg">
                    <i class="fa-solid fa-sliders text-lg"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold">IPTV Admin Dashboard</h1>
                    <p class="text-xs text-gray-400">Broadcast Ticker Messages, Severity Colors & User Targeting</p>
                </div>
            </div>
            <a href="index.php" class="px-4 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-xs font-semibold text-gray-200 transition">
                <i class="fa-solid fa-desktop mr-1.5"></i> Open Web Player
            </a>
        </div>

        <?php if ($msg): ?>
            <div class="p-4 rounded-xl border text-xs font-bold <?php echo $msg_type === 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-red-500/10 border-red-500/30 text-red-400'; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <!-- Create New Broadcast Message Form -->
        <div class="bg-gray-800/80 rounded-2xl p-6 border border-gray-700/80 shadow-xl space-y-4">
            <h2 class="text-base font-bold flex items-center gap-2">
                <i class="fa-solid fa-bullhorn text-indigo-400"></i>
                <span>Configure Ticker Broadcast Message</span>
            </h2>

            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Message Content (Arabic continuous scrolling Left-To-Right / English Right-To-Left)</label>
                    <textarea name="message" required rows="2" placeholder="e.g. ⚠️ Scheduled maintenance today at 22:00 UTC or أهلاً بكم في خدمة IPTV الممتازة" class="w-full p-3 rounded-xl bg-gray-900 border border-gray-700 text-xs text-white focus:outline-none focus:border-indigo-500"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Severity / Preset Type</label>
                        <select name="type" class="w-full p-2.5 rounded-xl bg-gray-900 border border-gray-700 text-xs text-white focus:outline-none">
                            <option value="alert">Alert / Critical (Red #EF4444)</option>
                            <option value="warning">Warning / Maintenance (Yellow #F59E0B)</option>
                            <option value="info" selected>Info / General (Blue #3B82F6)</option>
                            <option value="custom">Custom Hex Color</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Custom Hex Color (Optional)</label>
                        <input type="text" name="bg_color" placeholder="#8B5CF6 or purple" class="w-full p-2.5 rounded-xl bg-gray-900 border border-gray-700 text-xs text-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Target Recipient</label>
                        <select name="target_user_id" class="w-full p-2.5 rounded-xl bg-gray-900 border border-gray-700 text-xs text-white focus:outline-none">
                            <option value="0">Global Broadcast (All Users)</option>
                            <?php foreach ($users_list as $u): ?>
                                <option value="<?php echo $u['id']; ?>">User: <?php echo htmlspecialchars($u['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-gray-300">
                        <input type="checkbox" name="active" value="1" checked class="w-4 h-4 accent-indigo-600 rounded">
                        <span>Activate Message Immediately</span>
                    </label>

                    <button type="submit" name="save_ticker" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition">
                        Broadcast Message
                    </button>
                </div>
            </form>
        </div>

        <!-- Ticker Messages Table -->
        <div class="bg-gray-800/80 rounded-2xl p-6 border border-gray-700/80 shadow-xl space-y-4">
            <h2 class="text-base font-bold flex items-center gap-2">
                <i class="fa-solid fa-list-check text-indigo-400"></i>
                <span>Manage Existing Tickers</span>
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-gray-700 text-gray-400 uppercase tracking-wider">
                            <th class="p-3">ID</th>
                            <th class="p-3">Message</th>
                            <th class="p-3">Type / Color</th>
                            <th class="p-3">Target</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/50">
                        <?php if (empty($all_messages)): ?>
                            <tr>
                                <td colspan="6" class="p-4 text-center text-gray-500">No ticker messages created yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_messages as $m): ?>
                                <tr>
                                    <td class="p-3 text-gray-400">#<?php echo $m['id']; ?></td>
                                    <td class="p-3 font-medium max-w-xs truncate" title="<?php echo htmlspecialchars($m['message']); ?>"><?php echo htmlspecialchars($m['message']); ?></td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 rounded text-[10px] font-bold uppercase text-white" style="background-color: <?php echo !empty($m['bg_color']) ? htmlspecialchars($m['bg_color']) : ($m['type'] === 'alert' ? '#EF4444' : ($m['type'] === 'warning' ? '#F59E0B' : '#3B82F6')); ?>;">
                                            <?php echo htmlspecialchars($m['type']); ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-gray-300">
                                        <?php echo !empty($m['target_username']) ? htmlspecialchars($m['target_username']) : 'Global'; ?>
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 rounded-full text-[10px] font-bold <?php echo $m['active'] ? 'bg-emerald-500/20 text-emerald-400' : 'bg-gray-700 text-gray-400'; ?>">
                                            <?php echo $m['active'] ? 'Active' : 'Disabled'; ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-right space-x-2">
                                        <a href="admin.php?toggle_ticker=<?php echo $m['id']; ?>" class="px-2.5 py-1 rounded-lg bg-gray-700 hover:bg-gray-600 text-white text-[11px] font-semibold">
                                            Toggle Active
                                        </a>
                                        <a href="admin.php?delete_ticker=<?php echo $m['id']; ?>" onclick="return confirm('Delete message?')" class="px-2.5 py-1 rounded-lg bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white text-[11px] font-semibold">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>
