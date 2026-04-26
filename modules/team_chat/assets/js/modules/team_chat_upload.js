/**
 * team_chat_upload.js
 * File upload: file input, drag-drop, progress chips, attachment linking.
 */
var TC_Upload = (function () {
    'use strict';

    var _base    = window.TeamChatConfig ? window.TeamChatConfig.baseUrl : '';
    var _pending = [];  // [{ file, attachmentId, status }]
    var _convId  = 0;
    var MAX_MB   = window.TeamChatConfig ? window.TeamChatConfig.maxFileSizeMb : 10;
    var MAX_BYTES= MAX_MB * 1048576;

    var ALLOWED_TYPES = [
        'image/jpeg','image/png','image/gif','image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain','text/csv',
        'application/zip','application/x-rar-compressed',
        'video/mp4','video/webm',
        'audio/mpeg','audio/ogg','audio/wav',
    ];

    function init() {
        var composer = document.getElementById('tcComposer');
        if (composer) {
            _convId = parseInt(composer.dataset.convId) || 0;
        }

        var fileInput = document.getElementById('tcFileInput');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                handleFiles(this.files);
                this.value = ''; // Reset so same file can be re-selected
            });
        }

        // Thread file input
        var threadFileInput = document.getElementById('tcThreadFileInput');
        if (threadFileInput) {
            threadFileInput.addEventListener('change', function () {
                handleFiles(this.files);
                this.value = '';
            });
        }
    }

    /* ── Handle file list ────────────────────────────────── */
    function handleFiles(files) {
        if (!files || !files.length) return;

        Array.from(files).forEach(function (file) {
            // Validate size
            if (file.size > MAX_BYTES) {
                alert(file.name + ' exceeds the maximum file size of ' + MAX_MB + ' MB.');
                return;
            }

            // Validate type
            if (!ALLOWED_TYPES.includes(file.type)) {
                alert(file.name + ': file type not allowed.');
                return;
            }

            var pending = { file: file, attachmentId: null, status: 'pending', uid: _uid() };
            _pending.push(pending);
            _addChip(pending);
        });

        if (_pending.length) _showPreview();
    }

    /* ── Upload all pending to server ────────────────────── */
    function uploadAll(convId) {
        convId = convId || _convId;
        var promises = _pending
            .filter(function (p) { return p.status === 'pending'; })
            .map(function (p) { return _uploadOne(p, convId); });

        return Promise.all(promises).then(function (results) {
            return results.filter(Boolean);
        });
    }

    function _uploadOne(pending, convId) {
        pending.status = 'uploading';
        _updateChip(pending.uid, 'uploading', 0);

        var fd = new FormData();
        fd.append('file', pending.file);
        fd.append('conversation_id', convId);
        fd.append(window.TeamChatConfig.csrfTokenName, window.TeamChatConfig.csrfHash);

        return new Promise(function (resolve) {
            var xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    var pct = Math.round((e.loaded / e.total) * 100);
                    _updateChip(pending.uid, 'uploading', pct);
                }
            });

            xhr.addEventListener('load', function () {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success && res.data) {
                        pending.status      = 'done';
                        pending.attachmentId= res.data.id;
                        _updateChip(pending.uid, 'done', 100);
                        resolve(res.data.id);
                    } else {
                        pending.status = 'error';
                        _updateChip(pending.uid, 'error', 0);
                        alert('Upload failed: ' + (res.message || 'Unknown error'));
                        resolve(null);
                    }
                } catch (e) {
                    pending.status = 'error';
                    _updateChip(pending.uid, 'error', 0);
                    resolve(null);
                }
            });

            xhr.addEventListener('error', function () {
                pending.status = 'error';
                _updateChip(pending.uid, 'error', 0);
                resolve(null);
            });

            xhr.open('POST', _base + '/upload');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(fd);
        });
    }

    /* ── Attach uploaded file to a message ───────────────── */
    function attachToMessage(attachmentId, messageId) {
        var fd = new FormData();
        fd.append('attachment_id', attachmentId);
        fd.append('message_id', messageId);
        fd.append(window.TeamChatConfig.csrfTokenName, window.TeamChatConfig.csrfHash);

        return fetch(_base + '/upload/attach', {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body:    fd,
        }).then(function (r) { return r.json(); });
    }

    /* ── Get pending attachment IDs ──────────────────────── */
    function getPending() {
        return _pending.filter(function (p) { return p.status === 'pending' || p.status === 'done'; });
    }

    /* ── Clear preview ───────────────────────────────────── */
    function clearPreview() {
        _pending = [];
        var previewEl = document.getElementById('tcUploadPreview');
        var chipsEl   = document.getElementById('tcUploadChips');
        if (chipsEl)   chipsEl.innerHTML = '';
        if (previewEl) previewEl.classList.add('d-none');
    }

    /* ── Drop zone ───────────────────────────────────────── */
    function showDropZone() {
        var dz = document.getElementById('tcDropZone');
        if (dz) dz.classList.remove('d-none');
    }

    function hideDropZone() {
        var dz = document.getElementById('tcDropZone');
        if (dz) dz.classList.add('d-none');
    }

    /* ── DOM helpers ─────────────────────────────────────── */
    function _showPreview() {
        var previewEl = document.getElementById('tcUploadPreview');
        if (previewEl) previewEl.classList.remove('d-none');
    }

    function _addChip(pending) {
        var chipsEl = document.getElementById('tcUploadChips');
        if (!chipsEl) return;

        var chip = document.createElement('div');
        chip.className        = 'tc-upload-chip';
        chip.dataset.uid      = pending.uid;
        chip.innerHTML = '<i class="ti ti-file"></i>'
            + '<span class="tc-upload-chip__name" title="' + _esc(pending.file.name) + '">' + _esc(pending.file.name) + '</span>'
            + '<button type="button" class="tc-upload-chip__remove" title="Remove"><i class="ti ti-x"></i></button>';

        chip.querySelector('.tc-upload-chip__remove').addEventListener('click', function () {
            _removeChip(pending.uid);
        });

        chipsEl.appendChild(chip);
    }

    function _updateChip(uid, status, pct) {
        var chip = document.querySelector('.tc-upload-chip[data-uid="' + uid + '"]');
        if (!chip) return;

        var icon = chip.querySelector('i');
        if (status === 'uploading') {
            if (icon) icon.className = 'ti ti-loader-2';
            chip.title = pct + '%';
        } else if (status === 'done') {
            if (icon) icon.className = 'ti ti-check';
        } else if (status === 'error') {
            if (icon) icon.className = 'ti ti-alert-triangle';
            chip.style.borderColor = '#ef4444';
        }
    }

    function _removeChip(uid) {
        _pending = _pending.filter(function (p) { return p.uid !== uid; });
        var chip = document.querySelector('.tc-upload-chip[data-uid="' + uid + '"]');
        if (chip) chip.remove();
        if (!_pending.length) clearPreview();
    }

    function _esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function _uid()  { return Math.random().toString(36).substring(2, 10); }

    return { init: init, handleFiles: handleFiles, uploadAll: uploadAll, attachToMessage: attachToMessage, getPending: getPending, clearPreview: clearPreview, showDropZone: showDropZone, hideDropZone: hideDropZone };
})();