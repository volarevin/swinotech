<?php
require __DIR__ . '/../includes/auth.php';
require_role('admin');
require __DIR__ . '/../includes/db.php';

$pageTitle = 'Users';
$search = trim((string) ($_GET['search'] ?? ''));
$statement = $pdo->prepare('SELECT user_id, username, role, created_at FROM users WHERE (:search = "" OR username LIKE :like_username OR role LIKE :like_role) ORDER BY user_id DESC');
$statement->execute([
    'search' => $search,
    'like_username' => "%{$search}%",
    'like_role' => "%{$search}%",
]);
$users = $statement->fetchAll();

if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
    require __DIR__ . '/_results.php';
    exit;
}

require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">Administration · Access</p><h2>User management</h2><p>Manage staff access without exposing administrator credentials.</p></div><a class="btn btn-danger primary-action" href="add.php"><i data-lucide="user-plus" aria-hidden="true"></i> Add staff</a></div>
<div class="module-intro-card"><span class="module-intro-icon"><i data-lucide="users-round" aria-hidden="true"></i></span><div><strong><?= count($users) ?> accounts</strong><span>Staff accounts are created here. Update your own credentials in Settings.</span></div></div>
<form class="filter-bar row g-2 mb-3" method="get" action="index.php" data-ajax-filter data-results="#users-results"><div class="col-md-8"><label class="visually-hidden" for="user-search">Search users</label><div class="input-with-icon"><i data-lucide="search" aria-hidden="true"></i><input id="user-search" class="form-control" name="search" value="<?= e($search) ?>" placeholder="Search by username or role" autocomplete="off"></div></div></form>
<?php require __DIR__ . '/_results.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
