<?php $sisa = (int)$member['total_debt'] - (int)$member['total_paid'];
$badge['dibatalkan'] = 'Dibatalkan'; ?>
<a class="back" href="?">‹ Kembali ke rekap</a>
<div class="bar" style="padding:0 0 18px;border:0">
  <div><h1><?= e($member['name']) ?></h1><p class="sub" style="margin:0"><?= e($member['phone'] ?: 'Tanpa nomor telepon') ?></p></div>
  <div class="btns">
    <button class="btn" onclick="openM('mDebt',<?= $member['id'] ?>)">+ Catat hutang</button>
    <button class="btn pri" onclick="openM('mPay',<?= $member['id'] ?>)" <?= $sisa <= 0 ? 'disabled' : '' ?>>Pelunasan</button>
  </div>
</div>

<div class="stats" style="grid-template-columns:1.4fr 1fr 1fr">
  <div class="stat main"><small>Sisa hutang</small><b><?= rp($sisa) ?></b></div>
  <div class="stat"><small>Total hutang</small><b><?= rp((int)$member['total_debt']) ?></b></div>
  <div class="stat"><small>Total terbayar</small><b><?= rp((int)$member['total_paid']) ?></b></div>
</div>

<div class="grid2">
  <div class="card">
    <div class="bar"><h2>Hutang per tanggal</h2><span class="mute" style="font-size:13px">Total hutang yang dicatat di setiap tanggal</span></div>
    <div class="tablebox"><table>
      <thead><tr><th>Tanggal</th><th class="r">Jumlah catatan</th><th class="r">Total hutang</th><th class="r">Terbayar</th><th class="r">Sisa</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach (groupDebtsByDate($debts) as $g): ?>
        <tr>
          <td class="nowrap"><b><?= tglDate($g['date']) ?></b></td>
          <td class="r"><?= $g['count'] ?>x</td>
          <td class="r"><b><?= rp($g['amount']) ?></b></td>
          <td class="r"><?= rp($g['paid']) ?></td>
          <td class="r"><?= rp($g['sisa']) ?></td>
          <td><span class="badge b-<?= $g['status'] ?>"><?= $badge[$g['status']] ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php if (!$debts): ?><div class="empty">Belum ada hutang tercatat untuk member ini.</div><?php endif; ?>
  </div>

  <div class="card">
    <div class="bar"><h2>Rincian hutang</h2><span class="mute" style="font-size:13px">Pelunasan menutup hutang dari tanggal terlama</span></div>
    <div class="tablebox"><table>
      <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Pencatat</th><th class="r">Hutang</th><th class="r">Terbayar</th><th class="r">Sisa</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($debts as $d): $cancelledDebt = $d['cancelled_at'] !== null; ?>
        <tr class="<?= $cancelledDebt ? 'cancelled' : '' ?>">
          <td class="nowrap"><?= tglDate($d['debt_date']) ?></td>
          <td><?= $d['note'] ? e($d['note']) : '<span class="mute">-</span>' ?></td>
          <td><?= e($d['recorded_by']) ?></td>
          <td class="r"><?= rp((int)$d['amount']) ?></td>
          <td class="r"><?= rp($d['paid']) ?></td>
          <td class="r"><b><?= rp($d['sisa']) ?></b></td>
          <td><span class="badge b-<?= $d['status'] ?>"><?= $badge[$d['status']] ?></span><?php if ($cancelledDebt && $d['cancel_reason']): ?><div class="mute"><?= e($d['cancel_reason']) ?></div><?php endif; ?></td>
          <td class="r"><?php if (!$cancelledDebt): ?><form method="post"><?php hid('cancel_debt', $back); ?><input type="hidden" name="id" value="<?= (int)$d['id'] ?>"><input type="hidden" name="reason"><button class="btn sm danger" onclick="const reason=prompt('Alasan pembatalan hutang:'); if(!reason || !reason.trim()) return false; if(!confirm('Batalkan hutang <?= rp((int)$d['amount']) ?>?')) return false; this.form.reason.value=reason.trim();">Batalkan</button></form><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php if (!$debts): ?><div class="empty">Belum ada hutang tercatat untuk member ini.</div><?php endif; ?>
  </div>

  <div class="card">
    <div class="bar"><h2>Riwayat pelunasan</h2></div>
    <div class="tablebox"><table>
      <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Pencatat</th><th class="r">Nominal</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($pays as $p): $c = $p['cancelled_at'] !== null; ?>
        <tr class="<?= $c ? 'cancelled' : '' ?>">
          <td class="nowrap"><?= tgl($p['created_at']) ?></td>
          <td><?= $p['note'] ? e($p['note']) : '-' ?></td>
          <td><?= e($p['recorded_by']) ?></td>
          <td class="r"><?= rp((int)$p['amount']) ?></td>
          <td><?= $c ? '<span class="badge b-batal">Dibatalkan ' . tgl($p['cancelled_at']) . '</span>' : '<span class="badge b-lunas">Berlaku</span>' ?></td>
          <td class="r">
            <?php if (!$c): ?>
            <form method="post" onsubmit="return confirm('Batalkan pelunasan <?= rp((int)$p['amount']) ?>? Status hutang akan dihitung ulang.')">
              <?php hid('cancel_payment', $back); ?>
              <input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button class="btn sm danger">Batal lunas</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php if (!$pays): ?><div class="empty">Belum ada pelunasan.</div><?php endif; ?>
  </div>
</div>
