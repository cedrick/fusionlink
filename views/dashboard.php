<h1>Dashboard</h1>

<p>Welcome <?= htmlspecialchars($_SESSION['user']['email'] ?? ''); ?></p>
<p>You are now logged in.</p>

<ul>
  <li><a href="<?= url('/customers') ?>">Customer Management</a></li>
  <li><a href="<?= url('/plans') ?>">Internet Plans</a></li>
  <li><a href="<?= url('/subscriptions') ?>">Subscriptions</a></li>
  <li><a href="<?= url('/invoices') ?>">Invoices</a></li>
  <li><a href="<?= url('/payments') ?>">Payments</a></li>
</ul>

<p><a href="<?= url('/logout') ?>">Logout</a></p>
