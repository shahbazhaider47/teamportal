<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">

  <!-- HEADER -->
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-2 mb-3 rounded-3 shadow-sm">
    <h1 class="h6 mb-0"><?= html_escape($page_title ?? 'Create Feedback Form'); ?></h1>

    <a href="<?= site_url('feedback'); ?>" class="btn btn-light-primary btn-header">
      <i class="ti ti-arrow-left me-1"></i> Back to Forms
    </a>
  </div>

  <form method="post" id="feedbackForm" class="app-form">

    <div class="row">

      <!-- ================= BASIC INFORMATION (UNCHANGED) ================= -->
      <div class="col-md-6">
        <div class="card mb-3">
          <div class="card-body">

            <h5 class="mb-3 border-bottom pb-2">
              <i class="ti ti-info-circle me-2"></i> Basic Information
            </h5>

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label required">Form Title</label>
                    <input type="text" name="title" class="form-control"
                           placeholder="e.g. Monthly Employee Pulse"
                           required>
                  </div>
        
                  <div class="col-md-3">
                    <label class="form-label required">Frequency</label>
                    <select name="frequency" class="form-select" required>
                      <option value="weekly">Weekly</option>
                      <option value="monthly" selected>Monthly</option>
                      <option value="yearly">Yearly</option>
                    </select>
                  </div>
        
                  <div class="col-md-3">
                    <label class="form-label">Participation</label>
                    <select name="is_required" class="form-select">
                      <option value="1">Required</option>
                      <option value="0">Optional</option>
                    </select>
                  </div>
        
                  <div class="col-md-3">
                    <label class="form-label">Rating Scale</label>
                    <select name="rating_scale" class="form-select">
                      <option value="3">1–3</option>
                      <option value="5" selected>1–5</option>
                      <option value="10">1–10</option>
                    </select>
                  </div>
        
                  <div class="col-md-3">
                    <label class="form-label required">Start Date</label>
                    <input type="date" name="start_date" class="form-control basic-date" placeholder="YYYY-MM-DD" required>
                  </div>
        
                  <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control basic-date" placeholder="YYYY-MM-DD">
                  </div>
        
                  <div class="col-md-3 mt-3">
                    <div class="form-check mt-4">
                      <input class="form-check-input" type="checkbox" id="noEndDate"
                             onclick="document.querySelector('[name=end_date]').disabled=this.checked;">
                      <label class="form-check-label" for="noEndDate">
                        No End Date
                      </label>
                    </div>
                  </div>
        
                      <div class="col-md-3">
                        <div class="form-check mt-2">
                          <input type="checkbox" name="notify_participants" value="1" class="form-check-input" checked>
                          <label class="form-check-label">Notify Participants</label>
                        </div>
                      </div>
                
                      <div class="col-md-3">
                        <div class="form-check mt-2">
                          <input type="checkbox" name="notify_reviewers" value="1" class="form-check-input" checked>
                          <label class="form-check-label">Notify Reviewers</label>
                        </div>
                      </div>
                      
                  <div class="col-12">
                    <label class="form-label">Instructions</label>
                    <textarea name="description" class="form-control" rows="4"
                      placeholder="Explain how this feedback will be used (optional)"></textarea>
                  </div>
        
                <!-- ASSIGNMENT & NOTIFICATIONS -->
                    <div class="row g-3">
                
                      <div class="col-md-6">
                        <label class="form-label">Assign to Departments</label>
                        <select name="assigned_departments[]" class="form-select select2-multiple" multiple>
                          <?php foreach (($departments ?? []) as $d): ?>
                            <option value="<?= $d['id'] ?>">
                              <?= html_escape($d['name']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                
                      <div class="col-md-6">
                        <label class="form-label">Reviewers</label>
                        <select name="reviewers[]" class="form-select select2-multiple" multiple>
                          <?php foreach (($staff_members ?? []) as $s): ?>
                            <option value="<?= (int) $s['id'] ?>">
                                <?= html_escape($s['firstname'].' '.$s['lastname']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                
                    </div>
                  
                </div>
        
          </div>
        </div>
      </div>

      <!-- ================= QUESTIONS ================= -->
      <div class="col-md-6">
        <div class="card mb-3">
          <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="mb-0">
                Questions
                <span class="badge bg-primary ms-2" id="questionCount">0</span>
              </h5>

              <button type="button" class="btn btn-outline-primary btn-header" onclick="addQuestion()">
                <i class="ti ti-plus"></i> Add Question
              </button>
            </div>

            <div class="alert alert-light-primary small">
              <strong>Best Practices:</strong> Use clear, neutral wording. Mix question types for better engagement. Rating questions are automatically included in analytics.
            </div>

            <div id="questionsContainer"></div>

          </div>
        </div>
      </div>

      <!-- ACTIONS -->
      <div class="col-12 d-flex justify-content-between">
        <button type="submit" class="btn btn-primary btn-sm">Save Feedback Form</button>
      </div>

    </div>
  </form>
</div>

<!-- ================= QUESTION TEMPLATE ================= -->
<div id="questionTemplate" class="d-none app-form">
  <div class="question-card card mb-3">
    <div class="card-body">

      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="question-handle text-muted" style="cursor:move">
          <i class="ti ti-grip-vertical"></i>
        </span>

        <button type="button" class="btn btn-ssm btn-light-danger" onclick="removeQuestion(this)">
          <i class="ti ti-trash"></i>
        </button>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Question Text</label>
          <input type="text" name="questions[q{index}][label]" class="form-control" required placeholder="Enter your question here">
        </div>

        <div class="col-md-3">
          <label class="form-label">Question Type</label>
          <select name="questions[q{index}][type]" class="form-select">
            <option value="rating">Rating</option>
            <option value="textarea">Text</option>
            <option value="yes_no">Yes / No</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Category</label>
          <select name="questions[q{index}][category]" class="form-select">
            <option value="">General</option>
            <option value="performance">Performance</option>
            <option value="culture">Culture</option>
            <option value="leadership">Leadership</option>
            <option value="workload">Workload</option>
            <option value="communication">Communication</option>
          </select>
        </div>

        <div class="col-12 text-end">
          <div class="form-check d-inline-block">
            <input type="checkbox" class="form-check-input"
                   name="questions[q{index}][required]" value="1" checked>
            <label class="form-check-label small">Required</label>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
let questionIndex = 0;

function addQuestion() {
  const container = document.getElementById('questionsContainer');
  const tpl = document.getElementById('questionTemplate').innerHTML;
  const html = tpl.replaceAll('{index}', questionIndex);

  const div = document.createElement('div');
  div.innerHTML = html;
  container.appendChild(div.firstElementChild);

  questionIndex++;
  updateQuestionCount();
}

function removeQuestion(btn) {
  btn.closest('.question-card').remove();
  updateQuestionCount();
}

function updateQuestionCount() {
  document.getElementById('questionCount').innerText =
    document.querySelectorAll('#questionsContainer .question-card').length;
}

document.getElementById('feedbackForm').addEventListener('submit', function(e) {
  if (document.querySelectorAll('.question-card').length === 0) {
    e.preventDefault();
    alert('Please add at least one question.');
  }
});

document.addEventListener('DOMContentLoaded', () => {
  addQuestion();

  new Sortable(document.getElementById('questionsContainer'), {
    handle: '.question-handle',
    animation: 150,
    onEnd: updateQuestionCount
  });
});
</script>
