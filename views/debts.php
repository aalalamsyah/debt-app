<div class="page-heading">
    <div>
        <h1>Data Hutang</h1>
        <p class="sub">Seluruh pencatatan hutang, dapat ditelusuri berdasarkan member dan periode.</p>
    </div><button class="btn pri" onclick="openM('mDebt')">+ Catat hutang</button>
</div>
<section class="card transaction-card">
    <form class="member-toolbar transaction-toolbar" method="get"><input type="hidden" name="page" value="debts">
        <label class="member-search-label"><span>Pencarian</span><input type="search" name="q" value="<?= e($listFilters['q']) ?>" placeholder="Nama, telepon, atau keterangan…"></label>
        <label><span>Member</span><select name="member_id">
                <option value="0">Semua member</option><?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>" <?= $listFilters['member_id'] === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option><?php endforeach; ?>
            </select></label>
        <label><span>Dari tanggal</span><input type="date" name="from" value="<?= e($listFilters['from']) ?>"></label><label><span>Sampai tanggal</span><input type="date" name="to" value="<?= e($listFilters['to']) ?>"></label>
        <label><span>Urutkan</span><select name="sort">
                <option value="date" <?= $listFilters['sort'] === 'date' ? 'selected' : '' ?>>Tanggal hutang</option>
                <option value="member" <?= $listFilters['sort'] === 'member' ? 'selected' : '' ?>>Nama member</option>
                <option value="amount" <?= $listFilters['sort'] === 'amount' ? 'selected' : '' ?>>Nominal</option>
                <option value="created" <?= $listFilters['sort'] === 'created' ? 'selected' : '' ?>>Waktu dicatat</option>
            </select></label>
        <label><span>Arah</span><select name="dir">
                <option value="desc" <?= $listFilters['dir'] === 'desc' ? 'selected' : '' ?>>Terbaru</option>
                <option value="asc" <?= $listFilters['dir'] === 'asc' ? 'selected' : '' ?>>Terlama</option>
            </select></label>
        <label><span>Per halaman</span><select name="per_page"><?php foreach ([10, 25, 50, 100] as $n): ?><option value="<?= $n ?>" <?= $listPagination['per_page'] === $n ? 'selected' : '' ?>><?= $n ?> data</option><?php endforeach; ?></select></label><input type="hidden" name="p" value="1"><button class="btn pri">Terapkan</button><a class="btn" href="?page=debts">Reset</a>
    </form>
    <div class="tablebox">
        <table>
            <thead>
                <tr>
                    <th>Tanggal hutang</th>
                    <th>Member</th>
                    <th>Keterangan</th>
                    <th>Pencatat</th>
                    <th class="r">Nominal</th>
                    <th>Waktu dicatat</th>
                    <!-- <th></th> -->
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listRows as $r): ?><tr>
                        <td class="nowrap"><b><?= e(hariTanggal($r['debt_date'])) ?></b></td>
                        <td><a class="name" href="?page=member&id=<?= (int)$r['member_id'] ?>"><?= e($r['member_name']) ?></a><?php if ($r['phone']): ?><div class="mute member-phone"><?= e($r['phone']) ?></div><?php endif; ?></td>
                        <td><?= $r['note'] ? e($r['note']) : '<span class="mute">-</span>' ?></td>
                        <td><?= e($r['recorded_by']) ?></td>
                        <td class="r"><b><?= rp((int)$r['amount']) ?></b></td>
                        <td class="nowrap mute"><?= tgl($r['created_at']) ?></td>
                        <!-- <td><button class="btn sm" onclick="openM('mDebt',<?= (int)$r['member_id'] ?>)">+ Hutang</button></td> -->
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$listRows): ?><div class="empty">Tidak ada data hutang pada filter ini.</div><?php endif; ?>
    <?php $paginationPage = $listPagination;
    $paginationParams = $_GET;
    require BASE_PATH . '/views/pagination.php'; ?>
</section>