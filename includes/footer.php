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
<dialog class="confirmation-dialog audit-change-modal" id="audit-change-modal" aria-labelledby="audit-change-title">
    <div class="confirmation-dialog-card">
        <div class="confirmation-dialog-icon audit-change-icon" aria-hidden="true"><i data-lucide="git-compare-arrows"></i></div>
        <div class="confirmation-dialog-copy">
            <h2 id="audit-change-title">Change details</h2>
            <p id="audit-change-action"></p>
        </div>
        <div class="audit-change-values">
            <div><span class="audit-change-label">Before</span><div class="audit-change-list" id="audit-change-before"></div></div>
            <i data-lucide="arrow-right" aria-hidden="true"></i>
            <div><span class="audit-change-label">After</span><div class="audit-change-list" id="audit-change-after"></div></div>
        </div>
        <div class="confirmation-dialog-actions"><button type="button" class="btn btn-light" id="audit-change-close">Close</button></div>
    </div>
</dialog>
<div class="offcanvas offcanvas-start mobile-nav" tabindex="-1" id="mobileNav">
    <div class="offcanvas-header"><strong class="brand-title">SwinoTech</strong><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
    <div class="offcanvas-body">
        <div class="calendar-control calendar-control-mobile" data-calendar data-calendar-events="<?= e(json_encode($calendarEvents, JSON_UNESCAPED_SLASHES)) ?>">
            <button class="mobile-calendar-trigger" type="button" aria-expanded="false" aria-controls="mobile-calendar" data-calendar-toggle><i data-lucide="calendar-days" aria-hidden="true"></i><span>Farm calendar</span></button>
            <div class="calendar-popover" id="mobile-calendar" data-calendar-popover hidden>
                <div class="calendar-head"><div><div class="eyebrow">Farm reminders</div><h2 data-calendar-title></h2></div><button class="icon-button calendar-close" type="button" aria-label="Close calendar" data-calendar-close><i data-lucide="x" aria-hidden="true"></i></button></div>
                <div class="calendar-month-controls"><button class="icon-button" type="button" aria-label="Previous month" data-calendar-prev><i data-lucide="chevron-left" aria-hidden="true"></i></button><strong data-calendar-month></strong><button class="icon-button" type="button" aria-label="Next month" data-calendar-next><i data-lucide="chevron-right" aria-hidden="true"></i></button></div>
                <div class="calendar-weekdays" aria-hidden="true"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div>
                <div class="calendar-grid" role="grid" aria-label="Calendar days" data-calendar-grid></div>
                <section class="calendar-day-details" aria-live="polite"><div class="calendar-day-heading"><i data-lucide="calendar-check-2" aria-hidden="true"></i><strong data-calendar-selected-date></strong></div><div data-calendar-events-list></div></section>
            </div>
        </div>
        <?php foreach ($nav as $item): ?><?php $active = $item['section'] === 'dashboard' ? $isDashboard : $currentSection === $item['section']; ?><a href="<?= BASE_URL . '/' . $item['href'] ?>" class="mobile-nav-link<?= $active ? ' active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?>><i data-lucide="<?= e($item['icon']) ?>" aria-hidden="true"></i><?= e($item['label']) ?></a><?php endforeach; ?>
        <?php if ($role === 'admin'): ?>
            <?php $settingsActive = $currentSection === 'settings'; ?><a href="<?= BASE_URL ?>/settings/index.php" class="mobile-nav-link<?= $settingsActive ? ' active' : '' ?>" <?= $settingsActive ? 'aria-current="page"' : '' ?>><i data-lucide="settings" aria-hidden="true"></i>Settings</a>
            <?php $usersActive = $currentSection === 'users'; ?><a href="<?= BASE_URL ?>/users/index.php" class="mobile-nav-link<?= $usersActive ? ' active' : '' ?>" <?= $usersActive ? 'aria-current="page"' : '' ?>><i data-lucide="users" aria-hidden="true"></i>Users</a>
            <?php $auditActive = $currentSection === 'audit'; ?><a href="<?= BASE_URL ?>/audit/index.php" class="mobile-nav-link<?= $auditActive ? ' active' : '' ?>" <?= $auditActive ? 'aria-current="page"' : '' ?>><i data-lucide="scroll-text" aria-hidden="true"></i>Audit Logs</a>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= (int) filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
<?php if (!empty($pageScript)): ?><script src="<?= BASE_URL ?>/assets/js/<?= e($pageScript) ?>?v=<?= (int) filemtime(__DIR__ . '/../assets/js/' . $pageScript) ?>"></script><?php endif; ?>
</body>
</html>
