<section class="auth-wrap">
<div class="auth-card">
<span class="badge">Login</span>
<h1>Welcome Back</h1>
<p>Login to access your tickets or dashboard.</p>

<?php if (!empty($error)): ?>
<div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>


<?php if (!empty($_SESSION['password_setup_success'])): ?>
<div class="alert success">
<?= htmlspecialchars($_SESSION['password_setup_success']) ?>
</div>
<?php unset($_SESSION['password_setup_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['password_setup_error'])): ?>
<div class="alert error">
<?= htmlspecialchars($_SESSION['password_setup_error']) ?>
</div>
<?php unset($_SESSION['password_setup_error']); ?>
<?php endif; ?>

<form method="POST" action="<?= $appUrl ?>/login" class="auth-form">
<label>
Email Address
<input type="email" name="email" required>
</label>

<label>
Password
<input type="password" name="password" required>
</label>

<button type="submit" class="btn primary">Login</button>
</form>
<p class="auth-footer-text">
<a href="<?= $appUrl ?>/forgot-password">Forgot your password?</a>
</p>
<p class="auth-link">
Need an account?
<a href="<?= $appUrl ?>/register">Create one</a>
</p>
</div>
</section>
