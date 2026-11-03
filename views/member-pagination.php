<?php
$memberPagerParams = $_GET;
$memberPagerUrl = static function(int $n) use ($memberPagerParams, $memberPagerKey): string {
    $params = $memberPagerParams;
    $params[$memberPagerKey] = $n;
    return '?' . http_build_query($params);
};
$memberPagerStart = $memberPager['total'] ? (($memberPager['page'] - 1) * $memberPager['per_page'] + 1) : 0;
$memberPagerEnd = min($memberPager['page'] * $memberPager['per_page'], $memberPager['total']);
?>
<div class="pagination-bar member-detail-pagination">
  <span>Menampilkan <strong><?= number_format($memberPagerStart, 0, ',', '.') ?>–<?= number_format($memberPagerEnd, 0, ',', '.') ?></strong> dari <strong><?= number_format($memberPager['total'], 0, ',', '.') ?></strong> data</span>
  <nav class="pagination" aria-label="Navigasi halaman">
    <a class="page-link <?= $memberPager['page'] <= 1 ? 'disabled' : '' ?>" href="<?= e($memberPagerUrl(max(1, $memberPager['page'] - 1))) ?>">‹</a>
    <?php $start = max(1, $memberPager['page'] - 2); $end = min($memberPager['pages'], $memberPager['page'] + 2); for ($n = $start; $n <= $end; $n++): ?>
      <a class="page-link <?= $n === $memberPager['page'] ? 'active' : '' ?>" href="<?= e($memberPagerUrl($n)) ?>"><?= $n ?></a>
    <?php endfor; ?>
    <a class="page-link <?= $memberPager['page'] >= $memberPager['pages'] ? 'disabled' : '' ?>" href="<?= e($memberPagerUrl(min($memberPager['pages'], $memberPager['page'] + 1))) ?>">›</a>
  </nav>
</div>
