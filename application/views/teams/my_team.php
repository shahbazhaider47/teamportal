<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$table_id     = $table_id ?? 'dataTable';
/* ═══════════════════════════════════════════════════════════════
 *  PREP — all PHP logic before HTML
 * ═══════════════════════════════════════════════════════════════ */
$viewMode    = $viewMode    ?? 'own';
$scopeLabel  = $scopeLabel  ?? 'Your team members';
$teamUsers   = $teamUsers   ?? [];
$currentUser = (int)($currentUser ?? 0);
$myRole      = strtolower($myRole ?? 'employee');

/* ── Role badge map ─────────────────────────────────────────── */
$roleMeta = [
    'superadmin' => ['label' => 'Superadmin', 'cls' => 'tm-b-purple'],
    'director'   => ['label' => 'Director',   'cls' => 'tm-b-blue'],
    'manager'    => ['label' => 'Manager',     'cls' => 'tm-b-teal'],
    'teamlead'   => ['label' => 'Team Lead',   'cls' => 'tm-b-blue'],
    'employee'   => ['label' => 'Employee',    'cls' => 'tm-b-gray'],
];
$getRoleMeta = function(string $role) use ($roleMeta): array {
    return $roleMeta[strtolower(trim($role))] ?? ['label' => ucwords($role), 'cls' => 'tm-b-gray'];
};

/* ── Avatar HTML helper ─────────────────────────────────────── */
$mkAvatar = function(array $u, int $sz = 30, string $xCls = '') : string {
    $first    = trim($u['first_name'] ?? $u['firstname'] ?? '');
    $last     = trim($u['last_name']  ?? $u['lastname']  ?? '');
    $name     = trim("$first $last") ?: 'U';
    $file     = trim($u['profile_image'] ?? '');
    $initials = strtoupper(substr($first,0,1).substr($last,0,1)) ?: 'U';
    $fs       = max(10, (int)($sz * 0.36));
    $style    = "width:{$sz}px;height:{$sz}px;font-size:{$fs}px;flex-shrink:0;";
    if ($file !== '') {
        $src = base_url('uploads/users/profile/' . $file);
        $fb  = base_url('assets/images/default-avatar.png');
        return '<img src="'.html_escape($src).'" alt="'.html_escape($name).'" '
             . 'class="tm-av tm-av-img '.$xCls.'" style="'.$style.'" loading="lazy" '
             . 'onerror="this.onerror=null;this.src=\''.html_escape($fb).'\'">';
    }
    return '<span class="tm-av tm-av-init '.$xCls.'" style="'.$style.'">'
         . html_escape($initials).'</span>';
};

/* ── Build groups ───────────────────────────────────────────── */
// Each group: [label, sublabel, lead|null, members[]]
$groups = [];
if ($viewMode === 'global') {
    $tree = [];
    foreach ($teamUsers as $u) {
        $tree[$u['department_name'] ?? 'No Department'][$u['team_name'] ?? 'Unassigned'][] = $u;
    }
    foreach ($tree as $dept => $teams) {
        foreach ($teams as $team => $mbs) {
            $lead = null; $reg = [];
            foreach ($mbs as $m) { strtolower($m['user_role']??'') === 'teamlead' ? $lead=$m : $reg[]=$m; }
            $groups[] = ['label'=>$team,'sublabel'=>$dept,'lead'=>$lead,'members'=>$reg];
        }
    }
} elseif ($viewMode === 'dept') {
    $tree = [];
    foreach ($teamUsers as $u) { $tree[$u['team_name'] ?? 'Unassigned'][] = $u; }
    foreach ($tree as $team => $mbs) {
        $lead = null; $reg = [];
        foreach ($mbs as $m) { strtolower($m['user_role']??'') === 'teamlead' ? $lead=$m : $reg[]=$m; }
        $groups[] = ['label'=>$team,'sublabel'=>null,'lead'=>$lead,'members'=>$reg];
    }
} else {
    $lead = null; $reg = [];
    foreach ($teamUsers as $u) { strtolower($u['user_role']??'') === 'teamlead' ? $lead=$u : $reg[]=$u; }
    $groups[] = ['label'=>$teamName??'My Team','sublabel'=>null,'lead'=>$lead,'members'=>$reg];
}

/* ── Stats ──────────────────────────────────────────────────── */
$totalMembers   = count($teamUsers);
$totalLeads     = count(array_filter($groups, fn($g) => !empty($g['lead'])));
$totalEmployees = array_sum(array_map(fn($g) => count($g['members']), $groups));
$noReporting    = count(array_filter($teamUsers, fn($u) => empty($u['reporting_name'])));

/* ── Pre-serialise groups to JSON for JS ────────────────────── */
// Build a safe JSON payload — only the fields the JS needs
$jsGroups = [];
foreach ($groups as $g) {
    $mapUser = function(array $u, bool $isLead = false) use ($mkAvatar, $getRoleMeta, $currentUser): array {
        $first  = trim($u['first_name'] ?? $u['firstname'] ?? '');
        $last   = trim($u['last_name']  ?? $u['lastname']  ?? '');
        $role   = $u['user_role'] ?? 'employee';
        $meta   = $getRoleMeta($role);
        return [
            'id'        => (int)$u['id'],
            'empId'     => $u['emp_id'] ?? '',
            'name'      => trim("$first $last"),
            'email'     => $u['email'] ?? '',
            'title'     => $u['emp_title'] ?? $u['emp_title_name'] ?? '',
            'role'      => $role,
            'roleLabel' => $meta['label'],
            'roleCls'   => $meta['cls'],
            'reports'   => $u['reporting_name'] ?? null,
            'avatar'    => $mkAvatar($u, 28),
            'avatarLg'  => $mkAvatar($u, 36),
            'isSelf'    => ((int)$u['id'] === $currentUser),
            'isLead'    => $isLead,
            'progUrl'   => site_url('teams/member_progress/' . (int)$u['id']),
        ];
    };

    $jsGroups[] = [
        'label'    => $g['label'],
        'sublabel' => $g['sublabel'],
        'lead'     => $g['lead'] ? $mapUser($g['lead'], true) : null,
        'members'  => array_map(fn($m) => $mapUser($m), $g['members']),
    ];
}
?>

<style>

/* Badges */
.tm-badge{display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:600;
  padding:2px 8px;border-radius:20px;white-space:nowrap}
.tm-b-blue{background:#E6F1FB;color:#185FA5}
.tm-b-teal{background:#E1F5EE;color:#0F6E56}
.tm-b-purple{background:#EEEDFE;color:#534AB7}
.tm-b-amber{background:#FAEEDA;color:#854F0B}
.tm-b-gray{background:#F1EFE8;color:#5F5E5A}
.tm-b-scope{background:#F1EFE8;color:#5F5E5A;display:inline-flex;align-items:center;gap:4px}

/* Team cards grid */
.tm-grid{display:grid;gap:10px;grid-template-columns:repeat(auto-fill,minmax(200px,1fr))}
/* Team card */
.tm-card{background:#fff;border:0.5px solid rgba(0,0,0,.1);border-radius:12px;overflow:hidden;
  cursor:pointer;transition:border-color .15s,box-shadow .15s;user-select:none}
.tm-card:hover{border-color:#185FA5}
.tm-card.tm-active{border:1.5px solid #185FA5}
.tm-card-top{padding:14px 14px 10px}
.tm-card-icon{width:36px;height:36px;border-radius:9px;background:#E6F1FB;
  display:flex;align-items:center;justify-content:center;margin-bottom:10px}
.tm-card-icon svg{width:18px;height:18px}
.tm-card-name{font-size:13px;font-weight:600;color:#1a1a18;margin-bottom:2px}
.tm-card-dept{font-size:11px;color:#8a8a86}
.tm-card-foot{display:flex;align-items:center;justify-content:space-between;
  padding:8px 14px;border-top:0.5px solid rgba(0,0,0,.07);background:#fafaf9}
/* Avatar stack */
.tm-avstack{display:flex;align-items:center}
.tm-avstack .tm-av{width:22px!important;height:22px!important;font-size:9px!important;
  border:1.5px solid #fff;margin-left:-5px}
.tm-avstack .tm-av:first-child{margin-left:0}
.tm-overflow-av{width:22px;height:22px;border-radius:50%;background:#F1EFE8;color:#5F5E5A;
  font-size:9px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;
  border:1.5px solid #fff;margin-left:-5px}
.tm-lead-pill{display:flex;align-items:center;gap:5px;font-size:11px;color:#185FA5;font-weight:500}
.tm-lead-dot{width:20px;height:20px;border-radius:50%;background:#185FA5;color:#fff;
  display:inline-flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;flex-shrink:0}
/* Member panel */
.tm-panel{background:#fff;border:0.5px solid rgba(0,0,0,.1);border-radius:12px;overflow:hidden}
.tm-panel-head{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;
  padding:10px 16px;background:#f5f5f3;border-bottom:0.5px solid rgba(0,0,0,.08)}
.tm-panel-title{font-size:13px;font-weight:600;color:#1a1a18}
/* Lead strip */
.tm-lead-strip{display:flex;align-items:center;gap:10px;padding:11px 16px;
  background:#EEF5FC;border-bottom:0.5px solid #B5D4F4}
.tm-lead-crown{width:22px;height:22px;border-radius:50%;background:#185FA5;
  display:flex;align-items:center;justify-content:center;flex-shrink:0}
.tm-lead-crown svg{width:11px;height:11px;fill:#fff}
.tm-lead-name{font-size:13px;font-weight:600;color:#0C447C}
.tm-lead-meta{font-size:10px;color:#185FA5;margin-top:1px}
.tm-lead-lbl{font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
  color:#185FA5;background:#D0E7F8;padding:2px 7px;border-radius:20px;flex-shrink:0}
.tm-lead-reports{font-size:11px;color:#185FA5;flex-shrink:0;margin-left:auto;text-align:right}
.tm-lead-reports small{display:block;font-size:9px;text-transform:uppercase;
  letter-spacing:.04em;color:#8a8a86;margin-bottom:1px}
/* Column header */
.tm-col-h{display:flex;align-items:center;padding:6px 16px;
  background:#fafaf9;border-bottom:0.5px solid rgba(0,0,0,.07)}
.tm-col-h span{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#8a8a86}
/* Column widths */
.tm-c-id{width:72px;flex-shrink:0}
.tm-c-mb{flex:1;min-width:0;padding-right:10px}
.tm-c-ti{width:145px;flex-shrink:0;padding-right:8px}
.tm-c-ro{width:100px;flex-shrink:0;padding-right:8px}
.tm-c-re{width:120px;flex-shrink:0;padding-right:8px}
.tm-c-ac{width:86px;flex-shrink:0;display:flex;justify-content:flex-end}
/* Member row */
.tm-row{display:flex;align-items:center;padding:9px 16px;
  border-bottom:0.5px solid rgba(0,0,0,.06);transition:background .1s}
.tm-row:last-child{border-bottom:none}
.tm-row:hover{background:#fafaf9}
.tm-empid{font-size:11px;color:#8a8a86;font-family:monospace}
.tm-mname{font-size:13px;font-weight:500;color:#1a1a18;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tm-memail{font-size:10px;color:#8a8a86;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tm-mtitle{font-size:11px;color:#8a8a86}
.tm-you{font-size:9px;background:#E6F1FB;color:#185FA5;padding:1px 5px;
  border-radius:10px;font-weight:700;margin-left:4px;vertical-align:middle}
.tm-warn{color:#854F0B!important}
/* Progress button */
.tm-pbtn{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:500;
  padding:4px 10px;border-radius:7px;border:0.5px solid rgba(0,0,0,.18);
  background:transparent;cursor:pointer;color:#185FA5;text-decoration:none;
  white-space:nowrap;transition:background .12s,border-color .12s}
.tm-pbtn:hover{background:#E6F1FB;border-color:#185FA5;color:#185FA5;text-decoration:none}
.tm-pbtn.tm-pbtn-lead{background:#185FA5;color:#fff;border-color:#185FA5}
.tm-pbtn.tm-pbtn-lead:hover{background:#0C447C;border-color:#0C447C;color:#fff}
.tm-pbtn svg{width:11px;height:11px;flex-shrink:0}
/* Avatar base */
.tm-av{border-radius:50%;object-fit:cover;display:inline-flex;align-items:center;
  justify-content:center;font-weight:700;vertical-align:middle}
.tm-av-img{border:0}
.tm-av-init{background:#E6F1FB;color:#185FA5}
/* Empty */
.tm-empty{padding:48px 20px;text-align:center}
.tm-empty svg{width:44px;height:44px;color:#c8c8c4;display:block;margin:0 auto 14px}
.tm-empty p{font-size:14px;font-weight:500;color:#8a8a86;margin-bottom:4px}
.tm-empty small{font-size:12px;color:#b0b0ac}
/* No-lead notice */
.tm-no-lead{padding:10px 16px;font-size:12px;color:#854F0B;background:#FAEEDA;
  border-bottom:0.5px solid #FAC775;display:flex;align-items:center;gap:6px}
/* Loading shimmer */
@keyframes tm-shimmer{0%{opacity:.5}50%{opacity:1}100%{opacity:.5}}
.tm-loading{padding:32px 16px;text-align:center;font-size:12px;color:#8a8a86;
  animation:tm-shimmer 1.2s ease-in-out infinite}
</style>

<div class="container-fluid tm-wrap">

  <div class="view-header mb-3">
    <div class="view-icon me-3"><i class="fa-solid fa-users fa-fw"></i></div>
    <div class="flex-grow-1">
      <div class="view-title"><?= $page_title ?>
      <?php if (!empty($teamName) && $viewMode === 'own'): ?>
        <span class="view-title">— <?= html_escape($teamName) ?></span>
      <?php endif; ?>

        <span class="badge bg-light-success me-2"><?= count($groups) ?> Team<?= count($groups) !== 1 ? 's' : '' ?></span>
        <span class="badge bg-light-info me-2"><?= $totalMembers ?> Member<?= $totalMembers !== 1 ? 's' : '' ?></span>
        <div class="view-sub">All teams in your department</div>
      </div>
    </div>

    <div class="ms-auto d-flex gap-2">

      <a href="<?= site_url('teams/instructions') ?>" class="btn btn-primary btn-header">
        <i class="ti ti-pinned-filled me-1"></i> Instructions
      </a>

      <a href="<?= site_url('teams/rankings') ?>"
         class="btn btn-header btn-outline-primary">
        <i class="ti ti-trophy me-1"></i> Rankings
      </a>

      <?php
        // Show Team Progress button for own-team view where we know the team ID
        $currentTeamId = $teamUsers[0]['team_id'] ?? $teamUsers[0]['emp_team'] ?? 0;
        if ($viewMode === 'own' && $currentTeamId > 0):
      ?>
      <a href="<?= site_url('teams/team_progress/' . (int)$currentTeamId) ?>"
         class="btn btn-header btn-outline-success">
        <i class="ti ti-chart-bar me-1"></i> Team Progress
      </a>
      <?php endif; ?>

    <div class="btn-divider mt-1"></div>
    
        
        <?php render_export_buttons([
            'filename' => $page_title ?? 'export'
        ]); ?>
            
    </div>
  </div>

    <div class="collapse multi-collapse" id="showFilter">
        <div class="card">
            <div class="card-body">    
            <?php if (function_exists('app_table_filter')): ?>
                <?php app_table_filter($table_id, [
                    'exclude_columns' => ['EMP ID', 'Current Salary', 'Date of Joining'],
                ]);
                ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="row g-2 mb-3">
    
    <?php
    $team_metrics = [
        [
            'Total Members',
            $totalMembers ?? 0,
            'ti ti-users',
            '#6366f118',
            'Active & assigned'
        ],
    
        $viewMode === 'own' && !empty($teamLeadName)
            ? [
                'Team Lead',
                html_escape($teamLeadName),
                'ti ti-user-star',
                '#0ea5e918',
                html_escape($teamName ?? '')
            ]
            : [
                'Teams',
                count($groups),
                'ti ti-sitemap',
                '#8b5cf618',
                $viewMode === 'global' ? 'Across all departments' : 'In your department'
            ],
    
        [
            'Employees',
            $totalEmployees ?? 0,
            'ti ti-id-badge',
            '#16a34a18',
            'Reporting to leads'
        ],
    
        [
            'No Reporting Set',
            $noReporting ?? 0,
            'ti ti-alert-circle',
            $noReporting > 0 ? '#ef444418' : '#22c55e18',
            $noReporting > 0 ? 'Needs attention' : 'All assigned'
        ],
    ];
    ?>
    
    <?php foreach ($team_metrics as $m): ?>
    <div class="col">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:<?= $m[3] ?>;">
                <i class="<?= $m[2] ?>"></i>
            </div>
            <div>
                <div class="kpi-value <?= ($m[0] === 'No Reporting Set' && $noReporting > 0) ? 'warn' : '' ?>">
                    <?= $m[1] ?>
                </div>
                <div class="kpi-label"><?= $m[0] ?></div>
                <div class="kpi-sub small"><?= $m[4] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    </div>

  <!-- ══ EMPTY STATE ══════════════════════════════════════════ -->
  <?php if (empty($teamUsers)): ?>
    <div class="tm-panel">
      <div class="tm-empty">
        <svg viewBox="0 0 48 48" fill="none">
          <circle cx="19" cy="16" r="7" stroke="currentColor" stroke-width="2"/>
          <circle cx="34" cy="18" r="5.5" stroke="currentColor" stroke-width="2"/>
          <path d="M4 38c0-6.627 6.716-12 15-12s15 5.373 15 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <path d="M37 32c4 1.5 7 4.5 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <p><?= ($has_team ?? false) ? 'No active members in your team' : 'You are not assigned to any team' ?></p>
        <small>Contact your administrator to get assigned.</small>
      </div>
    </div>
  <?php else: ?>
  
    <!-- ══ TEAM CARDS GRID (only when multiple groups) ════════ -->
    <?php if (count($groups) > 1): ?>
    <div class="tm-grid mb-4" id="tmGrid">
      <?php foreach ($groups as $gi => $group):
        $allCount = count($group['members']) + ($group['lead'] ? 1 : 0);
        $leadFirst = $group['lead'] ? trim(($group['lead']['first_name'] ?? $group['lead']['firstname'] ?? '') . ' ' . ($group['lead']['last_name'] ?? $group['lead']['lastname'] ?? '')) : null;
        // Build initials list for avatar stack (max 3 + overflow)
        $stackUsers = array_merge($group['lead'] ? [$group['lead']] : [], array_slice($group['members'], 0, 3));
        $overflow   = max(0, $allCount - 3);
      ?>
      <div class="tm-card <?= $gi === 0 ? 'tm-active' : '' ?>"
           id="tmCard<?= $gi ?>"
           onclick="tmShowGroup(<?= $gi ?>)"
           role="button" tabindex="0"
           onkeydown="if(event.key==='Enter'||event.key===' ')tmShowGroup(<?= $gi ?>)">
        <div class="tm-card-top">
          <div class="tm-card-icon">
            <svg viewBox="0 0 18 18" fill="none">
              <circle cx="7" cy="6" r="3" stroke="#185FA5" stroke-width="1.4"/>
              <circle cx="13" cy="7" r="2.2" stroke="#185FA5" stroke-width="1.3"/>
              <path d="M1 15c0-2.5 2.7-4.5 6-4.5S13 12.5 13 15" stroke="#185FA5" stroke-width="1.4" stroke-linecap="round"/>
              <path d="M16 15c0-1.5-1.3-2.8-3-3" stroke="#185FA5" stroke-width="1.3" stroke-linecap="round"/>
            </svg>
          </div>
          <div class="tm-card-name"><?= html_escape($group['label']) ?></div>
          <?php if (!empty($group['sublabel'])): ?>
            <div class="tm-card-dept"><?= html_escape($group['sublabel']) ?></div>
          <?php endif; ?>
        </div>
<div class="tm-card-foot" style="flex-direction:column;gap:8px;align-items:stretch;">
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:7px">
              <div class="tm-avstack">
                <?php foreach ($stackUsers as $su):
                  echo $mkAvatar($su, 22);
                endforeach; ?>
                <?php if ($overflow > 0): ?>
                  <span class="tm-overflow-av">+<?= $overflow ?></span>
                <?php endif; ?>
              </div>
              <span style="font-size:11px;color:#8a8a86"><?= $allCount ?></span>
            </div>
            <?php if ($leadFirst): ?>
              <div class="tm-lead-pill">
                <span class="tm-lead-dot"><?= strtoupper(substr($leadFirst,0,1)) ?></span>
                <?= html_escape(explode(' ', $leadFirst)[0]) ?>
              </div>
            <?php else: ?>
              <span style="font-size:11px;color:#b0b0ac;font-style:italic">No lead</span>
            <?php endif; ?>
          </div>
          <?php
            // Get the team_id for this group from the first member
            $grpTeamId = 0;
            $allGrpUsers = array_merge(
                $group['lead'] ? [$group['lead']] : [],
                $group['members']
            );
            foreach ($allGrpUsers as $gu) {
                $grpTeamId = (int)($gu['team_id'] ?? $gu['emp_team'] ?? 0);
                if ($grpTeamId > 0) break;
            }
            if ($grpTeamId > 0):
          ?>
          <a href="<?= site_url('teams/team_progress/' . $grpTeamId) ?>"
             class="tm-pbtn"
             style="width:100%;justify-content:center;font-size:11px;"
             onclick="event.stopPropagation()">
            <svg width="11" height="11" viewBox="0 0 16 16" fill="none">
              <rect x="2" y="10" width="3" height="4" rx="1" stroke="currentColor" stroke-width="1.4"/>
              <rect x="6.5" y="6" width="3" height="8" rx="1" stroke="currentColor" stroke-width="1.4"/>
              <rect x="11" y="2" width="3" height="12" rx="1" stroke="currentColor" stroke-width="1.4"/>
            </svg>
            Team Progress
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ══ MEMBER PANEL ══════════════════════════════════════ -->
    <div class="tm-panel" id="tmPanel">

      <!-- Panel header -->
      <div class="tm-panel-head">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
            <circle cx="6" cy="5" r="2.5" stroke="#185FA5" stroke-width="1.4"/>
            <circle cx="11.5" cy="6" r="2" stroke="#185FA5" stroke-width="1.3"/>
            <path d="M1 13c0-2.21 2.24-4 5-4s5 1.79 5 4" stroke="#185FA5" stroke-width="1.4" stroke-linecap="round"/>
            <path d="M15 13c0-1.66-1.34-3-3-3" stroke="#185FA5" stroke-width="1.3" stroke-linecap="round"/>
          </svg>
          <span class="tm-panel-title" id="tmPanelTitle"></span>
          <span class="tm-badge tm-b-blue" id="tmPanelCount"></span>
          <span class="tm-badge tm-b-gray" id="tmPanelSublabel" style="display:none"></span>
        </div>
      </div>

      <!-- Dynamic body — rendered by JS -->
      <div id="tmPanelBody"></div>

    </div>

  <?php endif; ?>

</div>

<!-- ══ JSON DATA ISLAND ══════════════════════════════════════════ -->
<script id="tmGroupData" type="application/json">
<?= json_encode($jsGroups, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>
</script>

<script>
(function () {
  const GROUPS   = JSON.parse(document.getElementById('tmGroupData').textContent);
  let   activeIdx = 0;

  /* ── Icons (inline SVG strings) ── */
  const icoChart = '<svg width="11" height="11" viewBox="0 0 16 16" fill="none">'
      + '<rect x="2" y="10" width="3" height="4" rx="1" stroke="currentColor" stroke-width="1.4"/>'
      + '<rect x="6.5" y="6" width="3" height="8" rx="1" stroke="currentColor" stroke-width="1.4"/>'
      + '<rect x="11" y="2" width="3" height="12" rx="1" stroke="currentColor" stroke-width="1.4"/>'
      + '</svg>';

  const icoCrown = '<svg width="11" height="11" viewBox="0 0 16 16" fill="#fff">'
      + '<path d="M2 12h12L15 5l-4 3-3-5-3 5-4-3z"/>'
      + '</svg>';

  /* ── Escape helper ── */
  function esc(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;')
                        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  /* ── Render panel for group index ── */
  function renderGroup(idx) {
    const g    = GROUPS[idx];
    const body = document.getElementById('tmPanelBody');
    const all  = (g.lead ? [g.lead] : []).concat(g.members);

    // Panel header
    document.getElementById('tmPanelTitle').textContent = g.label;
    document.getElementById('tmPanelCount').textContent =
        all.length + ' member' + (all.length !== 1 ? 's' : '');

    const sub = document.getElementById('tmPanelSublabel');
    if (g.sublabel) { sub.textContent = g.sublabel; sub.style.display = ''; }
    else            { sub.style.display = 'none'; }

    let html = '';

    /* Lead strip */
    if (g.lead) {
      const l = g.lead;
      html += '<div class="tm-lead-strip">'
            + '<div class="tm-lead-crown">' + icoCrown + '</div>'
            + l.avatarLg
            + '<div style="flex:1;min-width:0">'
            +   '<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">'
            +     '<span class="tm-lead-name">' + esc(l.name)
            +     (l.isSelf ? '<span class="tm-you">You</span>' : '')
            +     '</span>'
            +     '<span class="tm-lead-lbl">Team Lead</span>'
            +   '</div>'
            +   '<div class="tm-lead-meta">'
            +     (l.title ? esc(l.title) + ' · ' : '')
            +     (l.empId ? '#' + esc(l.empId) : '')
            +   '</div>'
            + '</div>'
            + (l.reports
                ? '<div class="tm-lead-reports"><small>Reports to</small>' + esc(l.reports) + '</div>'
                : '')
            + '<a href="' + esc(l.progUrl) + '" class="tm-pbtn tm-pbtn-lead">'
            +   icoChart + ' Progress'
            + '</a>'
            + '</div>';
    } else {
      html += '<div class="tm-no-lead">'
            + '<svg width="13" height="13" viewBox="0 0 16 16" fill="none">'
            + '<circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.4"/>'
            + '<path d="M8 5v3M8 11v.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>'
            + '</svg> No team lead assigned for this team.</div>';
    }

    /* Column headers */
    html += '<div class="tm-col-h">'
          + '<span class="tm-c-id">Emp ID</span>'
          + '<span class="tm-c-mb">Member</span>'
          + '<span class="tm-c-ti">Designation</span>'
          + '<span class="tm-c-ro">Role</span>'
          + '<span class="tm-c-re">Reports to</span>'
          + '<span class="tm-c-ac" style="text-align:right">Action</span>'
          + '</div>';

    /* Member rows */
    if (g.members.length === 0) {
      html += '<div style="padding:24px 16px;font-size:12px;color:#8a8a86;text-align:center">'
            + 'No members besides the team lead.</div>';
    } else {
      g.members.forEach(function(m) {
        const warnRep = !m.reports;
        html += '<div class="tm-row" data-search="'
              + esc((m.name + ' ' + m.email + ' ' + m.empId + ' ' + m.title).toLowerCase()) + '">'

              + '<div class="tm-c-id"><span class="tm-empid">' + esc(m.empId || '—') + '</span></div>'

              + '<div class="tm-c-mb"><div style="display:flex;align-items:center;gap:8px">'
              +   m.avatar
              +   '<div style="min-width:0">'
              +     '<div class="tm-mname">' + esc(m.name)
              +       (m.isSelf ? '<span class="tm-you">You</span>' : '')
              +     '</div>'
              +     '<div class="tm-memail">' + esc(m.email) + '</div>'
              +   '</div>'
              + '</div></div>'

              + '<div class="tm-c-ti"><span class="tm-mtitle">' + esc(m.title || '—') + '</span></div>'

              + '<div class="tm-c-ro"><span class="tm-badge ' + esc(m.roleCls) + '">'
              +   esc(m.roleLabel) + '</span></div>'

              + '<div class="tm-c-re"><span style="font-size:11px;color:'
              +   (warnRep ? '#854F0B' : '#8a8a86') + '">'
              +   esc(m.reports || 'Not assigned') + '</span></div>'

              + '<div class="tm-c-ac" style="display:flex;gap:4px;justify-content:flex-end;">'
              +   '<a href="' + esc(m.progUrl) + '" class="tm-pbtn" title="Member Progress">'
              +     icoChart + ' Progress'
              +   '</a>'
              + '</div>'

              + '</div>';
      });
    }

    body.innerHTML = html;
  }

  /* ── Switch active card + re-render panel ── */
  window.tmShowGroup = function(idx) {
    // Deactivate old card
    const oldCard = document.getElementById('tmCard' + activeIdx);
    if (oldCard) oldCard.classList.remove('tm-active');

    // Activate new card
    activeIdx = idx;
    const newCard = document.getElementById('tmCard' + idx);
    if (newCard) newCard.classList.add('tm-active');

    renderGroup(idx);
  };

  /* ── Search filter ── */
  window.tmFilterRows = function(val) {
    const q    = val.toLowerCase().trim();
    const clr  = document.getElementById('tmSearchClear');
    if (clr) clr.style.display = q ? '' : 'none';
    document.querySelectorAll('#tmPanelBody .tm-row').forEach(function(row) {
      const haystack = row.dataset.search || '';
      row.style.display = (!q || haystack.includes(q)) ? '' : 'none';
    });
  };

  /* ── Export ── */
  window.tmExport = function() {
    const g   = GROUPS[activeIdx];
    const all = (g.lead ? [g.lead] : []).concat(g.members);
    const rows = [['Emp ID','Name','Email','Designation','Role','Reports To']];
    all.forEach(function(m) {
      rows.push([m.empId, m.name, m.email, m.title, m.roleLabel, m.reports || '']);
    });
    const csv  = rows.map(function(r) {
      return r.map(function(c) { return '"' + String(c).replace(/"/g,'""') + '"'; }).join(',');
    }).join('\n');
    const blob = new Blob([csv], {type:'text/csv'});
    const a    = document.createElement('a');
    a.href     = URL.createObjectURL(blob);
    a.download = (g.label || 'team') + '-members.csv';
    a.click();
  };

  /* ── Boot: render first group ── */
  if (GROUPS.length > 0) { renderGroup(0); }

})();
</script>