<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function audit_log(PDO $pdo, string $actionDescription, ?string $beforeValue = null, ?string $afterValue = null): void
{
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId === null) {
        return;
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, action_description, before_value, after_value)
             VALUES (:user_id, :action, :before_value, :after_value)'
        );
        $statement->execute([
            'user_id' => (int) $userId,
            'action' => $actionDescription,
            'before_value' => $beforeValue,
            'after_value' => $afterValue,
        ]);
    } catch (PDOException) {
    }
}
