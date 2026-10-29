<?php
require __DIR__ . '/../includes/auth.php'; require_login(); require __DIR__ . '/../includes/db.php';
$pageTitle = 'Pig Inventory';
$role = $_SESSION['role'] ?? 'staff';
$search = trim($_GET['search'] ?? ''); $category = $_GET['category'] ?? ''; $status = $_GET['status'] ?? 'all';
$statuses = ['all', 'active', 'dead', 'sold'];
if (!in_array($status, $statuses, true)) $status = 'all';
$sql = 'SELECT * FROM pigs WHERE 1=1'; $params = [];
if ($status !== 'all') {
    $sql .= ' AND status = :status'; $params['status'] = $status;
}
if ($search !== '') {
    $sql .= ' AND (tag_number LIKE :tag_search OR pen_number LIKE :pen_search)';
    $params['tag_search'] = "%{$search}%";
    $params['pen_search'] = "%{$search}%";
}
if (in_array($category, ['boar','fattener','sow'], true)) { $sql .= ' AND category = :category'; $params['category'] = $category; }
$sql .= ' ORDER BY date_added DESC, pig_id DESC'; $statement = $pdo->prepare($sql); $statement->execute($params); $pigs = $statement->fetchAll();
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
    require __DIR__ . '/_results.php';
    exit;
}
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">Herd register</p><h2>Pig inventory</h2><p>All pigs are shown by default; use the status filter to narrow the inventory.</p></div><a class="btn btn-danger primary-action" href="add.php">+ Add pig</a></div>
<form class="filter-bar row g-2 mb-3" method="get" action="index.php" data-pig-filter>
    <div class="col-md-5"><label class="visually-hidden" for="pig-search">Search pigs</label><input id="pig-search" class="form-control" name="search" value="<?= e($search) ?>" placeholder="Search tag number or pen" autocomplete="off"></div>
    <div class="col-md-3"><div class="filter-select" data-filter-select><label class="visually-hidden" for="pig-category">Pig category</label><input type="hidden" id="pig-category" name="category" value="<?= e($category) ?>"><button class="filter-select-toggle" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="pig-category-options" data-filter-select-toggle><?= e($category !== '' ? ucfirst($category) : 'All categories') ?><i data-lucide="chevron-down" aria-hidden="true"></i></button><div class="filter-select-menu" id="pig-category-options" role="listbox" hidden><?php foreach(['','boar','fattener','sow'] as $item): ?><button type="button" role="option" aria-selected="<?= $category === $item ? 'true' : 'false' ?>" data-filter-option="<?= e($item) ?>"><?= e($item !== '' ? ucfirst($item) : 'All categories') ?></button><?php endforeach; ?></div></div></div>
    <div class="col-md-2"><div class="filter-select" data-filter-select><label class="visually-hidden" for="pig-status">Pig status</label><input type="hidden" id="pig-status" name="status" value="<?= e($status) ?>"><button class="filter-select-toggle" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="pig-status-options" data-filter-select-toggle><?= e($status === 'all' ? 'Show all' : ucfirst($status)) ?><i data-lucide="chevron-down" aria-hidden="true"></i></button><div class="filter-select-menu" id="pig-status-options" role="listbox" hidden><?php foreach ($statuses as $item): ?><button type="button" role="option" aria-selected="<?= $status === $item ? 'true' : 'false' ?>" data-filter-option="<?= e($item) ?>"><?= e($item === 'all' ? 'Show all' : ucfirst($item)) ?></button><?php endforeach; ?></div></div></div>
    <div class="col-12"><span class="filter-status text-muted small" data-pig-filter-status role="status" aria-live="polite"></span></div>
</form>
<?php require __DIR__ . '/_results.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
