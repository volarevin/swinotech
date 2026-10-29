<?php
require __DIR__ . '/../includes/auth.php';
require_login();
require __DIR__ . '/../includes/db.php';

$pageTitle = 'Add Pig';
$error = null;
$categories = ['boar' => 'B', 'fattener' => 'F', 'sow' => 'S'];
$category = $_POST['category'] ?? 'boar';
$number = trim((string) ($_POST['tag_number'] ?? ''));
$penNumber = trim((string) ($_POST['pen_number'] ?? '1'));

function next_pig_number(PDO $pdo, string $prefix): int
{
    $statement = $pdo->prepare("SELECT tag_number FROM pigs WHERE tag_number LIKE :prefix");
    $statement->execute(['prefix' => $prefix . '-%']);
    $highest = 0;
    foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $tag) {
        if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/i', (string) $tag, $match)) {
            $highest = max($highest, (int) $match[1]);
        }
    }
    $number = $highest + 1;
    do {
        $tag = $prefix . '-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
        $check = $pdo->prepare('SELECT COUNT(*) FROM pigs WHERE tag_number = :tag');
        $check->execute(['tag' => $tag]);
        $number++;
    } while ((int) $check->fetchColumn() > 0);
    return $number - 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        if (!isset($categories[$category])) {
            throw new InvalidArgumentException('Choose a valid pig category.');
        }
        if ($number !== '' && (!ctype_digit($number) || (int) $number < 1 || (int) $number > 999999)) {
            throw new InvalidArgumentException('Tag number must be a whole number between 1 and 999999.');
        }
        if ($penNumber === '') {
            throw new InvalidArgumentException('Pen number is required.');
        }
        $prefix = $categories[$category];
        $tagNumber = $number === '' ? next_pig_number($pdo, $prefix) : (int) $number;
        $tag = $prefix . '-' . str_pad((string) $tagNumber, 3, '0', STR_PAD_LEFT);
        $statement = $pdo->prepare('INSERT INTO pigs (tag_number, category, weight, pen_number, status) VALUES (:tag, :category, :weight, :pen, :status)');
        $penDigits = preg_replace('/^pen[\s-]*/i', '', $penNumber);
        if ($penNumber !== '' && !preg_match('/^\d+$/', (string) $penDigits)) {
            throw new InvalidArgumentException('Pen number must contain digits only, such as 1 or 12.');
        }
        $statement->execute([
            'tag' => $tag,
            'category' => $category,
            'weight' => (float) $_POST['weight'],
            'pen' => $penNumber === '' ? null : 'Pen ' . (int) $penDigits,
            'status' => $_POST['status'] ?? 'active',
        ]);
        require __DIR__ . '/../includes/audit.php';
        audit_log($pdo, "Added pig {$tag} ({$category})");
        flash('success', 'Pig ' . $tag . ' added to the herd register.');
        redirect('pigs/index.php');
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } catch (PDOException $exception) {
        $error = match ($exception->getCode()) {
            '23000' => 'That tag number is already in use. Leave the number blank or choose another number.',
            '01000' => 'The database still does not allow the “sow” category. Re-import database-rebuild-mysql.sql, then try again.',
            default => 'Unable to save the pig: ' . $exception->getMessage(),
        };
    }
}
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><p class="eyebrow"><a class="back-button" href="index.php" aria-label="Back to pig inventory" title="Back to pig inventory"><i data-lucide="arrow-left" aria-hidden="true"></i></a>Herd register</p><h2>Add pig</h2><p>Choose a category and enter only the numeric part of the tag. Leave it blank to generate the next number automatically.</p></div></div>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
<div class="card"><form method="post" class="row g-3">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="col-md-6"><label class="form-label">Category</label><div class="filter-select compact-form-select" data-filter-select><input type="hidden" name="category" id="pig-category" value="<?= e($category) ?>"><button class="filter-select-toggle" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="pig-category-options" data-filter-select-toggle><?= e(ucfirst($category)) ?><i data-lucide="chevron-down" aria-hidden="true"></i></button><div class="filter-select-menu" id="pig-category-options" role="listbox" hidden><?php foreach ($categories as $value => $prefix): ?><button type="button" role="option" aria-selected="<?= $category === $value ? 'true' : 'false' ?>" data-filter-option="<?= e($value) ?>"><?= e(ucfirst($value)) ?> (<?= $prefix ?>)</button><?php endforeach; ?></div></div></div>
    <div class="col-md-6"><label class="form-label">Tag number <span class="text-muted fw-normal">(optional)</span><div class="input-group"><span class="input-group-text" id="tag-prefix">B-</span><input class="form-control" name="tag_number" id="tag-number" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="Auto" value="<?= e($number) ?>"></div><div class="form-text">Example: enter <strong>12</strong> to create <strong id="tag-preview">B-012</strong>.</div></label></div>
    <div class="col-md-6"><label class="form-label">Weight (kg)<input class="form-control" type="number" step=".01" min="0" name="weight" required value="<?= e($_POST['weight'] ?? '') ?>"></label></div>
    <div class="col-md-6"><label class="form-label">Pen number <span class="text-danger">*</span><div class="input-group"><span class="input-group-text">Pen</span><input class="form-control" name="pen_number" id="pen-number" inputmode="numeric" pattern="[0-9]+" placeholder="1" value="<?= e(preg_replace('/^pen[\s-]*/i', '', $penNumber)) ?>" required></div><div class="form-text">Enter only the number, for example <strong>1</strong>.</div></label></div>
    <div class="col-md-6"><label class="form-label">Status</label><div class="filter-select compact-form-select" data-filter-select><input type="hidden" name="status" value="<?= e($_POST['status'] ?? 'active') ?>"><button class="filter-select-toggle" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="pig-status-options" data-filter-select-toggle><?= e(ucfirst($_POST['status'] ?? 'active')) ?><i data-lucide="chevron-down" aria-hidden="true"></i></button><div class="filter-select-menu" id="pig-status-options" role="listbox" hidden><?php foreach (['active', 'sold', 'dead'] as $status): ?><button type="button" role="option" aria-selected="<?= ($_POST['status'] ?? 'active') === $status ? 'true' : 'false' ?>" data-filter-option="<?= $status ?>"><?= ucfirst($status) ?></button><?php endforeach; ?></div></div></div>
    <div class="col-12"><button class="btn btn-danger primary-action" type="submit">Save pig</button></div>
</form></div>
<script>
(() => {
    const category = document.getElementById('pig-category');
    const number = document.getElementById('tag-number');
    const prefix = document.getElementById('tag-prefix');
    const preview = document.getElementById('tag-preview');
    const prefixes = { boar: 'B', fattener: 'F', sow: 'S' };
    const update = () => {
        const value = number.value.replace(/\D/g, '');
        number.value = value;
        const tagPrefix = prefixes[category.value] || 'B';
        prefix.textContent = `${tagPrefix}-`;
        preview.textContent = `${tagPrefix}-${value ? value.padStart(3, '0') : '001 (next available)'}`;
    };
    category.addEventListener('change', update);
    number.addEventListener('input', update);
    const pen = document.getElementById('pen-number');
    pen.addEventListener('input', () => { pen.value = pen.value.replace(/\D/g, ''); });
    update();
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
