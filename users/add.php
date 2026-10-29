<?php
require __DIR__ . '/../includes/auth.php';
require_role('admin');
require __DIR__ . '/../includes/db.php';

$pageTitle = 'Add user';
$error = null;
$username = trim((string) ($_POST['username'] ?? ''));
$role = 'staff';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    try {
        if ($username === '' || !preg_match('/^[A-Za-z0-9._-]{3,100}$/', $username)) {
            throw new InvalidArgumentException('Username must be 3-100 letters, numbers, dots, underscores, or hyphens.');
        }
        if ($password === '' || $password !== $confirmPassword) {
            throw new InvalidArgumentException('Passwords are required and must match.');
        }
        $check = $pdo->prepare('SELECT 1 FROM users WHERE username = :username');
        $check->execute(['username' => $username]);
        if ($check->fetchColumn()) {
            throw new InvalidArgumentException('That username is already in use.');
        }
        $statement = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (:username, :password, :role)');
        $statement->execute([
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
        ]);
        require __DIR__ . '/../includes/audit.php';
        audit_log($pdo, "Created user {$username} ({$role})", null, "Username: {$username}; Role: Staff");
        flash('success', 'User created successfully.');
        redirect('users/index.php');
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } catch (PDOException) {
        $error = 'Unable to create the user.';
    }
}
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><p class="eyebrow"><a class="back-button" href="index.php" aria-label="Back to users"><i data-lucide="arrow-left" aria-hidden="true"></i></a>Administration</p><h2>Add user</h2></div></div>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
<div class="card"><form method="post" class="row g-3">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="col-md-6"><label class="form-label">Username<input class="form-control" name="username" value="<?= e($username) ?>" required autocomplete="username"></label></div>
    <div class="col-md-6"><label class="form-label">Password<input class="form-control" type="password" name="password" required autocomplete="new-password"></label></div>
    <div class="col-md-6"><label class="form-label">Confirm password<input class="form-control" type="password" name="confirm_password" required autocomplete="new-password"></label></div>
    <div class="col-12"><button class="btn btn-danger primary-action" type="submit">Create user</button></div>
</form></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
