<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
// ── Helper: derive human-friendly link text from a URL (safe to re-declare)
if (!function_exists('derive_link_text')) {
    function derive_link_text(?string $url): string
    {
        if (!$url) return 'Open';

        $parts = parse_url($url);
        $path  = isset($parts['path']) ? trim($parts['path'], "/") : '';
        if ($path === '') return 'Open';

        $segs = array_values(array_filter(explode('/', $path)));
        if (empty($segs)) return 'Open';

        // Opinionated mappings for nicer UX (extend as needed)
        $map = [
            'support' => [
                'ticket'       => 'Open Ticket',
                'tickets'      => 'Open Ticket',
                'view'         => 'Open Ticket',
            ],
            'teams' => [
                'instructions' => 'Review Instructions',
                'guide'        => 'Review Instructions',
            ],
            'users' => [
                'profile'      => 'Open Profile',
                'view'         => 'Open Profile',
                'settings'     => 'Open Settings',
            ],
        ];

        $first  = strtolower($segs[0] ?? '');
        $second = strtolower($segs[1] ?? '');

        if (isset($map[$first])) {
            if ($second && isset($map[$first][$second])) return $map[$first][$second];
            if (isset($segs[2]) && isset($map[$first][$second])) return $map[$first][$second];
        }

        // Fallback: use last meaningful segment (skip numeric IDs)
        $candidate = end($segs);
        if (ctype_digit((string)$candidate) && count($segs) > 1) {
            $candidate = prev($segs);
        }

        $pretty = ucwords(str_replace(['-', '_'], ' ', (string)$candidate));
        if ($pretty === '' || strlen($pretty) > 40) return 'Open';

        foreach (['Open','View','Review','Manage','Edit','Download'] as $v) {
            if (stripos($pretty, $v.' ') === 0) return $pretty;
        }

        if (preg_match('/(ticket|profile|instructions|document|settings|report|invoice|guide)$/i', $pretty)) {
            return 'Open ' . $pretty;
        }
        return 'Open ' . $pretty;
    }
}
?>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= html_escape($page_title ?? 'Notifications') ?></h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <form method="post" action="<?= site_url('notifications/mark_read'); ?>">
        <button type="submit" class="btn btn-primary btn-header">
          <i class="fas fa-check-double"></i> <small>Mark All as Read</small>
        </button>
      </form>

      <div class="btn-divider"></div>

      <form method="post" action="<?= site_url('notifications/clear_all'); ?>" class="me-2 mb-2 mb-sm-0">
        <button type="submit" class="btn btn-outline-primary btn-header">
          <i class="fas fa-trash-alt"></i> <small>Clear Notifications</small>
        </button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <?php if (empty($notifications)): ?>
        <div class="alert alert-light-primary">You don’t have any notifications yet.</div>
      <?php else: ?>

        <style>
          .notif-table td, .notif-table th { vertical-align: middle; }
          .notif-msg { max-width: 520px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
          .notif-from { display: flex; align-items: center; gap: .5rem; }
          .notif-from img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
          @media (max-width: 768px){ .notif-msg { max-width: 260px; } }
        </style>

        <div class="table-responsive">
          <table class="table table-hover table-sm small notif-table">
            <thead class="bg-light-primary">
              <tr class="small text-uppercase">
                <th style="width:220px">Notification From</th>
                <th>Description / Message</th>
                <th style="width:180px;">Actions</th>
                <th style="width:160px;">Date</th>
                <th style="width:90px;">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($notifications as $n): ?>
                <?php
                  // Sender image (absolute or relative)
                  $senderImg = trim((string)($n['sender_image'] ?? ''));
                  $senderImg = $senderImg !== '' && preg_match('#^https?://#i', $senderImg)
                                ? $senderImg
                                : base_url('uploads/users/profile/' . ($senderImg !== '' ? $senderImg : 'default.png'));

                  // Prefer full_text; fall back to short_text
                  $rawText  = (string)($n['full_text'] ?? $n['short_text'] ?? '');
                  $fullText = nl2br(html_escape($rawText));

                  // Dates
                  $createdAt = !empty($n['created_at']) ? date('M j, Y H:i', strtotime($n['created_at'])) : '';

                  // Links (support both relative + absolute)
                  $linkHrefRaw   = (string)($n['link'] ?? '');
                  $actionHrefRaw = (string)($n['action_url'] ?? '');

                  $linkHref = $linkHrefRaw !== ''
                    ? (preg_match('#^https?://#i', $linkHrefRaw) ? $linkHrefRaw : base_url(ltrim($linkHrefRaw, '/')))
                    : '';

                  $actionHref = $actionHrefRaw !== ''
                    ? (preg_match('#^https?://#i', $actionHrefRaw) ? $actionHrefRaw : base_url(ltrim($actionHrefRaw, '/')))
                    : '';

                  // Explicit labels if present; otherwise we will derive
                  $linkText   = trim((string)($n['link_text'] ?? ''));
                  $actionText = trim((string)($n['action_text'] ?? ''));

                  // Sender name (optional)
                  $senderName = trim(($n['sender_first'] ?? '') . ' ' . ($n['sender_last'] ?? ''));
                ?>
                <tr>
                  <td>
                    <div class="notif-from">
                      <img src="<?= html_escape($senderImg) ?>" alt="Sender"
                           onerror="this.onerror=null;this.src='<?= base_url('assets/images/default.png') ?>';">
                      <div class="small">
                        <div class="fw-semibold"><?= html_escape($senderName ?: 'System') ?></div>
                        <?php if (!empty($n['feature_key'])): ?>
                          <div class="text-muted">From: <?= html_escape(ucfirst($n['feature_key'])) ?></div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>

                  <td class="notif-msg">
                    <?= $fullText ?>
                  </td>

                  <td class="small">
                    <?php
                      // Use explicit text if provided; else derive from the URL
                      $primaryText = $linkText !== '' ? $linkText : derive_link_text($linkHref ?: null);
                      $secondaryText = $actionText !== '' ? $actionText : derive_link_text($actionHref ?: null);
                    ?>

                    <?php if (!empty($linkHref)): ?>
                      <a href="<?= html_escape($linkHref) ?>" class="btn btn-xs btn-info me-1">
                        <?= html_escape($primaryText) ?>
                      </a>
                    <?php endif; ?>

                    <?php if (!empty($actionHref)): ?>
                      <a href="<?= html_escape($actionHref) ?>" class="capital text-info" target="_blank" rel="noopener">
                        <?= html_escape($secondaryText) ?> <i class="ti ti-external-link"></i>
                      </a>
                    <?php endif; ?>

                    <?php if (empty($linkHref) && empty($actionHref)): ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>

                  <td class="small text-muted">
                    <?= $createdAt ?: '—' ?>
                  </td>

                  <td>
                    <?php if (empty($n['is_read'])): ?>
                      <span class="badge bg-primary">Unread</span>
                    <?php else: ?>
                      <span class="badge bg-light-primary">Read</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php endif; ?>
    </div>
  </div>
</div>
