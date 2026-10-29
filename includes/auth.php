<?php
declare(strict_types=1);

const BASE_URL = '/SwinoTech';
const VALID_ROLES = ['admin', 'staff'];

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function sale_reference(int $saleId): string
{
    $sequence = $saleId >= 1031 ? $saleId - 1030 : $saleId;
    return str_pad((string) max(1, $sequence), 3, '0', STR_PAD_LEFT);
}

function redirect(string $path): never
{
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

function require_login(): void
{
    if (!is_authenticated()) {
        $_SESSION = [];
        redirect('login.php');
    }
}

function is_authenticated(): bool
{
    return !empty($_SESSION['user_id'])
        && in_array((string) ($_SESSION['role'] ?? ''), VALID_ROLES, true);
}

function require_role(string $role): void
{
    require_login();
    if (!in_array($role, VALID_ROLES, true) || ($_SESSION['role'] ?? '') !== $role) {
        flash('error', 'You do not have permission to access that page.');
        redirect('dashboard.php');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(419);
        exit('Invalid form token.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}
