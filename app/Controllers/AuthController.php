<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDOException;

class AuthController extends Controller
{
    public function showRegister(): void
    {
        $this->view('auth/register', [
                'pageTitle' => 'Create Account',
                'error' => null,
                ]);
    }

public function register(): void
{
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $email === '' || $password === '') {
        $this->view('auth/register', [
                'pageTitle' => 'Create Account',
                'error' => 'Please complete all required fields.',
                ]);
        return;
    }

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $this->view('auth/register', [
            'pageTitle' => 'Create Account',
            'error' => 'Please enter a valid email address.',
            ]);
    return;
}

if (strlen($password) < 6) {
    $this->view('auth/register', [
            'pageTitle' => 'Create Account',
            'error' => 'Password must be at least 6 characters.',
            ]);
    return;
}

if ($password !== $confirmPassword) {
    $this->view('auth/register', [
            'pageTitle' => 'Create Account',
            'error' => 'Passwords do not match.',
            ]);
    return;
}

$db = Database::connect();

try {
    $stmt = $db->prepare("
        INSERT INTO users (full_name, email, phone, password, role, status)
        VALUES (:full_name, :email, :phone, :password, 'customer', 'active')
        ");

        $stmt->execute([
                ':full_name' => $fullName,
                ':email' => $email,
                ':phone' => $phone,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ]);
} catch (PDOException $e) {
$this->view('auth/register', [
        'pageTitle' => 'Create Account',
        'error' => 'This email address is already registered.',
        ]);
return;
}

$this->redirect('/login');
}

public function showLogin(): void
{
    $this->view('auth/login', [
            'pageTitle' => 'Login',
            'error' => null,
            ]);
}

public function login(): void
{
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $this->view('auth/login', [
                'pageTitle' => 'Login',
                'error' => 'Please enter your email and password.',
                ]);
        return;
    }

$db = Database::connect();

$stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);

$user = $stmt->fetch();

if (!$user || !password_verify($password, $user->password)) {
    $this->view('auth/login', [
            'pageTitle' => 'Login',
            'error' => 'Invalid login details.',
            ]);
    return;
}

if ($user->status !== 'active') {
    $this->view('auth/login', [
            'pageTitle' => 'Login',
            'error' => 'Your account has been disabled.',
            ]);
    return;
}

$_SESSION['user'] = (object) [
    'id' => $user->id,
    'full_name' => $user->full_name,
    'email' => $user->email,
    'phone' => $user->phone,
    'role' => $user->role,
    ];

if ($user->role === 'admin') {
    $this->redirect('/admin');
}

if ($user->role === 'scanner') {
    $this->redirect('/scanner');
}

$this->redirect('/my-tickets');
}

public function logout(): void
{
    unset($_SESSION['user']);

    session_regenerate_id(true);

    $this->redirect('/login');
}
}
