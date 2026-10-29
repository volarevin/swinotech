<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

$error = '';

if (is_authenticated()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare('SELECT user_id, username, password, role FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && in_array((string) $user['role'], VALID_ROLES, true) && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            require __DIR__ . '/includes/audit.php';
            audit_log($pdo, 'Logged in');
            header('Location: /SwinoTech/dashboard.php');
            exit;
        } else {
            $error = 'Invalid credentials.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · SwinoTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <script>(function(){try{var t=localStorage.getItem('swinotech-theme');if(t==='light'||t==='dark')document.documentElement.dataset.theme=t;else if(window.matchMedia('(prefers-color-scheme: dark)').matches)document.documentElement.dataset.theme='dark';}catch(e){}})();</script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="<?= htmlspecialchars('/SwinoTech/assets/css/style.css?v=' . filemtime(__DIR__ . '/assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
</head>
<body>
    <main class="login-page">
        <section class="login-brand" aria-label="SwinoTech introduction">
            <div>
                <a class="brand" href="/SwinoTech/login.php">
                    <span class="brand-mark"><img src="/SwinoTech/icons/logobig.jpg" alt=""></span>
                    <span><strong>Swino<span>Tech</span></strong><small>Piggery Management System</small></span>
                </a>
                <p class="login-eyebrow eyebrow mt-5 mb-2">Farm operations, at a glance</p>
                <h1 class="display-5 mb-3">A healthier herd starts with better visibility.</h1>
                <p class="login-description mb-0">Manage your herd, breeding records, sales, and barn conditions from one calm, focused workspace.</p>
            </div>
        </section>
        <section class="login-form-wrap">
            <button class="theme-toggle login-theme-toggle icon-button" type="button" aria-label="Toggle dark mode" aria-pressed="false" data-theme-toggle><i data-lucide="moon" aria-hidden="true"></i><span class="visually-hidden" data-theme-label>Enable dark mode</span></button>
            <div class="login-card">
                <div class="mb-4">
                    <div class="eyebrow mb-2">Welcome back</div>
                    <h1 class="mb-2">Sign in to SwinoTech</h1>
                    <p class="section-note mb-0">Use your farm operations account to continue.</p>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert" aria-live="polite"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="username">Username</label>
                        <input class="form-control form-control-lg" type="text" id="username" name="username"
                               value="<?= htmlspecialchars($username ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="password">Password</label>
                        <input class="form-control form-control-lg" type="password" id="password" name="password"
                               autocomplete="current-password" required>
                    </div>
                    <button class="btn btn-danger btn-lg w-100" type="submit">Sign in <i data-lucide="arrow-right" aria-hidden="true"></i></button>
                </form>
                <p class="section-note text-center mt-4 mb-0">SwinoTech farm operations portal</p>
            </div>
        </section>
    </main>
    <script src="/SwinoTech/assets/js/app.js"></script>
</body>
</html>
