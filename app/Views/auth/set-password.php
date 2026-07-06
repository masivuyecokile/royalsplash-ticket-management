<section class="auth-wrap">
<div class="auth-card">
<span class="badge">Royal Splash</span>

<h1>Set Your Password</h1>

<p class="muted">
Hi <strong><?= htmlspecialchars($user->full_name) ?></strong>, create your password to access your tickets later.
</p>

<?php if (!empty($_SESSION['password_setup_error'])): ?>
<div class="alert error">
<?= htmlspecialchars($_SESSION['password_setup_error']) ?>
</div>
<?php unset($_SESSION['password_setup_error']); ?>
<?php endif; ?>

<form method="POST" action="<?= $appUrl ?>/set-password" class="auth-form">
<input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

<div class="form-group">
<label>New Password</label>

<input
type="password"
name="password"
placeholder="Minimum 8 characters"
minlength="8"
required
>
</div>

<div class="form-group">
<label>Confirm Password</label>

<input
type="password"
name="confirm_password"
placeholder="Repeat password"
minlength="8"
required
>
</div>

<button type="submit" class="btn primary full-width">
Create Password
</button>
</form>

<p class="auth-footer-text">
Already set your password?
<a href="<?= $appUrl ?>/login">Login here</a>
</p>
</div>
</section>
