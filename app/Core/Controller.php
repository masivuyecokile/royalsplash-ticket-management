<?php

namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);

        require __DIR__ . '/../Views/layouts/header.php';
        require __DIR__ . '/../Views/' . $view . '.php';
        require __DIR__ . '/../Views/layouts/footer.php';
    }

protected function redirect(string $path): void
{
    $config = require __DIR__ . '/../../config/config.php';

    header('Location: ' . rtrim($config['app_url'], '/') . $path);
    exit;
}

protected function authUser(): ?object
{
    return $_SESSION['user'] ?? null;
}

protected function requireAuth(): void
{
    if (!$this->authUser()) {
        $this->redirect('/login');
    }
}

protected function requireRole(string $role): void
{
    $this->requireAuth();

    if (($this->authUser()->role ?? '') !== $role) {
        $this->redirect('/dashboard');
    }
}

protected function requireAnyRole(array $roles): void
{
    $this->requireAuth();

    $userRole = $this->authUser()->role ?? '';

    if (!in_array($userRole, $roles, true)) {
        $this->redirect('/dashboard');
    }
}
}
