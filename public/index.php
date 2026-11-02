<?php
declare(strict_types=1);
session_start();

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
date_default_timezone_set($config['timezone']);

require BASE_PATH . '/src/Database.php';
require BASE_PATH . '/src/DebtRepository.php';
require BASE_PATH . '/src/DashboardRepository.php';
require BASE_PATH . '/src/helpers.php';
require BASE_PATH . '/src/actions.php';
require BASE_PATH . '/src/auth.php';

$_SESSION['csrf'] ??= bin2hex(random_bytes(16));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['login', 'logout'], true)) handleAuthPost($config);
if (!isAuthed()) {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    require BASE_PATH . '/views/login.php';
    exit;
}

try {
    $pdo = Database::connect($config);
    $repo = new DebtRepository($pdo);
    $dashboardRepo = new DashboardRepository($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') handlePost($repo);

    $page      = $_GET['page'] ?? 'home';
    if ($page === 'reports') go('?', 'Menu Laporan sudah dinonaktifkan.');
    $members   = $repo->members(); // Hanya data ringkas untuk dropdown transaksi.
    $recorders = $repo->recorders();

    $today = date('Y-m-d');
    $defaultFrom = date('Y-m-01');
    $dateFrom = $_GET['from'] ?? $defaultFrom;
    $dateTo = $_GET['to'] ?? $today;
    $validDate = static fn($v): bool => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) === 1 && strtotime($v) !== false && date('Y-m-d', strtotime($v)) === $v;
    if (!$validDate($dateFrom)) $dateFrom = $defaultFrom;
    if (!$validDate($dateTo)) $dateTo = $today;
    if ($dateFrom > $dateTo) [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
    // Ekspor CSV dijalankan setelah autentikasi dan validasi periode, sebelum HTML dirender.
    $export = $_GET['export'] ?? '';
    if ($export === 'members' || $export === 'transactions') {
        $filename = $export === 'members' ? 'daftar-member-' . date('Ymd-His') . '.csv' : 'transaksi-' . $dateFrom . '-sampai-' . $dateTo . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8 dengan benar.
        $csvText = static function ($value): string {
            $value = (string)($value ?? '');
            // Cegah formula injection saat file dibuka di spreadsheet.
            return preg_match('/^[\s]*[=+@\-]/u', $value) ? "'" . $value : $value;
        };
        if ($export === 'members') {
            $filters = [
                'q' => trim((string)($_GET['q'] ?? '')),
                'status' => in_array($_GET['status'] ?? 'all', ['all', 'debt', 'paid'], true) ? ($_GET['status'] ?? 'all') : 'all',
                'sort' => in_array($_GET['sort'] ?? 'name', ['name', 'debt', 'paid', 'balance', 'last_debt'], true) ? ($_GET['sort'] ?? 'name') : 'name',
                'dir' => strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc',
            ];
            fputcsv($out, ['ID', 'Nama member', 'Nomor telepon', 'Total hutang', 'Total terbayar', 'Sisa hutang', 'Hutang terakhir']);
            foreach ($repo->exportMembers($filters['q'], $filters['status'], $filters['sort'], $filters['dir']) as $row) {
                fputcsv($out, [(int)$row['id'], $csvText($row['name']), $csvText($row['phone']), (int)$row['total_debt'], (int)$row['total_paid'], (int)$row['balance'], $row['last_debt'] ?? '']);
            }
        } else {
            fputcsv($out, ['Tanggal transaksi', 'Waktu dicatat', 'Jenis', 'Nama member', 'Nomor telepon', 'Nominal', 'Keterangan', 'Dicatat oleh', 'Status', 'Waktu pembatalan']);
            foreach ($repo->exportTransactions($dateFrom, $dateTo) as $row) {
                fputcsv($out, [$row['transaction_date'], $row['created_at'], $row['kind'], $csvText($row['member_name']), $csvText($row['phone']), (int)$row['amount'], $csvText($row['note']), $csvText($row['recorded_by']), $row['status'], $row['cancelled_at'] ?? '']);
            }
        }
        fclose($out);
        exit;
    }
    $dashboard = $trend = $topDebtors = $recentActivities = [];
    $memberRows = [];
    $memberPagination = ['page' => 1, 'per_page' => 10, 'total' => 0, 'pages' => 1];
    $memberFilters = ['q' => '', 'status' => 'all', 'sort' => 'name', 'dir' => 'asc'];
    if (in_array($page, ['home', 'reports'], true)) {
        $dashboard = $dashboardRepo->getSummary($dateFrom, $dateTo);
        $trend = $dashboardRepo->getTrend($dateFrom, $dateTo);
        $topDebtors = $dashboardRepo->getTopDebtors($dateTo);
        $recentActivities = $dashboardRepo->getRecentActivities();

        if ($page === 'home') {
        $memberFilters['q'] = trim((string)($_GET['q'] ?? ''));
        $memberFilters['status'] = in_array($_GET['status'] ?? 'all', ['all', 'debt', 'paid'], true) ? $_GET['status'] ?? 'all' : 'all';
        $memberFilters['sort'] = in_array($_GET['sort'] ?? 'name', ['name', 'debt', 'paid', 'balance', 'last_debt'], true) ? $_GET['sort'] ?? 'name' : 'name';
        $memberFilters['dir'] = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = (int)($_GET['per_page'] ?? 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) $perPage = 10;
        $requestedPage = max(1, (int)($_GET['p'] ?? 1));
        $memberResult = $repo->paginateMembers($memberFilters['q'], $memberFilters['status'], $memberFilters['sort'], $memberFilters['dir'], $perPage, ($requestedPage - 1) * $perPage);
        $totalPages = max(1, (int)ceil($memberResult['total'] / $perPage));
        $currentPage = min($requestedPage, $totalPages);
        if ($currentPage !== $requestedPage) {
            $memberResult = $repo->paginateMembers($memberFilters['q'], $memberFilters['status'], $memberFilters['sort'], $memberFilters['dir'], $perPage, ($currentPage - 1) * $perPage);
        }
        $memberRows = $memberResult['rows'];
        $memberPagination = ['page' => $currentPage, 'per_page' => $perPage, 'total' => $memberResult['total'], 'pages' => $totalPages];
        }
    }

    $member = null; $debts = []; $pays = [];
    $listRows = []; $listPagination = ['page'=>1,'per_page'=>25,'total'=>0,'pages'=>1];
    $listFilters = ['q'=>'','member_id'=>0,'from'=>$defaultFrom,'to'=>$today,'status'=>'all','sort'=>'date','dir'=>'desc'];
    if (in_array($page, ['debts','payments'], true)) {
        $listFilters['q'] = trim((string)($_GET['q'] ?? ''));
        $listFilters['member_id'] = max(0, (int)($_GET['member_id'] ?? 0));
        $listFilters['from'] = $validDate($_GET['from'] ?? '') ? $_GET['from'] : $defaultFrom;
        $listFilters['to'] = $validDate($_GET['to'] ?? '') ? $_GET['to'] : $today;
        if ($listFilters['from'] > $listFilters['to']) [$listFilters['from'], $listFilters['to']] = [$listFilters['to'], $listFilters['from']];
        // Gunakan nilai default saat parameter belum dikirim (mis. akses menu pertama kali).
        $requestedSort = (string)($_GET['sort'] ?? 'date');
        $requestedStatus = (string)($_GET['status'] ?? 'all');
        $listFilters['sort'] = in_array($requestedSort, ['date','amount','member','created'], true) ? $requestedSort : 'date';
        $listFilters['dir'] = strtolower((string)($_GET['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $listFilters['status'] = in_array($requestedStatus, ['all','valid','cancelled'], true) ? $requestedStatus : 'all';
        $perPage = in_array((int)($_GET['per_page'] ?? 25), [10,25,50,100], true) ? (int)($_GET['per_page'] ?? 25) : 25;
        $requested = max(1,(int)($_GET['p'] ?? 1));
        $result = $page === 'debts'
            ? $repo->paginateDebts($listFilters['q'],$listFilters['member_id'],$listFilters['from'],$listFilters['to'],$listFilters['sort'],$listFilters['dir'],$perPage,($requested-1)*$perPage,$listFilters['status'])
            : $repo->paginatePayments($listFilters['q'],$listFilters['member_id'],$listFilters['from'],$listFilters['to'],$listFilters['status'],$listFilters['sort'],$listFilters['dir'],$perPage,($requested-1)*$perPage);
        $pages=max(1,(int)ceil($result['total']/$perPage)); $current=min($requested,$pages);
        if($current!==$requested) $result = $page === 'debts'
            ? $repo->paginateDebts($listFilters['q'],$listFilters['member_id'],$listFilters['from'],$listFilters['to'],$listFilters['sort'],$listFilters['dir'],$perPage,($current-1)*$perPage,$listFilters['status'])
            : $repo->paginatePayments($listFilters['q'],$listFilters['member_id'],$listFilters['from'],$listFilters['to'],$listFilters['status'],$listFilters['sort'],$listFilters['dir'],$perPage,($current-1)*$perPage);
        $listRows=$result['rows']; $listPagination=['page'=>$current,'per_page'=>$perPage,'total'=>$result['total'],'pages'=>$pages];
    }

    $memberOverview = ['total_members'=>0, 'total_debt'=>0, 'total_paid'=>0, 'debtors'=>0];
    $memberSettingsRows = [];
    $memberSearch = '';
    if ($page === 'members') {
        $memberOverview = $repo->memberOverview();
        $memberSearch = trim((string)($_GET['q'] ?? ''));
        $memberSettingsRows = $repo->exportMembers($memberSearch, 'all', 'name', 'asc');
    }

    if ($page === 'member') {
        $member = $repo->findMember((int)($_GET['id'] ?? 0));
        if (!$member) go('?', 'Member tidak ditemukan.', 'err');
        $debts = $repo->debtsWithStatus((int)$member['id']);
        $pays  = $repo->payments((int)$member['id']);
    }
} catch (PDOException $ex) {
    http_response_code(500);
    exit('<h3>Database belum siap</h3><p>Pastikan database <b>debt_db</b> sudah dibuat dan skema sudah dijalankan '
       . '(<code>php database/migrate.php</code> atau <code>psql -f database/schema.sql</code>).</p><pre>' . e($ex->getMessage()) . '</pre>');
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$back  = $page === 'member' ? '?page=member&id=' . (int)$member['id'] : (in_array($page, ['debts','payments','members'], true) ? '?page=' . $page : '?');
$badge = ['lunas' => 'Lunas', 'sebagian' => 'Lunas sebagian', 'belum' => 'Belum lunas'];

require BASE_PATH . '/views/layout.php';
