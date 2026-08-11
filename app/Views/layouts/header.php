<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'ISP-BILLING-LITE') ?></title>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; background:#f6f7fb; color:#111; }
    .container { max-width: 1100px; margin: 0 auto; padding: 16px; }
    .nav { background:#111827; color:#fff; }
    .nav .container { display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .brand { font-weight:700; letter-spacing:.5px; }
    .links a { color:#fff; text-decoration:none; margin-left:12px; opacity:.9; }
    .links a:hover { opacity:1; text-decoration:underline; }
    .card { background:#fff; border-radius:12px; padding:18px; box-shadow:0 1px 8px rgba(0,0,0,.06); }
    h1 { margin: 0 0 10px; font-size: 32px; }
    p { margin: 0; }
    @media (max-width: 600px) {
      .links a { margin-left: 8px; font-size: 14px; }
      h1 { font-size: 26px; }
    }
  </style>
</head>
<body>
  <div class="nav">
    <div class="container">
      <div class="brand">ISP-BILLING-LITE</div>
      <div class="links">
        <a href="<?= url('/') ?>">Home</a>
        <a href="<?= url('/login') ?>">Login</a>
      </div>
    </div>
  </div>
  <div class="container">
