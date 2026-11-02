<?php
declare(strict_types=1);
session_start();

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
date_default_timezone_set($config['timezone']);

require BASE_PATH . '/src/Database.php';
require BASE_PATH . '/src/DebtRepository.php';
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
    $repo = new DebtRepository(Database::connect($config));

    if ($_SERVER['REQUEST_METHOD'] === 'POST') handlePost($repo);

    $page      = $_GET['page'] ?? 'home';
    $members   = $repo->members();
    $recorders = $repo->recorders();
    $sumDebt = $sumPaid = $nDebtors = 0;
    foreach ($members as $m) {
        $sumDebt += $m['total_debt'];
        $sumPaid += $m['total_paid'];
        if ($m['total_debt'] - $m['total_paid'] > 0) $nDebtors++;
    }

    $member = null; $debts = []; $pays = [];
    if ($page === 'member') {
        foreach ($members as $m) if ((int)$m['id'] === (int)($_GET['id'] ?? 0)) $member = $m;
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
$back  = $page === 'member' ? '?page=member&id=' . (int)$member['id'] : '?';
$badge = ['lunas' => 'Lunas', 'sebagian' => 'Lunas sebagian', 'belum' => 'Belum lunas'];

require BASE_PATH . '/views/layout.php';
