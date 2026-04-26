<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
// ----- DUMMIES (replace with real data) -----
$owner_name   = t_s($task['addedfrom_name']  ?? 'Owner Name');
$owner_avatar = (string)($task['addedfrom_avatar'] ?? '');
$assg_name    = t_s($task['assignee_name']   ?? 'Assignee Name');
$assg_avatar  = (string)($task['assignee_avatar'] ?? '');

$followers_resolved = is_array($followers_resolved ?? null) ? $followers_resolved : [
  ['id'=>101,'name'=>'John Carter','avatar'=>''],
  ['id'=>102,'name'=>'Ayesha Noor','avatar'=>''],
  ['id'=>103,'name'=>'David Lee','avatar'=>''],
];
?>

<style>
/* ---- Scoped: People Section ---- */
.people-section .mini-card {
  border: 1px solid #e9ecef; border-radius: 12px; background: #fff;
}
.people-section .mini-card .mini-head {
  border-bottom: 1px solid #eef2f7; padding: .5rem .75rem; background: #f8fafc;
}
.people-section .mini-card .mini-title {
  margin: 0; font-size: .9rem; font-weight: 600; color: #374151;
  display:flex; align-items:center; gap:.5rem;
}
.people-section .mini-body { padding: .75rem; }

.people-section .px-user {
  display:flex; align-items:center; gap:.75rem;
}
.people-section .px-user .avatar {
  width:40px; height:40px; border-radius:50%; object-fit:cover;
  border: 1px solid #e5e7eb; background:#f8fafc;
}
.people-section .px-user .avatar-fallback {
  width:40px; height:40px; border-radius:50%;
  display:inline-flex; align-items:center; justify-content:center;
  border:1px solid #e5e7eb; background:#f3f4f6; font-weight:700; color:#4b5563;
}
.people-section .px-user .meta .name { font-size:.92rem; font-weight:600; color:#111827; }
.people-section .px-user .meta .role { font-size:.75rem; color:#6b7280; }

.people-section .followers-wrap { display:flex; flex-wrap:wrap; gap:.5rem; }
.people-section .px-chip {
  display:flex; align-items:center; gap:.5rem; background:#fff;
  border:1px solid #eef2f7; border-radius:999px; padding:.35rem .6rem .35rem .35rem;
}
.people-section .px-chip .chip-avatar {
  width:26px; height:26px; border-radius:50%; object-fit:cover; border:1px solid #e5e7eb; background:#f8fafc;
}
.people-section .px-chip .chip-fallback {
  width:26px; height:26px; border-radius:50%;
  display:inline-flex; align-items:center; justify-content:center; border:1px solid #e5e7eb;
  background:#f3f4f6; font-size:.72rem; color:#4b5563;
}
.people-section .px-chip .chip-name { font-size:.8rem; color:#374151; }
.people-section .px-chip.dashed { border-style:dashed; color:#6b7280; }
</style>

<div class="people-section">
  <div class="row g-3">
    <!-- Owner -->
    <div class="col-12 col-md-6">
      <div class="mini-card h-100">
        <div class="mini-head">
          <h6 class="mini-title"><i class="ti ti-shield-star"></i> Task Owner</h6>
        </div>
        <div class="mini-body">
          <div class="px-user">
            <?php if (!empty($owner_avatar)): ?>
              <img src="<?= html_escape($owner_avatar) ?>" alt="Owner" class="avatar">
            <?php else: ?>
              <div class="avatar-fallback"><?= t_initials($owner_name) ?></div>
            <?php endif; ?>
            <div class="meta">
              <div class="name"><?= $owner_name ?></div>
              <div class="role">Created the task</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Assignee -->
    <div class="col-12 col-md-6">
      <div class="mini-card h-100">
        <div class="mini-head">
          <h6 class="mini-title"><i class="ti ti-user-check"></i> Assignee</h6>
        </div>
        <div class="mini-body">
          <div class="px-user">
            <?php if (!empty($assg_avatar)): ?>
              <img src="<?= html_escape($assg_avatar) ?>" alt="Assignee" class="avatar">
            <?php else: ?>
              <div class="avatar-fallback"><?= t_initials($assg_name) ?></div>
            <?php endif; ?>
            <div class="meta">
              <div class="name"><?= $assg_name ?></div>
              <div class="role">Responsible</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Followers -->
    <div class="col-12 col-md-6">
      <div class="mini-card h-100">
        <div class="mini-head">
          <h6 class="mini-title"><i class="ti ti-users"></i> Followers</h6>
        </div>
        <div class="mini-body">
          <div class="followers-wrap" id="followersWrap">
            <?php if (!empty($followers_resolved)): ?>
              <?php foreach ($followers_resolved as $f):
                $fn = t_s($f['name'] ?? 'Member');
                $fa = (string)($f['avatar'] ?? '');
              ?>
                <span class="px-chip" data-user-id="<?= (int)($f['id'] ?? 0) ?>" title="<?= $fn ?>">
                  <?php if ($fa !== ''): ?>
                    <img src="<?= html_escape($fa) ?>" alt="<?= $fn ?>" class="chip-avatar">
                  <?php else: ?>
                    <span class="chip-fallback"><?= t_initials($fn) ?></span>
                  <?php endif; ?>
                  <span class="chip-name"><?= $fn ?></span>
                </span>
              <?php endforeach; ?>
            <?php else: ?>
              <span class="text-muted small">No followers yet.</span>
            <?php endif; ?>

            <!-- Add follower (dummy) -->
            <button type="button" class="px-chip dashed" disabled>
              <i class="ti ti-user-plus"></i>
              <span class="chip-name">Add follower</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Visibility / placeholder (optional second column fill) -->
    <div class="col-12 col-md-6">
      <div class="mini-card h-100">
        <div class="mini-head">
          <h6 class="mini-title"><i class="ti ti-eye"></i> Visibility</h6>
        </div>
        <div class="mini-body">
          <div class="text-muted small">
            Team visibility: <span class="badge bg-light-secondary text-dark">Private</span><br>
            Share with more members by adding followers or reassigning the task.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (window.bootstrap) {
    document.querySelectorAll('.people-section [title]').forEach(function (el) {
      if (!el.getAttribute('data-bs-toggle')) {
        el.setAttribute('data-bs-toggle', 'tooltip');
        new bootstrap.Tooltip(el);
      }
    });
  }
});
</script>
