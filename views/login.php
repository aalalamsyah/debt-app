<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk · Buku Hutang</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="assets/favicon.svg?v=2">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
<form method="post" class="login card">
  <div class="logo-big"><i>BH</i></div>
  <h1>Buku Hutang</h1>
  <p class="sub">Masukkan PIN untuk melanjutkan.</p>
  <?php if ($flash): ?><div class="flash <?= $flash[0] ?>"><?= e($flash[1]) ?></div><?php endif; ?>
  <?php hid('login', '?'); ?>
  <input type="password" name="pin" inputmode="numeric" autocomplete="current-password" placeholder="PIN" autofocus required>
  <button class="btn pri" style="width:100%;margin-top:14px">Masuk</button>
</form>
</body>
</html>
