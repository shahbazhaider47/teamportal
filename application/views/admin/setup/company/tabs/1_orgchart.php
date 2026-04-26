<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
// --------------------------------------------------
// DATA PREP
// --------------------------------------------------
$activeUsers = array_filter($users ?? [], fn($u) => !empty($u['is_active']));
$totalStaff  = count($activeUsers);

// Group users by department
$usersByDept = [];
foreach ($activeUsers as $u) {
    $deptId = (int)($u['emp_department'] ?? 0);
    $usersByDept[$deptId][] = $u;
}

// Company logo
$companyLogo = !empty($company['light_logo']) && file_exists(FCPATH.'uploads/company/'.$company['light_logo'])
    ? base_url('uploads/company/'.$company['light_logo'])
    : base_url('uploads/company/default.png');

// Initials helper
function user_initials(array $u): string
{
    $name = trim($u['fullname'] ?? ($u['firstname'].' '.$u['lastname']));
    $parts = preg_split('/\s+/', $name);
    return strtoupper(
        mb_substr($parts[0] ?? 'U', 0, 1) .
        mb_substr($parts[1] ?? 'N', 0, 1)
    );
}
?>

<style>
/* ==========================================================
 | PURE HTML ORG CHART (OL / LI BASED)
 ========================================================== */

.org-container {
    overflow-x: auto;
    padding: 20px;
}

/* Reset */
.org-container ol {
    list-style: none;
    padding-left: 0;
    margin: 0;
}

/* Common box */
.rectangle {
    background: #fff;
    border: 1px solid #dee2e6;
    padding: 10px 16px;
    border-radius: 10px;
    display: inline-block;
    font-weight: 600;
    text-align: center;
    min-width: 220px;
    box-shadow: 0 6px 18px rgba(0,0,0,.06);
}

/* Levels */
.level-1 {
    font-size: 16px;
    background: #eef2ff;
    border-color: #c7d2fe;
}

.level-2 {
    font-size: 14px;
    background: #f8f9fa;
}

.level-3 {
    font-size: 13px;
}

/* Wrappers */
.level-2-wrapper,
.level-3-wrapper {
    display: flex;
    justify-content: center;
    gap: 40px;
    margin-top: 40px;
    position: relative;
}

/* Vertical connector */
.level-2-wrapper::before,
.level-3-wrapper::before {
    content: '';
    position: absolute;
    top: -20px;
    left: 50%;
    width: 1px;
    height: 20px;
    background: #adb5bd;
}

/* Horizontal connectors */
.level-2-wrapper > li::before,
.level-3-wrapper > li::before {
    content: '';
    position: absolute;
    top: -20px;
    left: 50%;
    width: 1px;
    height: 20px;
    background: #adb5bd;
}

/* Department toggle */
.dept-toggle {
    cursor: pointer;
}

.dept-users {
    display: none;
}

.dept-users.active {
    display: flex;
}
</style>

<!-- ==========================================================
 | ORG CHART
 ========================================================== -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-light-primary">
        <h6 class="card-title text-primary mb-0">
            <i class="ti ti-users me-2"></i> Organizational Chart
        </h6>
    </div>

    <div class="card-body">
        <div class="org-container">

            <!-- LEVEL 1 : COMPANY -->
            <div class="text-center">
                <img src="<?= $companyLogo ?>" class="rounded-circle mb-2" width="70" height="70" alt="">
                <h1 class="level-1 rectangle">
                    <?= html_escape($company['company_name'] ?? 'Company') ?><br>
                    <small class="text-muted fw-normal"><?= $totalStaff ?> Active Staff</small>
                </h1>
            </div>

            <!-- LEVEL 2 : DEPARTMENTS -->
            <ol class="level-2-wrapper">

                <?php foreach ($departments as $d): 
                    $deptUsers = $usersByDept[$d['id']] ?? [];
                ?>
                <li>

                    <!-- Department -->
                    <h2 class="level-2 rectangle dept-toggle"
                        onclick="toggleDept(<?= (int)$d['id'] ?>)">
                        <?= html_escape($d['name']) ?><br>
                        <small class="text-muted fw-normal">
                            <?= count($deptUsers) ?> Staff
                        </small>
                    </h2>

                    <!-- LEVEL 3 : USERS -->
                    <ol class="level-3-wrapper dept-users"
                        id="dept-<?= (int)$d['id'] ?>">

                        <?php if (empty($deptUsers)): ?>
                            <li>
                                <h3 class="level-3 rectangle text-muted">
                                    No Staff Assigned
                                </h3>
                            </li>
                        <?php else: ?>
                            <?php foreach ($deptUsers as $u): ?>
                                <li>
                                    <h3 class="level-3 rectangle">
                                        <?= html_escape($u['fullname']) ?><br>
                                        <small class="text-muted fw-normal">
                                            <?= html_escape($u['emp_title'] ?? '—') ?>
                                        </small>
                                    </h3>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </ol>
                </li>
                <?php endforeach; ?>

            </ol>
        </div>
    </div>
</div>

<script>
function toggleDept(id) {
    const el = document.getElementById('dept-' + id);
    if (!el) return;
    el.classList.toggle('active');
}
</script>
