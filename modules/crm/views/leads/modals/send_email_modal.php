<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$leadId    = (int)($lead['id'] ?? 0);
$leadEmail = $lead['contact_email'] ?? '';
?>

<div class="modal fade app-modal" id="sendEmailModal" tabindex="-1"
     aria-labelledby="sendEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <form class="app-form"
                  id="sendEmailForm"
                  action="<?= site_url('crm/leads/send_email_to_lead/' . $leadId) ?>"
                  method="post"
                  enctype="multipart/form-data">

                <!-- ── Header ─────────────────────────────────────── -->
                <div class="app-modal-header">
                    <div class="app-modal-header-left">
                        <div class="app-modal-icon app-modal-icon-teal">
                            <i class="ti ti-mail"></i>
                        </div>
                        <div class="app-modal-title-wrap">
                            <div class="app-modal-title" id="sendEmailModalLabel">Send Email to Lead</div>
                            <div class="app-modal-subtitle">Send reminder, follow-up, meeting invitation or custom email</div>
                        </div>
                    </div>
                    <button type="button" class="app-modal-close" data-bs-dismiss="modal">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <!-- ── Body ──────────────────────────────────────── -->
                <div class="app-modal-body">

                    <div class="app-form-section">
                        <div class="row g-3">

                            <!-- To -->
                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="sem_to">To</label>
                                    <input type="email"
                                           id="sem_to"
                                           name="to"
                                           class="app-form-control"
                                           value="<?= html_escape($leadEmail) ?>"
                                           required readonly>
                                </div>
                            </div>

                            <!-- CC -->
                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="sem_cc">CC</label>
                                    <input type="text"
                                           id="sem_cc"
                                           name="cc"
                                           class="app-form-control"
                                           placeholder="email1@example.com, email2@example.com">
                                </div>
                            </div>

                            <!-- Email Type -->
                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="sem_type">Email Type</label>
                                    <div class="app-form-select-wrap">
                                        <select class="app-form-control" id="sem_type">
                                            <option value="">— Custom —</option>
                                            <option value="reminder">Reminder</option>
                                            <option value="follow_up">Follow Up</option>
                                            <option value="meeting">Meeting</option>
                                            <option value="proposal">Proposal</option>
                                            <option value="introduction">Introduction</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Subject -->
                            <div class="col-md-8">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="sem_subject">Subject</label>
                                    <input type="text"
                                           id="sem_subject"
                                           name="subject"
                                           class="app-form-control"
                                           placeholder="Enter email subject…"
                                           required>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Message -->
                    <div class="app-form-section">
                        <div class="app-form-group">
                            <label class="app-form-label">Message</label>

                            <!-- Toolbar -->
                            <div class="sem-toolbar">

                                <div class="sem-tb-group">
                                    <select class="sem-tb-select" id="sem_heading" title="Paragraph style">
                                        <option value="div">Normal</option>
                                        <option value="h1">Heading 1</option>
                                        <option value="h2">Heading 2</option>
                                        <option value="h3">Heading 3</option>
                                    </select>
                                </div>

                                <div class="sem-tb-sep"></div>

                                <div class="sem-tb-group">
                                    <button type="button" class="sem-tb-btn" data-cmd="bold"          title="Bold"><i class="ti ti-bold"></i></button>
                                    <button type="button" class="sem-tb-btn" data-cmd="italic"        title="Italic"><i class="ti ti-italic"></i></button>
                                    <button type="button" class="sem-tb-btn" data-cmd="underline"     title="Underline"><i class="ti ti-underline"></i></button>
                                    <button type="button" class="sem-tb-btn" data-cmd="strikeThrough" title="Strikethrough"><i class="ti ti-strikethrough"></i></button>
                                </div>

                                <div class="sem-tb-sep"></div>

                                <div class="sem-tb-group">
                                    <button type="button" class="sem-tb-btn" data-cmd="justifyLeft"   title="Align left"><i class="ti ti-align-left"></i></button>
                                    <button type="button" class="sem-tb-btn" data-cmd="justifyCenter" title="Align center"><i class="ti ti-align-center"></i></button>
                                    <button type="button" class="sem-tb-btn" data-cmd="justifyRight"  title="Align right"><i class="ti ti-align-right"></i></button>
                                </div>

                                <div class="sem-tb-sep"></div>

                                <div class="sem-tb-group">
                                    <button type="button" class="sem-tb-btn" data-cmd="insertUnorderedList" title="Bullet list"><i class="ti ti-list"></i></button>
                                    <button type="button" class="sem-tb-btn" data-cmd="insertOrderedList"   title="Numbered list"><i class="ti ti-list-numbers"></i></button>
                                    <button type="button" class="sem-tb-btn" data-cmd="indent"              title="Indent"><i class="ti ti-indent-increase"></i></button>
                                    <button type="button" class="sem-tb-btn" data-cmd="outdent"             title="Outdent"><i class="ti ti-indent-decrease"></i></button>
                                </div>

                                <div class="sem-tb-sep"></div>

                                <div class="sem-tb-group">
                                    <button type="button" class="sem-tb-btn" id="sem_link_btn"       title="Insert link"><i class="ti ti-link"></i></button>
                                    <button type="button" class="sem-tb-btn" data-cmd="unlink"       title="Remove link"><i class="ti ti-link-off"></i></button>
                                    <button type="button" class="sem-tb-btn" data-cmd="removeFormat" title="Clear formatting"><i class="ti ti-clear-formatting"></i></button>
                                </div>

                                <div class="sem-tb-sep"></div>

                                <div class="sem-tb-group">
                                    <div class="sem-tb-color-wrap" title="Font color">
                                        <i class="ti ti-letter-a sem-color-icon"></i>
                                        <input type="color" class="sem-tb-color" id="sem_font_color" value="#000000">
                                    </div>
                                    <select class="sem-tb-select" id="sem_font_size" title="Font size">
                                        <option value="1">10px</option>
                                        <option value="2">12px</option>
                                        <option value="3" selected>14px</option>
                                        <option value="4">18px</option>
                                        <option value="5">24px</option>
                                        <option value="6">32px</option>
                                    </select>
                                </div>

                            </div>
                            <!-- /Toolbar -->

                            <div contenteditable="true"
                                 id="sem_editor"
                                 class="sem-editor"
                                 data-placeholder="Write your message here…"
                                 aria-label="Email message body"
                                 role="textbox"
                                 aria-multiline="true"></div>

                            <textarea name="message" id="sem_message_hidden" hidden></textarea>
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div class="app-form-section" style="margin-bottom:0;">
                        <div class="app-form-group">
                            <label class="app-form-label">Attachments</label>

                            <div class="sem-drop-zone" id="semDropZone">
                                <input type="file" name="attachments[]" id="sem_files" multiple hidden>
                                <i class="ti ti-cloud-upload sem-drop-icon"></i>
                                <p class="sem-drop-text">
                                    Drag &amp; drop files here or
                                    <span class="sem-browse" role="button" tabindex="0">browse files</span>
                                </p>
                                <p class="sem-drop-hint">Any file type &middot; max 10 MB per file</p>
                            </div>

                            <ul class="sem-file-list" id="semFileList" aria-live="polite"></ul>
                        </div>
                    </div>

                </div>
                <!-- /Body -->

                <!-- ── Footer ────────────────────────────────────── -->
                <div class="app-modal-footer">
                    <div class="app-modal-footer-left">
                        <i class="ti ti-info-circle" style="font-size:13px;"></i>
                        Email will be logged in lead activity.
                    </div>
                    <button type="button" class="app-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="app-btn-submit" id="semSubmitBtn">
                        <i class="ti ti-send"></i> Send Email
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     STYLES
════════════════════════════════════════════════════════════ -->
<style>
/* ── Toolbar ──────────────────────────────────────────────── */
.sem-toolbar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 2px;
    padding: 6px 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-bottom: none;
    border-radius: 8px 8px 0 0;
}
.sem-tb-group { display: flex; align-items: center; gap: 1px; }
.sem-tb-sep   { width: 1px; height: 22px; background: #e2e8f0; margin: 0 5px; flex-shrink: 0; }

.sem-tb-btn {
    width: 32px;
    height: 32px;
    display: grid;
    place-items: center;
    border: none;
    background: transparent;
    border-radius: 6px;
    color: #64748b;
    font-size: 15px;
    cursor: pointer;
    transition: background .12s, color .12s;
    flex-shrink: 0;
}
.sem-tb-btn:hover  { background: #e8fafa; color: #1e7a7a; }
.sem-tb-btn.active { background: #d0f5f5; color: #0f766e; }

.sem-tb-select {
    height: 30px;
    padding: 0 6px;
    font-size: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
    color: #475569;
    cursor: pointer;
    outline: none;
}
.sem-tb-select:focus { border-color: #5ebfbf; }

/* colour picker with icon overlay */
.sem-tb-color-wrap {
    position: relative;
    width: 32px;
    height: 32px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    overflow: hidden;
    cursor: pointer;
    flex-shrink: 0;
}
.sem-color-icon {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    font-size: 15px;
    color: #475569;
    pointer-events: none;
    z-index: 1;
    background: rgba(255,255,255,.7);
}
.sem-tb-color {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    border: none;
    padding: 0;
}

/* ── Editor area ──────────────────────────────────────────── */
.sem-editor {
    min-height: 220px;
    max-height: 380px;
    overflow-y: auto;
    padding: 14px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 0 0 8px 8px;
    font-size: 13.5px;
    line-height: 1.7;
    color: #0f172a;
    background: #fff;
    outline: none;
    word-break: break-word;
    transition: border-color .15s, box-shadow .15s;
}
.sem-editor:focus {
    border-color: #5ebfbf;
    box-shadow: 0 0 0 3px rgba(94,191,191,.15);
}
.sem-editor:empty::before {
    content: attr(data-placeholder);
    color: #94a3b8;
    pointer-events: none;
}

/* ── Drop zone ────────────────────────────────────────────── */
.sem-drop-zone {
    border: 2px dashed #e2e8f0;
    border-radius: 10px;
    padding: 22px 16px;
    text-align: center;
    background: #fafcff;
    transition: border-color .2s, background .2s;
    cursor: default;
}
.sem-drop-zone.drag-over { border-color: #5ebfbf; background: #f0fafa; }
.sem-drop-icon { font-size: 30px; color: #cbd5e1; display: block; margin-bottom: 6px; }
.sem-drop-text { font-size: 13px; color: #64748b; margin: 0 0 3px; }
.sem-drop-hint { font-size: 11px; color: #94a3b8; margin: 0; }
.sem-browse    { color: #5ebfbf; cursor: pointer; text-decoration: underline; }

/* ── File list ────────────────────────────────────────────── */
.sem-file-list {
    list-style: none;
    padding: 0;
    margin: 8px 0 0;
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.sem-file-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 12.5px;
    animation: semFileIn .15s ease;
}
@keyframes semFileIn {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0);    }
}
.sem-file-badge {
    padding: 2px 8px;
    border-radius: 5px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
    min-width: 38px;
    text-align: center;
    flex-shrink: 0;
}
.sem-file-name    { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #1e293b; }
.sem-file-size    { color: #94a3b8; font-size: 11px; white-space: nowrap; flex-shrink: 0; }
.sem-file-actions { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }

.sem-file-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 5px;
    border: 1px solid #e2e8f0;
    background: #fff;
    font-size: 11px;
    cursor: pointer;
    color: #64748b;
    text-decoration: none;
    transition: background .12s, border-color .12s, color .12s;
    white-space: nowrap;
    line-height: 1.6;
}
.sem-file-btn:hover { background: #f1f5f9; border-color: #cbd5e1; color: #1e293b; }
.sem-file-btn.sem-file-remove:hover { background: #fee2e2; border-color: #fca5a5; color: #b91c1c; }

/* spinner */
@keyframes semSpin { to { transform: rotate(360deg); } }
.sem-spin { display: inline-block; animation: semSpin .7s linear infinite; }
</style>


<!-- ══════════════════════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    const $id = id => document.getElementById(id);

    /* ── Helpers ──────────────────────────────────────────── */
    function formatBytes(b) {
        if (b < 1024)    return b + ' B';
        if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
        return (b / 1048576).toFixed(1) + ' MB';
    }

    const EXT_COLORS = {
        pdf:  ['#fee2e2','#b91c1c'],
        doc:  ['#dbeafe','#1d4ed8'], docx: ['#dbeafe','#1d4ed8'],
        xls:  ['#dcfce7','#15803d'], xlsx: ['#dcfce7','#15803d'],
        ppt:  ['#ffedd5','#c2410c'], pptx: ['#ffedd5','#c2410c'],
        png:  ['#f3e8ff','#7e22ce'], jpg:  ['#f3e8ff','#7e22ce'],
        jpeg: ['#f3e8ff','#7e22ce'], gif:  ['#f3e8ff','#7e22ce'], webp: ['#f3e8ff','#7e22ce'],
        zip:  ['#fef9c3','#a16207'], rar:  ['#fef9c3','#a16207'],
        txt:  ['#f1f5f9','#475569'], csv:  ['#f0fdf4','#166534'],
        mp4:  ['#ede9fe','#6d28d9'], mp3:  ['#ede9fe','#6d28d9'],
    };
    function badgeStyle(ext) {
        const [bg, color] = EXT_COLORS[ext.toLowerCase()] || ['#e2e8f0','#475569'];
        return `background:${bg};color:${color};`;
    }

    /* ── Subject auto-fill on type change ────────────────── */
    const SUBJECTS = {
        reminder:     'Friendly Reminder',
        follow_up:    'Following Up',
        meeting:      'Meeting Request',
        proposal:     'Proposal Discussion',
        introduction: 'Introduction & Next Steps',
    };

    $id('sem_type').addEventListener('change', function () {
        const sub = $id('sem_subject');
        if (this.value && !sub.value.trim()) {
            sub.value = SUBJECTS[this.value] || '';
        }
    });

    /* ── Rich text editor ─────────────────────────────────── */
    const editor = $id('sem_editor');

    document.querySelectorAll('.sem-tb-btn[data-cmd]').forEach(btn => {
        btn.addEventListener('mousedown', e => {
            e.preventDefault();
            document.execCommand(btn.dataset.cmd, false, null);
            syncToolbarState();
            editor.focus();
        });
    });

    $id('sem_link_btn').addEventListener('mousedown', e => {
        e.preventDefault();
        const url = prompt('Enter URL:', 'https://');
        if (url) document.execCommand('createLink', false, url);
        editor.focus();
    });

    $id('sem_font_size').addEventListener('change', function () {
        document.execCommand('fontSize', false, this.value);
        editor.focus();
    });

    $id('sem_heading').addEventListener('change', function () {
        document.execCommand('formatBlock', false, this.value);
        editor.focus();
    });

    $id('sem_font_color').addEventListener('input', function () {
        document.execCommand('foreColor', false, this.value);
        editor.focus();
    });

    function syncToolbarState() {
        ['bold','italic','underline','strikeThrough',
         'insertUnorderedList','insertOrderedList',
         'justifyLeft','justifyCenter','justifyRight'].forEach(cmd => {
            const btn = document.querySelector(`.sem-tb-btn[data-cmd="${cmd}"]`);
            if (btn) btn.classList.toggle('active', document.queryCommandState(cmd));
        });
    }
    editor.addEventListener('keyup',   syncToolbarState);
    editor.addEventListener('mouseup', syncToolbarState);

    /* ── File attachments ─────────────────────────────────── */
    const fileInput = $id('sem_files');
    const dropZone  = $id('semDropZone');
    const fileList  = $id('semFileList');
    let   files     = [];

    function rebuildInput() {
        const dt = new DataTransfer();
        files.forEach(f => dt.items.add(f));
        fileInput.files = dt.files;
    }

    function renderFiles() {
        fileList.innerHTML = '';
        files.forEach((file, i) => {
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            const url = URL.createObjectURL(file);

            const li = document.createElement('li');
            li.className = 'sem-file-item';
            li.innerHTML = `
                <span class="sem-file-badge" style="${badgeStyle(ext)}">${ext || '?'}</span>
                <span class="sem-file-name" title="${file.name}">${file.name}</span>
                <span class="sem-file-size">${formatBytes(file.size)}</span>
                <div class="sem-file-actions">
                    <a class="sem-file-btn"
                       href="${url}" target="_blank" rel="noopener"
                       title="View file">
                        <i class="ti ti-eye"></i> View
                    </a>
                    <button type="button"
                            class="sem-file-btn sem-file-remove"
                            data-index="${i}"
                            title="Remove file">
                        <i class="ti ti-trash"></i> Remove
                    </button>
                </div>`;
            fileList.appendChild(li);
        });
    }

    function addFiles(incoming) {
        Array.from(incoming).forEach(f => {
            if (!files.some(e => e.name === f.name && e.size === f.size)) files.push(f);
        });
        rebuildInput();
        renderFiles();
    }

    fileInput.addEventListener('change', () => addFiles(fileInput.files));

    fileList.addEventListener('click', e => {
        const btn = e.target.closest('.sem-file-remove');
        if (!btn) return;
        files.splice(+btn.dataset.index, 1);
        rebuildInput();
        renderFiles();
    });

    document.querySelector('.sem-browse').addEventListener('click',   () => fileInput.click());
    document.querySelector('.sem-browse').addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') fileInput.click();
    });

    ['dragenter','dragover'].forEach(ev =>
        dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.add('drag-over'); })
    );
    ['dragleave','drop'].forEach(ev =>
        dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.remove('drag-over'); })
    );
    dropZone.addEventListener('drop', e => addFiles(e.dataTransfer.files));

    /* ── Submit ───────────────────────────────────────────── */
    $id('sendEmailForm').addEventListener('submit', function (e) {
        $id('sem_message_hidden').value = editor.innerHTML;

        if (!editor.textContent.trim()) {
            e.preventDefault();
            editor.style.borderColor = '#ef4444';
            editor.focus();
            return;
        }

        const btn = $id('semSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader-2 sem-spin"></i> Sending…';
    });

    editor.addEventListener('focus', () => { editor.style.borderColor = ''; });

})();
</script>