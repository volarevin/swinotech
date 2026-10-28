        </main>
    </div>
</div>
<dialog class="confirmation-dialog" id="confirmation-modal" aria-labelledby="confirmation-modal-title">
    <div class="confirmation-dialog-card">
        <div class="confirmation-dialog-icon" aria-hidden="true"><i data-lucide="alert-triangle"></i></div>
        <div class="confirmation-dialog-copy">
            <h2 id="confirmation-modal-title">Please confirm</h2>
            <p id="confirmation-modal-message"></p>
        </div>
        <div class="confirmation-dialog-actions">
            <button type="button" class="btn btn-light" id="confirmation-modal-cancel">Cancel</button>
            <button type="button" class="btn btn-danger primary-action" id="confirmation-modal-submit">Continue</button>
        </div>
    </div>
</dialog>
<div class="offcanvas offcanvas-start mobile-nav" tabindex="-1" id="mobileNav">
    <div class="offcanvas-header"><strong class="brand-title">SwinoTech</strong><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
    <div class="offcanvas-body">
        <?php foreach ($nav as $item): ?><?php $active = $item['section'] === 'dashboard' ? $isDashboard : $currentSection === $item['section']; ?><a href="<?= BASE_URL . '/' . $item['href'] ?>" class="mobile-nav-link<?= $active ? ' active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?>><i data-lucide="<?= e($item['icon']) ?>" aria-hidden="true"></i><?= e($item['label']) ?></a><?php endforeach; ?>
        <?php if ($role === 'admin'): ?><?php $settingsActive = $currentSection === 'settings'; ?><a href="<?= BASE_URL ?>/settings/index.php" class="mobile-nav-link<?= $settingsActive ? ' active' : '' ?>" <?= $settingsActive ? 'aria-current="page"' : '' ?>><i data-lucide="settings" aria-hidden="true"></i>Settings</a><?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= (int) filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
<?php if (!empty($pageScript)): ?><script src="<?= BASE_URL ?>/assets/js/<?= e($pageScript) ?>?v=<?= (int) filemtime(__DIR__ . '/../assets/js/' . $pageScript) ?>"></script><?php endif; ?>
</body>
</html>
