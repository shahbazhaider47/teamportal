<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="container-fluid">
  <!-- Page header (left untouched) -->
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= html_escape($page_title) ?> <i class="ti ti-chevron-right"></i> <span class="small text-muted"><?= html_escape($title ?? 'Email Template'); ?></span></h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <div class="btn-divider"></div>

      <a href="<?= site_url('emails') ?>"
         id="btn-manage-users"
         class="btn btn-light-primary btn-header">
          <i class="fas fa-arrow-left me-1"></i> Go Back
      </a>
      
    </div>
  </div>

  <div id="wrapper">
    <div class="content">
      <div class="row">
        <!-- Editor -->
        <div class="col-md-8">
          <div class="card">
            <div class="card-body">
              <?php
                $tpl = isset($template) ? $template : null;
                $tplId = (int)($tpl->emailtemplateid ?? $tpl->id ?? 0);
                $action = site_url('emails/email_template/' . $tplId);
              ?>
              <form class="app-form" method="post" action="<?= $action ?>" novalidate>
                <!-- Name (read-only) -->
                <div class="mb-3">
                  <label for="name" class="form-label">Template name</label>
                  <input type="text" id="name" name="name" class="form-control" value="<?= html_escape($tpl->name ?? '') ?>" disabled>
                </div>

                <div class="mb-3">
                  <label for="subject" class="form-label">Subject</label>
                  <input type="text" id="subject" name="subject" class="form-control" value="<?= html_escape($tpl->subject ?? '') ?>" required>
                </div>

                <div class="mb-3">
                  <label for="fromname" class="form-label">From name</label>
                  <input type="text" id="fromname" name="fromname" class="form-control" value="<?= html_escape($tpl->fromname ?? '') ?>" required>
                </div>

                <div class="mb-3">
                  <label for="fromemail" class="form-label">From email</label>
                  <input type="email" id="fromemail" name="fromemail" class="form-control" value="<?= html_escape($tpl->fromemail ?? '') ?>">
                </div>

                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" id="plaintext" name="plaintext" <?= ((int)($tpl->plaintext ?? 0) === 1 ? 'checked' : '') ?>>
                  <label class="form-check-label" for="plaintext">Send as plain text</label>
                </div>

                <div class="form-check mb-3">
                  <input class="form-check-input" type="checkbox" id="disabled" name="disabled" <?= ((int)($tpl->active ?? 1) === 0 ? 'checked' : '') ?>>
                  <label class="form-check-label" for="disabled" title="When disabled, this template won't be used.">
                    Disable template (don’t send)
                  </label>
                </div>

                <hr class="my-3">

                <h5 class="mb-2">Message</h5>
                <textarea id="message" name="message" class="form-control rte" rows="16">
                <?= isset($template->message) ? $template->message : '' ?>
                </textarea>

                <div class="d-flex justify-content-end mt-3 gap-2">
                  <button type="submit" class="btn btn-primary">Submit</button>
                  <button type="button"
                          id="btnPreviewTpl"
                          class="btn btn-light"
                          data-preview-title="<?= html_escape($tpl->subject ?? 'Preview'); ?>">
                    Preview
                  </button>
                </div>

              </form>
            </div>
          </div>
        </div>

        <!-- Merge fields (optional) -->
        <div class="col-md-4 lg:tw-sticky lg:tw-top-2">
          <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700">Available merge fields</h4>

          <div class="card">
            <div class="card-body">
              <div class="row available_merge_fields_container">
                <?php if (empty($available_merge_fields) || !is_array($available_merge_fields)): ?>
                  <div class="col-md-12">
                    <em>No predefined merge fields. You can use placeholders like {user.fullname}.</em>
                  </div>
                <?php else: ?>
                  <?php foreach ($available_merge_fields as $group => $items): ?>
                    <div class="col-md-12 mb-2">
                      <h6 class="fw-bold mb-2"><?= html_escape(ucwords(str_replace(['_','-'], ' ', (string)$group))) ?></h6>
                      <?php foreach ($items as $mf): ?>
                        <?php
                          $key  = (string)($mf['key'] ?? '');
                          $name = (string)($mf['name'] ?? $key);
                        ?>
                        <p class="mb-1">
                          <?= html_escape($name) ?>
                          <?php if ($key !== ''): ?>
                            <span class="float-end">
                              <a href="#" class="add_merge_field" data-key="<?= html_escape($key) ?>"><?= html_escape($key) ?></a>
                            </span>
                          <?php endif; ?>
                        </p>
                      <?php endforeach; ?>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /.row -->
      <div class="btn-bottom-pusher"></div>
    </div>
  </div>
</div>


<!-- Template Preview Modal -->
<div class="modal fade" id="tplPreviewModal" tabindex="-1" aria-labelledby="tplPreviewLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title" id="tplPreviewLabel">Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-0" style="height:70vh;">
        <!-- We render the HTML inside this iframe for true email-like view -->
        <iframe id="tplPreviewFrame" title="Email Preview" style="width:100%; height:100%; border:0;"></iframe>
      </div>

      <div class="modal-footer py-2">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var lastFocused = null;
  var form = document.querySelector('form.app-form');
  var tplId = (function() {
    var action = form ? form.getAttribute('action') || '' : '';
    var m = action.match(/emails\/email_template\/(\d+)/);
    return m ? m[1] : '';
  })();

  // Track last focused field
  document.addEventListener('focusin', function (e) {
    var el = e.target;
    if (!el) return;
    if (el.id === 'subject' || el.id === 'message') {
      lastFocused = el;
    }
  });

  // Insert at caret helper for <input>/<textarea>
  function insertAtCaret(el, text) {
    if (!el) return;
    if (typeof el.selectionStart === 'number') {
      var start = el.selectionStart, end = el.selectionEnd;
      var v = el.value;
      el.value = v.slice(0, start) + text + v.slice(end);
      el.selectionStart = el.selectionEnd = start + text.length;
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.focus();
    } else {
      el.value += text;
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.focus();
    }
  }

  // Merge field click → insert token
  document.addEventListener('click', function (e) {
    var a = e.target.closest('.add_merge_field');
    if (!a) return;
    e.preventDefault();
    var token = a.getAttribute('data-key') || '';
    if (!token) return;

    // If focus is on subject, insert there
    var subj = document.getElementById('subject');
    if (document.activeElement === subj || lastFocused === subj) {
      insertAtCaret(subj, token);
      return;
    }

    // Otherwise, drop into the rich text editor for "message"
    if (window.RichText && window.RichText.insertHTML) {
      window.RichText.insertHTML('message', token);
    } else {
      // fallback to raw textarea
      var msg = document.getElementById('message');
      insertAtCaret(msg, token);
    }
  }, false);

  // Ensure the RTE saves content back to the textarea before submit
  if (form) form.addEventListener('submit', function () {
    if (window.RichText && window.RichText.triggerSave) window.RichText.triggerSave();
  });

  // Compose a minimal email wrapper for iframe
  function composeEmailHtml(subjectTxt, bodyHtml) {
    return [
      '<!doctype html><html><head>',
      '<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">',
      '<title>' + (subjectTxt || 'Preview') + '</title>',
      '<style>body{margin:0;padding:24px;background:#f5f7fb;color:#222;font:14px/1.6 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif}',
      '.email-container{max-width:600px;margin:0 auto;background:#fff;border:1px solid #eef0f5;border-radius:8px;box-shadow:0 1px 2px rgba(0,0,0,.03);overflow:hidden}',
      '.email-header{padding:16px 20px;border-bottom:1px solid #eef0f5;font-weight:600}',
      '.email-body{padding:20px}</style>',
      '</head><body><div class="email-container">',
      '<div class="email-header">' + (subjectTxt || 'Preview') + '</div>',
      '<div class="email-body">' + (bodyHtml || '<em>(Empty body)</em>') + '</div>',
      '</div></body></html>'
    ].join('');
  }

  // Preview with server-side merge substitutions
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('#btnPreviewTpl');
    if (!btn) return;
    e.preventDefault();

    if (window.RichText && window.RichText.triggerSave) window.RichText.triggerSave();

    var subjectTxt = (document.getElementById('subject')?.value || btn.getAttribute('data-preview-title') || 'Preview');
    var html = (window.RichText && window.RichText.getHTML)
      ? window.RichText.getHTML('message')
      : (document.getElementById('message')?.value || '');

    // Build form data for POST /emails/render_preview/{id}
    var fd = new FormData();
    fd.append('user_id', (window.CURRENT_USER_ID || '')); // optional context; add others if needed

    fetch('<?= site_url('emails/render_preview/') ?>' + encodeURIComponent(tplId), {
      method: 'POST',
      body: fd,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    })
    .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
    .then(function (data) {
      // Fallback to client values if server returns nothing
      var mergedSubject = (data && data.subject) ? data.subject : subjectTxt;
      var mergedHtml    = (data && data.html)    ? data.html    : html;

      var docHtml = composeEmailHtml(mergedSubject, mergedHtml);
      var iframe  = document.getElementById('tplPreviewFrame');
      if (!iframe) return;

      var doc = iframe.contentDocument || iframe.contentWindow.document;
      doc.open('text/html', 'replace'); doc.write(docHtml); doc.close();

      var titleNode = document.getElementById('tplPreviewLabel');
      if (titleNode) titleNode.textContent = mergedSubject;

      var modalEl = document.getElementById('tplPreviewModal');
      if (window.bootstrap && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
      } else {
        modalEl.style.display = 'block';
      }
    })
    .catch(function () {
      // If preview API fails, still show local preview
      var docHtml = composeEmailHtml(subjectTxt, html);
      var iframe  = document.getElementById('tplPreviewFrame');
      if (!iframe) return;
      var doc = iframe.contentDocument || iframe.contentWindow.document;
      doc.open('text/html', 'replace'); doc.write(docHtml); doc.close();

      var modalEl = document.getElementById('tplPreviewModal');
      if (window.bootstrap && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
      } else {
        modalEl.style.display = 'block';
      }
    });
  }, false);
})();
</script>