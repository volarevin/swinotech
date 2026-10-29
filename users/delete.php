<?php
require __DIR__ . '/../includes/auth.php';
require_role('admin');
require __DIR__ . '/../includes/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
verify_csrf();
$id = (int) ($_POST['user_id'] ?? 0);
$statement = $pdo->prepare('SELECT username, role FROM users WHERE user_id = :id');
$statement->execute(['id' => $id]);
$user = $statement->fetch();
if (!$user) {
    flash('error', 'User not found.');
    redirect('users/index.php');
}
if ($id === (int) $_SESSION['user_id']) {
    flash('error', 'You cannot delete your own account.');
    redirect('users/index.php');
}
if ($user['role'] === 'admin' && (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() <= 1) {
    flash('error', 'The last remaining admin cannot be deleted.');
    redirect('users/index.php');
}
$delete = $pdo->prepare('DELETE FROM users WHERE user_id = :id');
$delete->execute(['id' => $id]);
require __DIR__ . '/../includes/audit.php';
audit_log($pdo, "Deleted user {$user['username']}", "Username: {$user['username']}; Role: {$user['role']}", null);
flash('success', 'User deleted successfully.');
redirect('users/index.php');
