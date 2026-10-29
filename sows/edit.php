<?php
require __DIR__ . '/../includes/auth.php';
require_login();
require __DIR__ . '/../includes/db.php';

$id = (int) ($_GET['id'] ?? $_POST['sow_record_id'] ?? 0);
$statement = $pdo->prepare('SELECT s.*, p.tag_number FROM sow_records s JOIN pigs p ON p.pig_id = s.pig_id WHERE s.sow_record_id = :id');
$statement->execute(['id' => $id]);
$record = $statement->fetch();
if (!$record) {
    http_response_code(404);
    exit('Record not found.');
}

$pageTitle = 'Edit mating';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $matingDate = trim((string) ($_POST['mating_date'] ?? ''));
    if ($matingDate === '') {
        flash('error', 'Mating date is required.');
        redirect('sows/edit.php?id=' . $id);
    }
    $statement = $pdo->prepare('UPDATE sow_records SET mating_date = :mating_date, expected_farrowing_date = DATE_ADD(:expected_date, INTERVAL 114 DAY) WHERE sow_record_id = :id');
    $statement->execute(['mating_date' => $matingDate, 'expected_date' => $matingDate, 'id' => $id]);
    require __DIR__ . '/../includes/audit.php';
    $expectedDate = (new DateTimeImmutable($matingDate))->modify('+114 days')->format('Y-m-d');
    audit_log($pdo, "Updated sow record #{$id} for {$record['tag_number']}", "Mating: {$record['mating_date']}; Expected: {$record['expected_farrowing_date']}", "Mating: {$matingDate}; Expected: {$expectedDate}");
    flash('success', 'Gestation dates updated.');
    redirect('sows/index.php');
}
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><p class="eyebrow"><a class="back-button" href="index.php" aria-label="Back to sow tracker" title="Back to sow tracker"><i data-lucide="arrow-left" aria-hidden="true"></i></a>Breeding operations</p><h2>Edit <?= e($record['tag_number']) ?></h2><p>Update the mating date or record the outcome of this breeding cycle.</p></div></div>
<div class="card form-card mb-3">
    <h3 class="section-title">Mating details</h3>
    <form method="post" class="form-stack">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="sow_record_id" value="<?= $id ?>">
        <div class="form-field"><label class="form-label">Mating date<input class="form-control" type="date" name="mating_date" value="<?= e($record['mating_date']) ?>" required></label></div>
        <div class="form-actions"><button class="btn btn-danger primary-action" type="submit">Save changes</button></div>
    </form>
</div>
<?php if ($record['status'] === 'pregnant'): ?>
<div class="card form-card">
    <h3 class="section-title">Record breeding outcome</h3>
    <p class="section-note mb-3">Enter the total piglets born before marking this sow as farrowed. Failed cycles do not create piglet records.</p>
    <div class="form-stack">
        <form method="post" action="update-status.php" class="form-actions">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="sow_record_id" value="<?= $id ?>">
            <input type="hidden" name="new_status" value="farrowed">
            <div class="form-field"><label class="form-label" for="piglets-born">Piglets born</label><input class="form-control" id="piglets-born" type="number" name="piglets_born" min="1" step="1" required value=""></div>
            <button class="btn btn-success primary-action" id="farrowed-button" type="submit" disabled>Farrowed</button>
        </form>
        <form method="post" action="update-status.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="sow_record_id" value="<?= $id ?>">
            <input type="hidden" name="new_status" value="failed">
            <input type="hidden" name="piglets_born" value="0">
            <button class="btn btn-danger primary-action" type="submit">Mark failed</button>
        </form>
    </div>
</div>
<?php endif; ?>
<?php if ($record['status'] === 'pregnant'): ?>
<script>
const pigletsInput = document.getElementById('piglets-born');
const farrowedButton = document.getElementById('farrowed-button');
const updateFarrowedState = () => {
    const value = pigletsInput.value.trim();
    const count = Number(value);
    farrowedButton.disabled = value === '' || !Number.isInteger(count) || count < 1;
};
pigletsInput.addEventListener('input', updateFarrowedState);
updateFarrowedState();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
