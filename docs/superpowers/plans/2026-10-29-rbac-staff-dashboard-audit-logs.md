# RBAC, Staff Dashboard, User Management & Audit Logging Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Harden role-based access control for staff (block sales access entirely), add an admin-only User Management module for creating and editing staff accounts, and implement a global Audit Logging system with a filterable admin view.

**Architecture:** Reuse the existing `role` enum and `require_role()` middleware from `includes/auth.php`. Add a global `audit_log()` helper in `includes/audit.php` that writes to a new `audit_logs` table. Inject `audit_log()` calls into all existing POST handlers (login, pig CRUD, sow CRUD, sale creation, user CRUD). Conditionally hide sales-related UI in the sidebar, dashboard, and navigation for staff. Build new `users/` and `audit/` modules following the established CRUD + `_results.php` partial pattern.

**Tech Stack:** PHP 8+, PDO (MySQL/MariaDB), Bootstrap 5.3, vanilla JS, existing project conventions.

**Spec:** User request — RBAC hardening, User Management module, Audit Logging system.

## Global Constraints

- Follow existing PHP file naming, indentation, and auth patterns (`require __DIR__.'/../includes/auth.php'; require_login(); require __DIR__.'/../includes/db.php';`).
- All POST forms must include CSRF tokens via `csrf_token()` / `verify_csrf()`.
- All DB queries must use prepared statements.
- New modules must use the shared `header.php` / `footer.php` layout.
- Role values are exactly `'admin'` and `'staff'` (already defined in `VALID_ROLES`).
- Passwords must be hashed with `password_hash()`; never store plaintext.
- Do not duplicate entire modules — reuse existing files with conditional UI and role checks.

## Review Focus

1. Staff accessing `/sales/index.php` or `/sales/view.php` directly via URL must be redirected to dashboard with a flash error, not shown a partially rendered page.
2. Dashboard must not leak sales revenue or recent-sales data to staff via unwrapped stat cards or panels.
3. Deleting or demoting the currently logged-in admin (or the last remaining admin) must be blocked with a clear error flash.
4. Audit log helper must silently skip writing if `$_SESSION['user_id']` is missing or the `audit_logs` table does not yet exist, so missing infrastructure never breaks a user-facing operation.
5. The "Staff Only" filter on the Audit Logs page must correctly join `audit_logs` with `users` and exclude admin rows when active, including on pagination or search.

---

### Task 1: Audit Logs Database Table & Helper

**Files:**
- Modify: `b4staffswinotech.sql`
- Create: `includes/audit.php`

**Interfaces:**
- Consumes: `$_SESSION['user_id']` (set by `login.php` via `includes/auth.php`)
- Produces: `audit_log(PDO $pdo, string $actionDescription): void`

- [ ] **Step 1: Add `audit_logs` table to `b4staffswinotech.sql`**

  Append this `CREATE TABLE` after the existing `users` table definition:

  ```sql
  CREATE TABLE `audit_logs` (
    `log_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int UNSIGNED NOT NULL,
    `action_description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`),
    KEY `idx_audit_user_id` (`user_id`),
    KEY `idx_audit_created_at` (`created_at`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ```

- [ ] **Step 2: Create `includes/audit.php`**

  ```php
  <?php
  declare(strict_types=1);

  require_once __DIR__ . '/auth.php';

  function audit_log(PDO $pdo, string $actionDescription): void
  {
      $userId = $_SESSION['user_id'] ?? null;
      if ($userId === null) {
          return;
      }
      try {
          $stmt = $pdo->prepare(
              'INSERT INTO audit_logs (user_id, action_description) VALUES (:user_id, :action)'
          );
          $stmt->execute([
              'user_id' => (int) $userId,
              'action'  => $actionDescription,
          ]);
      } catch (PDOException) {
          // Silently ignore so audit failures never break user operations.
      }
  }
  ```

- [ ] **Step 3: Run the CREATE TABLE on the active database**

  Command: open MySQL/MariaDB CLI for the `swinotech` database and execute the `CREATE TABLE` statement from Step 1.

- [ ] **Step 4: Verify the helper works**

  Create a temporary test script `test_audit.php` (do not commit):
  ```php
  <?php
  require __DIR__ . '/includes/auth.php';
  require __DIR__ . '/includes/db.php';
  require __DIR__ . '/includes/audit.php';
  $_SESSION['user_id'] = 1;
  audit_log($pdo, 'Test audit entry');
  $row = $pdo->query("SELECT * FROM audit_logs ORDER BY log_id DESC LIMIT 1")->fetch();
  assert($row['action_description'] === 'Test audit entry');
  echo "PASS\n";
  ```
  Run it in the browser or CLI. Expected: prints `PASS` and the row appears in the database.

- [ ] **Step 5: Commit**

  ```bash
  git add b4staffswinotech.sql includes/audit.php
  git commit -m "feat: add audit_logs table and audit_log() helper

Co-Authored-By: Claude Code <noreply@anthropic.com>"
  ```

---

### Task 2: Harden RBAC — Block Staff from Sales & Hide Sales UI

**Files:**
- Modify: `sales/index.php`
- Modify: `sales/view.php`
- Modify: `pigs/delete.php`
- Modify: `includes/header.php`
- Modify: `dashboard.php`

**Interfaces:**
- Consumes: `require_role(string $role): void` from `includes/auth.php`
- Produces: Staff users are redirected away from all sales pages; sales nav and dashboard widgets are hidden for staff.

- [ ] **Step 1: Block staff from `sales/index.php`**

  After `require_login();` add `require_role('admin');`:
  ```php
  require __DIR__ . '/../includes/auth.php';
  require_login();
  require_role('admin');
  require __DIR__ . '/../includes/db.php';
  ```

- [ ] **Step 2: Block staff from `sales/view.php`**

  Same change as Step 1: after `require_login();` add `require_role('admin');`.

- [ ] **Step 3: Allow staff to mark pigs dead**  

  In `pigs/delete.php`, change `require_role('admin');` to `require_login();`  
  Rationale: the spec says staff has "Full management of Pig Inventory & Breeding operations"; marking a pig dead is an inventory status update.

- [ ] **Step 4: Hide Sales nav item for staff in `includes/header.php`**

  In the `$nav` array definition (around line 7), remove the `'Sales'` entry from the base array and append it conditionally:
  ```php
  $nav = [
      ['label' => 'Dashboard',   'href' => 'dashboard.php',       'icon' => 'layout-dashboard',       'section' => 'dashboard'],
      ['label' => 'Pig Inventory','href' => 'pigs/index.php',      'icon' => 'piggy-bank',             'section' => 'pigs'],
      ['label' => 'Sow Tracker',  'href' => 'sows/index.php',      'icon' => 'heart-pulse',            'section' => 'sows'],
      ['label' => 'Monitoring',   'href' => 'monitoring/index.php','icon' => 'activity',               'section' => 'monitoring'],
      ['label' => 'Reports',      'href' => 'reports/index.php',   'icon' => 'chart-no-axes-combined', 'section' => 'reports'],
  ];
  if ($role === 'admin') {
      $nav[] = ['label' => 'Sales', 'href' => 'sales/index.php', 'icon' => 'receipt-text', 'section' => 'sales'];
  }
  ```

- [ ] **Step 5: Hide sales widgets on `dashboard.php` for staff**

  Wrap the "Sales this month" stat card and the "Recent sales" panel in `$role === 'admin'` conditionals.  
  (If there is a "Revenue change" welcome banner line, wrap that too.)  
  The `$role` variable is already available in `header.php`; in `dashboard.php` read it from `$_SESSION['role'] ?? 'staff'`.

- [ ] **Step 6: Test staff restrictions**

  1. Log in as `staff` / `staff123`.
  2. Confirm **Sales** does **not** appear in the sidebar.
  3. Navigate directly to `/sales/index.php` and `/sales/view.php?id=1`. Expected: redirect to `dashboard.php` with flash error "You do not have permission to access that page."
  4. Confirm the dashboard does **not** show the sales stat card or recent-sales panel.
  5. Confirm the Pig Inventory "Mark dead" action is available and works for staff.

- [ ] **Step 7: Commit**

  ```bash
  git add sales/index.php sales/view.php pigs/delete.php includes/header.php dashboard.php
  git commit -m "feat: block staff from sales views and hide sales UI; allow staff pig status updates

Co-Authored-By: Claude Code <noreply@anthropic.com>"
  ```

---

### Task 3: User Management Module (Admin Only)

**Files:**
- Create: `users/index.php`
- Create: `users/_results.php`
- Create: `users/add.php`
- Create: `users/edit.php`
- Create: `users/delete.php`
- Modify: `includes/header.php`

**Interfaces:**
- Consumes: `audit_log(PDO $pdo, string $actionDescription): void` from `includes/audit.php`
- Produces: Full CRUD for user accounts with audit trail; admin self-deletion and last-admin guards.

- [ ] **Step 1: Create `users/index.php`**

  Pattern: same auth header as other modules (`require_role('admin')`), list users with a search filter, AJAX support via `_results.php`, "Add staff" button.  
  Query: `SELECT user_id, username, role, created_at FROM users ORDER BY user_id DESC` (add `created_at` column if it does not exist; see Step 1a).  
  Include `<?= csrf_token() ?>` in any forms that target `delete.php`.

- [ ] **Step 1a: (If needed) Add `created_at` to `users` table**

  If the `users` table does not have a `created_at` column, run:
  ```sql
  ALTER TABLE `users` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
  ```
  Also add it to `b4staffswinotech.sql`.

- [ ] **Step 2: Create `users/_results.php`**

  Table partial showing: Username, Role, Created, Actions (Edit / Delete).  
  Delete button submits a POST form to `delete.php` with `user_id` and CSRF token.  
  Disable or omit the Delete button for the row matching `$_SESSION['user_id']`.

- [ ] **Step 3: Create `users/add.php`**

  **GET:** form with `username`, `password`, `confirm_password`, and `role` (select: `admin` / `staff`, default `staff`).  
  **POST:** verify CSRF, validate `password === confirm_password`, check username uniqueness via `SELECT 1 FROM users WHERE username = ?`, hash password with `password_hash($password, PASSWORD_DEFAULT)`, insert, call `audit_log($pdo, "Created user {$username} ({$role})")`, flash success, redirect to `users/index.php`.

- [ ] **Step 4: Create `users/edit.php`**

  **GET:** fetch user by `?id=`, show form with `username`, `role` (select), `password` (optional, placeholder "Leave blank to keep current").  
  **POST:** verify CSRF, validate that `id !== $_SESSION['user_id']` when changing role from `admin` to `staff` **and** this is the last admin (query `SELECT COUNT(*) FROM users WHERE role = 'admin'`). If so, flash error and redirect. Otherwise update username, role, and optionally re-hash password. Call `audit_log($pdo, "Updated user {$username}")`. Flash success, redirect.

- [ ] **Step 5: Create `users/delete.php`**

  **POST only.** Verify CSRF. Fetch target user. Block deletion if `user_id === $_SESSION['user_id']` (flash error). Block deletion if target is the last admin (query `SELECT COUNT(*) FROM users WHERE role = 'admin'`; if `=== 1` and target role is `admin`, flash error). Otherwise delete and call `audit_log($pdo, "Deleted user {$username}")`. Redirect to `users/index.php`.

- [ ] **Step 6: Add Users nav item to `includes/header.php`**

  After the existing conditional Settings link (or before it), add:
  ```php
  <?php $usersActive = $currentSection === 'users'; ?>
  <a class="<?= $usersActive ? 'active' : '' ?>" href="<?= BASE_URL ?>/users/index.php" <?= $usersActive ? 'aria-current="page"' : '' ?>><i class="nav-icon" data-lucide="users" aria-hidden="true"></i><span>Users</span></a>
  ```
  Wrap this inside the existing `<?php if ($role === 'admin'): ?>` block.

- [ ] **Step 7: Test user CRUD**

  1. As admin, open **Users**. Create a new staff account `teststaff` / `test123`. Verify it appears in the list.
  2. Edit the new account, change its role to `admin`, save. Verify the role updates.
  3. Try to delete your own admin account — must show error flash.
  4. Create a second admin, then try to demote the original admin to staff when only 2 admins exist — must succeed.
  5. Delete the second admin, leaving only one. Try to delete the last admin — must show error flash.
  6. Log in as `teststaff`. Confirm the **Users** nav does not appear.
  7. Try to access `/users/index.php` directly as staff — must redirect to dashboard with permission error.

- [ ] **Step 8: Commit**

  ```bash
  git add users/ includes/header.php b4staffswinotech.sql
  git commit -m "feat: add admin-only user management module with CRUD and audit logging

Co-Authored-By: Claude Code <noreply@anthropic.com>"
  ```

---

### Task 4: Audit Logs View (Admin Only)

**Files:**
- Create: `audit/index.php`
- Create: `audit/_results.php`
- Modify: `includes/header.php`

**Interfaces:**
- Consumes: `audit_logs` table, `users` table (for username/role join)
- Produces: Filterable paginated audit log view with "Staff Only" toggle.

- [ ] **Step 1: Create `audit/index.php`**

  Auth: `require_role('admin')`.  
  Read `$_GET['staff_only']` (string `'1'` means filter to staff).  
  Base query:
  ```sql
  SELECT a.log_id, a.user_id, u.username, u.role, a.action_description, a.created_at
  FROM audit_logs a
  JOIN users u ON u.user_id = a.user_id
  WHERE (:staff_only = 0 OR u.role = 'staff')
  ORDER BY a.created_at DESC, a.log_id DESC
  LIMIT 100
  ```
  (Pagination is optional; 100 rows is sufficient for the initial deliverable.)  
  Render a toggle button link: `?staff_only=1` when off, `?staff_only=0` (or bare URL) when on. Style the active state with a Bootstrap `active` class.

- [ ] **Step 2: Create `audit/_results.php`**

  Table partial with columns: Timestamp, User, Role, Action.  
  Empty state: "No audit entries found." when `$logs` is empty.

- [ ] **Step 3: Add Audit Logs nav item to `includes/header.php`**

  Inside the `<?php if ($role === 'admin'): ?>` block, after the Users link:
  ```php
  <?php $auditActive = $currentSection === 'audit'; ?>
  <a class="<?= $auditActive ? 'active' : '' ?>" href="<?= BASE_URL ?>/audit/index.php" <?= $auditActive ? 'aria-current="page"' : '' ?>><i class="nav-icon" data-lucide="scroll-text" aria-hidden="true"></i><span>Audit Logs</span></a>
  ```

- [ ] **Step 4: Test the audit view**

  1. As admin, open **Audit Logs**. Confirm entries from Task 3 (user CRUD) are visible.
  2. Click **Staff Only**. Confirm only rows where Role = `staff` remain.
  3. Click again to clear the filter. Confirm all rows return.
  4. Log in as staff. Confirm **Audit Logs** nav is hidden. Direct access to `/audit/index.php` redirects with permission error.

- [ ] **Step 5: Commit**

  ```bash
  git add audit/ includes/header.php
  git commit -m "feat: add admin-only audit logs view with staff-only filter

Co-Authored-By: Claude Code <noreply@anthropic.com>"
  ```

---

### Task 5: Integrate Audit Logging into Auth & Pig Operations

**Files:**
- Modify: `login.php`
- Modify: `pigs/add.php`
- Modify: `pigs/edit.php`
- Modify: `pigs/delete.php`

**Interfaces:**
- Consumes: `audit_log(PDO $pdo, string $actionDescription): void`
- Produces: Every login and pig mutation writes an audit row.

- [ ] **Step 1: Log successful logins in `login.php`**

  After the session is populated and before redirecting to `dashboard.php`, add:
  ```php
  require __DIR__ . '/includes/audit.php';
  audit_log($pdo, "User {$user['username']} logged in");
  ```
  (The `$pdo` connection is already available inside `login.php` after the user query.)

- [ ] **Step 2: Log pig creation in `pigs/add.php`**

  After the successful `INSERT INTO pigs` and after fetching the new `tag_number`, add:
  ```php
  require __DIR__ . '/../includes/audit.php';
  audit_log($pdo, "Added pig {$tagNumber} ({$category})");
  ```

- [ ] **Step 3: Log pig updates in `pigs/edit.php`**

  After the successful `UPDATE pigs` statement, add:
  ```php
  require __DIR__ . '/../includes/audit.php';
  audit_log($pdo, "Updated pig {$tagNumber}");
  ```

- [ ] **Step 4: Log pig status changes in `pigs/delete.php`**

  After the successful `UPDATE pigs SET status = 'dead'` statement, add:
  ```php
  require __DIR__ . '/../includes/audit.php';
  audit_log($pdo, "Marked pig {$tagNumber} as dead");
  ```

- [ ] **Step 5: Test audit trails**

  1. Log in as admin. Check **Audit Logs** — a "User admin67 logged in" entry should appear.
  2. Go to **Pig Inventory**, add a pig. Check **Audit Logs** — "Added pig …" should appear.
  3. Edit the pig. Check **Audit Logs** — "Updated pig …" should appear.
  4. Mark the pig dead. Check **Audit Logs** — "Marked pig … as dead" should appear.
  5. Repeat steps 2–4 as staff. Confirm staff actions also appear in the audit log with Role = `staff`.

- [ ] **Step 6: Commit**

  ```bash
  git add login.php pigs/add.php pigs/edit.php pigs/delete.php
  git commit -m "feat: audit-log login and pig inventory operations

Co-Authored-By: Claude Code <noreply@anthropic.com>"
  ```

---

### Task 6: Integrate Audit Logging into Breeding & Sales Operations

**Files:**
- Modify: `sows/add.php`
- Modify: `sows/edit.php`
- Modify: `sows/update-status.php`
- Modify: `sales/add.php`

**Interfaces:**
- Consumes: `audit_log(PDO $pdo, string $actionDescription): void`
- Produces: Every sow record mutation and sale creation writes an audit row.

- [ ] **Step 1: Log sow mating creation in `sows/add.php`**

  After the successful `INSERT INTO sow_records`, add:
  ```php
  require __DIR__ . '/../includes/audit.php';
  audit_log($pdo, "Recorded mating for sow {$tagNumber} (due {$expectedDate})");
  ```

- [ ] **Step 2: Log sow record edits in `sows/edit.php`**

  After the successful `UPDATE sow_records`, add:
  ```php
  require __DIR__ . '/../includes/audit.php';
  audit_log($pdo, "Updated sow record #{$sowRecordId} for {$tagNumber}");
  ```

- [ ] **Step 3: Log sow status updates in `sows/update-status.php`**

  After the successful `UPDATE sow_records`, add:
  ```php
  require __DIR__ . '/../includes/audit.php';
  $statusLabel = $status === 'farrowed' ? "farrowed ({$pigletsBorn} piglets)" : $status;
  audit_log($pdo, "Marked sow record #{$sowRecordId} as {$statusLabel}");
  ```

- [ ] **Step 4: Log sale creation in `sales/add.php`**

  After the DB transaction commits successfully, add:
  ```php
  require __DIR__ . '/../includes/audit.php';
  audit_log($pdo, "Created sale #{$saleId} to buyer {$buyerName} for ₱{$totalAmount}");
  ```

- [ ] **Step 5: Test audit trails**

  1. As admin or staff, go to **Sow Tracker**, record a mating. Check **Audit Logs** — "Recorded mating for sow …" appears.
  2. Edit the mating record. Check **Audit Logs** — "Updated sow record …" appears.
  3. Mark the record as farrowed or failed. Check **Audit Logs** — the status update appears.
  4. As admin, create a sale. Check **Audit Logs** — "Created sale …" appears.
  5. Confirm no PHP errors occur even if the `audit_logs` table were temporarily unavailable (the try/catch in `audit_log()` handles this).

- [ ] **Step 6: Commit**

  ```bash
  git add sows/add.php sows/edit.php sows/update-status.php sales/add.php
  git commit -m "feat: audit-log breeding and sales operations

Co-Authored-By: Claude Code <noreply@anthropic.com>"
  ```

---

## Self-Review Checklist

| Spec Requirement | Implementing Task | Verified |
|---|---|---|
| Admin unrestricted access | All tasks (no admin restrictions added beyond existing) | ✓ |
| Staff blocked from viewing/creating sales | Task 2 | ✓ |
| Staff blocked from IoT device configuration | Already enforced by `settings/index.php` (`require_role('admin')`); no change needed | ✓ |
| Staff full pig & breeding management | Task 2 (removed admin-only from `pigs/delete.php`) | ✓ |
| Staff can view barn environment | Already allowed (`monitoring/index.php` uses `require_login()` only) | ✓ |
| Admin-only User Management CRUD | Task 3 | ✓ |
| Audit logs table (user_id, action_description, timestamp) | Task 1 | ✓ |
| Global audit logging helper | Task 1 | ✓ |
| Audit logs view with Staff Only filter | Task 4 | ✓ |
| Audit log entries for login, create, update, delete | Tasks 3, 5, 6 | ✓ |
| Reuse existing PHP files with conditional UI | Tasks 2, 3, 4, 5, 6 | ✓ |

**Type consistency verified:**
- `audit_log()` signature is `function audit_log(PDO $pdo, string $actionDescription): void` everywhere.
- Role checks use `require_role('admin')` consistently.
- CSRF tokens use `csrf_token()` / `verify_csrf()` consistently.

**Proportion:** The plan is ~6 tasks for 3 subsystems (RBAC, User Management, Audit Logging). Each task is independently testable and ends with a commit. No task is a pure "sprinkle" — each has a coherent deliverable.
