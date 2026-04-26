<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">

<div class="view-header mb-3">
    <div class="view-icon me-3"><i class="ti ti-user-check"></i></div>
        <div class="flex-grow-1">
          <div class="view-title"><?= $page_title ?></div>
        </div>
        
    <div class="ms-auto d-flex gap-2">

        <a href="<?= site_url('evaluations/my') ?>" 
           class="btn btn-header <?= empty($_GET['type']) ? 'bg-primary' : 'bg-light-primary' ?>">
           <i class="ti ti-list-check"></i> All
        </a>
    
        <a href="<?= site_url('evaluations/my?type=monthly') ?>" 
           class="btn btn-header <?= ($_GET['type'] ?? '') === 'monthly' ? 'bg-primary' : 'bg-light-primary' ?>">
           <i class="ti ti-calendar"></i> Monthly
        </a>
    
        <a href="<?= site_url('evaluations/my?type=annual') ?>" 
           class="btn btn-header <?= ($_GET['type'] ?? '') === 'annual' ? 'bg-primary' : 'bg-light-primary' ?>">
           <i class="ti ti-calendar-time"></i> Annual
        </a>
          
    </div>
</div>

  <!-- TABLE -->
  <div class="solid-card">
    <div class="card-body">

    <!-- ── KPI Strip (User Scoped) ───────────────────────────────── -->
    <div class="row g-2 mb-3">
        <?php
        $kpis = [
            ['Total',      $kpi['total'] ?? 0,                                   'ti ti-clipboard-list', '#6366f118'],
            ['Draft',      $kpi['draft'] ?? 0,                                   'ti ti-pencil',         '#94a3b818'],
            ['Submitted',  $kpi['submitted'] ?? 0,                               'ti ti-send',           '#0ea5e918'],
            ['Approved',   $kpi['approved'] ?? 0,                                'ti ti-circle-check',   '#16a34a18'],
            ['Rejected',   $kpi['rejected'] ?? 0,                                'ti ti-circle-x',       '#ef444418'],
            ['Avg Rating', number_format(($kpi['avg_rating'] ?? 0), 1) . ' / 5', 'ti ti-star',           '#f59e0b18'],
            ['Last Score', $last_eval ? number_format($last_eval['score_ratings'], 1) . ' / 5' : '—', 'ti ti-trending-up', '#22c55e18'],
        ];
        ?>
    
        <?php foreach ($kpis as $m): ?>
        <div class="col">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:<?= $m[3] ?>;">
                    <i class="<?= $m[2] ?>"></i>
                </div>
                <div>
                    <div class="kpi-value"><?= $m[1] ?></div>
                    <div class="kpi-label"><?= $m[0] ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

      <div class="table-responsive">
        <table class="table table-sm small table-hover table-bottom-border align-middle mb-0">
          <thead class="bg-light-primary">
            <tr>
              <th>Period</th>
              <th>Template</th>
              <th class="text-center">Attendance</th>
              <th class="text-center">Rating</th>
              <th class="text-center">Status</th>
              <th>Review Date</th>
              <th>Reviewed By</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>

          <tbody>
            <?php if (!empty($evaluations)): ?>
              <?php foreach ($evaluations as $ev): ?>
                <tr>

                  <!-- Period -->
                  <td>
                    <?= e($ev['review_period'] ?: '—') ?>
                    <div class="x-small text-muted">
                      <?= ucfirst($ev['review_type']) ?>
                    </div>
                  </td>

                  <!-- Template -->
                  <td><?= e($ev['template_name'] ?? '-') ?></td>

                  <!-- Attendance -->
                  <td class="text-center">
                    <?= eval_score_badge($ev['score_attendance'] ?? null) ?>
                  </td>

                  <!-- Rating -->
                  <td class="text-center">
                    <?= eval_score_badge($ev['score_ratings'] ?? null) ?>
                  </td>

                  <!-- Status -->
                  <td class="text-center">
                    <?= eval_status_badge($ev['status']) ?>
                  </td>

                  <!-- Date -->
                  <td>
                    <?= $ev['review_date'] 
                        ? date('d M Y', strtotime($ev['review_date'])) 
                        : '—' ?>
                  </td>

                  <!-- Reviewer -->
                  <td>
                    <?= e(trim(($ev['reviewer_firstname'] ?? '') . ' ' . ($ev['reviewer_lastname'] ?? ''))) ?: '—' ?>
                  </td>

                  <!-- Action -->
                  <td class="text-end">
                    <a href="<?= site_url('evaluations/my/view/' . $ev['id']) ?>"
                       class="btn btn-ssm btn-outline-primary"
                       title="View">
                      <i class="ti ti-eye"></i>
                    </a>
                  </td>

                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center text-muted py-4">
                  <i class="ti ti-clipboard-off fs-3 d-block mb-2 opacity-50"></i>
                  No evaluations found.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>

    <?php if (!empty($evaluations)): ?>
      <div class="card-footer bg-transparent py-2 px-3">
        <small class="text-muted"><?= count($evaluations) ?> evaluation(s)</small>
      </div>
    <?php endif; ?>

  </div>

</div>