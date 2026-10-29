<div id="users-results" class="card table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Account</th><th>Role</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        <?php if (!$users): ?><tr><td colspan="4" class="text-center text-muted py-4">No users found.</td></tr>
        <?php else: foreach ($users as $user): ?>
            <tr>
                <td><div class="identity-cell"><span class="table-icon"><i data-lucide="user-round" aria-hidden="true"></i></span><strong><?= e($user['username']) ?></strong></div></td>
                <td><span class="role-badge role-<?= e($user['role']) ?>"><i data-lucide="<?= $user['role'] === 'admin' ? 'shield-check' : 'user-round' ?>" aria-hidden="true"></i><?= e(ucfirst($user['role'])) ?></span></td>
                <td><span class="muted-with-icon"><i data-lucide="calendar-days" aria-hidden="true"></i><?= e(date('M d, Y', strtotime($user['created_at']))) ?></span></td>
                <td class="text-end">
                    <?php if ((int) $user['user_id'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int) $user['user_id'] ?>"><i data-lucide="pencil" aria-hidden="true"></i>Edit</a>
                        <form class="d-inline" method="post" action="delete.php" data-confirm="Delete this user?">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
                            <button class="btn btn-sm btn-delete" type="submit"><i data-lucide="trash-2" aria-hidden="true"></i>Delete</button>
                        </form>
                    <?php else: ?><span class="text-muted small">Manage in Settings</span><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
