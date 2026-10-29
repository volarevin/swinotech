<?php
require __DIR__ . '/../includes/auth.php';
require_role('admin');
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/dashboard-data.php';

$pageTitle = 'Settings';
$profile = dashboard_profile($pdo);
$pdo->exec("CREATE TABLE IF NOT EXISTS device_settings (
    setting_id TINYINT UNSIGNED NOT NULL,
    device_name VARCHAR(100) NOT NULL DEFAULT 'ESP32-01',
    device_token VARCHAR(255) NOT NULL,
    PRIMARY KEY (setting_id)
) ENGINE=InnoDB");
$deviceStatement = $pdo->query('SELECT device_name, device_token FROM device_settings WHERE setting_id = 1');
$device = $deviceStatement->fetch() ?: ['device_name' => '', 'device_token' => ''];
$currentUser = ['username' => (string) ($_SESSION['username'] ?? '')];
$profileError = null;
$credentialError = null;
$deviceError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'device_pair') {
        $deviceName = trim((string) ($_POST['device_name'] ?? ''));
        if ($deviceName === '' || strlen($deviceName) > 100) {
            $deviceError = 'Device name is required and must be 100 characters or fewer.';
        } else {
            try {
                $deviceToken = bin2hex(random_bytes(24));
                $statement = $pdo->prepare('INSERT INTO device_settings (setting_id, device_name, device_token) VALUES (1, :device_name, :device_token) ON DUPLICATE KEY UPDATE device_name = VALUES(device_name), device_token = VALUES(device_token)');
                $statement->execute(['device_name' => $deviceName, 'device_token' => $deviceToken]);
                flash('success', 'Device paired. Copy this token into the device configuration now: ' . $deviceToken);
                redirect('settings/index.php');
            } catch (PDOException | RandomException $exception) {
                error_log('SwinoTech device pairing failed: ' . $exception->getMessage());
                $deviceError = 'Unable to pair the device.';
            }
        }
    } elseif (($_POST['action'] ?? '') === 'device') {
        $device = [
            'device_name' => trim((string) ($_POST['device_name'] ?? '')),
            'device_token' => trim((string) ($_POST['device_token'] ?? '')),
        ];
        if ($device['device_name'] === '' || strlen($device['device_name']) > 100) {
            $deviceError = 'Device name is required and must be 100 characters or fewer.';
        } elseif (strlen($device['device_token']) < 16 || strlen($device['device_token']) > 255) {
            $deviceError = 'The device token must be between 16 and 255 characters.';
        } elseif (!preg_match('/^[A-Za-z0-9._:-]+$/', $device['device_token'])) {
            $deviceError = 'The device token may contain only letters, numbers, dots, underscores, colons, and hyphens.';
        } else {
            try {
                $statement = $pdo->prepare('INSERT INTO device_settings (setting_id, device_name, device_token) VALUES (1, :device_name, :device_token) ON DUPLICATE KEY UPDATE device_name = VALUES(device_name), device_token = VALUES(device_token)');
                $statement->execute($device);
                flash('success', 'Device integration settings updated.');
                redirect('settings/index.php');
            } catch (PDOException $exception) {
                $deviceError = 'Unable to update the device integration settings.';
            }
        }
    } elseif (($_POST['action'] ?? '') === 'credentials') {
        $currentUser['username'] = trim((string) ($_POST['username'] ?? ''));
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $userStatement = $pdo->prepare('SELECT username, password FROM users WHERE user_id = :user_id');
        $userStatement->execute(['user_id' => (int) $_SESSION['user_id']]);
        $user = $userStatement->fetch();

        if ($currentUser['username'] === '' || !preg_match('/^[A-Za-z0-9._-]{3,100}$/', $currentUser['username'])) {
            $credentialError = 'Username must be 3–100 characters and use only letters, numbers, dots, underscores, or hyphens.';
        } elseif (!$user || !password_verify($currentPassword, $user['password'])) {
            $credentialError = 'Enter your current password to confirm this change.';
        } elseif ($newPassword !== '' && strlen($newPassword) < 8) {
            $credentialError = 'The new password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $credentialError = 'The new password and confirmation do not match.';
        } else {
            try {
                $fields = ['username = :username'];
                $parameters = ['username' => $currentUser['username'], 'user_id' => (int) $_SESSION['user_id']];
                if ($newPassword !== '') {
                    $fields[] = 'password = :password';
                    $parameters['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                }
                $statement = $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE user_id = :user_id');
                $statement->execute($parameters);
                $_SESSION['username'] = $currentUser['username'];
                flash('success', 'User credentials updated.');
                redirect('settings/index.php');
            } catch (PDOException $exception) {
                $credentialError = $exception->getCode() === '23000' ? 'That username is already in use.' : 'Unable to update the user credentials.';
            }
        }
    } else {
        $profile = [
            'farm_name' => trim((string) ($_POST['farm_name'] ?? '')),
            'owner_name' => trim((string) ($_POST['owner_name'] ?? '')),
            'location' => trim((string) ($_POST['location'] ?? '')),
            'heat_threshold' => trim((string) ($_POST['heat_threshold'] ?? '')),
            'due_soon_days' => trim((string) ($_POST['due_soon_days'] ?? '')),
        ];

        if ($profile['farm_name'] === '' || $profile['owner_name'] === '' || $profile['location'] === '') {
            $profileError = 'Farm name, owner name, and location are required.';
        } elseif (!is_numeric($profile['heat_threshold']) || (float) $profile['heat_threshold'] < 0 || (float) $profile['heat_threshold'] > 100) {
            $profileError = 'Heat threshold must be between 0 and 100°C.';
        } elseif (filter_var($profile['due_soon_days'], FILTER_VALIDATE_INT) === false || (int) $profile['due_soon_days'] < 1 || (int) $profile['due_soon_days'] > 30) {
            $profileError = 'Due-soon days must be a whole number between 1 and 30.';
        } else {
            try {
                $statement = $pdo->prepare('INSERT INTO farm_profile (profile_id, farm_name, owner_name, location, heat_threshold, due_soon_days) VALUES (1, :farm_name, :owner_name, :location, :heat_threshold, :due_soon_days) ON DUPLICATE KEY UPDATE farm_name = VALUES(farm_name), owner_name = VALUES(owner_name), location = VALUES(location), heat_threshold = VALUES(heat_threshold), due_soon_days = VALUES(due_soon_days)');
                $statement->execute([
                    'farm_name' => $profile['farm_name'],
                    'owner_name' => $profile['owner_name'],
                    'location' => $profile['location'],
                    'heat_threshold' => (float) $profile['heat_threshold'],
                    'due_soon_days' => (int) $profile['due_soon_days'],
                ]);
                flash('success', 'Farm profile updated.');
                redirect('settings/index.php');
            } catch (PDOException $exception) {
                $profileError = 'Unable to update the farm profile.';
            }
        }

        $profile['heat_threshold'] = (string) $profile['heat_threshold'];
        $profile['due_soon_days'] = (string) $profile['due_soon_days'];
    }
}

require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">Administration</p><h2>Settings</h2><p>Manage the farm profile and device integration settings.</p></div></div>
<?php if ($profileError): ?><div class="alert alert-danger"><?= e($profileError) ?></div><?php endif; ?>
<?php if ($credentialError): ?><div class="alert alert-danger"><?= e($credentialError) ?></div><?php endif; ?>
<?php if ($deviceError): ?><div class="alert alert-danger"><?= e($deviceError) ?></div><?php endif; ?>
<div class="row g-3 settings-card-row">
    <div class="col-xl-6 d-flex">
        <div class="card settings-card w-100">
            <div class="settings-card-heading"><h3 class="section-title">Farm profile</h3><span class="metric-icon metric-brand"><i data-lucide="building-2" aria-hidden="true"></i></span></div>
            <div class="section-note mb-3">These values are stored in the farm profile and used by the dashboard and alerts.</div>
            <form method="post" class="row g-3 settings-form">
                <input type="hidden" name="action" value="profile">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="col-12"><label class="form-label">Farm name<input class="form-control" name="farm_name" required value="<?= e($profile['farm_name']) ?>"></label></div>
                <div class="col-md-6"><label class="form-label">Owner name<input class="form-control" name="owner_name" required value="<?= e($profile['owner_name']) ?>"></label></div>
                <div class="col-md-6"><label class="form-label">Location<input class="form-control" name="location" required value="<?= e($profile['location']) ?>"></label></div>
                <div class="col-md-6"><label class="form-label">Heat threshold (°C)<input class="form-control" type="number" name="heat_threshold" min="0" max="100" step=".01" required value="<?= e($profile['heat_threshold']) ?>"></label></div>
                <div class="col-md-6"><label class="form-label">Due soon (days)<input class="form-control" type="number" name="due_soon_days" min="1" max="30" step="1" required value="<?= e($profile['due_soon_days']) ?>"></label></div>
                <div class="col-12 settings-actions"><button class="btn btn-danger primary-action">Save changes</button></div>
            </form>
        </div>
    </div>
    <div class="col-xl-6 d-flex">
        <div class="card settings-card w-100">
                <div class="settings-card-heading"><h3 class="section-title">User credentials</h3><span class="metric-icon metric-clay"><i data-lucide="user-round-cog" aria-hidden="true"></i></span></div>
                <div class="section-note mb-3">Update the signed-in administrator account. Your current password is required for verification.</div>
                <form method="post" class="row g-3 settings-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="credentials">
                    <div class="col-12"><label class="form-label">Username<input class="form-control" name="username" maxlength="100" autocomplete="username" required value="<?= e($currentUser['username']) ?>"></label></div>
                    <div class="col-12"><label class="form-label">Current password<input class="form-control" type="password" name="current_password" autocomplete="current-password" required></label></div>
                    <div class="col-md-6"><label class="form-label">New password <span class="text-muted fw-normal">(optional)</span><input class="form-control" type="password" name="new_password" minlength="8" autocomplete="new-password"></label></div>
                    <div class="col-md-6"><label class="form-label">Confirm new password<input class="form-control" type="password" name="confirm_password" minlength="8" autocomplete="new-password"></label></div>
                    <div class="col-12"><div class="form-text">Leave the new password fields blank if you only want to change the username.</div></div>
                    <div class="col-12 settings-actions"><button class="btn btn-danger primary-action">Update credentials</button></div>
                </form>
            </div>
        </div>
    <div class="col-12"><div class="card"><div class="settings-card-heading"><h3 class="section-title">Device integration</h3><span class="metric-icon metric-success"><i data-lucide="cpu" aria-hidden="true"></i></span></div><div class="section-note mb-3">Pair a device with a generated token, then configure the device to send readings to the sensor API.</div><form method="post" class="row g-3"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="col-md-5"><label class="form-label">Device name<input class="form-control" name="device_name" maxlength="100" required value="<?= e($device['device_name']) ?>"></label></div><div class="col-md-7"><label class="form-label">Pairing token<input class="form-control" type="password" name="device_token" maxlength="255" pattern="[A-Za-z0-9._:-]*" value="<?= e($device['device_token']) ?>"></label></div><div class="col-12"><div class="form-text">The token is stored in the database and must be sent in the <code>X-Device-Token</code> header to <code>/SwinoTech/api/sensor-data.php</code>. Use Pair device to rotate it.</div></div><div class="col-12 d-flex flex-wrap gap-2"><button class="btn btn-danger primary-action" type="submit" name="action" value="device">Save device settings</button><button class="btn btn-outline-secondary" type="submit" name="action" value="device_pair">Pair / rotate device token</button></div></form></div></div>
    <div class="col-12"><div class="card"><h3 class="section-title">Role behavior</h3><div class="section-note">Staff can view operational records. Admin users can add sales, mark pigs dead, and access settings.</div></div></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
