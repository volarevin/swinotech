<?php
require __DIR__ . '/../includes/auth.php'; require_role('admin'); require __DIR__ . '/../includes/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf(); $statement = $pdo->prepare("UPDATE pigs SET status='dead' WHERE pig_id=:id AND status='active'"); $statement->execute(['id'=>(int)($_POST['pig_id'] ?? 0)]); flash('success','Pig marked as dead.'); redirect('pigs/index.php');
