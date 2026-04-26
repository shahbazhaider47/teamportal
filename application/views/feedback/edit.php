<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">

  <!-- HEADER -->
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-2 mb-3 rounded-3 shadow-sm">
    <h1 class="h6 mb-0"><?= html_escape($page_title ?? 'Edit Feedback Form'); ?></h1>

    <a href="<?= site_url('feedback'); ?>" class="btn btn-light-primary btn-header">
      <i class="ti ti-arrow-left me-1"></i> Back to Forms
    </a>
  </div>

  <form method="post" id="feedbackForm" class="app-form">

    <div class="row">
    <div class="col-md-6">

    <!-- BASIC INFORMATION -->
    <div class="card mb-3">
      <div class="card-body">

        <h5 class="mb-3 border-bottom pb-2">
          <i class="ti ti-info-circle me-2"></i> Basic Information
        </h5>

        <div class="row g-3">

          <div class="col-md-6">
            <label class="form-label fw-semibold required">Form Title</label>
            <input type="text" name="title" class="form-control"
                   value="<?= html_escape($form['title']) ?>" required>
          </div>

          <div class="col-md-3">
            <label class="form-label fw-semibold required">Frequency</label>
            <select name="frequency" class="form-select" required>
              <?php foreach (['weekly','monthly','yearly'] as $f): ?>
                <option value="<?= $f ?>" <?= $form['frequency']===$f?'selected':'' ?>>
                  <?= ucfirst($f) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                  <option value="draft" <?= $form['status'] ? 'selected':'' ?>>Required</option>
                  <option value="active" <?= !$form['status'] ? 'selected':'' ?>>Optional</option>
                </select>
            </div>
            
          <div class="col-md-3">
            <label class="form-label fw-semibold">Participation</label>
            <select name="is_required" class="form-select">
              <option value="1" <?= $form['is_required'] ? 'selected':'' ?>>Required</option>
              <option value="0" <?= !$form['is_required'] ? 'selected':'' ?>>Optional</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label fw-semibold">Rating Scale</label>
            <select name="rating_scale" class="form-select">
              <?php foreach ([3,5,10] as $scale): ?>
                <option value="<?= $scale ?>" <?= ($schema['settings']['rating_scale'] ?? 5) == $scale ? 'selected':'' ?>>
                  1–<?= $scale ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label fw-semibold required">Start Date</label>
            <input type="date" name="start_date" class="form-control"
                   value="<?= $form['start_date'] ?>" required>
          </div>

          <div class="col-md-3">
            <label class="form-label fw-semibold">End Date</label>
            <input type="date" name="end_date" class="form-control"
                   value="<?= $form['end_date'] ?>">
          </div>


<div class="col-md-3">
  <div class="form-check mt-2">
    <input
      class="form-check-input"
      type="checkbox"
      id="noEndDate"
      <?= empty($form['end_date']) ? 'checked' : '' ?>
      onclick="document.querySelector('[name=end_date]').disabled=this.checked;"
    >
    <label class="form-check-label" for="noEndDate">
      No End Date
    </label>
  </div>
</div>

<div class="col-md-3">
  <div class="form-check mt-2">
    <input
      type="checkbox"
      name="notify_participants"
      value="1"
      class="form-check-input"
      <?= !empty($form['notify_participants']) ? 'checked' : '' ?>
    >
    <label class="form-check-label">
      Notify Participants
    </label>
  </div>
</div>

<div class="col-md-3">
  <div class="form-check mt-2">
    <input
      type="checkbox"
      name="notify_reviewers"
      value="1"
      class="form-check-input"
      <?= !empty($form['notify_reviewers']) ? 'checked' : '' ?>
    >
    <label class="form-check-label">
      Notify Reviewers
    </label>
  </div>
</div>

              
          <div class="col-12">
            <label class="form-label fw-semibold">Instructions</label>
            <textarea name="description" class="form-control" rows="4"><?= html_escape($form['description']) ?></textarea>
          </div>

          <!-- ASSIGNMENTS -->
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Assign to Departments</label>
              <select name="assigned_departments[]" class="form-select select2-multiple" multiple>
                <?php
                  $assigned = array_filter(explode(',', $form['assigned_departments'] ?? ''));
                ?>
                <?php foreach ($departments as $d): ?>
                  <option value="<?= $d['id'] ?>" <?= in_array($d['id'], $assigned) ? 'selected':'' ?>>
                    <?= html_escape($d['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Reviewers</label>
              <select name="reviewers[]" class="form-select select2-multiple" multiple>
                <?php
                  $reviewers = array_filter(explode(',', $form['reviewers'] ?? ''));
                ?>
                <?php foreach ($staff_members as $u): ?>
                  <option value="<?= $u['id'] ?>" <?= in_array($u['id'], $reviewers) ? 'selected':'' ?>>
                    <?= html_escape($u['firstname'].' '.$u['lastname']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

        </div>
      </div>
    </div>
    </div>

<!-- QUESTIONS -->
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

      <div class="alert alert-info small">
        Use clear, neutral wording. Rating questions contribute to analytics automatically.
      </div>

      <div id="questionsContainer"></div>

    </div>
  </div>
</div>


    <!-- ACTION -->
    <div class="d-flex justify-content-end">
      <button type="submit" class="btn btn-primary btn-sm">
        Update Feedback Form
      </button>
    </div>

    </div>
  </form>
</div>

<!-- QUESTION TEMPLATE -->
<div id="questionTemplate" class="d-none">
  <div class="question-card card mb-3">
    <div class="card-body">

      <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Question</strong>
        <div class="d-flex gap-2">
          <span class="question-handle text-muted">
            <i class="ti ti-grip-vertical"></i>
          </span>
          <button type="button"
                  class="btn btn-sm btn-light-danger"
                  onclick="removeQuestion(this)">
            <i class="ti ti-trash"></i>
          </button>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <input type="text"
                 name="questions[q{index}][label]"
                 class="form-control"
                 placeholder="Question text"
                 required>
        </div>

        <div class="col-md-3">
          <select name="questions[q{index}][type]"
                  class="form-select"
                  onchange="updateQuestionOptions(this)">
            <option value="rating">Rating</option>
            <option value="textarea">Text</option>
            <option value="yes_no">Yes / No</option>
          </select>
        </div>

        <div class="col-md-3">
          <input type="text"
                 name="questions[q{index}][category]"
                 class="form-control"
                 placeholder="Category">
        </div>

        <div class="col-12 options-container"></div>

        <div class="col-12 text-end">
          <div class="form-check d-inline-block">
            <input type="checkbox"
                   class="form-check-input"
                   name="questions[q{index}][required]"
                   value="1"
                   checked>
            <label class="form-check-label">
              Required
            </label>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>


<script>
let questionIndex = 0;
const existingQuestions = <?= json_encode($schema['questions'] ?? []) ?>;

function addQuestion(data = null) {
  const container = document.getElementById('questionsContainer');
  const tpl = document.getElementById('questionTemplate').innerHTML;
  const html = tpl.replaceAll('{index}', questionIndex);

  const div = document.createElement('div');
  div.innerHTML = html;
  const card = div.firstElementChild;

  if (data) {
    card.querySelector('[name$="[label]"]').value = data.label || '';
    card.querySelector('[name$="[type]"]').value = data.type || 'rating';
    card.querySelector('[name$="[category]"]').value = data.category || '';

    if (data.required == 0) {
      card.querySelector('[name$="[required]"]').checked = false;
    }
  }

  container.appendChild(card);
  questionIndex++;
  updateQuestionCount();
}

function removeQuestion(btn) {
  btn.closest('.question-card').remove();
  updateQuestionCount();
}

function updateQuestionCount() {
  document.getElementById('questionCount').innerText =
    document.querySelectorAll('.question-card').length;
}

function updateQuestionOptions(select) {
  const card = select.closest('.question-card');
  const options = card.querySelector('.options-container');
  options.innerHTML = '';
}

document.getElementById('feedbackForm').addEventListener('submit', function(e) {
  if (document.querySelectorAll('.question-card').length === 0) {
    e.preventDefault();
    alert('Please add at least one question.');
  }
});

new Sortable(document.getElementById('questionsContainer'), {
  handle: '.question-handle',
  animation: 150
});

document.addEventListener('DOMContentLoaded', () => {
  if (existingQuestions.length) {
    existingQuestions.forEach(q => addQuestion(q));
  } else {
    addQuestion();
  }
});
</script>

