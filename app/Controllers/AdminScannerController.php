<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\UserInviteService;
use PDO;

class AdminScannerController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin');

        $db = Database::connect();

        $scanners = $this->getScanners($db);

        $this->view('admin/scanners/index', [
                'pageTitle' => 'Scanner Users',
                'scanners' => $scanners,
                ]);
    }

public function store(): void
{
    $this->requireRole('admin');

    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($fullName === '' || $email === '') {
        $_SESSION['admin_error'] = 'Scanner name and email are required.';
        $this->redirect('/admin/scanners');
    }

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['admin_error'] = 'Please enter a valid email address.';
    $this->redirect('/admin/scanners');
}

if (!in_array($status, ['active', 'disabled'], true)) {
    $status = 'active';
}

if ($password === '') {
    $_SESSION['admin_error'] = 'Please enter a password for the scanner user.';
    $this->redirect('/admin/scanners');
}

if (strlen($password) < 8) {
    $_SESSION['admin_error'] = 'Scanner password must be at least 8 characters.';
    $this->redirect('/admin/scanners');
}

if ($password !== $confirmPassword) {
    $_SESSION['admin_error'] = 'Scanner passwords do not match.';
    $this->redirect('/admin/scanners');
}

$db = Database::connect();

$existingStmt = $db->prepare("
    SELECT id
    FROM users
    WHERE email = :email
    LIMIT 1
    ");

    $existingStmt->execute([
            ':email' => $email,
            ]);

    if ($existingStmt->fetch()) {
        $_SESSION['admin_error'] = 'A user with this email already exists.';
        $this->redirect('/admin/scanners');
    }

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare("
    INSERT INTO users (
        full_name,
        email,
        phone,
        password,
        role,
        status
    ) VALUES (
    :full_name,
    :email,
    :phone,
    :password,
    'scanner',
    :status
)
");

$stmt->execute([
        ':full_name' => $fullName,
        ':email' => $email,
        ':phone' => $phone !== '' ? $phone : null,
        ':password' => $passwordHash,
        ':status' => $status,
        ]);

$_SESSION['admin_success'] = 'Scanner user created successfully. They can now login using the password you set.';
$this->redirect('/admin/scanners');
}

public function edit(): void
{
    $this->requireRole('admin');

    $scannerId = (int) ($_GET['id'] ?? 0);

    if ($scannerId <= 0) {
        $this->redirect('/admin/scanners');
    }

$db = Database::connect();

$scanner = $this->findScanner($db, $scannerId);

if (!$scanner) {
    $_SESSION['admin_error'] = 'Scanner user not found.';
    $this->redirect('/admin/scanners');
}

$this->view('admin/scanners/edit', [
        'pageTitle' => 'Edit Scanner',
        'scanner' => $scanner,
        ]);
}

public function update(): void
{
    $this->requireRole('admin');

    $scannerId = (int) ($_POST['scanner_id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($scannerId <= 0) {
        $this->redirect('/admin/scanners');
    }

if ($fullName === '' || $email === '') {
    $_SESSION['admin_error'] = 'Scanner name and email are required.';
    $this->redirect('/admin/scanners/edit?id=' . $scannerId);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['admin_error'] = 'Please enter a valid email address.';
    $this->redirect('/admin/scanners/edit?id=' . $scannerId);
}

if (!in_array($status, ['active', 'disabled'], true)) {
    $status = 'active';
}

if ($password !== '' && strlen($password) < 8) {
    $_SESSION['admin_error'] = 'New password must be at least 8 characters.';
    $this->redirect('/admin/scanners/edit?id=' . $scannerId);
}

if ($password !== '' && $password !== $confirmPassword) {
    $_SESSION['admin_error'] = 'New passwords do not match.';
    $this->redirect('/admin/scanners/edit?id=' . $scannerId);
}

$db = Database::connect();

$scanner = $this->findScanner($db, $scannerId);

if (!$scanner) {
    $_SESSION['admin_error'] = 'Scanner user not found.';
    $this->redirect('/admin/scanners');
}

$duplicateStmt = $db->prepare("
    SELECT id
    FROM users
    WHERE email = :email
    AND id != :id
    LIMIT 1
    ");

    $duplicateStmt->execute([
            ':email' => $email,
            ':id' => $scannerId,
            ]);

    if ($duplicateStmt->fetch()) {
        $_SESSION['admin_error'] = 'Another user already uses this email address.';
        $this->redirect('/admin/scanners/edit?id=' . $scannerId);
    }

if ($password !== '') {
    $stmt = $db->prepare("
        UPDATE users
        SET
        full_name = :full_name,
        email = :email,
        phone = :phone,
        status = :status,
        password = :password,
        updated_at = NOW()
        WHERE id = :id
        AND role = 'scanner'
        ");

        $stmt->execute([
                ':full_name' => $fullName,
                ':email' => $email,
                ':phone' => $phone !== '' ? $phone : null,
                ':status' => $status,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':id' => $scannerId,
                ]);

        $_SESSION['admin_success'] = 'Scanner user updated and password changed successfully.';
    } else {
    $stmt = $db->prepare("
        UPDATE users
        SET
        full_name = :full_name,
        email = :email,
        phone = :phone,
        status = :status,
        updated_at = NOW()
        WHERE id = :id
        AND role = 'scanner'
        ");

        $stmt->execute([
                ':full_name' => $fullName,
                ':email' => $email,
                ':phone' => $phone !== '' ? $phone : null,
                ':status' => $status,
                ':id' => $scannerId,
                ]);

        $_SESSION['admin_success'] = 'Scanner user updated successfully.';
    }

$this->redirect('/admin/scanners/edit?id=' . $scannerId);
}

public function updateStatus(): void
{
    $this->requireRole('admin');

    $scannerId = (int) ($_POST['scanner_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    if ($scannerId <= 0 || !in_array($status, ['active', 'disabled'], true)) {
        $_SESSION['admin_error'] = 'Invalid scanner status request.';
        $this->redirect('/admin/scanners');
    }

$db = Database::connect();

$scanner = $this->findScanner($db, $scannerId);

if (!$scanner) {
    $_SESSION['admin_error'] = 'Scanner user not found.';
    $this->redirect('/admin/scanners');
}

$stmt = $db->prepare("
    UPDATE users
    SET
    status = :status,
    updated_at = NOW()
    WHERE id = :id
    AND role = 'scanner'
    ");

    $stmt->execute([
            ':status' => $status,
            ':id' => $scannerId,
            ]);

    $_SESSION['admin_success'] = 'Scanner status updated successfully.';
    $this->redirect('/admin/scanners');
}

public function resendPasswordSetup(): void
{
    $this->requireRole('admin');

    $scannerId = (int) ($_POST['scanner_id'] ?? 0);

    if ($scannerId <= 0) {
        $_SESSION['admin_error'] = 'Invalid scanner user.';
        $this->redirect('/admin/scanners');
    }

$db = Database::connect();

$scanner = $this->findScanner($db, $scannerId);

if (!$scanner) {
    $_SESSION['admin_error'] = 'Scanner user not found.';
    $this->redirect('/admin/scanners');
}

if ($scanner->status !== 'active') {
    $_SESSION['admin_error'] = 'Only active scanner users can receive password setup emails.';
    $this->redirect('/admin/scanners/edit?id=' . $scannerId);
}

try {
    UserInviteService::sendPasswordSetupEmail($db, $scannerId);
    $_SESSION['admin_success'] = 'Password setup email sent successfully.';
} catch (\Throwable $e) {
$_SESSION['admin_error'] = 'Could not send password setup email: ' . $e->getMessage();
}

$this->redirect('/admin/scanners/edit?id=' . $scannerId);
}

private function getScanners(PDO $db): array
{
    $stmt = $db->query("
        SELECT
        u.*,
        (
            SELECT COUNT(*)
            FROM tickets t
            WHERE t.scanned_by = u.id
        ) AS scan_count,
    (
        SELECT MAX(t.scanned_at)
        FROM tickets t
        WHERE t.scanned_by = u.id
    ) AS last_scan_at
FROM users u
WHERE u.role = 'scanner'
ORDER BY u.id DESC
");

return $stmt->fetchAll();
}

private function findScanner(PDO $db, int $scannerId): ?object
{
    $stmt = $db->prepare("
        SELECT
        u.*,
        (
            SELECT COUNT(*)
            FROM tickets t
            WHERE t.scanned_by = u.id
        ) AS scan_count,
    (
        SELECT MAX(t.scanned_at)
        FROM tickets t
        WHERE t.scanned_by = u.id
    ) AS last_scan_at
FROM users u
WHERE u.id = :id
AND u.role = 'scanner'
LIMIT 1
");

$stmt->execute([
        ':id' => $scannerId,
        ]);

$scanner = $stmt->fetch();

return $scanner ?: null;
}
}
