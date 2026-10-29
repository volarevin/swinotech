<?php
require __DIR__ . '/../includes/auth.php'; require_login(); require __DIR__ . '/../includes/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$pigId = (int) ($_POST['pig_id'] ?? 0);
$pigStatement = $pdo->prepare('SELECT tag_number FROM pigs WHERE pig_id = :id');
$pigStatement->execute(['id' => $pigId]);
$pig = $pigStatement->fetch();
$statement = $pdo->prepare("UPDATE pigs SET status='dead' WHERE pig_id=:id AND status='active'");
$statement->execute(['id' => $pigId]);
if ($statement->rowCount() === 1 && $pig) {
    require __DIR__ . '/../includes/audit.php';
    audit_log($pdo, "Marked pig {$pig['tag_number']} as dead");
}
flash('success','Pig marked as dead.'); redirect('pigs/index.php');
