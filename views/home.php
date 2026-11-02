<div class="page-heading">
  <div><h1>Dashboard</h1><p class="sub">Pantau posisi hutang dan aktivitas pembayaran dalam satu tampilan.</p></div>
  <button class="btn pri" onclick="openM('mMember')">+ Tambah member</button>
</div>

<form class="dashboard-filter" method="get">
  <input type="hidden" name="page" value="home">
  <div class="filter-label"><strong>Periode analisis</strong><small>Hutang berdasarkan tanggal hutang · pembayaran berdasarkan tanggal diterima</small></div>
  <div class="filter-fields">
    <label>Dari tanggal<input type="date" name="from" value="<?= e($dateFrom) ?>" required></label>
    <label>Sampai tanggal<input type="date" name="to" value="<?= e($dateTo) ?>" required></label>
    <button class="btn pri" type="submit">Terapkan</button>
    <a class="btn" href="?">Bulan ini</a>
    <a class="btn" href="?export=transactions&amp;from=<?= e($dateFrom) ?>&amp;to=<?= e($dateTo) ?>">↓ Ekspor transaksi</a>
  </div>
</form>

<div class="stats dashboard-stats">
  <div class="stat main"><small>Saldo hutang akhir periode</small><b><?= rp($dashboard['outstanding']) ?></b><span class="stat-hint">Akumulasi sampai <?= tglDate($dateTo) ?></span></div>
  <div class="stat"><small>Hutang baru</small><b><?= rp($dashboard['debt_period']) ?></b><span class="stat-hint">Dicatat pada periode terpilih</span></div>
  <div class="stat"><small>Pembayaran diterima</small><b><?= rp($dashboard['payment_period']) ?></b><span class="stat-hint">Pembayaran valid pada periode</span></div>
  <div class="stat"><small>Member masih berhutang</small><b><?= number_format($dashboard['debtors'], 0, ',', '.') ?> <span class="stat-unit">/ <?= number_format($dashboard['members'], 0, ',', '.') ?></span></b><span class="stat-hint">Posisi pada akhir periode</span></div>
</div>

<div class="analytics-grid">
  <section class="card chart-card chart-wide">
    <div class="bar"><div><h2>Tren hutang & pembayaran</h2><small class="card-caption">Pergerakan nominal per hari atau per bulan</small></div><span class="chart-legend"><i class="legend-debt"></i> Hutang baru <i class="legend-payment"></i> Pembayaran</span></div>
    <div class="chart-wrap"><canvas id="trendChart" role="img" aria-label="Grafik garis tren hutang baru dan pembayaran"></canvas></div>
  </section>
  <section class="card chart-card">
    <div class="bar"><div><h2>Perbandingan transaksi</h2><small class="card-caption">Hutang baru vs pembayaran</small></div></div>
    <div class="chart-wrap chart-wrap-bar"><canvas id="comparisonChart" role="img" aria-label="Grafik batang perbandingan hutang dan pembayaran"></canvas></div>
  </section>
</div>

<div class="dashboard-lower">
  <section class="card">
    <div class="bar"><div><h2>Top 5 sisa hutang</h2><small class="card-caption">Saldo terbesar pada <?= tglDate($dateTo) ?></small></div></div>
    <?php if ($topDebtors): ?><div class="tablebox"><table class="compact-table"><thead><tr><th>Member</th><th class="r">Total hutang</th><th class="r">Terbayar</th><th class="r">Sisa</th></tr></thead><tbody>
      <?php foreach ($topDebtors as $d): ?><tr><td><a class="name" href="?page=member&id=<?= (int)$d['id'] ?>"><?= e($d['name']) ?></a></td><td class="r"><?= rp((int)$d['total_debt']) ?></td><td class="r"><?= rp((int)$d['total_paid']) ?></td><td class="r bad"><strong><?= rp((int)$d['balance']) ?></strong></td></tr><?php endforeach; ?>
    </tbody></table></div><?php else: ?><div class="empty">Tidak ada member dengan saldo hutang pada akhir periode ini.</div><?php endif; ?>
  </section>
  <section class="card">
    <div class="bar"><div><h2>Aktivitas terbaru</h2><small class="card-caption">8 transaksi terakhir</small></div></div>
    <?php if ($recentActivities): ?><div class="activity-list">
      <?php foreach ($recentActivities as $a): $isPayment = $a['kind'] === 'payment'; $isCancelled = $isPayment && $a['cancelled_at'] !== null; ?>
        <div class="activity-item"><span class="activity-icon <?= $isPayment ? 'payment' : 'debt' ?>"><?= $isPayment ? '↓' : '+' ?></span><div class="activity-copy"><strong><?= e($a['member_name']) ?></strong><span><?= $isCancelled ? 'Pembayaran dibatalkan' : ($isPayment ? 'Pembayaran diterima' : 'Hutang dicatat') ?> · <?= e($a['recorded_by']) ?></span><small><?= e(hariTanggal($a['happened_at'], true)) ?></small></div><b class="activity-amount <?= $isPayment && !$isCancelled ? 'good' : ($isCancelled ? 'mute' : 'bad') ?>"><?= $isPayment ? '− ' : '+ ' ?><?= rp((int)$a['amount']) ?></b></div>
      <?php endforeach; ?>
    </div><?php else: ?><div class="empty">Belum ada aktivitas transaksi.</div><?php endif; ?>
  </section>
</div>

