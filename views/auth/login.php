<h1>Welcome back</h1>
<p class="form-help">Sign in to access the enterprise billing console.</p>

<form method="POST" action="<?= url('/login') ?>">
    <?= csrf_field() ?>
    <div class="form-grid" style="grid-template-columns:1fr;">
        <div class="form-group">
            <label for="email">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                required
                autocomplete="email"
                placeholder="Enter your email"
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Enter your password"
            >
        </div>

        <label class="form-help" style="display:flex;align-items:flex-start;gap:10px;margin:0;cursor:pointer;">
            <input
                type="checkbox"
                name="remember_me"
                value="1"
                checked
                style="width:auto;min-height:0;margin-top:2px;"
            >
            <span>Keep me signed in on this device</span>
        </label>

        <div class="form-group">
            <button type="submit" class="btn" style="width:100%;">Sign in</button>
        </div>
    </div>
</form>
