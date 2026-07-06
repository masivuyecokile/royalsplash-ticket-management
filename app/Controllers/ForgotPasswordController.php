<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\CustomerPasswordResetService;
use PDO;

class ForgotPasswordController extends Controller
{
    public function showRequest(): void
    {
        $this->view('auth/forgot-password', [
                'pageTitle' => 'Forgot Password',
                ]);
    }

public function sendLink(): void
{
    $email = strtolower(trim($_POST['email'] ?? ''));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['forgot_password_error'] = 'Please enter a valid email address.';
        $this->redirect('/forgot-password');
    }

$db = Database::connect();

$stmt = $db->prepare("
    SELECT *
    FROM users
    WHERE email = :email
    AND role = 'customer'
    AND status = 'active'
    LIMIT 1
    ");

    $stmt->execute([
            ':email' => $email,
            ]);

    $user = $stmt->fetch();

    if ($user) {
        try {
            $resetLink = CustomerPasswordResetService::sendResetEmail($db, (int) $user->id);

            if ($this->isDebugMode()) {
                $_SESSION['forgot_password_debug_link'] = $resetLink;
            }
} catch (\Throwable $e) {
if ($this->isDebugMode()) {
    $_SESSION['forgot_password_error'] = 'Debug error: ' . $e->getMessage();
    $this->redirect('/forgot-password');
}
}
}

$_SESSION['forgot_password_success'] = 'If this email belongs to a customer account, a reset link has been sent.';
$this->redirect('/forgot-password');
}

public function showReset(): void
{
    $token = $this->cleanToken($_GET['token'] ?? '');

    if ($token === '') {
        $_SESSION['login_error'] = 'Invalid reset link. Debug: token is empty.';
        $this->redirect('/login');
    }

$db = Database::connect();

$tokenRow = $this->findValidResetToken($db, $token);

if (!$tokenRow) {
    $debugReason = $this->getResetTokenDebugReason($db, $token);

    if ($this->isDebugMode()) {
        $_SESSION['login_error'] = 'Invalid reset link. Debug: ' . $debugReason;
    } else {
    $_SESSION['login_error'] = 'Invalid or expired reset link.';
}

$this->redirect('/login');
}

$this->view('auth/reset-password', [
        'pageTitle' => 'Reset Password',
        'token' => $token,
        'user' => $tokenRow,
        ]);
}

public function updatePassword(): void
{
    $token = $this->cleanToken($_POST['token'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($token === '') {
        $_SESSION['login_error'] = 'Invalid or expired reset link.';
        $this->redirect('/login');
    }

if ($password === '' || strlen($password) < 8) {
    $_SESSION['reset_password_error'] = 'Password must be at least 8 characters.';
    $this->redirect('/reset-password?token=' . urlencode($token));
}

if ($password !== $confirmPassword) {
    $_SESSION['reset_password_error'] = 'Passwords do not match.';
    $this->redirect('/reset-password?token=' . urlencode($token));
}

$db = Database::connect();

$tokenRow = $this->findValidResetToken($db, $token);

if (!$tokenRow) {
    $_SESSION['login_error'] = 'Invalid or expired reset link.';
    $this->redirect('/login');
}

$db->beginTransaction();

try {
    $updateUser = $db->prepare("
        UPDATE users
        SET
        password = :password,
        updated_at = NOW()
        WHERE id = :user_id
        AND role = 'customer'
        AND status = 'active'
        ");

        $updateUser->execute([
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':user_id' => (int) $tokenRow->user_id,
                ]);

        $updateToken = $db->prepare("
            UPDATE password_setup_tokens
            SET used_at = NOW()
            WHERE id = :id
            ");

            $updateToken->execute([
                    ':id' => (int) $tokenRow->token_id,
                    ]);

            $db->commit();

            $_SESSION['login_success'] = 'Your password has been reset. You can now login.';
            $this->redirect('/login');
    } catch (\Throwable $e) {
    $db->rollBack();

    $_SESSION['reset_password_error'] = 'Could not reset password. Please try again.';
    $this->redirect('/reset-password?token=' . urlencode($token));
}
}

private function findValidResetToken(PDO $db, string $plainToken): ?object
{
    $tokenHash = hash('sha256', $plainToken);

    $stmt = $db->prepare("
        SELECT
        pst.id AS token_id,
        pst.user_id,
        pst.expires_at,
        u.full_name,
        u.email
        FROM password_setup_tokens pst
        INNER JOIN users u ON u.id = pst.user_id
        WHERE pst.token_hash = :token_hash
        AND pst.purpose = 'reset_password'
        AND pst.used_at IS NULL
        AND pst.expires_at > NOW()
        AND u.role = 'customer'
        AND u.status = 'active'
        LIMIT 1
        ");

        $stmt->execute([
                ':token_hash' => $tokenHash,
                ]);

        $tokenRow = $stmt->fetch();

        return $tokenRow ?: null;
    }

private function getResetTokenDebugReason(PDO $db, string $plainToken): string
{
    $tokenHash = hash('sha256', $plainToken);

    $stmt = $db->prepare("
        SELECT
        pst.id AS token_id,
        pst.user_id,
        pst.token_hash,
        pst.purpose,
        pst.expires_at,
        pst.used_at,
        pst.created_at,
        u.email,
        u.role,
        u.status,
        NOW() AS mysql_now
        FROM password_setup_tokens pst
        LEFT JOIN users u ON u.id = pst.user_id
        WHERE pst.token_hash = :token_hash
        LIMIT 1
        ");

        $stmt->execute([
                ':token_hash' => $tokenHash,
                ]);

        $row = $stmt->fetch();

        if (!$row) {
            return 'Token hash not found in password_setup_tokens.';
        }

    if ($row->purpose !== 'reset_password') {
        return 'Token purpose is ' . $row->purpose . ', expected reset_password.';
    }

if (!empty($row->used_at)) {
    return 'Token was already used at ' . $row->used_at . '.';
}

if (strtotime($row->expires_at) <= strtotime($row->mysql_now)) {
    return 'Token expired. Expires at ' . $row->expires_at . ', MySQL now is ' . $row->mysql_now . '.';
}

if ($row->role !== 'customer') {
    return 'User role is ' . $row->role . ', only customer can reset password.';
}

if ($row->status !== 'active') {
    return 'User status is ' . $row->status . ', expected active.';
}

return 'Unknown validation failure.';
}

private function cleanToken(string $token): string
{
    $token = trim($token);
    $token = html_entity_decode($token, ENT_QUOTES, 'UTF-8');
    $token = urldecode($token);
    $token = preg_replace('/[^a-f0-9]/i', '', $token);

    return $token;
}

private function isDebugMode(): bool
{
    $config = require __DIR__ . '/../../config/config.php';

    return in_array($config['app_env'] ?? 'production', ['local', 'staging'], true);
}
}
