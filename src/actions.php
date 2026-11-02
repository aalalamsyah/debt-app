<?php
declare(strict_types=1);

/** Menangani semua form POST. Selalu berakhir dengan redirect. */
function handlePost(DebtRepository $repo): void
{
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) go('?', 'Sesi tidak valid, silakan ulangi.', 'err');

    $back = $_POST['back'] ?? '?';
    if (!preg_match('/^\?[\w=&]*$/', $back)) $back = '?';

    $act    = $_POST['action'] ?? '';
    $mid    = (int)($_POST['member_id'] ?? 0);
    $amount = num($_POST['amount'] ?? 0);
    $note   = trim($_POST['note'] ?? '') ?: null;
    $by     = trim($_POST['recorded_by'] ?? '');

    if ($act === 'add_member') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') go($back, 'Nama member wajib diisi.', 'err');
        $repo->addMember($name, trim($_POST['phone'] ?? '') ?: null);
        go($back, "Member \"$name\" ditambahkan.");
    }

    if ($act === 'update_member') {
        $memberId = (int)($_POST['member_id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        if ($memberId <= 0) go($back, 'Member tidak valid.', 'err');
        if ($name === '') go($back, 'Nama member wajib diisi.', 'err');
        if (mb_strlen($name) > 100 || mb_strlen($phone) > 30) go($back, 'Nama maksimal 100 karakter dan nomor HP maksimal 30 karakter.', 'err');
        if (!$repo->updateMember($memberId, $name, $phone !== '' ? $phone : null)) {
            // rowCount dapat 0 jika nilai tidak berubah; pastikan member masih ada.
            if (!$repo->findMember($memberId)) go($back, 'Member tidak ditemukan.', 'err');
        }
        go($back, 'Data member berhasil diperbarui.');
    }

    if ($act === 'add_debt' || $act === 'add_payment') {
        if ($mid <= 0)    go($back, 'Pilih member terlebih dahulu.', 'err');
        if ($amount <= 0) go($back, 'Nominal harus lebih dari 0.', 'err');
        if ($by === '')   go($back, 'Pencatat wajib diisi.', 'err');

        if ($act === 'add_debt') {
            $date = (string)($_POST['debt_date'] ?? '');
            $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$dt || $dt->format('Y-m-d') !== $date) go($back, 'Tanggal hutang tidak valid.', 'err');
            if ($date > date('Y-m-d')) go($back, 'Tanggal hutang tidak boleh melebihi hari ini.', 'err');
            $repo->addDebt($mid, $amount, $note, $by, $date);
            go($back, 'Hutang ' . rp($amount) . ' berhasil dicatat.');
        }
        $sisa = $repo->remaining($mid);
        if ($amount > $sisa) go($back, 'Pelunasan melebihi sisa hutang (' . rp($sisa) . ').', 'err');
        $repo->addPayment($mid, $amount, $note, $by);
        go($back, 'Pelunasan ' . rp($amount) . ' berhasil dicatat.');
    }

    if ($act === 'cancel_debt') {
        $id = (int)($_POST['id'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($id <= 0 || $reason === '') go($back, 'ID hutang dan alasan pembatalan wajib diisi.', 'err');
        if (!$repo->cancelDebt($id, $reason)) go($back, 'Hutang tidak ditemukan atau sudah dibatalkan.', 'err');
        go($back, 'Data hutang berhasil dibatalkan. Saldo member dihitung ulang.');
    }

    if ($act === 'cancel_payment') {
        $repo->cancelPayment((int)($_POST['id'] ?? 0));
        go($back, 'Pelunasan dibatalkan. Status hutang dihitung ulang.');
    }
    go($back);
}
