<div class="card">
  <h1>Login</h1>
  <p>Please sign in to continue.</p>

  <form method="POST" action="<?= url('/login') ?>">
        <?= csrf_field() ?>
    <div style="margin-bottom:12px;">
      <label>Email</label><br>
      <input type="email" name="email" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px;">
    </div>

    <div style="margin-bottom:12px;">
      <label>Password</label><br>
      <input type="password" name="password" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px;">
    </div>

    <button type="submit" style="padding:10px 14px; border:0; border-radius:8px; cursor:pointer;">
      Login
    </button>
  </form>
</div>
