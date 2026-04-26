<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="ti ti-message-circle me-2"></i>
                        <?= html_escape($form['title']) ?>
                    </h4>
                </div>
                
                <div class="card-body">
                    <?php if (!empty($form['description'])): ?>
                        <div class="alert alert-info mb-4">
                            <i class="ti ti-info-circle me-2"></i>
                            <?= nl2br(html_escape($form['description'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Form Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="ti ti-calendar me-2"></i>Form Details
                                    </h6>
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-1">
                                            <strong>Frequency:</strong>
                                            <span class="badge bg-info float-end">
                                                <?= ucfirst($form['frequency']) ?>
                                            </span>
                                        </li>
                                        <li class="mb-1">
                                            <strong>Required:</strong>
                                            <span class="badge bg-<?= $form['is_required'] ? 'danger' : 'secondary' ?> float-end">
                                                <?= $form['is_required'] ? 'Required' : 'Optional' ?>
                                            </span>
                                        </li>
                                        <li class="mb-1">
                                            <strong>Responses:</strong>
                                            <span class="badge bg-dark float-end">
                                                <?= $form['is_anonymous'] ? 'Anonymous' : 'Named' ?>
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="ti ti-clock me-2"></i>Time Frame
                                    </h6>
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-1">
                                            <strong>Started:</strong>
                                            <span class="float-end">
                                                <?= date('M d, Y', strtotime($form['start_date'])) ?>
                                            </span>
                                        </li>
                                        <?php if ($form['end_date']): ?>
                                            <li class="mb-1">
                                                <strong>Ends:</strong>
                                                <span class="float-end text-<?= strtotime($form['end_date']) < time() ? 'danger' : 'success' ?>">
                                                    <?= date('M d, Y', strtotime($form['end_date'])) ?>
                                                </span>
                                            </li>
                                        <?php else: ?>
                                            <li class="mb-1">
                                                <strong>Ends:</strong>
                                                <span class="float-end text-success">No end date</span>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Feedback Form -->
                    <form method="post" id="feedbackForm">
                        <?php
                        $questions = $schema['questions'] ?? [];
                        $settings = $schema['settings'] ?? [];
                        $rating_scale = $settings['rating_scale'] ?? 5;
                        $allow_comments = $settings['allow_comments'] ?? 1;
                        ?>
                        
                        <?php if (empty($questions)): ?>
                            <div class="alert alert-warning">
                                No questions have been configured for this feedback form.
                            </div>
                        <?php else: ?>
                            <div class="questions-container">
                                <?php 
                                $question_num = 1;
                                foreach ($questions as $qid => $question): 
                                    if (empty(trim($question['label']))) continue;
                                ?>
                                    <div class="question-card card mb-4 border-<?= $question['type'] === 'rating' ? 'primary' : 'secondary' ?>">
                                        <div class="card-body">
                                            <h5 class="question-title mb-3">
                                                <span class="badge bg-primary me-2"><?= $question_num ?></span>
                                                <?= html_escape($question['label']) ?>
                                                <?php if (!empty($question['required'])): ?>
                                                    <span class="text-danger">*</span>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($question['category'])): ?>
                                                    <span class="badge bg-info-subtle text-info float-end">
                                                        <?= html_escape($question['category']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </h5>
                                            
                                            <!-- Question Response Area -->
                                            <div class="response-area">
                                                <?php switch($question['type']): 
                                                    case 'rating': ?>
                                                        <div class="rating-container">
                                                            <div class="rating-scale mb-2">
                                                                <small class="text-muted">Select a rating from 1 (Lowest) to <?= $rating_scale ?> (Highest)</small>
                                                            </div>
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div class="rating-options">
                                                                    <?php for ($i = 1; $i <= $rating_scale; $i++): ?>
                                                                        <div class="form-check form-check-inline">
                                                                            <input class="form-check-input" 
                                                                                   type="radio" 
                                                                                   name="answers[<?= $qid ?>]" 
                                                                                   value="<?= $i ?>"
                                                                                   id="rating_<?= $qid ?>_<?= $i ?>"
                                                                                   <?= !empty($question['required']) ? 'required' : '' ?>>
                                                                            <label class="form-check-label" for="rating_<?= $qid ?>_<?= $i ?>">
                                                                                <?= $i ?>
                                                                            </label>
                                                                        </div>
                                                                    <?php endfor; ?>
                                                                </div>
                                                                
                                                                <div class="rating-labels">
                                                                    <small class="text-muted me-2">1 = Poor</small>
                                                                    <small class="text-muted"><?= $rating_scale ?> = Excellent</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php break;
                                                    
                                                    case 'textarea': ?>
                                                        <textarea name="answers[<?= $qid ?>]" 
                                                                  class="form-control" 
                                                                  rows="3"
                                                                  placeholder="Type your response here..."
                                                                  <?= !empty($question['required']) ? 'required' : '' ?>></textarea>
                                                        <?php break;
                                                    
                                                    case 'multiple_choice': ?>
                                                        <?php if (!empty($question['options'])): 
                                                            $options = json_decode($question['options'], true); ?>
                                                            <?php foreach ($options as $opt_index => $option): ?>
                                                                <div class="form-check mb-2">
                                                                    <input class="form-check-input" 
                                                                           type="radio" 
                                                                           name="answers[<?= $qid ?>]" 
                                                                           value="<?= html_escape($option) ?>"
                                                                           id="mc_<?= $qid ?>_<?= $opt_index ?>"
                                                                           <?= !empty($question['required']) ? 'required' : '' ?>>
                                                                    <label class="form-check-label" for="mc_<?= $qid ?>_<?= $opt_index ?>">
                                                                        <?= html_escape($option) ?>
                                                                    </label>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                        <?php break;
                                                    
                                                    case 'checkbox': ?>
                                                        <?php if (!empty($question['options'])): 
                                                            $options = json_decode($question['options'], true); ?>
                                                            <?php foreach ($options as $opt_index => $option): ?>
                                                                <div class="form-check mb-2">
                                                                    <input class="form-check-input" 
                                                                           type="checkbox" 
                                                                           name="answers[<?= $qid ?>][]" 
                                                                           value="<?= html_escape($option) ?>"
                                                                           id="cb_<?= $qid ?>_<?= $opt_index ?>">
                                                                    <label class="form-check-label" for="cb_<?= $qid ?>_<?= $opt_index ?>">
                                                                        <?= html_escape($option) ?>
                                                                    </label>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                        <?php break;
                                                    
                                                    case 'dropdown': ?>
                                                        <?php if (!empty($question['options'])): 
                                                            $options = json_decode($question['options'], true); ?>
                                                            <select name="answers[<?= $qid ?>]" 
                                                                    class="form-select"
                                                                    <?= !empty($question['required']) ? 'required' : '' ?>>
                                                                <option value="">Select an option</option>
                                                                <?php foreach ($options as $option): ?>
                                                                    <option value="<?= html_escape($option) ?>">
                                                                        <?= html_escape($option) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        <?php endif; ?>
                                                        <?php break;
                                                    
                                                    case 'yes_no': ?>
                                                        <div class="btn-group" role="group">
                                                            <input type="radio" 
                                                                   class="btn-check" 
                                                                   name="answers[<?= $qid ?>]" 
                                                                   value="Yes"
                                                                   id="yes_<?= $qid ?>"
                                                                   <?= !empty($question['required']) ? 'required' : '' ?>>
                                                            <label class="btn btn-outline-success" for="yes_<?= $qid ?>">
                                                                Yes
                                                            </label>
                                                            
                                                            <input type="radio" 
                                                                   class="btn-check" 
                                                                   name="answers[<?= $qid ?>]" 
                                                                   value="No"
                                                                   id="no_<?= $qid ?>"
                                                                   <?= !empty($question['required']) ? 'required' : '' ?>>
                                                            <label class="btn btn-outline-danger" for="no_<?= $qid ?>">
                                                                No
                                                            </label>
                                                        </div>
                                                        <?php break;
                                                    
                                                    case 'scale': ?>
                                                        <div class="scale-container">
                                                            <div class="row align-items-center">
                                                                <div class="col-md-2">
                                                                    <small class="text-muted">
                                                                        <?= html_escape($question['min_label'] ?? 'Poor') ?>
                                                                    </small>
                                                                </div>
                                                                
                                                                <div class="col-md-8">
                                                                    <input type="range" 
                                                                           class="form-range" 
                                                                           min="1" 
                                                                           max="<?= $question['scale_steps'] ?? 5 ?>" 
                                                                           step="1"
                                                                           name="answers[<?= $qid ?>]"
                                                                           oninput="document.getElementById('scale_value_<?= $qid ?>').textContent = this.value"
                                                                           <?= !empty($question['required']) ? 'required' : '' ?>>
                                                                </div>
                                                                
                                                                <div class="col-md-2 text-end">
                                                                    <span class="badge bg-primary" id="scale_value_<?= $qid ?>">
                                                                        <?= ceil(($question['scale_steps'] ?? 5) / 2) ?>
                                                                    </span>
                                                                    <small class="text-muted ms-1">
                                                                        <?= html_escape($question['max_label'] ?? 'Excellent') ?>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php break;
                                                endswitch; ?>
                                            </div>
                                            
                                            <!-- Comments Section -->
                                            <?php if ($allow_comments && $question['type'] !== 'textarea'): ?>
                                                <div class="comments-section mt-3">
                                                    <label class="form-label small">
                                                        Additional Comments (Optional)
                                                    </label>
                                                    <textarea name="comments[<?= $qid ?>]" 
                                                              class="form-control form-control-sm" 
                                                              rows="2"
                                                              placeholder="Any additional comments..."></textarea>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php $question_num++; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- General Comments -->
                        <?php if ($allow_comments): ?>
                            <div class="card mb-4 border-info">
                                <div class="card-header bg-info-subtle">
                                    <h6 class="mb-0">
                                        <i class="ti ti-message-circle me-2"></i>
                                        General Comments
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <textarea name="general_comments" 
                                              class="form-control" 
                                              rows="4"
                                              placeholder="Any additional overall comments or suggestions..."></textarea>
                                    <div class="form-text mt-2">
                                        Your feedback is valuable for continuous improvement.
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Confidentiality Notice -->
                        <div class="alert alert-warning mb-4">
                            <i class="ti ti-shield-check me-2"></i>
                            <strong>Confidentiality Notice:</strong>
                            Your feedback will be treated as 
                            <strong><?= $form['is_anonymous'] ? 'completely anonymous' : 'confidential' ?></strong>. 
                            <?php if (!$form['is_anonymous']): ?>
                                Your name will only be visible to authorized reviewers.
                            <?php endif; ?>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between">
                            <a href="<?= site_url('dashboard') ?>" class="btn btn-light">
                                <i class="ti ti-arrow-left me-1"></i> Cancel
                            </a>
                            
                            <div>
                                <button type="button" class="btn btn-secondary me-2" onclick="saveAsDraft()">
                                    <i class="ti ti-device-floppy me-1"></i> Save as Draft
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-send me-1"></i> Submit Feedback
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation and handling
document.getElementById('feedbackForm').addEventListener('submit', function(e) {
    // Validate required fields
    const requiredFields = this.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value || (field.type === 'checkbox' && !field.checked)) {
            isValid = false;
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Please complete all required fields.');
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="ti ti-loader me-1"></i> Submitting...';
    submitBtn.disabled = true;
    
    return true;
});

function saveAsDraft() {
    // Save form data to localStorage as draft
    const formData = new FormData(document.getElementById('feedbackForm'));
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    localStorage.setItem('feedback_draft_<?= $form['id'] ?>', JSON.stringify(data));
    
    // Show success message
    const btn = event.target;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="ti ti-check me-1"></i> Draft Saved!';
    btn.classList.remove('btn-secondary');
    btn.classList.add('btn-success');
    
    setTimeout(() => {
        btn.innerHTML = originalHTML;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-secondary');
    }, 2000);
}

// Load draft if exists
document.addEventListener('DOMContentLoaded', function() {
    const draft = localStorage.getItem('feedback_draft_<?= $form['id'] ?>');
    if (draft) {
        if (confirm('You have a saved draft. Would you like to load it?')) {
            const data = JSON.parse(draft);
            
            // Populate form fields
            for (const [key, value] of Object.entries(data)) {
                const element = document.querySelector(`[name="${key}"]`);
                if (element) {
                    if (element.type === 'checkbox' || element.type === 'radio') {
                        if (Array.isArray(value)) {
                            value.forEach(val => {
                                const checkbox = document.querySelector(`[name="${key}"][value="${val}"]`);
                                if (checkbox) checkbox.checked = true;
                            });
                        } else {
                            const radio = document.querySelector(`[name="${key}"][value="${value}"]`);
                            if (radio) radio.checked = true;
                        }
                    } else {
                        element.value = value;
                    }
                }
            }
            
            // Remove draft after loading
            localStorage.removeItem('feedback_draft_<?= $form['id'] ?>');
        }
    }
    
    // Add input validation styles
    document.querySelectorAll('[required]').forEach(field => {
        field.addEventListener('change', function() {
            if (this.value) {
                this.classList.remove('is-invalid');
            }
        });
    });
});
</script>

<style>
.question-card {
    transition: all 0.3s ease;
}

.question-card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.rating-options .form-check-input {
    width: 1.2em;
    height: 1.2em;
}

.scale-container input[type="range"] {
    height: 8px;
    border-radius: 4px;
}

.comments-section textarea {
    font-size: 0.875rem;
}
</style>