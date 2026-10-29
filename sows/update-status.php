<?php
require __DIR__ . '/../includes/auth.php';
require_login();
require __DIR__ . '/../includes/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
verify_csrf();
$status = $_POST['new_status'] ?? '';
$id = (int) ($_POST['sow_record_id'] ?? 0);
$recordStatement = $pdo->prepare('SELECT status, piglets_born FROM sow_records WHERE sow_record_id = :id');
$recordStatement->execute(['id' => $id]);
$recordBefore = $recordStatement->fetch();
if (!in_array($status, ['farrowed', 'failed'], true)) {
    http_response_code(422);
    exit('Invalid status.');
}
$piglets = filter_var($_POST['piglets_born'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($status === 'farrowed' && $piglets === false) {
    flash('error', 'Enter a number greater than zero before marking the sow as farrowed.');
    redirect('sows/edit.php?id=' . $id);
}
$statement = $pdo->prepare('UPDATE sow_records SET status = :status, piglets_born = :piglets WHERE sow_record_id = :id');
$statement->execute(['status' => $status, 'piglets' => $status === 'failed' ? 0 : $piglets, 'id' => $id]);
$statusLabel = $status === 'farrowed' ? "farrowed ({$piglets} piglets)" : $status;
require __DIR__ . '/../includes/audit.php';
audit_log($pdo, "Marked sow record #{$id} as {$statusLabel}", $recordBefore ? "Status: {$recordBefore['status']}; Piglets: {$recordBefore['piglets_born']}" : null, "Status: {$status}; Piglets: " . ($status === 'failed' ? 0 : $piglets));
flash('success', $status === 'farrowed' ? 'Sow marked as farrowed and piglet count saved.' : 'Sow marked as failed.');
redirect('sows/index.php');
