<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?= $page === 'error' ? 'Halaman tidak ditemukan · ' : ($page === 'member' ? e($member['name']) . ' · ' : '') ?>Buku Hutang</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg?v=2">
    <link rel="stylesheet" href="assets/style.css">
    <?php if ($page === 'home'): ?><script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script><?php endif; ?>
    <script>
        (() => {
            const s = localStorage.getItem('debt-app-theme') || 'light';
            document.documentElement.dataset.theme = s === 'system' ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : s;
        })();
    </script>
</head>

<body>
    <div class="app-shell">
        <aside class="sidebar" id="appSidebar" aria-label="Navigasi utama">
            <a class="brand" href="?"><span class="brand-mark">BH</span><span class="brand-copy"><strong>Buku Hutang</strong><small>Manajemen hutang</small></span></a>
            <div class="nav-section-label">MENU UTAMA</div>
            <nav class="side-nav">
                <a class="nav-link <?= $page === 'home' ? 'active' : '' ?>" href="?"><svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="8" rx="1.5" />
                        <rect x="14" y="3" width="7" height="5" rx="1.5" />
                        <rect x="14" y="12" width="7" height="9" rx="1.5" />
                        <rect x="3" y="15" width="7" height="6" rx="1.5" />
                    </svg><span>Dashboard</span></a>
                <a class="nav-link <?= $page === 'members' ? 'active' : '' ?>" href="?page=members"><svg viewBox="0 0 24 24">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="10" cy="7" r="4" />
                        <path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg><span>Data Member</span></a>
                <a class="nav-link <?= $page === 'debts' ? 'active' : '' ?>" href="?page=debts"><svg viewBox="0 0 24 24">
                        <path d="M8 3h8l4 4v14H4V3zM8 3v5h8V3M8 13h8M8 17h5" />
                    </svg><span>Data Hutang</span></a>
                <a class="nav-link <?= $page === 'payments' ? 'active' : '' ?>" href="?page=payments"><svg viewBox="0 0 24 24">
                        <rect x="3" y="5" width="18" height="14" rx="2" />
                        <path d="M3 10h18M7 15h3" />
                    </svg><span>Data Pembayaran</span></a>
            </nav>

            <div class="sidebar-bottom">
                <div class="sidebar-note"><span class="note-icon">i</span><span><strong>Kelola lebih mudah</strong><small>Pantau hutang dan pembayaran dalam satu tempat.</small></span></div>
                <form method="post" class="logout-form"><?php hid('logout', '?'); ?><button class="nav-link logout-link" type="submit"><svg viewBox="0 0 24 24">
                            <path d="M10 17l5-5-5-5M15 12H3M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" />
                        </svg><span>Keluar</span></button></form>
            </div>
        </aside>
        <button class="sidebar-backdrop" id="sidebarBackdrop" aria-label="Tutup navigasi"></button>
        <div class="app-main">
            <header class="topbar">
                <div class="topbar-left"><button class="icon-button menu-toggle" id="menuToggle" aria-label="Buka navigasi" aria-expanded="false"><svg viewBox="0 0 24 24">
                            <path d="M4 6h16M4 12h16M4 18h16" />
                        </svg></button>
                    <div class="breadcrumb"><span>Workspace</span><b>/</b><strong><?= $page === 'error' ? 'Halaman tidak ditemukan' : ($page === 'members' ? 'Data Member' : ($page === 'member' ? 'Detail Member' : ($page === 'debts' ? 'Data Hutang' : ($page === 'payments' ? 'Data Pembayaran' : ($page === 'reports' ? 'Laporan' : 'Dashboard'))))) ?></strong></div>
                </div>
                <div class="topbar-actions"><span class="topbar-date"><?= e(hariTanggal(date('Y-m-d'))) ?></span><button class="icon-button" id="themeToggle" type="button" aria-label="Ganti tema" title="Ganti tema"><svg class="sun-icon" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="4" />
                            <path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" />
                        </svg><svg class="moon-icon" viewBox="0 0 24 24">
                            <path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.6 8.6 0 1 0 20.5 14.2Z" />
                        </svg></button>
                    <div class="user-chip"><span class="avatar">A</span><span class="user-label">Admin</span></div>
                </div>
            </header>
            <main class="content"><?php if ($flash): ?><div class="toast-notice <?= $flash[0] === 'err' ? 'error' : 'success' ?>" role="status"><span class="toast-mark"><?= $flash[0] === 'err' ? '!' : '✓' ?></span><span><?= e($flash[1]) ?></span><button type="button" class="toast-close" aria-label="Tutup notifikasi">×</button></div><?php endif; ?><?php require BASE_PATH . '/views/' . ($page === 'error' ? 'error' : (in_array($page, ['members', 'member', 'debts', 'payments', 'reports'], true) ? $page : 'home')) . '.php'; ?></main>
            <footer class="app-footer"><span>Buku Hutang</span><span>Manajemen hutang sederhana</span></footer>
        </div>
    </div>
    <?php require BASE_PATH . '/views/modals.php'; ?><script src="assets/app.js"></script><?php if ($page === 'home'): ?><script>window.debtDashboardData = <?= json_encode($trend, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>;</script><script src="assets/charts.js" defer></script><?php endif; ?>
</body>

</html>