<section class="auth-wrap">
<div class="auth-card">
<span class="badge">Customer Access</span>

<h1>Reset Password</h1>

<p class="muted">
Hi <strong><?= htmlspecialchars($user->full_name) ?></strong>, create a new password for your customer account.
</p>

<?php if (!empty($_SESSION['reset_password_error'])): ?>
<div class="alert error">
<?= htmlspecialchars($_SESSION['reset_password_error']) ?>
</div>
<?php unset($_SESSION['reset_password_error']); ?>
<?php endif; ?>

<form method="POST" action="<?= $appUrl ?>/reset-password" class="auth-form">
<input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

<div class="form-group">
<label>New Password</label>

<input
type="password"
name="password"
minlength="8"
placeholder="Minimum 8 characters"
required
>
</div>

<div class="form-group">
<label>Confirm Password</label>

<input
type="password"
name="confirm_password"
minlength="8"
placeholder="Repeat password"
required
>
</div>

<button type="submit" class="btn primary full-width">
Reset Password
</button>
</form>

<p class="auth-footer-text">
<a href="<?= $appUrl ?>/login">Back to Login</a>
</p>
</div>
</section>
