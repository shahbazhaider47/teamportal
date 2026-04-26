            <!-- Reporting Team -->
        <div class="tab-pane fade show active" id="teams" role="tabpanel" aria-labelledby="teams-tab" tabindex="0">    
            <div class="section-card mb-3">
            
              <div class="card-header section-title bg-primary d-flex align-items-center">
                <i class="ti ti-users me-2"></i>
                <span>Reporting Team</span>
              </div>
            
              <div class="card-body pt-3 pb-2">
            
                <!-- Team Name -->
                <div class="info-row">
                  <div class="info-icon">
                    <i class="ti ti-sitemap me-2"></i>
                    <span class="info-label">Current Team</span>
                  </div>
                  <div class="info-value me-2">
                    <?= !empty($teamName) ? html_escape($teamName) : '<span class="text-muted">Not Assigned</span>' ?>
                  </div>
                </div>
            
                <!-- Reports To -->
                <div class="info-row">
                  <div class="info-icon">
                    <i class="ti ti-arrow-up-right me-2"></i>
                    <span class="info-label">Reports To</span>
                  </div>
                  <div class="info-value">
                    <?php
                      $role = strtolower($user['user_role'] ?? '');
            
                      if ($role === 'employee' && !empty($teamLeadName)) {
                        echo user_profile_image($teamLeadName);
                      } elseif ($role === 'teamlead' && !empty($managerName)) {
                        echo user_profile_image($managerName);
                      } elseif ($role === 'manager' && !empty($reportingName)) {
                        echo user_profile_image($reportingName);
                      } else {
                        echo '<span class="text-muted">—</span>';
                      }
                    ?>
                  </div>
                </div>
            
                <?php
                $team_members = get_team_members(
                    $user['emp_team'] ?? null,
                    $user['id'] ?? null
                );
                ?>
                
                <!-- Team Members -->
                <?php if (!empty($team_members)): ?>
                  <div class="row g-3 mt-2 mb-2">
                
                    <?php foreach ($team_members as $member): ?>
                      <?php
                        $member_name = trim(
                          $member['fullname']
                          ?: ($member['firstname'].' '.$member['lastname'])
                        );
                      ?>
                
                      <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                        <div class="d-flex align-items-center">
                
                          <!-- Avatar -->
                          <div class="flex-shrink-0 me-3">
                            <?php if (!empty($member['profile_image'])
                                && file_exists(FCPATH . 'uploads/users/profile/' . $member['profile_image'])): ?>
                              <img
                                src="<?= base_url('uploads/users/profile/' . $member['profile_image']) ?>"
                                alt="<?= html_escape($member_name) ?>"
                                class="rounded-circle shadow-sm"
                                style="width:35px;height:35px;object-fit:cover;"
                              >
                            <?php else: ?>
                              <div
                                class="rounded-circle d-flex align-items-center justify-content-center bg-light-primary text-white fw-bold shadow-sm"
                                style="width:35px;height:35px;"
                              >
                                <?= strtoupper(substr($member_name, 0, 1)) ?>
                              </div>
                            <?php endif; ?>
                          </div>
                
                          <!-- Info -->
                          <div class="flex-grow-1">
                            <div class="small fw-medium">
                              <?= html_escape($member_name) ?>
                            </div>
                            <div class="text-muted small">
                              <?= emp_id_display($member['emp_id'] ?? '-') ?>
                              <i class="ti ti-dots-vertical"></i>
                              <?= resolve_emp_title($member['emp_title']) ?>
                            </div>
                          </div>
                
                        </div>
                      </div>
                    <?php endforeach; ?>
                
                  </div>
                <?php else: ?>
                  <span class="text-muted">No team members found.</span>
                <?php endif; ?>
            
              </div>
            </div>
        </div>    