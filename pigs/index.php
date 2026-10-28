<?php
require __DIR__ . '/../includes/auth.php'; require_login(); require __DIR__ . '/../includes/db.php';
$pageTitle = 'Pig Inventory';
$role = $_SESSION['role'] ?? 'staff';
$search = trim($_GET['search'] ?? ''); $category = $_GET['category'] ?? ''; $showAll = isset($_GET['all']);
$sql = 'SELECT * FROM pigs WHERE 1=1'; $params = [];
if (!$showAll) $sql .= " AND status = 'active'";
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
<div class="page-heading"><div><p class="eyebrow">Herd register</p><h2>Pig inventory</h2><p>Active pigs are shown by default; sold and dead records remain available for audit.</p></div><a class="btn btn-danger primary-action" href="add.php">+ Add pig</a></div>
<form class="filter-bar row g-2 mb-3" method="get" action="index.php" data-pig-filter>
    <div class="col-md-5"><label class="visually-hidden" for="pig-search">Search pigs</label><input id="pig-search" class="form-control" name="search" value="<?= e($search) ?>" placeholder="Search tag number or pen" autocomplete="off"></div>
    <div class="col-md-3"><label class="visually-hidden" for="pig-category">Pig category</label><select id="pig-category" class="form-select" name="category"><option value="">All categories</option><?php foreach(['boar','fattener','sow'] as $item): ?><option value="<?= e($item) ?>" <?= $category === $item ? 'selected' : '' ?>><?= e(ucfirst($item)) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2 d-grid"><button class="btn btn-outline-secondary" type="submit"><i data-lucide="filter" aria-hidden="true"></i> Apply</button></div>
    <div class="col-md-2 d-grid"><a class="btn btn-light" data-pig-status-toggle href="?<?= $showAll ? '' : 'all=1' ?>"><?= $showAll ? 'Active only' : 'Show all' ?></a></div>
    <div class="col-12"><span class="filter-status text-muted small" data-pig-filter-status role="status" aria-live="polite"></span></div>
</form>
<?php require __DIR__ . '/_results.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
