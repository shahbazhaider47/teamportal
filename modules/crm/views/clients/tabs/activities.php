          <div class="tab-pane" id="tab-audit">

            <div class="audit-section">

              <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-calendar-plus"></i></div>
                <span class="audit-label">Created At</span>
                <span class="audit-value"><?= html_escape($client['created_at'] ?? '—') ?></span>
              </div>

              <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-user"></i></div>
                <span class="audit-label">Created By</span>
                <span class="audit-value"><?= html_escape($client['created_by'] ?? '—') ?></span>
              </div>

              <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-calendar-stats"></i></div>
                <span class="audit-label">Last Updated</span>
                <span class="audit-value"><?= html_escape($client['updated_at'] ?? '—') ?></span>
              </div>

              <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-user-check"></i></div>
                <span class="audit-label">Updated By</span>
                <span class="audit-value"><?= html_escape($client['updated_by'] ?? '—') ?></span>
              </div>
              
            </div>
          </div>