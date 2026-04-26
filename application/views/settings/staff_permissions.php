<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/**
 * application/views/settings/staff_permissions.php
 *
 * Unified permissions manager — Single User + Multiple Users tabs.
 * Rendered by Settings::manage_permissions()
 *
 * Expected $view_data keys:
 *   tab              string   'single' | 'multi'
 *   modules          array    merged core + module permissions map
 *   users            array    [{id, fullname, emp_id}]
 *   selected_user_id int      currently loaded user (single tab)
 *   user_grants      array    permission keys granted to selected user
 *   user_denies      array    permission keys denied to selected user
 */

$tab              = $tab              ?? 'single';
$modules          = $modules          ?? [];
$users            = $users            ?? [];
$selected_user_id = (int)($selected_user_id ?? 0);
$user_grants      = $user_grants      ?? [];
$user_denies      = $user_denies      ?? [];

$user_data_for_js = array_map(function ($u) {
    return [
        'id'       => (int)$u['id'],
        'name'     => (!empty($u['emp_id']) ? $u['emp_id'] . ' — ' : '') . ($u['fullname'] ?: 'User #' . $u['id']),
        'emp_id'   => $u['emp_id']   ?? '',
        'fullname' => $u['fullname'] ?? '',
        'avatar'   => user_avatar_url($u['profile_image'] ?? ''),
    ];
}, $users);

$can_edit = $can_edit ?? false;
?>

<style>
/* ── Tab panes ──────────────────────────────────────────────── */
.sp-tab-pane          { display: none; }
.sp-tab-pane.active   { display: block; }

/* Active state for the header tab buttons */
.sp-tab-btn.active {
    background: var(--bs-primary, #0d6efd) !important;
    color: #fff !important;
    border-color: var(--bs-primary, #0d6efd) !important;
}

/* ── Single-user selector row ───────────────────────────────── */
.sp-user-row {
    display: flex;
    align-items: stretch;
    margin-bottom: 18px;
    max-width: 640px;
}
.sp-user-row .input-group-text {
    font-size: 12.5px;
    font-weight: 500;
    white-space: nowrap;
    background: #f8f9fa;
    border-right: 0;
    border-radius: 6px 0 0 6px;
}
.sp-user-row .form-select {
    font-size: 13px;
    border-left: 0;
    border-right: 0;
    border-radius: 0;
}
.sp-user-row .btn {
    border-radius: 0 6px 6px 0;
    font-size: 13px;
    white-space: nowrap;
}

/* ── Loaded-user badge ──────────────────────────────────────── */
.sp-loaded-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: #e8f5e9;
    border: 1px solid #a5d6a7;
    border-radius: 20px;
    font-size: 12px;
    color: #2e7d32;
    font-weight: 600;
    margin-bottom: 16px;
}

/* ── No-user placeholder ────────────────────────────────────── */
.sp-no-user {
    text-align: center;
    padding: 52px 20px;
    color: #c0c7d0;
    border: 1px dashed #dee2e6;
    border-radius: 8px;
    background: #fafbfc;
}
.sp-no-user i   { font-size: 36px; display: block; margin-bottom: 12px; }
.sp-no-user p   { font-size: 13.5px; margin: 0; color: #8a93a2; }
.sp-no-user strong { color: #495057; }

/* ── Multi-user: outer layout ───────────────────────────────── */
.sp-multi-selector {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    margin-bottom: 20px;
}
.sp-multi-left  { flex: 0 0 340px; min-width: 0; }
.sp-multi-right {
    flex: 1;
    min-width: 0;
    min-height: 80px;
    border: 1px dashed #ced4da;
    border-radius: 6px;
    background: #f8f9fa;
    padding: 10px 12px;
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 7px;
}
.sp-multi-right.empty {
    align-items: center;
    justify-content: center;
    color: #adb5bd;
    font-size: 12.5px;
    font-style: italic;
}
.sp-multi-label {
    font-size: 12.5px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* ── Multi-user dropdown input ──────────────────────────────── */
.multi-user-select-wrapper {
    border: 1px solid #ced4da;
    border-radius: 6px;
    padding: 6px 10px;
    min-height: 40px;
    display: flex;
    align-items: center;
    background: #fff;
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s;
}
.multi-user-select-wrapper:focus-within {
    border-color: #86b7fe;
    box-shadow: 0 0 0 .2rem rgba(13,110,253,.18);
}
.multi-user-placeholder {
    color: #6c757d;
    font-size: 13px;
    flex: 1;
    user-select: none;
}
.multi-user-search {
    border: none;
    outline: none;
    width: 100%;
    font-size: 13px;
    padding: 2px 0;
    background: transparent;
}
.multi-user-dropdown-wrap { position: relative; }
.multi-user-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    box-shadow: 0 6px 20px rgba(0,0,0,.1);
    max-height: 240px;
    overflow-y: auto;
    z-index: 1055;
    display: none;
}
.multi-user-dropdown.show   { display: block; }
.multi-user-option {
    padding: 8px 12px;
    cursor: pointer;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 9px;
    border-bottom: 1px solid #f3f4f6;
    transition: background .1s;
}
.multi-user-option:last-child { border-bottom: none; }
.multi-user-option:hover      { background: #f0f4ff; }
.multi-user-option-avatar {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}
.no-users-message {
    padding: 14px;
    text-align: center;
    color: #6c757d;
    font-size: 13px;
    font-style: italic;
}

/* ── User chips ─────────────────────────────────────────────── */
.user-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    padding: 3px 9px 3px 5px;
    font-size: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    white-space: nowrap;
}
.user-chip-avatar {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    object-fit: cover;
}
.user-chip-remove {
    background: none;
    border: none;
    color: #adb5bd;
    font-size: 15px;
    line-height: 1;
    padding: 0;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 50%;
    transition: background .15s, color .15s;
}
.user-chip-remove:hover { background: #dc3545; color: #fff; }


.sp-mod {
    border: 1px solid #e9ecf0;
    border-radius: 8px;
    margin-bottom: 10px;
    overflow: hidden;
}
.sp-mod-hd {
    padding: 8px 14px;
    background: #f5f7fa;
    border-bottom: 1px solid #e9ecf0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.sp-mod-title {
    margin: 0;
    font-weight: 700;
    font-size: 12.5px;
    color: #1a1a2e;
}
.sp-mod-toggle-all {
    font-size: 11px;
    font-weight: 600;
    color: #0d6efd;
    background: none;
    border: 1px solid transparent;
    cursor: pointer;
    padding: 2px 8px;
    border-radius: 4px;
    white-space: nowrap;
    transition: background .12s, border-color .12s;
}
.sp-mod-toggle-all:hover {
    background: #e8f0fe;
    border-color: #c7d9fb;
}

/* Grid: label col | Allow col | Deny col */
.perm-grid {
    display: grid;
    grid-template-columns: 1fr 100px 100px;
    align-items: stretch;
}
.perm-head {
    font-size: 10.5px;
    font-weight: 700;
    color: #8a93a2;
    text-transform: uppercase;
    letter-spacing: .05em;
    padding: 6px 14px;
    border-bottom: 1px solid #e9ecf0;
    background: #fafbfc;
}
.perm-head.tc { text-align: center; }

/* Each row = 3 cells via display:contents */
.perm-row        { display: contents; }
.perm-row > div  {
    padding: 7px 14px;
    border-bottom: 1px solid #f1f3f6;
    display: flex;
    align-items: center;
}
.perm-row:last-child > div { border-bottom: none; }
.perm-row:nth-child(even) > div { background: #fafbfc; }

/* Label cell */
.perm-name-col {
    flex-direction: column;
    align-items: flex-start !important;
    gap: 2px;
}
.perm-label { font-size: 12.5px; color: #2d3748; line-height: 1.3; }

/* Checkbox columns */
.perm-toggle-col { justify-content: center; }
.perm-toggle-col .form-check {
    margin: 0;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.perm-toggle-col .form-check-input {
    width: 15px;
    height: 15px;
    margin: 0;
    cursor: pointer;
    flex-shrink: 0;
}
.grant-cb:checked { accent-color: #198754; }
.deny-cb:checked  { accent-color: #dc3545; }

/* ── Sticky save bar ────────────────────────────────────────── */
.sp-save-bar {
    position: sticky;
    bottom: 0;
    z-index: 100;
    background: #fff;
    border-top: 1px solid #e9ecef;
    padding: 11px 0;
    margin-top: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.sp-save-summary {
    font-size: 12px;
    color: #6c757d;
    margin-left: 4px;
}

/* ── Responsive ─────────────────────────────────────────────── */
@media (max-width: 860px) {
    .sp-multi-selector { flex-direction: column; }
    .sp-multi-left     { flex: none; width: 100%; }
    .sp-multi-right    { width: 100%; }
}
@media (max-width: 640px) {
    .perm-grid      { grid-template-columns: 1fr 72px 72px; }
    .sp-user-row    { max-width: 100%; }
    .perm-head      { padding: 6px 10px; font-size: 10px; }
    .perm-row > div { padding: 6px 10px; }
}
@media (max-width: 460px) {
    .perm-grid { grid-template-columns: 1fr 56px 56px; }
}
</style>

<div class="container-fluid">
    
<div class="view-header mb-3">
    <div class="view-icon me-3"><i class="fa-solid fa-user-shield"></i></div>
    <div class="flex-grow-1">
        <div class="view-title"><?= $page_title ?? 'Staff Permissions' ?></div>
        <div class="view-sub">Manage grant and deny permissions for individual staff or groups of users.</div>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
        <button type="button"
                class="sp-tab-btn btn btn-light-primary btn-sm btn-header <?= $tab === 'single' ? 'active' : '' ?>"
                data-tab="single">
            <i class="fa-solid fa-user me-1"></i> Single
        </button>
        <div class="btn-divider"></div>
        <button type="button"
                class="sp-tab-btn btn btn-light-primary btn-sm btn-header <?= $tab === 'multi' ? 'active' : '' ?>"
                data-tab="multi">
            <i class="fa-solid fa-users me-1"></i> Multiple
        </button>
    </div>
</div>

<div class="sp-tab-pane bg-white p-3 <?= $tab === 'single' ? 'active' : '' ?>" id="tab-single">
    <form method="post"
          action="<?= site_url('settings/manage_permissions?tab=single') ?>"
          class="app-form"
          id="form-single">

        <input type="hidden" name="active_tab" value="single">

        <div class="sp-user-row input-group">
            <span class="input-group-text">
                <i class="fa-solid fa-user me-2 text-primary"></i>
                Select User <span class="text-danger ms-1">*</span>
            </span>
            <select id="singleUserSelect"
                    class="form-select js-searchable-select"
                    name="user_id"
                    <?= !$can_edit ? 'disabled' : '' ?>>
                <option value="">— Choose a user —</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= (int)$u['id'] ?>"
                        <?= ((int)$u['id'] === $selected_user_id) ? 'selected' : '' ?>>
                        <?= e((!empty($u['emp_id']) ? $u['emp_id'] . ' — ' : '') . ($u['fullname'] ?: 'User #' . $u['id'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button"
                    id="btnLoadUser"
                    class="btn btn-outline-primary"
                    <?= ($selected_user_id <= 0) ? 'disabled' : '' ?>>
                <i class="fa-solid fa-rotate-right me-1"></i> Load
            </button>
        </div>

        <?php if ($selected_user_id > 0):
            $loaded_name = '';
            foreach ($users as $u) {
                if ((int)$u['id'] === $selected_user_id) {
                    $loaded_name = (!empty($u['emp_id']) ? $u['emp_id'] . ' — ' : '')
                                 . ($u['fullname'] ?: 'User #' . $u['id']);
                    break;
                }
            }
        ?>
            <div class="sp-loaded-badge">
                <i class="fa-solid fa-circle-check"></i>
                Editing: <?= user_profile_small($loaded_name) ?>
            </div>

            <?= render_permission_grid($modules, $user_grants, $user_denies, 'single', $can_edit) ?>

            <?php if ($can_edit): ?>
                <div class="sp-save-bar">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Permissions
                    </button>
                    <button type="button"
                            class="btn btn-outline-secondary btn-sm btn-clear-perms"
                            data-form="form-single">
                        <i class="fa-solid fa-eraser me-1"></i> Clear All
                    </button>
                    <span class="sp-save-summary">
                        <span class="text-success fw-semibold" id="single-grant-count">0</span> grants
                        &nbsp;·&nbsp;
                        <span class="text-danger fw-semibold" id="single-deny-count">0</span> denies
                    </span>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="sp-no-user">
                <i class="fa-solid fa-user-clock"></i>
                <p>Select a user above and click <strong>Load</strong> to view and manage their permissions.</p>
            </div>
        <?php endif; ?>

    </form>
</div>

<?php /* ═══════════════════════════════════════════
       TAB — MULTIPLE USERS
       ═══════════════════════════════════════════ */ ?>
<div class="sp-tab-pane bg-white p-3 <?= $tab === 'multi' ? 'active' : '' ?>" id="tab-multi">
    <form method="post"
          action="<?= site_url('settings/manage_permissions?tab=multi') ?>"
          class="app-form"
          id="form-multi">

        <input type="hidden" name="active_tab" value="multi">
        <input type="hidden" name="user_ids"   id="userIdsInput" value="">

        <?php /* two-column: search on left, chips panel on right */ ?>
        <div class="sp-multi-selector">

            <div class="sp-multi-left">
                <div class="sp-multi-label">
                    <i class="fa-solid fa-users text-primary"></i>
                    Select Users <span class="text-danger">*</span>
                </div>
                <div class="multi-user-dropdown-wrap">
                    <div class="multi-user-select-wrapper" id="multiUserSelectWrapper">
                        <div class="multi-user-placeholder" id="multiUserPlaceholder">
                            Click to search and add users…
                        </div>
                        <input type="text"
                               class="multi-user-search"
                               id="multiUserSearch"
                               placeholder="Type name or ID…"
                               autocomplete="off"
                               style="display:none;">
                    </div>
                    <div class="multi-user-dropdown" id="multiUserDropdown"></div>
                </div>
                <div class="mt-2" style="font-size:11px;color:#adb5bd;line-height:1.4;">
                    <i class="fa-solid fa-triangle-exclamation me-1" style="color:#f59e0b;"></i>
                    Saving will <strong>overwrite</strong> each selected user's existing permissions.
                </div>
            </div>

            <?php /* chips panel — sits inline to the right */ ?>
            <div class="sp-multi-right empty" id="selectedUsersChips">
                No users selected yet
            </div>

        </div>

        <?= render_permission_grid($modules, [], [], 'multi', $can_edit) ?>

        <?php if ($can_edit): ?>
            <div class="sp-save-bar">
                <button type="submit"
                        class="btn btn-primary btn-sm"
                        id="btnMultiSave"
                        disabled>
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save to Selected Users
                </button>
                <button type="button"
                        class="btn btn-outline-secondary btn-sm btn-clear-perms"
                        data-form="form-multi">
                    <i class="fa-solid fa-eraser me-1"></i> Clear All
                </button>
                <span class="sp-save-summary">
                    <span class="text-success fw-semibold" id="multi-grant-count">0</span> grants
                    &nbsp;·&nbsp;
                    <span class="text-danger fw-semibold" id="multi-deny-count">0</span> denies
                    &nbsp;·&nbsp;
                    <span class="fw-semibold" id="multi-user-count">0</span> users
                </span>
            </div>
        <?php endif; ?>

    </form>
</div>

<?php /* ═══════════════════════════════════════════
       PHP HELPER — shared permission grid renderer
       ═══════════════════════════════════════════ */ ?>
<?php
function render_permission_grid(array $modules, array $grants, array $denies, string $ns, bool $can_edit): string
{
    ob_start(); ?>

    <?php foreach ($modules as $moduleKey => $actions):
        $actionArr   = $actions['actions'] ?? $actions;
        $moduleLabel = $actions['name']    ?? ucfirst($moduleKey);
        if (empty($actionArr)) continue; ?>

    <section class="sp-mod">
        <header class="sp-mod-hd">
            <p class="sp-mod-title"><?= e($moduleLabel) ?></p>
            <?php if ($can_edit): ?>
                <div class="d-flex align-items-center gap-1">
                    <button type="button"
                            class="sp-mod-toggle-all"
                            data-module="<?= e($moduleKey) ?>"
                            data-ns="<?= e($ns) ?>"
                            data-state="grant">
                        <i class="fa-solid fa-check fa-xs me-1"></i>Grant all
                    </button>
                </div>
            <?php endif; ?>
        </header>

        <div class="perm-grid">
            <div class="perm-head">Permissions</div>
            <div class="perm-head tc">Allow</div>
            <div class="perm-head tc">Deny</div>

            <?php foreach ($actionArr as $actionKey => $meta):
                $label = is_array($meta) ? ($meta['label'] ?? ucfirst($actionKey)) : (string)$meta;
                $perm  = $moduleKey . ':' . $actionKey;
                $gid   = 'g-' . $ns . '-' . md5($perm);
                $did   = 'd-' . $ns . '-' . md5($perm);
            ?>
            <div class="perm-row" data-module="<?= e($moduleKey) ?>">

                <div class="perm-name-col">
                    <span class="perm-label"><?= e($label) ?></span>
                </div>

                <div class="perm-toggle-col" data-label="Allow">
                    <div class="form-check">
                        <input type="checkbox"
                               class="form-check-input grant-cb"
                               id="<?= $gid ?>"
                               data-pair="#<?= $did ?>"
                               name="settings[grants][]"
                               value="<?= e($perm) ?>"
                               <?= in_array($perm, $grants, true) ? 'checked' : '' ?>
                               <?= !$can_edit ? 'disabled' : '' ?>>
                    </div>
                </div>

                <div class="perm-toggle-col" data-label="Deny">
                    <div class="form-check">
                        <input type="checkbox"
                               class="form-check-input deny-cb"
                               id="<?= $did ?>"
                               data-pair="#<?= $gid ?>"
                               name="settings[denies][]"
                               value="<?= e($perm) ?>"
                               <?= in_array($perm, $denies, true) ? 'checked' : '' ?>
                               <?= !$can_edit ? 'disabled' : '' ?>>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php endforeach;
    return ob_get_clean();
}
?>

</div>

<script>
(function () {
'use strict';

/* ── 1. TAB SWITCHING ──────────────────────────────────────── */
document.querySelectorAll('.sp-tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var t   = this.dataset.tab;
        var url = new URL(window.location.href);
        url.searchParams.set('tab', t);
        url.searchParams.delete('uid');
        history.pushState({}, '', url.toString());

        document.querySelectorAll('.sp-tab-btn').forEach(function (b) { b.classList.remove('active'); });
        document.querySelectorAll('.sp-tab-pane').forEach(function (p) { p.classList.remove('active'); });

        this.classList.add('active');
        var pane = document.getElementById('tab-' + t);
        if (pane) pane.classList.add('active');
    });
});

/* ── 2. SINGLE USER — load button ──────────────────────────── */
var singleSelect = document.getElementById('singleUserSelect');
var loadBtn      = document.getElementById('btnLoadUser');

if (singleSelect && loadBtn) {
    singleSelect.addEventListener('change', function () {
        loadBtn.disabled = !this.value;
    });
    loadBtn.addEventListener('click', function () {
        if (!singleSelect.value) return;
        var url = new URL(window.location.href);
        url.searchParams.set('tab', 'single');
        url.searchParams.set('uid', singleSelect.value);
        window.location.href = url.toString();
    });
}

/* ── 3. GRANT / DENY MUTUAL EXCLUSION ─────────────────────── */
document.addEventListener('change', function (e) {
    var cb = e.target;
    if (!cb.matches('.grant-cb, .deny-cb')) return;
    var pair = document.querySelector(cb.dataset.pair);
    if (pair && cb.checked) pair.checked = false;
    updateCounters();
});

/* ── 4. LIVE COUNTERS ──────────────────────────────────────── */
function countIn(formId) {
    var f = document.getElementById(formId);
    if (!f) return { grants: 0, denies: 0 };
    return {
        grants: f.querySelectorAll('.grant-cb:checked').length,
        denies: f.querySelectorAll('.deny-cb:checked').length,
    };
}
function updateCounters() {
    var s = countIn('form-single');
    var m = countIn('form-multi');
    set('single-grant-count', s.grants);
    set('single-deny-count',  s.denies);
    set('multi-grant-count',  m.grants);
    set('multi-deny-count',   m.denies);
}
function set(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; }
updateCounters();

/* ── 5. MODULE "GRANT ALL / DENY ALL / CLEAR" TOGGLE ──────── */
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.sp-mod-toggle-all');
    if (!btn) return;

    var mk    = btn.dataset.module;
    var ns    = btn.dataset.ns;
    var state = btn.dataset.state;

    document.querySelectorAll('#tab-' + ns + ' .perm-row[data-module="' + mk + '"]')
        .forEach(function (row) {
            var g = row.querySelector('.grant-cb');
            var d = row.querySelector('.deny-cb');
            if (!g || !d) return;
            if (state === 'grant') { g.checked = true;  d.checked = false; }
            else if (state === 'deny')  { g.checked = false; d.checked = true;  }
            else                        { g.checked = false; d.checked = false; }
        });

    var next   = state === 'grant' ? 'deny' : (state === 'deny' ? 'clear' : 'grant');
    var labels = { grant: 'Grant all', deny: 'Deny all', clear: 'Clear all' };
    btn.dataset.state = next;
    btn.textContent   = labels[next];
    updateCounters();
});

/* ── 6. CLEAR ALL ──────────────────────────────────────────── */
document.querySelectorAll('.btn-clear-perms').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var f = document.getElementById(this.dataset.form);
        if (!f) return;
        f.querySelectorAll('.grant-cb, .deny-cb').forEach(function (cb) { cb.checked = false; });
        updateCounters();
    });
});

/* ── 7. MULTI-USER CHIP SELECTOR ───────────────────────────── */
var userData    = <?= json_encode($user_data_for_js) ?>;
var selected    = new Set();

var wrapper     = document.getElementById('multiUserSelectWrapper');
var searchInput = document.getElementById('multiUserSearch');
var placeholder = document.getElementById('multiUserPlaceholder');
var dropdown    = document.getElementById('multiUserDropdown');
var chips       = document.getElementById('selectedUsersChips');
var idsInput    = document.getElementById('userIdsInput');
var saveBtn     = document.getElementById('btnMultiSave');
var countEl     = document.getElementById('multi-user-count');

if (!wrapper) return;

function esc(str) {
    var d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
}

function syncInput() {
    if (idsInput) idsInput.value        = Array.from(selected).join(',');
    if (saveBtn)  saveBtn.disabled      = selected.size === 0;
    if (countEl)  countEl.textContent   = selected.size;
}

function renderChips() {
    chips.innerHTML = '';
    if (selected.size === 0) {
        chips.className   = 'sp-multi-right empty';
        chips.textContent = 'No users selected yet';
        return;
    }
    chips.className = 'sp-multi-right';
    selected.forEach(function (uid) {
        var u = userData.find(function (x) { return x.id === uid; });
        if (!u) return;
        var chip = document.createElement('div');
        chip.className = 'user-chip';
        chip.innerHTML =
            '<img src="' + esc(u.avatar) + '" alt="" class="user-chip-avatar">' +
            '<span>' + esc(u.name) + '</span>' +
            '<button type="button" class="user-chip-remove" data-uid="' + uid + '">&times;</button>';
        chips.appendChild(chip);
    });
    chips.querySelectorAll('.user-chip-remove').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            selected.delete(parseInt(this.dataset.uid, 10));
            renderChips();
            renderDropdown(searchInput.value);
            syncInput();
        });
    });
}

function renderDropdown(term) {
    dropdown.innerHTML = '';
    var q    = (term || '').toLowerCase().trim();
    var list = userData.filter(function (u) {
        if (selected.has(u.id)) return false;
        if (!q) return true;
        return u.name.toLowerCase().includes(q) ||
               u.emp_id.toLowerCase().includes(q) ||
               u.fullname.toLowerCase().includes(q);
    });

    if (list.length === 0) {
        var msg = document.createElement('div');
        msg.className   = 'no-users-message';
        msg.textContent = q
            ? 'No matching users'
            : (userData.length === selected.size ? 'All users selected' : 'No users available');
        dropdown.appendChild(msg);
        return;
    }

    list.forEach(function (u) {
        var opt = document.createElement('div');
        opt.className   = 'multi-user-option';
        opt.dataset.uid = u.id;
        opt.innerHTML   =
            '<img src="' + esc(u.avatar) + '" alt="" class="multi-user-option-avatar">' +
            '<span>' + esc(u.name) + '</span>';
        opt.addEventListener('click', function (e) {
            e.stopPropagation();
            selected.add(parseInt(this.dataset.uid, 10));
            searchInput.value = '';
            renderChips();
            renderDropdown('');
            syncInput();
        });
        dropdown.appendChild(opt);
    });
}

function openDropdown() {
    placeholder.style.display = 'none';
    searchInput.style.display = 'block';
    searchInput.focus();
    renderDropdown(searchInput.value);
    dropdown.classList.add('show');
}

function closeDropdown() {
    dropdown.classList.remove('show');
    if (!searchInput.value) {
        placeholder.style.display = 'block';
        searchInput.style.display = 'none';
    }
}

wrapper.addEventListener('click',     function (e) { e.stopPropagation(); openDropdown(); });
searchInput.addEventListener('input', function ()  { renderDropdown(this.value); });
searchInput.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDropdown(); });
document.addEventListener('click', function (e) {
    if (!wrapper.contains(e.target) && !dropdown.contains(e.target)) closeDropdown();
});

renderChips();
syncInput();

})();
</script>