                  <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab" tabindex="0">
                    <?php if (!empty($documents)): ?>
                      <div class="card-body table-responsive">
                        <table class="table small table-hover align-middle">
                          <thead class="bg-light-primary">
                            <tr>
                              <th>Document Title</th>
                              <th>Document Type</th>
                              <th>Attached File</th>
                              <th>Expiry Date</th>
                              <th>View</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($documents as $i => $doc): ?>
                              <tr>
                                <td>
                                  <strong><?= html_escape($doc['title']) ?></strong>
                                </td>
                                <td><?= html_escape($doc['doc_type']) ?></td>
                                <td>
                                    <?php if($doc['file_path']): ?>
                                        <div class="text-muted small mt-1">
                                            <?= strtoupper(substr($doc['file_path'], 1)) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No file</span>
                                    <?php endif ?>
                                </td>
                                <td>
                                  <?php
                                    if (!empty($doc['expiry_date']) && $doc['expiry_date'] !== '0000-00-00') {
                                      $expiry = new DateTime($doc['expiry_date']);
                                      $today = new DateTime();
                                      $interval = $today->diff($expiry);
                                      if ($expiry < $today) {
                                        echo '<span class="badge bg-danger">Expired</span>';
                                      } elseif ($interval->days <= 30) {
                                        echo '<span class="badge bg-warning text-dark">Expiring</span>';
                                      } else {
                                        echo date('M d, Y', strtotime($doc['expiry_date']));
                                      }
                                    } else {
                                      echo '<span class="text-muted">N/A</span>';
                                    }
                                  ?>
                                </td>
                                <td>
                                <?php if($doc['file_path']): ?>
                                    <a href="<?= base_url('uploads/hrm/documents/'.$doc['file_path']) ?>"
                                        target="_blank"
                                        class="btn btn-ssm btn-light-primary"
                                        title="View Document">
                                        <i class="ti ti-external-link"></i> View
                                    </a>
                                <?php endif ?>
                                </td>
                              </tr>
                            <?php endforeach ?>
                          </tbody>
                        </table>
                      </div>
                    <?php else: ?>
                      <div class="text-muted py-4 text-center">No employee documents found.</div>
                    <?php endif ?>
                  </div>