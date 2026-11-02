<?php
declare(strict_types=1);

const MAX_PIN_FAILS = 5;
const LOCK_SECONDS  = 60;

function isAuthed(): bool { return !empty($_SESSION['auth']); }

/** Login/logout dengan PIN. Salah 5x berturut-turut: terkunci 60 detik. */
function handleAuthPost(array $config): never
{
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) go('?', 'Sesi tidak valid, silakan ulangi.', 'err');

    if (($_POST['action'] ?? '') === 'logout') {
        unset($_SESSION['auth']);
        go('?');
    }

    if (($_SESSION['lock_until'] ?? 0) > time()) {
        go('?', 'Terlalu banyak percobaan. Coba lagi dalam ' . ($_SESSION['lock_until'] - time()) . ' detik.', 'err');
    }
    if (hash_equals((string)$config['pin'], trim((string)($_POST['pin'] ?? '')))) {
        session_regenerate_id(true);
        $_SESSION['auth'] = true;
        unset($_SESSION['fails'], $_SESSION['lock_until']);
        go('?');
    }
    $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
    if ($_SESSION['fails'] >= MAX_PIN_FAILS) {
        $_SESSION['lock_until'] = time() + LOCK_SECONDS;
        $_SESSION['fails'] = 0;
    }
    go('?', 'PIN salah.', 'err');
}
