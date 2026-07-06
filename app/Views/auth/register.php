<section class="auth-wrap">
<div class="auth-card">
<span class="badge">Create Account</span>
<h1>Join Royal Splash</h1>
<p>Create your account to buy, download and manage your tickets.</p>

<?php if (!empty($error)): ?>
<div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="<?= $appUrl ?>/register" class="auth-form">
<label>
Full Name
<input type="text" name="full_name" required>
</label>

<label>
Email Address
<input type="email" name="email" required>
</label>

<label>
Phone Number
<input type="text" name="phone">
</label>

<label>
Password
<input type="password" name="password" required>
</label>

<label>
Confirm Password
<input type="password" name="confirm_password" required>
</label>

<button type="submit" class="btn primary">Create Account</button>
</form>

<p class="auth-link">
Already have an account?
<a href="<?= $appUrl ?>/login">Login</a>
</p>
</div>
</section>
