<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">

    <!-- HEADER -->
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
        <h1 class="h6 header-title"><?= html_escape($page_title); ?></h1>

        <div class="d-flex align-items-center gap-2">

            <button class="btn btn-primary btn-header"
                    data-bs-toggle="modal"
                    data-bs-target="#addDoLaterModal">
                <i class="ti ti-plus me-1"></i> Add Task
            </button>

            <div class="btn-divider"></div>

            <div class="input-group input-group-sm" style="width:200px">
                <input type="text"
                       class="form-control dynamic-search-input"
                       placeholder="Search..."
                       data-table-target="dataTable">
            </div>
        </div>
    </div>

    <!-- TABLE -->
    <div class="card">
        <div class="card-body table-responsive">

            <table class="table table-sm align-middle table-hover" id="dataTable">
                <thead class="bg-light-primary">
                    <tr>
                        <th style="width:60px">ID</th>
                        <th style="width:110px">Type</th>
                        <th>Reference / Instructions</th>
                        <th style="width:160px">Status</th>
                        <th style="width:100px">Priority</th>
                        <th class="text-end" style="width:140px">Actions</th>
                    </tr>
                </thead>
                <tbody>

                <?php foreach ($tasks as $t): ?>
                    <tr>
                        <td><?= (int)$t['id']; ?></td>

                        <td>
                            <span class="badge bg-primary">
                                <?= strtoupper(html_escape($t['type'])); ?>
                            </span>
                        </td>

                        <td class="text-muted small">
                            <?= html_escape($t['reference'] ?: '-'); ?>
                        </td>

                        <td class="small app-form form-select-sm p-0">
                            <select class="form-select form-select-sm change-status app-form small py-1"
                                    data-id="<?= (int)$t['id']; ?>">
                                <?php foreach (['pending','in_process','completed','needs_review','blocked','obsolete'] as $s): ?>
                                    <option value="<?= $s; ?>" <?= $t['status'] === $s ? 'selected' : ''; ?> class="small">
                                        <?= ucfirst(str_replace('_',' ',$s)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>

                        <td>
                            <span class="badge bg-light-danger">
                                <?= ucfirst($t['priority']); ?>
                            </span>
                        </td>

                        <td class="text-end">
                            <div class="btn-group btn-group-sm">

                                <button class="btn btn-light-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#viewCodeModal<?= $t['id']; ?>"
                                        title="View Code">
                                    <i class="ti ti-code"></i>
                                </button>

                                <button class="btn btn-light-warning"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editDoLaterModal<?= $t['id']; ?>"
                                        title="Edit Task">
                                    <i class="ti ti-edit"></i>
                                </button>

                                <a href="<?= site_url('do_later/delete/'.$t['id']); ?>"
                                   class="btn btn-light-danger"
                                   onclick="return confirm('Delete this task?')"
                                   title="Delete">
                                    <i class="ti ti-trash"></i>
                                </a>

                            </div>
                        </td>
                    </tr>

                    <!-- VIEW CODE MODAL -->
                    <div class="modal fade" id="viewCodeModal<?= $t['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">

                                <div class="modal-header">
                                    <h6 class="modal-title">
                                        <i class="ti ti-code me-1"></i> Task Code
                                    </h6>

                                    <div class="d-flex gap-2">
                                        <button type="button"
                                                class="btn btn-sm btn-light-secondary toggle-code-theme"
                                                data-target="codeBlock<?= $t['id']; ?>">
                                            <i class="ti ti-adjustments"></i>
                                        </button>

                                        <button type="button"
                                                class="btn btn-sm btn-light-primary copy-code"
                                                data-target="codeBlock<?= $t['id']; ?>">
                                            <i class="ti ti-copy"></i>
                                        </button>

                                        <button class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                </div>

                                <div class="modal-body">
                                    <pre id="codeBlock<?= $t['id']; ?>"
                                         class="p-3 rounded bg-dark text-light"
                                         style="font-size:13px; white-space:pre-wrap;">
<?= html_escape($t['code']); ?>
                                    </pre>
                                </div>

                            </div>
                        </div>
                    </div>


                <?php endforeach; ?>
                </tbody>
            </table>

        </div>
    </div>

</div>

<?php foreach ($tasks as $t): ?>

<!-- EDIT MODAL -->
<div class="modal fade"
     id="editDoLaterModal<?= $t['id']; ?>"
     tabindex="-1"
     aria-hidden="true"
     data-bs-backdrop="static"
     data-bs-keyboard="false">

    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="post"
              action="<?= site_url('do_later/update/'.$t['id']); ?>"
              class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-edit me-1"></i> Edit Task
                </h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

                                <div class="modal-body">
                                    <div class="row g-3">

                                        <div class="col-md-4">
                                            <label class="form-label">Type</label>
                                            <select name="type" class="form-select">
                                                <?php foreach (['controller','model','view','helper','library','module','css','js','assets','sql','config','other'] as $type): ?>
                                                    <option value="<?= $type; ?>" <?= $t['type'] === $type ? 'selected' : ''; ?>>
                                                        <?= ucfirst($type); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Priority</label>
                                            <select name="priority" class="form-select">
                                                <?php foreach (['low','medium','high','critical'] as $p): ?>
                                                    <option value="<?= $p; ?>" <?= $t['priority'] === $p ? 'selected' : ''; ?>>
                                                        <?= ucfirst($p); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-select">
                                                <?php foreach (['pending','in_process','completed','needs_review','blocked','obsolete'] as $s): ?>
                                                    <option value="<?= $s; ?>" <?= $t['status'] === $s ? 'selected' : ''; ?>>
                                                        <?= ucfirst(str_replace('_',' ',$s)); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Reference</label>
                                            <input type="text"
                                                   name="reference"
                                                   value="<?= html_escape($t['reference']); ?>"
                                                   class="form-control">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Code</label>
                                            <textarea name="code"
                                                      rows="14"
                                                      class="form-control font-monospace"><?= html_escape($t['code']); ?></textarea>
                                        </div>

                                    </div>
                                </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i> Update
                </button>
            </div>

        </form>
    </div>
</div>

<?php endforeach; ?>

<div class="modal fade" id="addDoLaterModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="post" action="<?= site_url('do_later/store'); ?>" class="modal-content app-form">

            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">
                    <i class="ti ti-clock-edit me-1"></i> Add Do-Later Task
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="row g-3">

                    <!-- TYPE -->
                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="">Select type</option>
                            <option value="controller">Controller</option>
                            <option value="model">Model</option>
                            <option value="view">View</option>
                            <option value="helper">Helper</option>
                            <option value="library">Library</option>
                            <option value="module">Module</option>
                            <option value="css">CSS</option>
                            <option value="js">JavaScript</option>
                            <option value="assets">Assets</option>
                            <option value="sql">SQL</option>
                            <option value="config">Config</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <!-- PRIORITY -->
                    <div class="col-md-4">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>

                    <!-- STATUS -->
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="pending" selected>Pending</option>
                            <option value="in_process">In Process</option>
                            <option value="needs_review">Needs Review</option>
                            <option value="blocked">Blocked</option>
                            <option value="completed">Completed</option>
                            <option value="obsolete">Obsolete</option>
                        </select>
                    </div>

                    <!-- REFERENCE -->
                    <div class="col-12">
                        <label class="form-label">Reference (optional)</label>
                        <input type="text"
                               name="reference"
                               class="form-control"
                               placeholder="e.g. modules/hrm/controllers/Payroll.php">
                    </div>

                    <!-- CODE -->
                    <div class="col-12">
                        <label class="form-label">
                            Code / Notes
                            <span class="text-muted small">(stored as-is)</span>
                        </label>
                        <textarea name="code"
                                  rows="14"
                                  class="form-control font-monospace"
                                  placeholder="Paste full PHP / JS / HTML / SQL code here..."
                                  required></textarea>
                    </div>

                </div>

            </div>

            <div class="modal-footer">
                <button type="button"
                        class="btn btn-light-primary btn-sm"
                        data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="submit"
                        class="btn btn-primary btn-sm">
                    <i class="ti ti-device-floppy me-1"></i> Save Task
                </button>
            </div>

        </form>
    </div>
</div>

<script>
/* Status Change */
document.querySelectorAll('.change-status').forEach(el => {
    el.addEventListener('change', function () {
        fetch("<?= site_url('do_later/update_status'); ?>/" + this.dataset.id, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'status=' + this.value
        });
    });
});

/* Copy Code */
document.querySelectorAll('.copy-code').forEach(btn => {
    btn.addEventListener('click', function () {
        const el = document.getElementById(this.dataset.target);
        navigator.clipboard.writeText(el.innerText);
    });
});

/* Toggle Code Theme */
document.querySelectorAll('.toggle-code-theme').forEach(btn => {
    btn.addEventListener('click', function () {
        const el = document.getElementById(this.dataset.target);
        el.classList.toggle('bg-dark');
        el.classList.toggle('text-light');
        el.classList.toggle('bg-light');
        el.classList.toggle('text-dark');
    });
});
</script>