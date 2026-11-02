<h1>Rekap hutang</h1>
<p class="sub">Ringkasan hutang semua member. Klik nama member untuk melihat rincian per tanggal.</p>

<div class="stats">
  <div class="stat main"><small>Total sisa hutang</small><b><?= rp($sumDebt - $sumPaid) ?></b></div>
  <div class="stat"><small>Total hutang tercatat</small><b><?= rp($sumDebt) ?></b></div>
  <div class="stat"><small>Total terbayar</small><b><?= rp($sumPaid) ?></b></div>
  <div class="stat"><small>Member berhutang</small><b><?= $nDebtors ?> <span class="mute" style="font-size:14px;font-weight:500">dari <?= count($members) ?></span></b></div>
</div>

<div class="card">
  <div class="bar">
    <h2>Daftar member</h2><input class="search" placeholder="Cari nama member…" oninput="filt(this.value)">
  </div>
  <div class="tablebox">
    <table>
      <thead>
        <tr>
          <th>Member</th>
          <th class="r">Total hutang</th>
          <th class="r">Terbayar</th>
          <th class="r">Sisa</th>
          <th>Hutang terakhir</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="tb">
        <?php foreach ($members as $m):
          $sisa = $m['total_debt'] - $m['total_paid'];
          $pct = $m['total_debt'] > 0 ? round($m['total_paid'] / $m['total_debt'] * 100) : 0; ?>
          <tr data-n="<?= e(strtolower($m['name'])) ?>">
            <td><a class="name" href="?page=member&id=<?= $m['id'] ?>"><?= e($m['name']) ?></a>
              <?php if ($m['phone']): ?><div class="mute" style="font-size:13px"><?= e($m['phone']) ?></div><?php endif; ?></td>
            <td class="r"><?= rp((int)$m['total_debt']) ?></td>
            <td class="r"><?= rp((int)$m['total_paid']) ?><div class="prog"><i style="width:<?= $pct ?>%"></i></div>
            </td>
            <td class="r <?= $sisa > 0 ? 'bad' : 'good' ?>"><?= $sisa > 0 ? rp($sisa) : 'Lunas' ?></td>
            <td class="mute nowrap"><?= $m['last_debt'] ? tglDate($m['last_debt']) : '-' ?></td>
            <td class="r nowrap">
              <div class="btns" style="justify-content:flex-end">
                <button class="btn sm" onclick="openM('mDebt',<?= $m['id'] ?>)">+ Hutang</button>
                <button class="btn sm" onclick="openM('mPay',<?= $m['id'] ?>)" <?= $sisa <= 0 ? 'disabled' : '' ?>>Lunasi</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!$members): ?><div class="empty">Belum ada member. Klik <b>+ Member</b> untuk menambahkan penghutang pertama.</div><?php endif; ?>
</div>