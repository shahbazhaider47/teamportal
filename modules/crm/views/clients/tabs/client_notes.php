<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $CI =& get_instance(); ?>
<?php
$notes    = is_array($notes ?? null) ? $notes : [];
$clientId = (int)($client['id'] ?? 0);

$totalNotes    = count($notes);
$internalCount = 0;
$visibleCount  = 0;
foreach ($notes as $n) {
    if ((int)($n['is_internal'] ?? 1)) {
        $internalCount++;
    } else {
        $visibleCount++;
    }
}
?>

<style>
/* ── Notes Tab ─────────────────────────────────────────────────── */
.nt-layout          { display: flex; gap: 24px; align-items: flex-start; }
.nt-sidebar         { width: 260px; flex-shrink: 0; display: flex; flex-direction: column; gap: 16px; }
.nt-main            { flex: 1; min-width: 0; }

/* Sidebar card */
.nt-card {
    background    : #fff;
    border        : 1px solid #e9ecef;
    border-radius : 14px;
    padding       : 18px;
    box-shadow    : 0 1px 4px rgba(0,0,0,.04);
}
.nt-card-title {
    font-size     : 11px;
    font-weight   : 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color         : #6c757d;
    margin-bottom : 14px;
    display       : flex;
    align-items   : center;
    gap           : 6px;
}

/* Stat row */
.nt-stat-row {
    display       : flex;
    align-items   : center;
    gap           : 10px;
    padding       : 8px 0;
    border-bottom : 1px solid #f1f3f5;
}
.nt-stat-row:last-child { border-bottom: none; }
.nt-stat-icon {
    width         : 32px;
    height        : 32px;
    border-radius : 8px;
    display       : flex;
    align-items   : center;
    justify-content: center;
    font-size     : 15px;
    flex-shrink   : 0;
}
.nt-stat-icon.blue   { background: #e7f1ff; color: #0d6efd; }
.nt-stat-icon.purple { background: #f0ebff; color: #6f42c1; }
.nt-stat-icon.teal   { background: #e6faf5; color: #20c997; }
.nt-stat-icon.orange { background: #fff3e6; color: #fd7e14; }
.nt-stat-icon.gray   { background: #f1f3f5; color: #6c757d; }

.nt-stat-body        { flex: 1; min-width: 0; }
.nt-stat-label       { font-size: 11px; color: #6c757d; line-height: 1.2; }
.nt-stat-value       {
    font-size     : 13px;
    font-weight   : 600;
    color         : #212529;
    white-space   : nowrap;
    overflow      : hidden;
    text-overflow : ellipsis;
}

/* Main panel header */
.nt-panel-header {
    display       : flex;
    align-items   : center;
    justify-content: space-between;
    margin-bottom : 16px;
}
.nt-panel-title {
    font-size     : 15px;
    font-weight   : 700;
    color         : #212529;
    display       : flex;
    align-items   : center;
    gap           : 7px;
}
.nt-panel-title i { color: #0d6efd; }

/* Filter pills */
.nt-filters      { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 16px; }
.nt-filter-btn {
    font-size     : 12px;
    font-weight   : 600;
    padding       : 4px 12px;
    border-radius : 20px;
    border        : 1.5px solid #dee2e6;
    background    : #fff;
    color         : #6c757d;
    cursor        : pointer;
    transition    : all .15s;
    letter-spacing: .02em;
}
.nt-filter-btn:hover,
.nt-filter-btn.active { border-color: #0d6efd; background: #e7f1ff; color: #0d6efd; }

/* Note card */
.nt-note {
    background    : #fff;
    border        : 1px solid #e9ecef;
    border-radius : 14px;
    padding       : 16px 18px;
    margin-bottom : 12px;
    box-shadow    : 0 1px 3px rgba(0,0,0,.04);
    transition    : box-shadow .18s, border-color .18s;
    position      : relative;
    overflow      : hidden;
}
.nt-note::before {
    content       : '';
    position      : absolute;
    left          : 0; top: 0; bottom: 0;
    width         : 4px;
    border-radius : 14px 0 0 14px;
}
.nt-note.is-internal::before { background: #6f42c1; }
.nt-note.is-visible::before  { background: #20c997; }
.nt-note:hover { box-shadow: 0 4px 14px rgba(0,0,0,.08); border-color: #d0d7de; }

.nt-note-head {
    display       : flex;
    align-items   : flex-start;
    justify-content: space-between;
    gap           : 12px;
    margin-bottom : 10px;
}
.nt-note-author-wrap { display: flex; align-items: center; gap: 10px; }

.nt-author-name  { font-size: 13px; font-weight: 700; color: #212529; line-height: 1.3; }
.nt-author-meta  { font-size: 11px; color: #6c757d; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.nt-author-meta i { font-size: 11px; }
.nt-meta-sep     { color: #dee2e6; }

.nt-note-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

/* Visibility badge */
.nt-badge {
    font-size     : 10px;
    font-weight   : 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    padding       : 3px 9px;
    border-radius : 20px;
}
.nt-badge.internal { background: #f0ebff; color: #6f42c1; }
.nt-badge.visible  { background: #e6faf5; color: #20c997; }

/* Action buttons */
.nt-btn-icon {
    width         : 30px;
    height        : 30px;
    border-radius : 8px;
    border        : 1.5px solid #dee2e6;
    background    : #fff;
    display       : flex;
    align-items   : center;
    justify-content: center;
    font-size     : 14px;
    cursor        : pointer;
    color         : #495057;
    transition    : all .15s;
    text-decoration: none;
    padding       : 0;
}
.nt-btn-icon:hover.edit   { border-color: #0d6efd; background: #e7f1ff; color: #0d6efd; }
.nt-btn-icon:hover.delete { border-color: #dc3545; background: #fde8ea; color: #dc3545; }

/* Note body */
.nt-note-body {
    font-size     : 13.5px;
    color         : #495057;
    line-height   : 1.65;
    white-space   : pre-line;
    word-break    : break-word;
}

/* Empty state */
.nt-empty {
    text-align    : center;
    padding       : 60px 20px;
    color         : #adb5bd;
}
.nt-empty i    { font-size: 48px; display: block; margin-bottom: 12px; }
.nt-empty p    { font-size: 14px; margin: 0; }

/* Responsive */
@media (max-width: 768px) {
    .nt-layout  { flex-direction: column; }
    .nt-sidebar { width: 100%; }
}
</style>

<div class="nt-layout">

    <!-- ── Sidebar ─────────────────────────────────────────────── -->
    <aside class="nt-sidebar">

        <!-- Client info -->
        <div class="nt-card">
            <div class="nt-card-title"><i class="ti ti-chart-bar"></i> Overview</div>

            <div class="nt-stat-row">
                <div class="nt-stat-icon orange"><i class="ti ti-notes"></i></div>
                <div class="nt-stat-body">
                    <div class="nt-stat-label">Total Notes</div>
                    <div class="nt-stat-value"><?= $totalNotes ?></div>
                </div>
            </div>
            
            <div class="nt-stat-row">
                <div class="nt-stat-icon purple"><i class="ti ti-files"></i></div>
                <div class="nt-stat-body">
                    <div class="nt-stat-label">Intrnal Notes</div>
                    <div class="nt-stat-value"><?= $internalCount ?></div>
                </div>
            </div>

            <div class="nt-stat-row">
                <div class="nt-stat-icon teal"><i class="ti ti-lock-open"></i></div>
                <div class="nt-stat-body">
                    <div class="nt-stat-label">Public Notes</div>
                    <div class="nt-stat-value"><?= $visibleCount ?></div>
                </div>
            </div>

        </div>

    </aside>

    <!-- ── Main ────────────────────────────────────────────────── -->
    <main class="nt-main">

        <!-- Header -->
        <div class="nt-panel-header">
            <div class="nt-panel-title">
                <i class="ti ti-message-2"></i> Client Notes
                <?php if ($totalNotes > 0): ?>
                    <span class="badge bg-light-primary text-primary ms-1"><?= $totalNotes ?></span>
                <?php endif; ?>
            </div>

            <?php if (staff_can('client_edit', 'crm') || staff_can('client_create', 'crm')): ?>
                <button type="button"
                        class="btn btn-sm btn-primary d-flex align-items-center gap-1"
                        data-bs-toggle="modal"
                        data-bs-target="#addClientNoteModal">
                    <i class="ti ti-plus"></i> Add Note
                </button>
            <?php endif; ?>
        </div>

        <!-- Filter pills -->
        <?php if ($totalNotes > 0): ?>
            <div class="nt-filters" id="ntFilters">
                <button class="nt-filter-btn active" data-filter="all">All (<?= $totalNotes ?>)</button>
                <button class="nt-filter-btn" data-filter="internal">Internal (<?= $internalCount ?>)</button>
                <button class="nt-filter-btn" data-filter="visible">Visible (<?= $visibleCount ?>)</button>
            </div>
        <?php endif; ?>

        <!-- Notes list -->
        <?php if (!empty($notes)): ?>
            <div id="ntNotesList">
                <?php foreach ($notes as $note): ?>
                    <?php
                    $noteId     = (int)($note['id'] ?? 0);
                    $authorName = trim((string)($note['fullname'] ?? (trim(($note['firstname'] ?? '') . ' ' . ($note['lastname'] ?? '')))));
                    $authorName = $authorName !== '' ? $authorName : 'Unknown User';
                    $noteText   = trim((string)($note['note'] ?? ''));
                    $createdAt  = !empty($note['created_at']) ? date('M d, Y · h:i A', strtotime($note['created_at'])) : '—';
                    $updatedAt  = !empty($note['updated_at']) ? date('M d, Y · h:i A', strtotime($note['updated_at'])) : '';
                    $isInternal = (int)($note['is_internal'] ?? 1);
                    $noteClass  = $isInternal ? 'is-internal' : 'is-visible';
                    ?>
                    <div class="nt-note <?= $noteClass ?>"
                         data-visibility="<?= $isInternal ? 'internal' : 'visible' ?>">

                        <div class="nt-note-head">
                            <div class="nt-note-author-wrap">
                                <div class="text-primary small fw-semibold">
                                <?= user_profile_image($authorName) ?>
                                </div>
                            </div>

                            <div class="nt-note-actions">
                                <span class="nt-badge <?= $isInternal ? 'internal' : 'visible' ?>">
                                    <i class="ti <?= $isInternal ? 'ti-lock' : 'ti-eye' ?> me-1"></i>
                                    <?= $isInternal ? 'Internal' : 'Visible' ?>
                                </span>

                                <?php if (staff_can('client_edit', 'crm')): ?>
                                    <button type="button"
                                            class="nt-btn-icon edit"
                                            title="Edit note"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editClientNoteModal<?= $noteId ?>">
                                        <i class="ti ti-pencil"></i>
                                    </button>
                                <?php endif; ?>

                                <?php if (staff_can('client_delete', 'crm') || staff_can('client_edit', 'crm')): ?>
                                    <form action="<?= site_url('crm/client_delete_note/' . $noteId) ?>"
                                          method="post"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete this note? This action cannot be undone.');">
                                        <button type="submit" class="nt-btn-icon delete" title="Delete note">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="nt-note-body mb-3"><?= html_escape($noteText) ?></div>

                                <div>
                                    <div class="nt-author-meta">
                                        <i class="ti ti-calendar-event"></i><?= html_escape($createdAt) ?>
                                        <?php if ($updatedAt): ?>
                                            <span class="nt-meta-sep">|</span>
                                            <i class="ti ti-pencil"></i>Edited <?= html_escape($updatedAt) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                    </div>

                    <?php $CI->load->view('clients/modals/edit_note_modal', [
                        'client' => $client,
                        'note'   => $note,
                    ]); ?>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="nt-empty">
                <i class="ti ti-notes-off"></i>
                <p>No notes yet for this client.<br>
                <?php if (staff_can('client_edit', 'crm') || staff_can('client_create', 'crm')): ?>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#addClientNoteModal">Add the first note</a>
                <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

    </main>
</div>

<?php $CI->load->view('clients/modals/add_note_modal', ['client' => $client]); ?>

<script>
(function () {
    var filtersEl = document.getElementById('ntFilters');
    var listEl    = document.getElementById('ntNotesList');
    if (!filtersEl || !listEl) return;

    filtersEl.addEventListener('click', function (e) {
        var btn = e.target.closest('.nt-filter-btn');
        if (!btn) return;

        // Toggle active
        filtersEl.querySelectorAll('.nt-filter-btn').forEach(function (b) {
            b.classList.remove('active');
        });
        btn.classList.add('active');

        var filter = btn.dataset.filter;
        listEl.querySelectorAll('.nt-note').forEach(function (card) {
            if (filter === 'all' || card.dataset.visibility === filter) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
})();
</script>