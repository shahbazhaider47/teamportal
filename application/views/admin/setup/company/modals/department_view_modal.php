<?php if (!empty($d) && is_array($d)): ?>
<!-- Per-row View Modal (hidden by default, shows on button click) -->
<div class="modal fade"
     id="viewDepartmentModal-<?= (int)$d['id'] ?>"
     tabindex="-1"
     aria-labelledby="viewDepartmentLabel-<?= (int)$d['id'] ?>"
     aria-hidden="true">

  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <!-- HEADER -->
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white small"
            id="viewDepartmentLabel-<?= (int)$d['id'] ?>">
          Department Details | <?= html_escape($d['name']) ?>
        </h5>
        <button type="button"
                class="btn-close"
                data-bs-dismiss="modal"
                aria-label="Close"></button>
      </div>

      <!-- BODY -->
      <div class="modal-body">

        <!-- HOD -->
        <div>
          <?php if (!empty($d['hod_user'])): ?>
            <div class="d-flex align-items-center gap-2">
              <span class="text-primary fw-semibold">Department Head:</span> <?= user_profile_image($d['hod_user']['id']) ?>
            </div>
          <?php else: ?>
            <span class="text-primary fw-semibold">HOD: Not Assigned</span>
          <?php endif; ?>
        </div>
          
        <!-- MEMBERS COUNT -->
        <div class="app-divider-v mt-3 mb-3 secondary justify-content-center">
          <span class="badge text-bg-primary">
            Total Members (<?= isset($d['users']) ? count($d['users']) : 0 ?>)
          </span>
        </div>

        <!-- MEMBERS LIST -->
        <?php if (!empty($d['users'])): ?>
          <?php
            usort($d['users'], function($a, $b) {
              return strcmp(
                ($a['firstname'] ?? '').($a['lastname'] ?? ''),
                ($b['firstname'] ?? '').($b['lastname'] ?? '')
              );
            });

            $chunks = array_chunk($d['users'], ceil(count($d['users']) / 2));
          ?>

          <div class="row">
            <?php foreach ($chunks as $chunk): ?>
              <div class="col-md-6">
                <ul class="list-group mb-3">
                  <?php foreach ($chunk as $user): ?>
                    <li class="list-group-item d-flex align-items-center">
                      <?php if (!empty($user['profile_image'])): ?>
                        <img src="<?= base_url('uploads/users/profile/'.$user['profile_image']) ?>"
                             class="rounded-circle me-2"
                             width="25" height="25" alt="">
                      <?php else: ?>
                        <span class="bg-light-primary h-25 w-25 d-flex-center b-r-50 small me-2">
                          <?= strtoupper(
                                substr($user['firstname'],0,1).
                                substr($user['lastname'],0,1)
                              ) ?>
                        </span>
                      <?php endif; ?>
                      <span class="small">
                        <?= html_escape($user['firstname'].' '.$user['lastname']) ?>
                      </span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endforeach; ?>
          </div>

        <?php else: ?>
          <div class="alert alert-light-primary mb-0">
            No staff assigned to this department.
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>
<?php endif; ?>
