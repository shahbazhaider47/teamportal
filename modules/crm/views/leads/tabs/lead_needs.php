<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isDeleted = ((int)($lead['is_deleted'] ?? 0) === 1);
?>

<div class="tab-pane fade show active" id="leaddetails" role="tabpanel" aria-labelledby="leaddetails-tab" tabindex="0">
    <div class="section-card mb-3">

        <div class="audit-section">
            <div class="audit-section-header d-flex align-items-center justify-content-between">
                <span><i class="ti ti-list-check"></i>Lead Requirements</span>

                <?php if (!$isDeleted && !empty($can['edit'])): ?>
                    <button type="button" class="btn-icon bg-light-primary" data-bs-toggle="modal" data-bs-target="#editLeadNeedsModal">
                        <i class="ti ti-pencil-plus"></i>
                    </button>
                <?php endif; ?>
            </div>

            <div>

                <!-- Practice Needs -->
                <div class="audit-row">
                    <div class="audit-icon">
                        <i class="ti ti-notes"></i>
                    </div>

                    <div class="audit-content">
                        <div class="audit-label">Practice Needs</div>
                        <div class="text-muted">
                            <?= nl2br(html_escape($lead['practice_needs'] ?? '—')) ?>
                        </div>
                    </div>
                </div>

                <!-- Pain Points -->
                <div class="audit-row">
                    <div class="audit-icon">
                        <i class="ti ti-alert-circle"></i>
                    </div>

                    <div class="audit-content">
                        <div class="audit-label">Pain Points</div>
                        <div class="text-muted">
                            <?= nl2br(html_escape($lead['pain_points'] ?? '—')) ?>
                        </div>
                    </div>
                </div>

                <!-- Decision Criteria -->
                <div class="audit-row">
                    <div class="audit-icon">
                        <i class="ti ti-checklist"></i>
                    </div>

                    <div class="audit-content">
                        <div class="audit-label">Decision Criteria</div>
                        <div class="text-muted">
                            <?= nl2br(html_escape($lead['decision_criteria'] ?? '—')) ?>
                        </div>
                    </div>
                </div>

                <!-- Key Decision Makers -->
                <div class="audit-row">
                    <div class="audit-icon">
                        <i class="ti ti-users"></i>
                    </div>

                    <div class="audit-content">
                        <div class="audit-label">Key Decision Makers</div>
                        <div class="text-muted lowercase">
                            <?= nl2br(html_escape($lead['key_decision_makers'] ?? '—')) ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>