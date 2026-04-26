<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">

    <!-- PAGE HEADER -->
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header px-3 py-2 mb-3 rounded-3 shadow-sm">
        <h1 class="h6 mb-0"><?= html_escape($page_title ?? 'Company Policy') ?> </h1>
    </div>

    <div class="row g-3">

        <!-- LEFT: POLICY DETAILS / LIST -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
        
                    <div class="list-group list-group-flush">
        
                        <?php if (!empty($policies)): ?>
                            <?php foreach ($policies as $policy): ?>
        
                                <div class="list-group-item">
        
                                    <div class="fw-semibold text-primary">
                                        <?= html_escape($policy['title']) ?>
                                    </div>
        
                                    <?php if (!empty($policy['description'])): ?>
                                        <div class="small text-muted">
                                            <?= html_escape($policy['description']) ?>
                                        </div>
                                    <?php endif; ?>
        
                                    <div class="small fw-semibold mt-3 text-muted">
                                        Date Uploaded: <?= date('M d, Y', strtotime($policy['created_at'])) ?>
                                    </div>
        
                                </div>
        
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-3 text-center text-muted">
                                No company policy found.
                            </div>
                        <?php endif; ?>
        
                    </div>
        
                </div>
            </div>
        </div>


        <!-- RIGHT: FILE VIEW ONLY -->
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-body p-0">

                    <?php if (!empty($active_policy) && !empty($active_policy['file_path'])): ?>

                        <?php
                            $file     = $active_policy['file_path'];
                            $file_url = base_url('uploads/hrm/documents/' . $file);
                            $ext      = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        ?>

                        <div style="height: 75vh; overflow: auto;">
                            <?php if ($ext === 'pdf'): ?>
                                <iframe src="<?= $file_url ?>"
                                        width="100%"
                                        height="100%"
                                        style="border: none;"></iframe>

                            <?php elseif (in_array($ext, ['jpg','jpeg','png','gif'])): ?>
                                <img src="<?= $file_url ?>"
                                     class="img-fluid d-block mx-auto"
                                     alt="Company Policy">

                            <?php else: ?>
                                <div class="text-center p-5">
                                    <p class="mb-2">Preview not available.</p>
                                    <a href="<?= $file_url ?>" target="_blank" class="btn btn-primary btn-sm">
                                        Download File
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            No policy selected or file missing.
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>
</div>
