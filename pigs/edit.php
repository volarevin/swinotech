<?php
require __DIR__ . '/../includes/auth.php';
require_login();
require __DIR__ . '/../includes/db.php';

$id = (int) ($_GET['id'] ?? $_POST['pig_id'] ?? 0);
$statement = $pdo->prepare('SELECT * FROM pigs WHERE pig_id = :id');
$statement->execute(['id' => $id]);
$pig = $statement->fetch();
if (!$pig) {
    http_response_code(404);
    exit('Pig not found.');
}

$pageTitle = 'Edit Pig';
$error = null;
$categories = ['boar' => 'B', 'fattener' => 'F', 'sow' => 'S'];
$category = $_POST['category'] ?? $pig['category'];
$status = $_POST['status'] ?? $pig['status'];
$tagNumber = $_POST['tag_number'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && preg_match('/-(\d+)$/', (string) $pig['tag_number'], $match)) {
    $tagNumber = $match[1];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        if (!isset($categories[$category])) {
            throw new InvalidArgumentException('Choose a valid pig category.');
        }
        $tagNumber = trim((string) ($_POST['tag_number'] ?? ''));
        if (!ctype_digit($tagNumber) || (int) $tagNumber < 1 || (int) $tagNumber > 999999) {
            throw new InvalidArgumentException('Tag number must be a whole number between 1 and 999999.');
        }
        $penInput = trim((string) ($_POST['pen_number'] ?? ''));
        if ($penInput === '') {
            throw new InvalidArgumentException('Pen number is required.');
        }
        $penDigits = preg_replace('/^pen[\s-]*/i', '', $penInput);
        if ($penInput !== '' && !preg_match('/^\d+$/', (string) $penDigits)) {
            throw new InvalidArgumentException('Pen number must contain digits only, such as 1 or 12.');
        }
        $tag = $categories[$category] . '-' . str_pad((string) (int) $tagNumber, 3, '0', STR_PAD_LEFT);
        $statement = $pdo->prepare('UPDATE pigs SET tag_number = :tag, category = :category, weight = :weight, pen_number = :pen, status = :status WHERE pig_id = :id');
        if (!in_array($status, ['active', 'sold', 'dead'], true)) {
            throw new InvalidArgumentException('Choose a valid pig status.');
        }
        $statement->execute([
            'tag' => $tag,
            'category' => $category,
            'weight' => (float) $_POST['weight'],
            'pen' => $penInput === '' ? null : 'Pen ' . (int) $penDigits,
            'status' => $status,
            'id' => $id,
        ]);
        require __DIR__ . '/../includes/audit.php';
        audit_log($pdo, "Updated pig {$tag}", "Tag: {$pig['tag_number']}; Category: {$pig['category']}; Weight: {$pig['weight']} kg; Pen: {$pig['pen_number']}; Status: {$pig['status']}", "Tag: {$tag}; Category: {$category}; Weight: {$_POST['weight']} kg; Pen: Pen " . (int) $penDigits . "; Status: {$status}");
        flash('success', 'Pig updated as ' . $tag . '.');
        redirect('pigs/index.php');
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
        $pig = array_merge($pig, $_POST);
    } catch (PDOException $exception) {
        $error = $exception->getCode() === '23000' ? 'That tag number is already in use.' : 'Unable to update the pig.';
        $pig = array_merge($pig, $_POST);
    }
}
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><p class="eyebrow"><a class="back-button" href="index.php" aria-label="Back to pig inventory" title="Back to pig inventory"><i data-lucide="arrow-left" aria-hidden="true"></i></a>Herd register</p><h2>Edit <?= e($pig['tag_number']) ?></h2><p>The tag prefix follows the selected category automatically.</p></div></div>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="card"><form method="post" class="row g-3">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="pig_id" value="<?= $id ?>">
    <div class="col-md-6"><label class="form-label">Category</label><div class="filter-select compact-form-select" data-filter-select><input type="hidden" name="category" id="pig-category" value="<?= e($category) ?>"><button class="filter-select-toggle" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="edit-pig-category-options" data-filter-select-toggle><?= e(ucfirst($category)) ?><i data-lucide="chevron-down" aria-hidden="true"></i></button><div class="filter-select-menu" id="edit-pig-category-options" role="listbox" hidden><?php foreach ($categories as $value => $prefix): ?><button type="button" role="option" aria-selected="<?= $category === $value ? 'true' : 'false' ?>" data-filter-option="<?= e($value) ?>"><?= e(ucfirst($value)) ?> (<?= $prefix ?>)</button><?php endforeach; ?></div></div></div>
    <div class="col-md-6"><label class="form-label">Tag number<div class="input-group"><span class="input-group-text" id="tag-prefix"><?= e($categories[$category] ?? 'B') ?>-</span><input class="form-control" name="tag_number" id="tag-number" inputmode="numeric" pattern="[0-9]*" maxlength="6" value="<?= e($tagNumber) ?>" required></div><div class="form-text">The category prefix and numeric suffix stay synchronized.</div></label></div>
    <div class="col-md-6"><label class="form-label">Weight (kg)<input class="form-control" type="number" step=".01" min="0" name="weight" required value="<?= e($pig['weight']) ?>"></label></div>
    <div class="col-md-6"><label class="form-label">Pen number <span class="text-danger">*</span><div class="input-group"><span class="input-group-text">Pen</span><input class="form-control" name="pen_number" id="pen-number" inputmode="numeric" pattern="[0-9]+" placeholder="1" value="<?= e(preg_replace('/^pen[\s-]*/i', '', (string) ($pig['pen_number'] ?? ''))) ?>" required></div><div class="form-text">Enter only the number, for example <strong>1</strong>.</div></label></div>
    <div class="col-md-6"><label class="form-label">Status</label><div class="filter-select compact-form-select" data-filter-select><input type="hidden" name="status" value="<?= e($status) ?>"><button class="filter-select-toggle" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="edit-pig-status-options" data-filter-select-toggle><?= e(ucfirst($status)) ?><i data-lucide="chevron-down" aria-hidden="true"></i></button><div class="filter-select-menu" id="edit-pig-status-options" role="listbox" hidden><?php foreach (['active', 'sold', 'dead'] as $item): ?><button type="button" role="option" aria-selected="<?= $status === $item ? 'true' : 'false' ?>" data-filter-option="<?= $item ?>"><?= ucfirst($item) ?></button><?php endforeach; ?></div></div></div>
    <div class="col-12"><button class="btn btn-danger primary-action" type="submit">Save changes</button></div>
</form></div>
<script>
const categoryInput = document.getElementById('pig-category');
const tagPrefix = document.getElementById('tag-prefix');
const tagNumber = document.getElementById('tag-number');
const prefixes = { boar: 'B-', fattener: 'F-', sow: 'S-' };
categoryInput.addEventListener('change', () => { tagPrefix.textContent = prefixes[categoryInput.value]; });
tagNumber.addEventListener('input', event => { event.target.value = event.target.value.replace(/\D/g, ''); });
document.getElementById('pen-number').addEventListener('input', event => { event.target.value = event.target.value.replace(/\D/g, ''); });
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
