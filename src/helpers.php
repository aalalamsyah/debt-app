<?php
declare(strict_types=1);

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rp(int|float $n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function tgl(string $ts): string { return date('d/m/Y H:i', strtotime($ts)); }
function tglDate(string $d): string { return date('d/m/Y', strtotime($d)); }

/** Format tanggal dengan nama hari dan bulan dalam Bahasa Indonesia. */
function hariTanggal(string $value, bool $withTime = false): string
{
    $timestamp = strtotime($value);
    if ($timestamp === false) return $value;

    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $result = $hari[(int)date('w', $timestamp)] . ', ' . (int)date('j', $timestamp) . ' ' . $bulan[(int)date('n', $timestamp)] . ' ' . date('Y', $timestamp);

    return $withTime ? $result . ' · ' . date('H:i', $timestamp) : $result;
}

//tgl dengan jam
// function tglJam(string $ts): string { return date('d/m/Y H:i', strtotime($ts)); }

function num(mixed $v): int { return (int)preg_replace('/\D/', '', (string)$v); }

function go(string $url, string $msg = '', string $type = 'ok'): never
{
    if ($msg !== '') $_SESSION['flash'] = [$type, $msg];
    header('Location: ' . $url);
    exit;
}

/* Helper khusus form di view */
function hid(string $act, string $back): void
{
    echo '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">'
       . '<input type="hidden" name="action" value="' . $act . '">'
       . '<input type="hidden" name="back" value="' . e($back) . '">';
}

function memberSelect(array $members, bool $onlyDebtors): void
{
    echo '<label>Member</label><select name="member_id" required><option value="">Pilih member…</option>';
    foreach ($members as $m) {
        $s = (int)$m['total_debt'] - (int)$m['total_paid'];
        if ($onlyDebtors && $s <= 0) continue;
        echo '<option value="' . $m['id'] . '" data-sisa="' . $s . '">' . e($m['name'])
           . ($onlyDebtors ? ' — sisa ' . rp($s) : '') . '</option>';
    }
    echo '</select>';
}

/** Kelompokkan hutang (hasil debtsWithStatus) per tanggal: jumlah catatan, total hutang, terbayar, sisa, status. */
function groupDebtsByDate(array $debts): array
{
    $g = [];
    foreach ($debts as $d) {
        if (($d['cancelled_at'] ?? null) !== null) continue;
        $k = $d['debt_date'];
        $g[$k] ??= ['date' => $k, 'count' => 0, 'amount' => 0, 'paid' => 0, 'sisa' => 0];
        $g[$k]['count']++;
        $g[$k]['amount'] += (int)$d['amount'];
        $g[$k]['paid']   += $d['paid'];
        $g[$k]['sisa']   += $d['sisa'];
    }
    foreach ($g as &$r) {
        $r['status'] = $r['sisa'] === 0 ? 'lunas' : ($r['paid'] > 0 ? 'sebagian' : 'belum');
    }
    krsort($g);
    return array_values($g);
}
