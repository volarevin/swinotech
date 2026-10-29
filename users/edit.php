<?php
require __DIR__ . '/../includes/auth.php';
require_role('admin');
require __DIR__ . '/../includes/db.php';

$id = (int) ($_GET['id'] ?? $_POST['user_id'] ?? 0);
$statement = $pdo->prepare('SELECT user_id, username, role FROM users WHERE user_id = :id');
$statement->execute(['id' => $id]);
$user = $statement->fetch();
if (!$user) {
    http_response_code(404);
    exit('User not found.');
}
if ($id === (int) $_SESSION['user_id']) {
    flash('error', 'Use Settings to update your own credentials.');
    redirect('users/index.php');
}

$pageTitle = 'Edit user';
$error = null;
$username = trim((string) ($_POST['username'] ?? $user['username']));
$role = $_POST['role'] ?? $user['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = (string) ($_POST['password'] ?? '');
    try {
        if ($username === '' || !preg_match('/^[A-Za-z0-9._-]{3,100}$/', $username)) {
            throw new InvalidArgumentException('Username must be 3-100 letters, numbers, dots, underscores, or hyphens.');
        }
        if (!in_array($role, VALID_ROLES, true)) {
            throw new InvalidArgumentException('Choose a valid role.');
        }
        if ($user['role'] === 'admin' && $role === 'staff') {
            $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
            if ($adminCount <= 1) {
                throw new InvalidArgumentException('The last remaining admin cannot be demoted.');
            }
        }
        $check = $pdo->prepare('SELECT 1 FROM users WHERE username = :username AND user_id <> :id');
        $check->execute(['username' => $username, 'id' => $id]);
        if ($check->fetchColumn()) {
            throw new InvalidArgumentException('That username is already in use.');
        }
        $params = ['username' => $username, 'role' => $role, 'id' => $id];
        $sql = 'UPDATE users SET username = :username, role = :role';
        if ($password !== '') {
            $sql .= ', password = :password';
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE user_id = :id';
        $pdo->prepare($sql)->execute($params);
        require __DIR__ . '/../includes/audit.php';
        audit_log($pdo, "Updated user {$username}", "Username: {$user['username']}; Role: {$user['role']}", "Username: {$username}; Role: {$role}");
        flash('success', 'User updated successfully.');
        redirect('users/index.php');
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } catch (PDOException) {
        $error = 'Unable to update the user.';
    }
}
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><p class="eyebrow"><a class="back-button" href="index.php" aria-label="Back to users"><i data-lucide="arrow-left" aria-hidden="true"></i></a>Administration</p><h2>Edit user</h2></div></div>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
<div class="card"><form method="post" class="row g-3">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="user_id" value="<?= $id ?>">
    <div class="col-md-6"><label class="form-label">Username<input class="form-control" name="username" value="<?= e($username) ?>" required autocomplete="username"></label></div>
    <div class="col-md-6"><label class="form-label">Role<select class="form-select" name="role"><option value="staff" <?= $role === 'staff' ? 'selected' : '' ?>>Staff</option><option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option></select></label></div>
    <div class="col-md-6"><label class="form-label">New password <span class="text-muted fw-normal">(optional)</span><input class="form-control" type="password" name="password" placeholder="Leave blank to keep current" autocomplete="new-password"></label></div>
    <div class="col-12"><button class="btn btn-danger primary-action" type="submit">Save changes</button></div>
</form></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
