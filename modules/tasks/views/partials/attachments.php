<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="mb-3">
  <div class="card-header py-2 d-flex align-items-center justify-content-between gap-2 flex-wrap">
    <strong class="text-muted">Tasks Attachments</strong>
    <span class="small text-muted">Total Files: <?= (int)count($attachments ?? []) ?></span>
  </div>

  <div class="card-body">
    <?php if (!empty($attachments)): ?>
      <div class="list-group">
        <?php foreach ($attachments as $a):
          $id        = (int)($a['id'] ?? 0);
          $fileName  = (string)($a['file_name'] ?? '');
          $relPath   = (string)($a['file_path'] ?? ''); // e.g. "uploads/tasks/xxx.pdf"
          $fileUrl   = $relPath !== '' ? base_url($relPath) : '#';
          $uploaded  = (string)($a['uploaded_at'] ?? '');

          // Prefer enriched fields from controller/model
          $byName    = trim((string)($a['uploaded_by_name'] ?? ''));
          $byUrl     = trim((string)($a['uploaded_by_url']  ?? ''));
          $byAvatar  = trim((string)($a['uploaded_by_avatar'] ?? ''));
          $byId      = isset($a['uploaded_by']) ? (int)$a['uploaded_by'] : 0;

          if ($byName === '') {
            // Fallback to an id label if name was not enriched upstream
            $byName = $byId > 0 ? ('#'.$byId) : '—';
          }

          $ext  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
          $icon = 'paperclip';
          if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true))      $icon = 'photo';
          elseif ($ext === 'pdf')                                           $icon = 'file-type-pdf';
          elseif (in_array($ext, ['doc','docx'], true))                     $icon = 'file-type-doc';
          elseif (in_array($ext, ['xls','xlsx','csv'], true))               $icon = 'file-type-xls';
        ?>
          <div class="list-group-item d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
              <i class="ti ti-<?= $icon ?> text-muted"></i>
              <div class="text-truncate small">

                <?php
                if (!function_exists('truncate_filename')) {
                    /**
                     * Truncate filename to $max chars, preserving extension.
                     * Uses middle ellipsis on the base name (before extension).
                     */
                    function truncate_filename(string $name, int $max = 40): string
                    {
                        $name = trim($name);
                        if ($name === '') return '—';
                
                        $ext  = pathinfo($name, PATHINFO_EXTENSION);
                        $base = $ext !== '' ? pathinfo($name, PATHINFO_FILENAME) : $name;
                
                        // If already short enough, return as-is
                        if (mb_strlen($name) <= $max) return $name;
                
                        // Budget for base part (minus ext + dot + ellipsis)
                        $extPartLen = $ext !== '' ? (mb_strlen($ext) + 1) : 0; // +1 for dot
                        $ellipsis   = '…';
                        $budget     = $max - $extPartLen - mb_strlen($ellipsis);
                
                        if ($budget <= 1) {
                            // Edge case: not enough room; hard cut total
                            return mb_substr($name, 0, max(1, $max - 1)) . $ellipsis;
                        }
                
                        // Split base into prefix/suffix
                        $prefixLen = (int)ceil($budget * 0.6);  // keep a bit more from the start
                        $suffixLen = $budget - $prefixLen;
                        $prefix    = mb_substr($base, 0, $prefixLen);
                        $suffix    = mb_substr($base, -$suffixLen);
                
                        return $ext !== ''
                            ? ($prefix . $ellipsis . $suffix . '.' . $ext)
                            : ($prefix . $ellipsis . $suffix);
                    }
                }
                ?>
                <?php
                $rawName     = $fileName !== '' ? $fileName : basename($relPath);
                $displayName = truncate_filename($rawName, 40);
                ?>
                <a href="<?= $fileUrl ?>" target="_blank" rel="noopener"
                   class="text-decoration-none"
                   title="<?= html_escape($rawName) ?>">
                  <?= html_escape($displayName) ?>
                </a>

                <div class="small text-muted d-flex align-items-center gap-2 mt-1">
                  <span class="d-inline-flex align-items-center gap-1">
                    <?php if ($byAvatar !== ''): ?>
                      <img src="<?= html_escape($byAvatar) ?>" alt="" width="16" height="16" class="rounded-circle" style="object-fit:cover;">
                      <?= html_escape($byName) ?>
                    <?php endif; ?>
                  </span>
                  <span><?= format_datetime($uploaded) ?></span>
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center gap-1 flex-shrink-0">
              <?php if (!empty($canEdit) || !empty($isAssignee)): ?>
                <form method="post"
                      action="<?= site_url('tasks/attachments/'.$id.'/delete') ?>"
                      onsubmit="return confirm('Delete this file?');"
                      class="m-0">
                  <button class="btn btn-light-danger btn-ssm" type="submit" <?= $id ? '' : 'disabled' ?>>
                  <i class="ti ti-trash"></i>
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="text-muted small text-center py-3">No files uploaded.</div>
    <?php endif; ?>

    <?php if (!empty($canEdit) || !empty($isAssignee)): ?>
      <hr class="my-3">

      <form method="post" enctype="multipart/form-data" action="<?= site_url('tasks/attachments/'.$taskId.'/upload') ?>">
        <div class="row g-2 align-items-center">
          <div class="col-md-9">
            <input type="file"
                   name="file"
                   class="form-control form-control-sm"
                   required
                   <?= !empty($acceptAttr) ? 'accept="'.html_escape($acceptAttr).'"' : '' ?>>
          </div>
          <div class="col-md-3 d-grid">
            <button class="btn btn-outline-primary btn-sm" type="submit">
              <i class="ti ti-upload"></i> Upload
            </button>
          </div>
        </div>
        <div class="form-text mt-1 text-muted small">
          <?php if (!empty($maxFilesSetting)): ?>
            Max files: <?= (int)$maxFilesSetting ?>.
          <?php else: ?>
            File uploads disabled by settings.
          <?php endif; ?>
          Max size 10MB. Allowed: <?= html_escape($allowedCsv ?: 'jpg,png,pdf,doc,docx,xls,xlsx') ?>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<style>
  .list-group-item .ti { font-size: 18px; }

</style>
