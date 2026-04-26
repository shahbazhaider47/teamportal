                    <!-- Activity Tab -->
                    <div class="tab-pane fade" id="activity_log" role="tabpanel">
                        <div class="card-body">
                          <?php if (!empty($activity_logs)): ?>
                            <ul class="app-timeline-box">
                              <?php foreach ($activity_logs as $log): ?>
                                <li class="timeline-section">
                                  <div class="timeline-icon">
                                    <span class="text-light-info h-35 w-35 d-flex-center b-r-50">
                                      <i class="ti ti-circle-check f-s-20"></i>
                                    </span>
                                  </div>
                                  <div class="timeline-content bg-light-primary b-1-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                     <p class="text-dark mt-2 small"><?= e($log['action']) ?></p>
                                      <p class="text-dark mb-0 small"><?= time_ago($log['created_at']) ?>
                                      <br><?= e(date('d M Y, h:i A', strtotime($log['created_at']))) ?>
                                      </p>
                                    </div>
                                  </div>
                                </li>
                              <?php endforeach; ?>
                            </ul>
                          <?php else: ?>
                            <div class="text-center text-muted py-4">
                              No activity recorded yet.
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>