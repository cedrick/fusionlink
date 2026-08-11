<?php
// views/layouts/main.php
// Safe layout with navbar + container
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= isset($title) ? htmlspecialchars($title) . " - ISP-BILLING-LITE" : "ISP-BILLING-LITE" ?></title>
  <style>
    body { margin:0; font-family: Arial, sans-serif; background:#f5f7fb; }
    .nav { background:#0b1220; padding:14px 18px; display:flex; gap:16px; align-items:center; }
    .nav a { color:#fff; text-decoration:none; }
    .brand { font-weight:bold; letter-spacing:0.5px; }
    .spacer { flex:1; }
    .container { max-width: 980px; margin: 24px auto; background:#fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
  </style>
</head>
<body>

  <div class="nav">
    <a class="brand" href="<?= url('/dashboard') ?>">ISP-BILLING-LITE</a>

    <a href="<?= url('/customers') ?>">Customers</a>
    <a href="<?= url('/plans') ?>">Plans</a>
    <a href="<?= url('/subscriptions') ?>">Subscriptions</a>
    <a href="<?= url('/invoices') ?>">Invoices</a>
    <a href="<?= url('/payments') ?>">Payments</a>
    <a href="<?= url('/users') ?>">Users</a>

    <div class="spacer"></div>

    <?php if (!empty($_SESSION['user'])): ?>
      <a href="<?= url('/logout') ?>">Logout</a>
    <?php else: ?>
      <a href="<?= url('/login') ?>">Login</a>
    <?php endif; ?>
  </div>

  <div class="container">
    <?php
      // This is where your page view content is printed.
      // Your View::render() should set something like $content or include the view directly.
      // We support both patterns safely:
      if (isset($content)) {
          echo $content;
      } elseif (isset($viewFile) && is_file($viewFile)) {
          include $viewFile;
      } else {
          // If your framework includes the view before layout, this will just be empty (OK).
      }
    ?>
  </div>

</body>
</html>
