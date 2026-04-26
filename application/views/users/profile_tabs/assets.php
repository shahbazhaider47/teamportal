                <!-- Assets Tab -->
                <div class="tab-pane fade" id="assets" role="tabpanel" aria-labelledby="assets-tab" tabindex="0">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="mb-0"><i class="ti ti-device-laptop pe-2"></i>Assigned Assets</h5>
                      <?php if (!empty($assets)): ?>
                        <span class="badge bg-light-primary">
                          <?= count($assets) ?> Assigned
                        </span>
                      <?php endif; ?>
                    </div>
                
                    <div class="card-body">
                      <?php if (empty($assets)): ?>
                        <div class="text-muted">No assets are currently assigned.</div>
                      <?php else: ?>
                        <div class="table-responsive">
                            <table class="table small table-hover align-middle">
                              <thead class="bg-light-primary">
                              <tr>
                                <th>Assigned Asset</th>
                                <th>Type</th>
                                <th>Serial</th>
                                <th>Status</th>
                                <th>Assigned On</th>
                                <th>Value</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($assets as $a): ?>
                                <?php
                                  $status     = strtolower($a['status'] ?? '');
                                  $badgeClass = match ($status) {
                                    'assigned', 'in_use', 'in use' => 'bg-success-subtle text-success',
                                    'maintenance', 'repair'       => 'bg-warning-subtle text-warning',
                                    'lost', 'retired'             => 'bg-danger-subtle text-danger',
                                    default                       => 'bg-secondary-subtle text-secondary'
                                  };
                                ?>
                                <tr>
                                  <td>
                                    <?php if (!empty($a['id'])): ?>
                                      <a href="<?= site_url('asset/view/'.$a['id']) ?>" class="text-decoration-underline">
                                        <?= e($a['name'] ?? $a['asset_name'] ?? '-') ?>
                                      </a>
                                    <?php else: ?>
                                      <?= e($a['name'] ?? $a['asset_name'] ?? '-') ?>
                                    <?php endif; ?>
                                  </td>
                                  <td><?= e($a['asset_type'] ?? '-') ?></td>
                                  <td><?= e($a['serial_no'] ?? '-') ?></td>
                                  <td>
                                    <span class="badge <?= $badgeClass ?>"><?= e(ucwords($status ?: 'Unknown')) ?></span>
                                  </td>
                                  <td><?= !empty($a['created_at']) ? e(date('d M Y', strtotime($a['created_at']))) : '-' ?></td>
                                                                  <td>
                                  <?= isset($a['price']) && $a['price'] > 0
                                      ? html_escape(get_base_currency_symbol()) . number_format($a['price'])
                                      : '-' ?>
                                </td>
                                </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>