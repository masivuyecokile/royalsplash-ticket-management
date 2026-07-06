<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class PasswordSetupController extends Controller
{
    public function show(): void
    {
        $token = trim($_GET['token'] ?? '');

        if ($token === '') {
            $_SESSION['password_setup_error'] = 'Invalid password setup link.';
            $this->redirect('/login');
        }

    $tokenRecord = $this->findValidToken($token);

    if (!$tokenRecord) {
        $_SESSION['password_setup_error'] = 'This password setup link is invalid, expired, or already used.';
        $this->redirect('/login');
    }

$this->view('auth/set-password', [
        'pageTitle' => 'Set Password',
        'token' => $token,
        'user' => $tokenRecord,
        ]);
}

public function update(): void
{
    $token = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($token === '') {
        $_SESSION['password_setup_error'] = 'Invalid password setup request.';
        $this->redirect('/login');
    }

$tokenRecord = $this->findValidToken($token);

if (!$tokenRecord) {
    $_SESSION['password_setup_error'] = 'This password setup link is invalid, expired, or already used.';
    $this->redirect('/login');
}

if (strlen($password) < 8) {
    $_SESSION['password_setup_error'] = 'Password must be at least 8 characters.';
    $this->redirect('/set-password?token=' . urlencode($token));
}

if ($password !== $confirmPassword) {
    $_SESSION['password_setup_error'] = 'Passwords do not match.';
    $this->redirect('/set-password?token=' . urlencode($token));
}

$db = Database::connect();

try {
    $db->beginTransaction();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $updateUser = $db->prepare("
        UPDATE users
        SET password = :password
        WHERE id = :id
        ");

        $updateUser->execute([
                ':password' => $passwordHash,
                ':id' => (int) $tokenRecord->user_id,
                ]);

        $markUsed = $db->prepare("
            UPDATE password_setup_tokens
            SET used_at = NOW()
            WHERE id = :id
            ");

            $markUsed->execute([
                    ':id' => (int) $tokenRecord->token_id,
                    ]);

            $db->commit();

            $_SESSION['password_setup_success'] = 'Your password has been created. You can now login.';
            $this->redirect('/login');
    } catch (\Throwable $e) {
    $db->rollBack();

    $_SESSION['password_setup_error'] = 'Could not set password. Please try again.';
    $this->redirect('/set-password?token=' . urlencode($token));
}
}

private function findValidToken(string $plainToken): ?object
{
    $tokenHash = hash('sha256', $plainToken);
    $db = Database::connect();

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
        AND pst.purpose = 'set_password'
        AND pst.used_at IS NULL
        AND pst.expires_at > NOW()
        AND u.status = 'active'
        LIMIT 1
        ");

        $stmt->execute([
                ':token_hash' => $tokenHash,
                ]);

        $record = $stmt->fetch();

        return $record ?: null;
    }
}
