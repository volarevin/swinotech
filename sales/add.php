<?php
require __DIR__ . '/../includes/auth.php';
require_role('admin');
require __DIR__ . '/../includes/db.php';

$pageTitle = 'Create sale';
$error = null;
$pigs = $pdo->query("SELECT * FROM pigs WHERE status = 'active' ORDER BY tag_number")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $buyer = trim((string) ($_POST['buyer_name'] ?? ''));
    $date = $_POST['sale_date'] ?? date('Y-m-d');
    $pigIds = array_values(array_filter(array_map('intval', (array) ($_POST['pig_id'] ?? []))));
    $prices = (array) ($_POST['price_per_kg'] ?? []);

    if ($buyer === '' || !$pigIds) {
        $error = 'Buyer and at least one pig are required.';
    } elseif (count($pigIds) !== count(array_unique($pigIds))) {
        $error = 'Each pig can only be included once in a sale.';
    } else {
        try {
            $pdo->beginTransaction();
            $insert = $pdo->prepare('INSERT INTO sales (buyer_name, sale_date, total_amount) VALUES (:buyer, :date, 0)');
            $insert->execute(['buyer' => $buyer, 'date' => $date]);
            $saleId = (int) $pdo->lastInsertId();
            $selectPig = $pdo->prepare("SELECT pig_id, weight FROM pigs WHERE pig_id = :pig AND status = 'active' FOR UPDATE");
            $item = $pdo->prepare('INSERT INTO sale_items (sale_id, pig_id, sold_weight, price_per_kg) VALUES (:sale, :pig, :weight, :price)');
            $update = $pdo->prepare("UPDATE pigs SET status = 'sold' WHERE pig_id = :pig AND status = 'active'");
            $total = 0.0;
            foreach ($pigIds as $index => $pigId) {
                $selectPig->execute(['pig' => $pigId]);
                $pig = $selectPig->fetch();
                $price = (float) ($prices[$index] ?? 0);
                if (!$pig || $price <= 0) {
                    throw new RuntimeException('Each selected pig needs a valid price per kg.');
                }
                $weight = (float) $pig['weight'];
                $item->execute(['sale' => $saleId, 'pig' => $pigId, 'weight' => $weight, 'price' => $price]);
                $update->execute(['pig' => $pigId]);
                if ($update->rowCount() !== 1) {
                    throw new RuntimeException('One selected pig is no longer active.');
                }
                $total += $weight * $price;
            }
            $finish = $pdo->prepare('UPDATE sales SET total_amount = :total WHERE sale_id = :sale');
            $finish->execute(['total' => $total, 'sale' => $saleId]);
            $pdo->commit();
            require __DIR__ . '/../includes/audit.php';
            audit_log($pdo, "Created sale #{$saleId} to buyer {$buyer} for ₱{$total}");
            flash('success', 'Whole-pig sale recorded and pigs marked sold.');
            redirect('sales/view.php?id=' . $saleId);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $exception instanceof PDOException && $exception->getCode() === '23000'
                ? 'A selected pig has already been sold.'
                : $exception->getMessage();
        }
    }
}
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><p class="eyebrow"><a class="back-button" href="index.php" aria-label="Back to transactions" title="Back to transactions"><i data-lucide="arrow-left" aria-hidden="true"></i></a>Transactions</p><h2>Create sale</h2><p>Select one or more active pigs. Their recorded weight is used automatically; enter only the price per kilogram.</p></div></div>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
<div class="card"><form method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="row g-3"><div class="col-md-6"><label class="form-label">Buyer name<input class="form-control" name="buyer_name" required value="<?= e($_POST['buyer_name'] ?? '') ?>"></label></div><div class="col-md-6"><label class="form-label">Sale date<input class="form-control" type="date" name="sale_date" value="<?= e($_POST['sale_date'] ?? date('Y-m-d')) ?>" required></label></div></div>
    <div id="sale-lines" class="mt-4"><div class="row g-2 sale-line mb-2"><div class="col-md-5"><label class="visually-hidden">Pig</label><div class="filter-select compact-form-select pig-select" data-filter-select><input type="hidden" name="pig_id[]" value=""><button class="filter-select-toggle" type="button" aria-haspopup="listbox" aria-expanded="false" data-filter-select-toggle>Select pig<i data-lucide="chevron-down" aria-hidden="true"></i></button><div class="filter-select-menu" role="listbox" hidden><button type="button" role="option" aria-selected="true" data-filter-option="" data-weight="">Select pig</button><?php foreach ($pigs as $pig): ?><button type="button" role="option" aria-selected="false" data-filter-option="<?= (int) $pig['pig_id'] ?>" data-weight="<?= e($pig['weight']) ?>"><?= e($pig['tag_number']) ?> · <?= e($pig['weight']) ?> kg</button><?php endforeach; ?></div></div></div><div class="col-md-3"><label class="visually-hidden">Recorded weight</label><div class="input-group"><input class="form-control recorded-weight" type="number" readonly placeholder="Recorded weight"><span class="input-group-text">kg</span></div></div><div class="col-md-3"><label class="visually-hidden">Price per kg</label><div class="input-group"><span class="input-group-text">₱</span><input class="form-control" type="number" step=".01" min=".01" name="price_per_kg[]" placeholder="Price / kg" required></div></div><div class="col-md-1"><button type="button" class="btn btn-light remove-line" aria-label="Remove pig">×</button></div></div></div>
    <button type="button" class="btn btn-outline-secondary btn-sm" id="add-line">+ Add pig</button><div class="mt-4"><button class="btn btn-danger primary-action" type="submit">Record sale</button></div>
</form></div>
<script>
(() => {
    const lines = document.getElementById('sale-lines');
    const syncWeight = (line) => {
        const selected = line.querySelector('.pig-select input[type="hidden"]').value;
        const option = line.querySelector(`[data-filter-option="${selected}"]`);
        line.querySelector('.recorded-weight').value = option?.dataset.weight || '';
    };
    const bind = (line) => line.querySelector('.pig-select input[type="hidden"]').addEventListener('change', () => syncWeight(line));
    bind(lines.querySelector('.sale-line'));
    document.getElementById('add-line').addEventListener('click', () => {
        const line = lines.querySelector('.sale-line').cloneNode(true);
        line.querySelector('.pig-select input[type="hidden"]').value = '';
        line.querySelector('.filter-select-toggle').firstChild.textContent = 'Select pig';
        line.querySelectorAll('[data-filter-option]').forEach((option, index) => option.setAttribute('aria-selected', index === 0 ? 'true' : 'false'));
        line.querySelector('.recorded-weight').value = '';
        line.querySelector('input[name="price_per_kg[]"]').value = '';
        line.querySelectorAll('[data-filter-select]').forEach((select) => { delete select.dataset.bound; });
        lines.appendChild(line);
        bind(line);
        bindFilterSelects();
    });
    document.addEventListener('click', (event) => {
        if (event.target.classList.contains('remove-line') && document.querySelectorAll('.sale-line').length > 1) {
            event.target.closest('.sale-line').remove();
        }
    });
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
