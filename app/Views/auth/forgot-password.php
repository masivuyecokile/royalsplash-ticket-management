<section class="auth-wrap">
<div class="auth-card">
<span class="badge">Customer Access</span>

<h1>Forgot Password</h1>

<p class="muted">
Enter your customer email address and we will send you a secure reset link.
</p>

<?php if (!empty($_SESSION['forgot_password_success'])): ?>
<div class="alert success">
<?= htmlspecialchars($_SESSION['forgot_password_success']) ?>
</div>
<?php unset($_SESSION['forgot_password_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['forgot_password_error'])): ?>
<div class="alert error">
<?= htmlspecialchars($_SESSION['forgot_password_error']) ?>
</div>
<?php unset($_SESSION['forgot_password_error']); ?>
<?php endif; ?>

<form method="POST" action="<?= $appUrl ?>/forgot-password" class="auth-form">
<div class="form-group">
<label>Email Address</label>

<input
type="email"
name="email"
placeholder="Enter your customer email"
required
>
</div>

<button type="submit" class="btn primary full-width">
Send Reset Link
</button>
</form>

<p class="auth-footer-text">
Remembered your password?
<a href="<?= $appUrl ?>/login">Back to Login</a>
</p>
</div>
</section>
