<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Buku Hutang</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" type="image/svg+xml" href="assets/favicon.svg?v=2">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="assets/style.css">
</head>

<body>
  <header class="top">
    <div class="wrap">
      <a class="logo" href="?"><i>BH</i> Buku Hutang</a>
      <div class="btns">
        <form method="post" style="margin:0"><?php hid('logout', '?'); ?><button class="btn">Keluar</button></form>
        <button class="btn" onclick="openM('mMember')">+ Member</button>
        <!-- <button class="btn pri" onclick="openM('mDebt'<?= $member ? ',' . (int)$member['id'] : '' ?>)">Catat hutang</button> -->
      </div>
    </div>
  </header>

  <main>
    <div class="wrap">
      <?php if ($flash): ?><div class="flash <?= $flash[0] ?>"><?= e($flash[1]) ?></div><?php endif; ?>
      <?php require BASE_PATH . '/views/' . ($page === 'member' ? 'member' : 'home') . '.php'; ?>
    </div>
  </main>

  <?php require BASE_PATH . '/views/modals.php'; ?>
  <script src="assets/app.js"></script>
</body>

</html>