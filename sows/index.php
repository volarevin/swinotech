<?php
require __DIR__ . '/../includes/auth.php'; require_login(); require __DIR__ . '/../includes/db.php';
$pageTitle = 'Sow Tracker'; $tab = $_GET['tab'] ?? 'all'; $search = trim($_GET['search'] ?? '');
$records = $pdo->query('SELECT s.*, p.tag_number FROM sow_records s JOIN pigs p ON p.pig_id=s.pig_id ORDER BY s.expected_farrowing_date ASC')->fetchAll();
$records = array_values(array_filter($records, function ($row) use ($tab, $search) { return ($tab === 'all' || $row['status'] === $tab) && ($search === '' || stripos($row['tag_number'], $search) !== false); }));
$counts = ['pregnant'=>0,'farrowed'=>0,'failed'=>0]; foreach ($pdo->query('SELECT status, COUNT(*) total FROM sow_records GROUP BY status') as $row) $counts[$row['status']] = (int)$row['total'];
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
    require __DIR__ . '/_results.php';
    exit;
}
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">Breeding operations</p><h2>Sow tracker</h2><p>114-day gestation countdowns with due-soon and overdue warnings.</p></div><a class="btn btn-danger primary-action" href="add.php">+ Record mating</a></div>
<div class="row g-3 mb-3"><?php foreach([['Pregnant','pregnant','heart-pulse'],['Due soon','due','calendar-clock'],['Farrowed','farrowed','badge-check'],['Failed','failed','circle-x'] ] as [$label,$key,$icon]): ?><div class="col-6 col-xl-3"><div class="card stat-card dashboard-stat"><div class="stat-top"><span class="metric-icon metric-<?= $key==='pregnant'?'blue':($key==='due'?'warning':($key==='farrowed'?'success':($key==='failed'?'clay':'brand'))) ?>"><i data-lucide="<?= $icon ?>" aria-hidden="true"></i></span><span class="stat-label"><?= $label ?></span></div><div class="stat-value"><?php if ($key==='due') { $due=(int)$pdo->query("SELECT COUNT(*) FROM sow_records WHERE status='pregnant' AND expected_farrowing_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn(); echo $due; } else echo $counts[$key]; ?></div><div class="stat-note"><?= $key==='due'?'Next 7 days':'Current records' ?></div></div></div><?php endforeach; ?></div>
<form class="filter-bar row g-2 mb-3" method="get" action="index.php" data-ajax-filter data-results="#sow-results"><div class="col-md-5"><input class="form-control" name="search" value="<?= e($search) ?>" placeholder="Search sow tag"></div><div class="col-md-3"><div class="filter-select" data-filter-select><label class="visually-hidden" for="sow-status">Sow status</label><input type="hidden" id="sow-status" name="tab" value="<?= e($tab) ?>"><button class="filter-select-toggle" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="sow-status-options" data-filter-select-toggle><?= e($tab !== 'all' ? ucfirst($tab) : 'All statuses') ?><i data-lucide="chevron-down" aria-hidden="true"></i></button><div class="filter-select-menu" id="sow-status-options" role="listbox" hidden><?php foreach(['all','pregnant','farrowed','failed'] as $item): ?><button type="button" role="option" aria-selected="<?= $tab === $item ? 'true' : 'false' ?>" data-filter-option="<?= e($item) ?>"><?= e($item !== 'all' ? ucfirst($item) : 'All statuses') ?></button><?php endforeach; ?></div></div></div></form>
<?php require __DIR__ . '/_results.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
