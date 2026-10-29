<?php
require __DIR__ . '/../includes/auth.php';
require_role('admin');
require __DIR__ . '/../includes/db.php';

$pageTitle = 'Audit Logs';
$staffOnly = ($_GET['staff_only'] ?? '') === '1';
$search = trim((string) ($_GET['search'] ?? ''));
$type = (string) ($_GET['type'] ?? '');
$auditTypes = [
    'update' => ['label' => 'Updates only', 'sql' => "(a.action_description LIKE 'Updated %' OR a.action_description LIKE 'Marked %')"],
    'auth' => ['label' => 'Log in/out only', 'sql' => "(a.action_description = 'Logged in' OR a.action_description = 'Logged out')"],
    'add' => ['label' => 'Adds only', 'sql' => "(a.action_description LIKE 'Added %' OR a.action_description LIKE 'Created %' OR a.action_description LIKE 'Recorded %')"],
];
if (!isset($auditTypes[$type])) {
    $type = '';
}
$deleteError = null;
$filterSql = $type === '' ? '1=1' : $auditTypes[$type]['sql'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $selectedLogIds = array_values(array_filter(array_map('intval', (array) ($_POST['log_ids'] ?? []))));
    $deleteAll = ($_POST['delete_all'] ?? '') === '1';
    if ($deleteAll) {
        $delete = $pdo->prepare("DELETE a FROM audit_logs a JOIN users u ON u.user_id = a.user_id
            WHERE {$filterSql} AND (:staff_only = 0 OR u.role = 'staff')
              AND (:search = '' OR u.username LIKE :like_user OR a.action_description LIKE :like_action OR a.before_value LIKE :like_before OR a.after_value LIKE :like_after)");
        $likeSearch = "%{$search}%";
        $delete->execute([
            'staff_only' => $staffOnly ? 1 : 0,
            'search' => $search,
            'like_user' => $likeSearch,
            'like_action' => $likeSearch,
            'like_before' => $likeSearch,
            'like_after' => $likeSearch,
        ]);
        flash('success', $delete->rowCount() . ' audit log(s) deleted.');
        redirect('audit/index.php?staff_only=' . ($staffOnly ? '1' : '0') . '&type=' . urlencode($type) . '&search=' . urlencode($search));
    }
    if (!$selectedLogIds) {
        $deleteError = 'Select at least one audit log to delete.';
    } else {
        $placeholders = implode(',', array_fill(0, count($selectedLogIds), '?'));
        $delete = $pdo->prepare("DELETE FROM audit_logs WHERE log_id IN ({$placeholders})");
        $delete->execute($selectedLogIds);
        flash('success', $delete->rowCount() . ' audit log(s) deleted.');
        redirect('audit/index.php?staff_only=' . ($staffOnly ? '1' : '0') . '&type=' . urlencode($type) . '&search=' . urlencode($search));
    }
}
$statement = $pdo->prepare("SELECT a.log_id, a.user_id, u.username, u.role, a.action_description, a.before_value, a.after_value, a.created_at
    FROM audit_logs a JOIN users u ON u.user_id = a.user_id
    WHERE {$filterSql} AND (:staff_only = 0 OR u.role = 'staff')
      AND (:search = '' OR u.username LIKE :like_user OR a.action_description LIKE :like_action OR a.before_value LIKE :like_before OR a.after_value LIKE :like_after)
    ORDER BY a.created_at DESC, a.log_id DESC LIMIT 100");
$likeSearch = "%{$search}%";
$statement->execute(['staff_only' => $staffOnly ? 1 : 0, 'search' => $search, 'like_user' => $likeSearch, 'like_action' => $likeSearch, 'like_before' => $likeSearch, 'like_after' => $likeSearch]);
$logs = $statement->fetchAll();
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
    echo '<div id="audit-results-fragment">';
    require __DIR__ . '/_results.php';
    echo '</div>';
    exit;
}
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">Administration · Oversight</p><h2>Audit logs</h2><p>Trace who changed what, with before-and-after context for record updates.</p></div><a class="btn btn-outline-secondary <?= $staffOnly ? 'active' : '' ?>" href="?staff_only=<?= $staffOnly ? '0' : '1' ?>&type=<?= urlencode($type) ?>&search=<?= urlencode($search) ?>" data-audit-staff-toggle><i data-lucide="filter" aria-hidden="true"></i><?= $staffOnly ? 'All users' : 'Staff only' ?></a></div>
<?php if ($deleteError): ?><div class="alert alert-danger" role="alert"><?= e($deleteError) ?></div><?php endif; ?>
<form class="filter-bar row g-2 mb-3" method="get" action="index.php" data-audit-filter><input type="hidden" name="staff_only" value="<?= $staffOnly ? '1' : '0' ?>"><div class="col-md-6"><label class="visually-hidden" for="audit-search">Search audit logs</label><div class="input-with-icon"><i data-lucide="search" aria-hidden="true"></i><input id="audit-search" class="form-control" name="search" value="<?= e($search) ?>" placeholder="Search user, action, before, or after value" autocomplete="off"></div></div><div class="col-md-4"><label class="visually-hidden" for="audit-type">Audit action type</label><div class="filter-select compact-form-select" data-filter-select><input type="hidden" id="audit-type" name="type" value="<?= e($type) ?>"><button class="filter-select-toggle" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="audit-type-options" data-filter-select-toggle><?= e($type === '' ? 'All actions' : $auditTypes[$type]['label']) ?><i data-lucide="chevron-down" aria-hidden="true"></i></button><div class="filter-select-menu" id="audit-type-options" role="listbox" hidden><button type="button" role="option" aria-selected="<?= $type === '' ? 'true' : 'false' ?>" data-filter-option="">All actions</button><?php foreach ($auditTypes as $key => $auditType): ?><button type="button" role="option" aria-selected="<?= $type === $key ? 'true' : 'false' ?>" data-filter-option="<?= e($key) ?>"><?= e($auditType['label']) ?></button><?php endforeach; ?></div></div></div></form>
<form method="post" action="index.php?staff_only=<?= $staffOnly ? '1' : '0' ?>&type=<?= urlencode($type) ?>&search=<?= urlencode($search) ?>" data-audit-delete-form>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="audit-toolbar"><label class="select-all-control"><input type="checkbox" data-select-all="audit-log"><span>Select all visible</span></label><button class="btn btn-delete" type="submit" data-confirm-dialog="Delete the selected audit logs?"><i data-lucide="trash-2" aria-hidden="true"></i>Delete selected</button><button class="btn btn-delete" type="submit" name="delete_all" value="1" data-confirm-dialog="Delete every log matching the current filters?"><i data-lucide="trash" aria-hidden="true"></i>Delete all results</button></div>
    <div id="audit-results"><?php require __DIR__ . '/_results.php'; ?></div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
