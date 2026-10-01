<?php
// Main UI: IPTV Web Player - Modern, Elegant, and Interactive Dashboard
session_start();

$conn_ok = false;
$active_ticker = null;
$playlists_data = [];
$channels_data = [];

if (file_exists('config.php')) {
    @include_once('db.php');
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $conn_ok = true;

        // Fetch active ticker message
        $user_id = $_SESSION['user_id'] ?? 0;
        $stmt = $conn->prepare("SELECT * FROM admin_messages WHERE active = 1 AND (target_user_id IS NULL OR target_user_id = ? OR target_user_id = 0) ORDER BY created_at DESC LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $msg = $res->fetch_assoc()) {
                if (!empty($msg['message'])) {
                    $active_ticker = $msg;
                }
            }
        }

        // Fetch playlists
        $res_p = $conn->query("SELECT p.*, (SELECT COUNT(*) FROM channels c WHERE c.playlist_id = p.id) as channel_count FROM playlists p ORDER BY p.id DESC");
        if ($res_p) {
            while ($row = $res_p->fetch_assoc()) {
                $playlists_data[] = $row;
            }
        }

        // Fetch channels
        $res_c = $conn->query("SELECT c.*, p.name as playlist_name FROM channels c LEFT JOIN playlists p ON c.playlist_id = p.id ORDER BY c.id DESC LIMIT 100");
        if ($res_c) {
            while ($row = $res_c->fetch_assoc()) {
                $channels_data[] = $row;
            }
        }
    }
}

// Fallback demo channels if DB has no records, so player UI is rich and testable immediately
if (empty($channels_data)) {
    $channels_data = [
        [
            'id' => 1,
            'name' => 'Big Buck Bunny (4K HD)',
            'playlist_name' => 'Demo Streams',
            'stream_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
            'category' => 'Movies'
        ],
        [
            'id' => 2,
            'name' => 'Elephant Dream (720p)',
            'playlist_name' => 'Demo Streams',
            'stream_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
            'category' => 'Movies'
        ],
        [
            'id' => 3,
            'name' => 'For Bigger Blazes (Sports)',
            'playlist_name' => 'Sports HD',
            'stream_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
            'category' => 'Sports'
        ],
        [
            'id' => 4,
            'name' => 'Sintel Animated Cinema',
            'playlist_name' => 'Cinema World',
            'stream_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/Sintel.mp4',
            'category' => 'Movies'
        ],
        [
            'id' => 5,
            'name' => 'Tears of Steel (Sci-Fi)',
            'playlist_name' => 'Cinema World',
            'stream_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/TearsOfSteel.mp4',
            'category' => 'Sci-Fi'
        ]
    ];
}

// Function to check if text contains Arabic characters
function is_arabic_text($text) {
    return preg_match('/[\x{0600}-\x{06FF}]/u', $text);
}

// Helper for ticker bg color
function get_ticker_bg($ticker) {
    if (!$ticker) return '';
    if (!empty($ticker['bg_color'])) {
        return $ticker['bg_color'];
    }
    $type = strtolower($ticker['type'] ?? 'info');
    switch ($type) {
        case 'alert':
        case 'critical':
        case 'red':
            return '#EF4444';
        case 'warning':
        case 'maintenance':
        case 'yellow':
            return '#F59E0B';
        case 'info':
        case 'general':
        case 'blue':
        default:
            return '#3B82F6';
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPTV BOX - Modern Streaming Portal</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            900: '#312e81',
                        },
                        darkbg: '#0b0f19',
                        darkcard: '#111827',
                        glassdark: 'rgba(17, 24, 39, 0.75)',
                        glasslight: 'rgba(255, 255, 255, 0.75)',
                    },
                    animation: {
                        'pulse-subtle': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'fade-in': 'fadeIn 0.3s ease-out forwards',
                        'slide-up': 'slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'scale(0.98)' },
                            '100%': { opacity: '1', transform: 'scale(1)' },
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Noto+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'Noto Sans Arabic', sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Frosted Glass Utilities */
        .glass-panel {
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }
        .dark ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.5);
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.8);
        }

        /* Ticker Animations */
        @keyframes ticker-rtl {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        @keyframes ticker-ltr {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .ticker-text-rtl {
            display: inline-block;
            white-space: nowrap;
            padding-left: 100%;
            animation: ticker-rtl 22s linear infinite;
        }

        .ticker-text-ltr {
            display: inline-block;
            white-space: nowrap;
            padding-right: 100%;
            animation: ticker-ltr 22s linear infinite;
        }

        .ticker-container:hover .ticker-text-rtl,
        .ticker-container:hover .ticker-text-ltr {
            animation-play-state: paused;
        }

        /* Accordion transition logic */
        .accordion-content {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .accordion-content > div {
            overflow: hidden;
        }
        .accordion-content.expanded {
            grid-template-rows: 1fr;
        }

        /* Poster hover effects */
        .poster-card {
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
        }
        .poster-card:hover {
            transform: translateY(-6px) scale(1.02);
        }
    </style>
</head>

<body class="bg-gray-100 dark:bg-darkbg text-gray-900 dark:text-gray-100 min-h-screen flex flex-col selection:bg-brand-500 selection:text-white pb-14">

    <!-- Header / Navbar -->
    <header class="sticky top-0 z-40 w-full glass-panel bg-white/80 dark:bg-darkbg/80 border-b border-gray-200/80 dark:border-gray-800/80 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">

            <!-- Left Branding & Mobile Toggle -->
            <div class="flex items-center gap-3">
                <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-600 dark:text-gray-300 hover:text-brand-500 focus:outline-none rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>

                <a href="index.php" class="flex items-center gap-2.5 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-400 flex items-center justify-center text-white shadow-lg shadow-brand-500/30 group-hover:scale-105 transition-transform duration-300">
                        <i class="fa-solid fa-play text-base ml-0.5"></i>
                    </div>
                    <div>
                        <span class="font-extrabold text-xl tracking-tight bg-gradient-to-r from-brand-600 via-indigo-500 to-purple-500 bg-clip-text text-transparent">IPTV<span class="text-gray-900 dark:text-white">STREAM</span></span>
                        <span class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest -mt-1">Web Player</span>
                    </div>
                </a>
            </div>

            <!-- Middle Search Bar -->
            <div class="hidden sm:flex flex-1 max-w-md mx-4 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" id="global-search" placeholder="Search channels, movies, series..."
                       class="w-full pl-10 pr-4 py-2 text-sm rounded-full bg-gray-100 dark:bg-gray-800/80 border border-transparent focus:border-brand-500 focus:bg-white dark:focus:bg-gray-900 focus:outline-none transition-all duration-200 shadow-inner">
            </div>

            <!-- Right Controls: LTR/RTL, Theme Switcher, Account -->
            <div class="flex items-center gap-2 sm:gap-3">

                <!-- Direction Toggle -->
                <button id="dir-toggle" title="Switch Text Alignment (LTR / RTL)"
                        class="p-2.5 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800/80 transition-all active:scale-95 flex items-center gap-1.5 text-xs font-semibold">
                    <i class="fa-solid fa-right-left text-sm"></i>
                    <span id="dir-label" class="hidden lg:inline">LTR</span>
                </button>

                <!-- Theme Switcher Switch (Sun / Moon / Monitor) -->
                <div class="relative bg-gray-200 dark:bg-gray-800 p-1 rounded-xl flex items-center gap-1 shadow-inner">
                    <button data-theme-btn="light" title="Light Mode"
                            class="theme-btn p-1.5 px-2 rounded-lg text-xs text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all">
                        <i class="fa-solid fa-sun"></i>
                    </button>
                    <button data-theme-btn="dark" title="Dark Mode"
                            class="theme-btn p-1.5 px-2 rounded-lg text-xs text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all">
                        <i class="fa-solid fa-moon"></i>
                    </button>
                    <button data-theme-btn="system" title="System Auto"
                            class="theme-btn p-1.5 px-2 rounded-lg text-xs text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all">
                        <i class="fa-solid fa-desktop"></i>
                    </button>
                </div>

                <!-- User Account Quick Status -->
                <button onclick="switchTab('login')" class="flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-xl bg-brand-500/10 hover:bg-brand-500/20 text-brand-600 dark:text-brand-400 border border-brand-500/20 transition duration-200 text-xs font-medium">
                    <i class="fa-solid fa-user-circle text-sm"></i>
                    <span class="hidden sm:inline">
                        <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Account / Login'; ?>
                    </span>
                </button>
            </div>
        </div>
    </header>

    <!-- App Layout Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full flex-1 pt-6 flex flex-col md:flex-row gap-6">

        <!-- Sidebar Navigation -->
        <aside id="sidebar" class="hidden md:flex flex-col w-full md:w-64 shrink-0 gap-6 transition-all duration-300">

            <!-- Main Tabs Nav -->
            <nav class="glass-panel bg-white/70 dark:bg-gray-900/60 rounded-2xl p-3 border border-gray-200/80 dark:border-gray-800/80 shadow-sm flex flex-col gap-1">
                <div class="px-3 py-2 text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Navigation</div>

                <button onclick="switchTab('playlists')" data-tab-btn="playlists"
                        class="tab-btn active flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800/60 hover:text-brand-500 dark:hover:text-brand-400">
                    <i class="fa-solid fa-layer-group text-base w-5 text-center"></i>
                    <span>Playlists</span>
                    <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-400"><?php echo count($playlists_data); ?></span>
                </button>

                <button onclick="switchTab('channels')" data-tab-btn="channels"
                        class="tab-btn flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800/60 hover:text-brand-500 dark:hover:text-brand-400">
                    <i class="fa-solid fa-tv text-base w-5 text-center"></i>
                    <span>All Channels</span>
                    <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-400"><?php echo count($channels_data); ?></span>
                </button>

                <button onclick="switchTab('series')" data-tab-btn="series"
                        class="tab-btn flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800/60 hover:text-brand-500 dark:hover:text-brand-400">
                    <i class="fa-solid fa-film text-base w-5 text-center"></i>
                    <span>Series & VOD</span>
                    <span class="ml-auto text-[10px] uppercase font-bold px-1.5 py-0.5 rounded bg-indigo-500/20 text-indigo-500">New</span>
                </button>

                <button onclick="switchTab('local')" data-tab-btn="local"
                        class="tab-btn flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800/60 hover:text-brand-500 dark:hover:text-brand-400">
                    <i class="fa-solid fa-file-arrow-up text-base w-5 text-center"></i>
                    <span>Local File</span>
                </button>

                <button onclick="switchTab('login')" data-tab-btn="login"
                        class="tab-btn flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800/60 hover:text-brand-500 dark:hover:text-brand-400">
                    <i class="fa-solid fa-user-gear text-base w-5 text-center"></i>
                    <span>User & Settings</span>
                </button>
            </nav>

            <!-- Expandable Sidebar Categories Accordion -->
            <div class="glass-panel bg-white/70 dark:bg-gray-900/60 rounded-2xl p-3 border border-gray-200/80 dark:border-gray-800/80 shadow-sm flex flex-col gap-2">
                <button onclick="toggleAccordion('cat-accordion-1')" class="flex items-center justify-between w-full px-3 py-2 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-900 dark:hover:text-white transition">
                    <span><i class="fa-solid fa-list-ul mr-1.5"></i> Categories</span>
                    <i id="cat-accordion-1-icon" class="fa-solid fa-chevron-down text-xs transition-transform duration-300"></i>
                </button>

                <div id="cat-accordion-1" class="accordion-content expanded">
                    <div class="flex flex-col gap-1 pt-1">
                        <button onclick="filterCategory('All')" class="category-chip text-left px-3 py-1.5 rounded-lg text-xs font-medium text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-950/40 hover:bg-brand-100 dark:hover:bg-brand-900/40 transition">
                            <i class="fa-solid fa-globe mr-1.5 text-xs"></i> All Categories
                        </button>
                        <button onclick="filterCategory('Movies')" class="category-chip text-left px-3 py-1.5 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/60 transition">
                            <i class="fa-solid fa-clapperboard mr-1.5 text-xs"></i> Movies & Cinema
                        </button>
                        <button onclick="filterCategory('Sports')" class="category-chip text-left px-3 py-1.5 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/60 transition">
                            <i class="fa-solid fa-futbol mr-1.5 text-xs"></i> Live Sports
                        </button>
                        <button onclick="filterCategory('Sci-Fi')" class="category-chip text-left px-3 py-1.5 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/60 transition">
                            <i class="fa-solid fa-rocket mr-1.5 text-xs"></i> Sci-Fi & Drama
                        </button>
                    </div>
                </div>
            </div>

            <!-- System Info Widget -->
            <div class="glass-panel bg-gradient-to-br from-indigo-900/20 to-purple-900/20 dark:from-indigo-950/40 dark:to-purple-950/40 rounded-2xl p-4 border border-indigo-500/20 flex flex-col gap-2">
                <div class="flex items-center gap-2 text-indigo-500 dark:text-indigo-400 font-bold text-xs">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Free Trial Status</span>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    Enjoy high-speed streaming. 1-Hour Trial Active.
                </p>
                <button onclick="switchTab('login')" class="mt-2 w-full py-1.5 px-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition">
                    Upgrade Subscription
                </button>
            </div>

        </aside>

        <!-- Main Workspace / Video Player & Content Panels -->
        <main class="flex-1 flex flex-col gap-6 min-w-0">

            <!-- High-End HTML5 Video Player Container -->
            <section class="glass-panel bg-gray-900 rounded-3xl overflow-hidden shadow-2xl border border-gray-800 relative group">
                <div class="aspect-video relative bg-black flex items-center justify-center">
                    <video id="main-player" controls class="w-full h-full object-contain" poster="https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?q=80&w=1200&auto=format&fit=crop">
                        <source id="video-source" src="<?php echo htmlspecialchars($channels_data[0]['stream_url'] ?? ''); ?>" type="video/mp4">
                        Your browser does not support HTML5 video streaming.
                    </video>

                    <!-- Player Status Overlay Badge -->
                    <div class="absolute top-4 left-4 pointer-events-none flex items-center gap-2 bg-black/60 backdrop-blur-md px-3 py-1.5 rounded-full border border-white/10 text-xs font-medium text-white">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span id="current-channel-title"><?php echo htmlspecialchars($channels_data[0]['name'] ?? 'Select a Channel'); ?></span>
                    </div>

                    <!-- Live Quality Badge -->
                    <div class="absolute top-4 right-4 pointer-events-none bg-brand-600/80 backdrop-blur-md px-2.5 py-1 rounded-md text-[10px] font-extrabold tracking-widest text-white uppercase border border-white/20">
                        4K ULTRA HD
                    </div>
                </div>

                <!-- Interactive Player Info Footer Bar -->
                <div class="p-4 bg-gray-900/90 backdrop-blur-md border-t border-gray-800/80 flex flex-wrap items-center justify-between gap-3 text-white">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-brand-600/30 border border-brand-500/40 flex items-center justify-center text-brand-400">
                            <i class="fa-solid fa-broadcast-tower text-lg animate-pulse"></i>
                        </div>
                        <div>
                            <h3 id="playing-now-name" class="font-bold text-base tracking-tight"><?php echo htmlspecialchars($channels_data[0]['name'] ?? 'No Channel Playing'); ?></h3>
                            <p id="playing-now-playlist" class="text-xs text-gray-400"><?php echo htmlspecialchars($channels_data[0]['playlist_name'] ?? 'Direct Stream'); ?></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button onclick="reloadPlayer()" title="Reload Stream" class="p-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white transition">
                            <i class="fa-solid fa-rotate-right text-sm"></i>
                        </button>
                        <button onclick="toggleFullscreen()" title="Fullscreen" class="p-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white transition">
                            <i class="fa-solid fa-expand text-sm"></i>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Dynamic Tab Content View Container -->
            <div class="relative min-h-[300px]">

                <!-- TAB 1: Playlists Panel -->
                <div id="panel-playlists" class="tab-panel active space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold tracking-tight">Downloaded Playlists</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">M3U and M3U8 IPTV playlists synced in system</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php if (empty($playlists_data)): ?>
                            <div class="col-span-full glass-panel bg-white/60 dark:bg-gray-900/60 p-8 rounded-2xl border border-dashed border-gray-300 dark:border-gray-800 text-center">
                                <i class="fa-solid fa-folder-open text-4xl text-gray-400 mb-3 block"></i>
                                <h3 class="font-bold text-gray-700 dark:text-gray-300">No Playlists Found</h3>
                                <p class="text-xs text-gray-500 mt-1">Upload a playlist from the Local File tab or via Admin Panel.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($playlists_data as $playlist): ?>
                                <div class="poster-card glass-panel bg-white/80 dark:bg-gray-900/70 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm flex flex-col justify-between gap-4">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                                <i class="fa-solid fa-list-check text-lg"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($playlist['name']); ?></h4>
                                                <span class="text-[11px] text-gray-500 dark:text-gray-400"><?php echo $playlist['channel_count']; ?> Channels</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-800/60">
                                        <span class="text-[10px] text-gray-400">Added: <?php echo date('M d, Y', strtotime($playlist['created_at'])); ?></span>
                                        <button onclick="switchTab('channels')" class="px-3 py-1.5 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-xs font-semibold shadow-sm transition">
                                            View Channels <i class="fa-solid fa-arrow-right ml-1"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TAB 2: All Channels Panel -->
                <div id="panel-channels" class="tab-panel hidden space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-bold tracking-tight">All Live Channels</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Browse and stream channels from all sources</p>
                        </div>
                    </div>

                    <div id="channels-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <?php foreach ($channels_data as $channel): ?>
                            <div data-channel-name="<?php echo strtolower(htmlspecialchars($channel['name'])); ?>"
                                 data-category="<?php echo htmlspecialchars($channel['category'] ?? 'All'); ?>"
                                 class="channel-item poster-card glass-panel bg-white/80 dark:bg-gray-900/70 p-4 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm flex items-center justify-between gap-3 group">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-11 h-11 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0 text-brand-500 font-bold group-hover:bg-brand-500 group-hover:text-white transition duration-300 shadow-inner">
                                        <i class="fa-solid fa-tv text-lg"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-sm truncate text-gray-900 dark:text-white group-hover:text-brand-500 transition-colors"><?php echo htmlspecialchars($channel['name']); ?></h4>
                                        <span class="text-[11px] text-gray-500 dark:text-gray-400 truncate block"><?php echo htmlspecialchars($channel['playlist_name'] ?? 'IPTV Stream'); ?></span>
                                    </div>
                                </div>
                                <button onclick="playStream('<?php echo addslashes($channel['stream_url']); ?>', '<?php echo addslashes($channel['name']); ?>', '<?php echo addslashes($channel['playlist_name'] ?? ''); ?>')"
                                        class="p-2.5 rounded-xl bg-brand-500/10 hover:bg-brand-500 text-brand-600 hover:text-white transition duration-200 shrink-0">
                                    <i class="fa-solid fa-play text-xs"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- TAB 3: Series & VOD Panel (With Accordions) -->
                <div id="panel-series" class="tab-panel hidden space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold tracking-tight">TV Series & On-Demand Movies</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Stream top TV series with season and episode accordions</p>
                        </div>
                    </div>

                    <!-- Accordion Series List -->
                    <div class="space-y-4">

                        <!-- Series Item 1 -->
                        <div class="glass-panel bg-white/80 dark:bg-gray-900/70 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 overflow-hidden shadow-sm">
                            <button onclick="toggleAccordion('series-acc-1')" class="w-full p-4 flex items-center justify-between text-left hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                <div class="flex items-center gap-4">
                                    <img src="https://images.unsplash.com/photo-1536440136628-849c177e76a1?q=80&w=200&auto=format&fit=crop" class="w-12 h-16 rounded-lg object-cover shadow-md" alt="Series poster">
                                    <div>
                                        <h3 class="font-bold text-base text-gray-900 dark:text-white">Cyber Neon 2088</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Sci-Fi • 2 Seasons • 16 Episodes</p>
                                    </div>
                                </div>
                                <i id="series-acc-1-icon" class="fa-solid fa-chevron-down text-sm text-gray-400 transition-transform duration-300"></i>
                            </button>

                            <div id="series-acc-1" class="accordion-content">
                                <div class="p-4 pt-0 border-t border-gray-100 dark:border-gray-800 space-y-3">

                                    <!-- Season 1 Sub-Accordion -->
                                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                                        <button onclick="toggleAccordion('s1-episodes')" class="w-full bg-gray-50 dark:bg-gray-800/40 p-3 flex items-center justify-between text-xs font-bold text-gray-700 dark:text-gray-300">
                                            <span>Season 1 (8 Episodes)</span>
                                            <i id="s1-episodes-icon" class="fa-solid fa-chevron-down text-xs transition-transform duration-300"></i>
                                        </button>
                                        <div id="s1-episodes" class="accordion-content expanded">
                                            <div class="p-2 divide-y divide-gray-100 dark:divide-gray-800/50">
                                                <div class="p-2 flex items-center justify-between text-xs hover:bg-gray-100 dark:hover:bg-gray-800/80 rounded-lg transition">
                                                    <span>E01 - Awakening (45m)</span>
                                                    <button onclick="playStream('https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', 'Cyber Neon S01E01', 'Series VOD')" class="text-brand-500 font-semibold hover:underline"><i class="fa-solid fa-play mr-1"></i> Play</button>
                                                </div>
                                                <div class="p-2 flex items-center justify-between text-xs hover:bg-gray-100 dark:hover:bg-gray-800/80 rounded-lg transition">
                                                    <span>E02 - The Protocol (42m)</span>
                                                    <button onclick="playStream('https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4', 'Cyber Neon S01E02', 'Series VOD')" class="text-brand-500 font-semibold hover:underline"><i class="fa-solid fa-play mr-1"></i> Play</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- TAB 4: Local File Playback -->
                <div id="panel-local" class="tab-panel hidden space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold tracking-tight">Local File Playback & Import</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Load or parse custom M3U/M3U8 playlist files directly</p>
                        </div>
                    </div>

                    <div class="glass-panel bg-white/80 dark:bg-gray-900/70 p-8 rounded-2xl border border-2 border-dashed border-gray-300 dark:border-gray-800 text-center flex flex-col items-center justify-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-brand-500/10 text-brand-500 flex items-center justify-center text-2xl">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 dark:text-gray-200">Select or Drag M3U / M3U8 File</h3>
                            <p class="text-xs text-gray-500 mt-1">Parse channels in browser or upload to system database</p>
                        </div>
                        <input type="file" id="local-file-input" accept=".m3u,.m3u8" class="hidden" onchange="handleFileSelect(event)">
                        <button onclick="document.getElementById('local-file-input').click()" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-lg shadow-brand-600/30 transition">
                            Browse Local File
                        </button>
                    </div>

                    <div id="local-parsed-results" class="hidden space-y-3">
                        <h3 class="font-bold text-sm">Parsed Local Channels:</h3>
                        <div id="local-channels-list" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3"></div>
                    </div>
                </div>

                <!-- TAB 5: Login & User Account -->
                <div id="panel-login" class="tab-panel hidden space-y-4">
                    <div class="max-w-md mx-auto glass-panel bg-white/90 dark:bg-gray-900/80 p-6 rounded-3xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl space-y-6">

                        <div class="text-center space-y-1">
                            <div class="w-12 h-12 rounded-2xl bg-brand-600 text-white mx-auto flex items-center justify-center text-xl shadow-lg shadow-brand-500/30">
                                <i class="fa-solid fa-user-shield"></i>
                            </div>
                            <h3 class="font-extrabold text-lg">Account Access</h3>
                            <p class="text-xs text-gray-500">Sign in or create account for unlimited streaming access</p>
                        </div>

                        <div class="flex bg-gray-100 dark:bg-gray-800 p-1 rounded-xl">
                            <button id="auth-tab-login-btn" onclick="toggleAuthForm('login')" class="flex-1 py-1.5 text-xs font-bold rounded-lg bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white transition">Login</button>
                            <button id="auth-tab-reg-btn" onclick="toggleAuthForm('register')" class="flex-1 py-1.5 text-xs font-bold rounded-lg text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">Register</button>
                        </div>

                        <!-- Login Form -->
                        <form id="form-login" method="post" action="user.php" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold mb-1 text-gray-600 dark:text-gray-400">Username</label>
                                <input name="username" required placeholder="Enter username" class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none focus:border-brand-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1 text-gray-600 dark:text-gray-400">Password</label>
                                <input name="password" type="password" required placeholder="••••••••" class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none focus:border-brand-500">
                            </div>
                            <button type="submit" name="login" class="w-full py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-brand-600/30 transition">
                                Sign In
                            </button>
                        </form>

                        <!-- Register Form -->
                        <form id="form-register" method="post" action="user.php" class="space-y-4 hidden">
                            <div>
                                <label class="block text-xs font-semibold mb-1 text-gray-600 dark:text-gray-400">Username</label>
                                <input name="username" required placeholder="Choose username" class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none focus:border-brand-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1 text-gray-600 dark:text-gray-400">Password</label>
                                <input name="password" type="password" required placeholder="••••••••" class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:outline-none focus:border-brand-500">
                            </div>
                            <button type="submit" name="register" class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/30 transition">
                                Register & Start 1-Hour Free Trial
                            </button>
                        </form>

                    </div>
                </div>

            </div>

        </main>

    </div>

    <!-- Dynamic Ticker Bar (Bottom Ticker) -->
    <?php if ($active_ticker):
        $ticker_bg = get_ticker_bg($active_ticker);
        $is_arabic = is_arabic_text($active_ticker['message']);
        $anim_class = $is_arabic ? 'ticker-text-ltr' : 'ticker-text-rtl';
        $ticker_dir = $is_arabic ? 'rtl' : 'ltr';
    ?>
        <div id="ticker-bar"
             style="background-color: <?php echo htmlspecialchars($ticker_bg); ?>;"
             class="ticker-container fixed bottom-0 left-0 right-0 z-50 py-2.5 px-4 shadow-2xl flex items-center overflow-hidden border-t border-white/20 text-white text-sm font-semibold tracking-wide backdrop-blur-md">

            <div class="shrink-0 px-3 py-1 bg-black/20 rounded-lg text-xs font-bold uppercase tracking-wider mr-3 flex items-center gap-1.5 border border-white/10">
                <i class="fa-solid fa-bullhorn text-amber-300 animate-bounce"></i>
                <span>Notice</span>
            </div>

            <div class="flex-1 overflow-hidden relative">
                <div dir="<?php echo $ticker_dir; ?>" class="<?php echo $anim_class; ?> drop-shadow-md">
                    <?php echo htmlspecialchars($active_ticker['message']); ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Hidden Ticker Placeholder (HIDDEN by default per specification) -->
        <div id="ticker-bar" style="display: none;"></div>
    <?php endif; ?>


    <!-- JavaScript Engine -->
    <script>
        // -----------------------------------------------------------------
        // 1. Theme Engine (Dark / Light / System Auto Detection)
        // -----------------------------------------------------------------
        const themeBtns = document.querySelectorAll('[data-theme-btn]');

        function applyTheme(themeMode) {
            let isDark = false;
            if (themeMode === 'system') {
                isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            } else {
                isDark = themeMode === 'dark';
            }

            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            // Update button active state styling
            themeBtns.forEach(btn => {
                const btnMode = btn.getAttribute('data-theme-btn');
                if (btnMode === themeMode) {
                    btn.classList.add('bg-white', 'dark:bg-gray-700', 'text-brand-600', 'dark:text-white', 'shadow');
                    btn.classList.remove('text-gray-500', 'dark:text-gray-400');
                } else {
                    btn.classList.remove('bg-white', 'dark:bg-gray-700', 'text-brand-600', 'dark:text-white', 'shadow');
                    btn.classList.add('text-gray-500', 'dark:text-gray-400');
                }
            });

            localStorage.setItem('iptv_theme', themeMode);
        }

        // Initialize Theme
        const savedTheme = localStorage.getItem('iptv_theme') || 'dark';
        applyTheme(savedTheme);

        // System Preference Listener
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (localStorage.getItem('iptv_theme') === 'system') {
                applyTheme('system');
            }
        });

        themeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                applyTheme(btn.getAttribute('data-theme-btn'));
            });
        });

        // -----------------------------------------------------------------
        // 2. Text Direction Engine (LTR / RTL)
        // -----------------------------------------------------------------
        const dirToggleBtn = document.getElementById('dir-toggle');
        const dirLabel = document.getElementById('dir-label');

        function setDirection(dir) {
            document.documentElement.setAttribute('dir', dir);
            dirLabel.textContent = dir.toUpperCase();
            localStorage.setItem('iptv_dir', dir);
        }

        dirToggleBtn.addEventListener('click', () => {
            const currentDir = document.documentElement.getAttribute('dir') || 'ltr';
            const newDir = currentDir === 'ltr' ? 'rtl' : 'ltr';
            setDirection(newDir);
        });

        const savedDir = localStorage.getItem('iptv_dir') || 'ltr';
        setDirection(savedDir);

        // Mobile Menu Toggle
        document.getElementById('mobile-menu-toggle').addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden');
        });

        // -----------------------------------------------------------------
        // 3. Tab Switching Logic
        // -----------------------------------------------------------------
        function switchTab(tabId) {
            // Hide all tab panels
            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.classList.add('hidden');
                panel.classList.remove('active', 'animate-fade-in');
            });

            // Show selected tab panel
            const activePanel = document.getElementById('panel-' + tabId);
            if (activePanel) {
                activePanel.classList.remove('hidden');
                activePanel.classList.add('active', 'animate-fade-in');
            }

            // Update Tab Button Active State
            document.querySelectorAll('[data-tab-btn]').forEach(btn => {
                if (btn.getAttribute('data-tab-btn') === tabId) {
                    btn.classList.add('bg-brand-500/10', 'text-brand-600', 'dark:text-brand-400');
                    btn.classList.remove('text-gray-600', 'dark:text-gray-300');
                } else {
                    btn.classList.remove('bg-brand-500/10', 'text-brand-600', 'dark:text-brand-400');
                    btn.classList.add('text-gray-600', 'dark:text-gray-300');
                }
            });
        }

        // -----------------------------------------------------------------
        // 4. Expandable Accordion Logic
        // -----------------------------------------------------------------
        function toggleAccordion(id) {
            const el = document.getElementById(id);
            const icon = document.getElementById(id + '-icon');
            if (el) {
                el.classList.toggle('expanded');
                if (icon) {
                    icon.classList.toggle('rotate-180');
                }
            }
        }

        // -----------------------------------------------------------------
        // 5. Video Player Functions
        // -----------------------------------------------------------------
        function playStream(url, name, playlistName) {
            const player = document.getElementById('main-player');
            const source = document.getElementById('video-source');

            source.src = url;
            player.load();
            player.play().catch(e => console.log('Autoplay prevented:', e));

            document.getElementById('current-channel-title').textContent = name;
            document.getElementById('playing-now-name').textContent = name;
            document.getElementById('playing-now-playlist').textContent = playlistName || 'IPTV Stream';

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function reloadPlayer() {
            const player = document.getElementById('main-player');
            player.load();
            player.play();
        }

        function toggleFullscreen() {
            const player = document.getElementById('main-player');
            if (!document.fullscreenElement) {
                if (player.requestFullscreen) player.requestFullscreen();
            } else {
                if (document.exitFullscreen) document.exitFullscreen();
            }
        }

        // -----------------------------------------------------------------
        // 6. Global Search & Category Filters
        // -----------------------------------------------------------------
        document.getElementById('global-search').addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.channel-item').forEach(item => {
                const channelName = item.getAttribute('data-channel-name');
                if (channelName.includes(term)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        function filterCategory(cat) {
            document.querySelectorAll('.channel-item').forEach(item => {
                const itemCat = item.getAttribute('data-category');
                if (cat === 'All' || itemCat === cat) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
            switchTab('channels');
        }

        // -----------------------------------------------------------------
        // 7. Local File Parsing (M3U / M3U8 Client-side Parser)
        // -----------------------------------------------------------------
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                const content = e.target.result;
                const lines = content.split('\n');
                const channels = [];
                let currentName = '';

                lines.forEach(line => {
                    line = line.trim();
                    if (line.startsWith('#EXTINF:')) {
                        const parts = line.split(',');
                        currentName = parts[1] || 'Local Channel';
                    } else if (line && !line.startsWith('#')) {
                        if (currentName) {
                            channels.push({ name: currentName, url: line });
                            currentName = '';
                        }
                    }
                });

                displayLocalChannels(channels);
            };
            reader.readAsText(file);
        }

        function displayLocalChannels(channels) {
            const container = document.getElementById('local-channels-list');
            const resultsSection = document.getElementById('local-parsed-results');
            container.innerHTML = '';

            if (channels.length === 0) {
                resultsSection.classList.add('hidden');
                alert('No valid streams found in file.');
                return;
            }

            channels.forEach(ch => {
                const card = document.createElement('div');
                card.className = 'glass-panel bg-white/80 dark:bg-gray-900/70 p-3 rounded-xl border border-gray-200 dark:border-gray-800 flex items-center justify-between text-xs';
                card.innerHTML = `
                    <span class="font-bold truncate max-w-[180px]">${ch.name}</span>
                    <button class="px-2.5 py-1 rounded-lg bg-brand-500 text-white font-semibold hover:bg-brand-600">Play</button>
                `;
                card.querySelector('button').onclick = () => playStream(ch.url, ch.name, 'Local File');
                container.appendChild(card);
            });

            resultsSection.classList.remove('hidden');
        }

        // Auth Form Toggle
        function toggleAuthForm(mode) {
            const loginForm = document.getElementById('form-login');
            const regForm = document.getElementById('form-register');
            const loginBtn = document.getElementById('auth-tab-login-btn');
            const regBtn = document.getElementById('auth-tab-reg-btn');

            if (mode === 'login') {
                loginForm.classList.remove('hidden');
                regForm.classList.add('hidden');
                loginBtn.classList.add('bg-white', 'dark:bg-gray-700', 'shadow', 'text-gray-900', 'dark:text-white');
                regBtn.classList.remove('bg-white', 'dark:bg-gray-700', 'shadow', 'text-gray-900', 'dark:text-white');
            } else {
                loginForm.classList.add('hidden');
                regForm.classList.remove('hidden');
                regBtn.classList.add('bg-white', 'dark:bg-gray-700', 'shadow', 'text-gray-900', 'dark:text-white');
                loginBtn.classList.remove('bg-white', 'dark:bg-gray-700', 'shadow', 'text-gray-900', 'dark:text-white');
            }
        }
    </script>
</body>
</html>
