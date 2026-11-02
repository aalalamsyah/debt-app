<div class="page-heading">
  <div><h1>Data Member</h1><p class="sub">Ringkasan saldo seluruh waktu dan pengaturan identitas member.</p></div>
  <button class="btn pri" onclick="openM('mMember')">+ Tambah member</button>
</div>

<div class="stats dashboard-stats">
  <div class="stat main"><small>Total member</small><b><?= number_format((int)$memberOverview['total_members'], 0, ',', '.') ?></b><span class="stat-hint">Seluruh member terdaftar</span></div>
  <div class="stat"><small>Total hutang</small><b><?= rp((int)$memberOverview['total_debt']) ?></b><span class="stat-hint">Akumulasi seluruh waktu</span></div>
  <div class="stat"><small>Total pembayaran valid</small><b><?= rp((int)$memberOverview['total_paid']) ?></b><span class="stat-hint">Tidak termasuk pembayaran batal</span></div>
  <div class="stat"><small>Sisa hutang keseluruhan</small><b><?= rp((int)$memberOverview['total_debt'] - (int)$memberOverview['total_paid']) ?></b><span class="stat-hint"><?= number_format((int)$memberOverview['debtors'], 0, ',', '.') ?> member masih berhutang</span></div>
</div>

<section class="card member-table-card">
  <div class="bar member-list-heading"><div><h2>Pengaturan member</h2><small class="card-caption">Ubah nama dan nomor HP. Riwayat transaksi tidak ikut berubah.</small></div></div>
  <form class="member-toolbar" method="get">
    <input type="hidden" name="page" value="members">
    <label class="member-search-label"><span>Cari nama atau nomor HP</span><input class="search" type="search" name="q" value="<?= e($memberSearch) ?>" placeholder="Ketik nama atau nomor HP…"></label>
    <button class="btn pri" type="submit">Cari</button>
    <a class="btn" href="?page=members">Reset</a>
  </form>
  <div class="tablebox"><table><thead><tr><th>Nama member</th><th>Nomor HP</th><th class="r">Sisa hutang</th><th class="r">Pengaturan</th></tr></thead><tbody>
  <?php foreach ($memberSettingsRows as $m): $balance = (int)$m['total_debt'] - (int)$m['total_paid']; ?>
    <tr>
      <td><strong><?= e($m['name']) ?></strong></td>
      <td><?= $m['phone'] ? e($m['phone']) : '<span class="mute">Belum diisi</span>' ?></td>
      <td class="r <?= $balance > 0 ? 'bad' : 'good' ?>"><?= $balance > 0 ? rp($balance) : 'Lunas' ?></td>
      <td class="r"><button type="button" class="btn sm" onclick="openEditMember(<?= (int)$m['id'] ?>, <?= htmlspecialchars(json_encode($m['name'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode((string)($m['phone'] ?? ''), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">Edit</button></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
  <?php if (!$memberSettingsRows): ?><div class="empty"><?= $memberSearch === '' ? 'Belum ada member. Klik + Tambah member untuk memulai.' : 'Member tidak ditemukan.' ?></div><?php endif; ?>
  <div class="pagination-bar"><span>Menampilkan <strong><?= number_format(count($memberSettingsRows), 0, ',', '.') ?></strong> dari <strong><?= number_format((int)$memberOverview['total_members'], 0, ',', '.') ?></strong> member<?php if ($memberSearch !== ''): ?> · hasil pencarian<?php endif; ?></span></div>
</section>
