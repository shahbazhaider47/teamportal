<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * _file_preview_modal.php
 * Fully JS-driven lightbox. PHP outputs the shell only.
 * Opened by: TeamChat.openFilePreview(attachmentId)
 */
?>

<div class="modal fade tc-file-preview-modal" id="tcFilePreviewModal" tabindex="-1"
     aria-labelledby="tcFilePreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content tc-file-preview-modal__content">

            <?php /* Header */ ?>
            <div class="modal-header tc-file-preview-modal__header">

                <div class="tc-file-preview-modal__meta">
                    <span class="tc-file-preview-modal__filename" id="tcPreviewFilename">
                        Loading…
                    </span>
                    <span class="tc-file-preview-modal__size text-muted small"
                          id="tcPreviewFileSize"></span>
                </div>

                <div class="tc-file-preview-modal__actions ms-auto">

                    <?php /* Download */ ?>
                    <a href="#"
                       id="tcPreviewDownloadBtn"
                       class="tc-icon-btn"
                       download
                       title="Download">
                        <i class="ti ti-download"></i>
                    </a>

                    <?php /* Open in new tab */ ?>
                    <a href="#"
                       id="tcPreviewOpenBtn"
                       class="tc-icon-btn"
                       target="_blank"
                       rel="noopener noreferrer"
                       title="Open in new tab">
                        <i class="ti ti-external-link"></i>
                    </a>

                    <button type="button" class="btn-close btn-close-white"
                            data-bs-dismiss="modal"></button>
                </div>

            </div>

            <?php /* Preview body */ ?>
            <div class="modal-body tc-file-preview-modal__body" id="tcPreviewBody">

                <?php /* Loading spinner */ ?>
                <div class="tc-file-preview-modal__loading" id="tcPreviewLoading">
                    <div class="tc-spinner"></div>
                </div>

                <?php /* Image preview */ ?>
                <div class="tc-file-preview-modal__image-wrap d-none" id="tcPreviewImageWrap">
                    <img id="tcPreviewImage"
                         src=""
                         alt=""
                         class="tc-file-preview-modal__image">
                </div>

                <?php /* PDF preview (iframe) */ ?>
                <div class="tc-file-preview-modal__pdf-wrap d-none" id="tcPreviewPdfWrap">
                    <iframe id="tcPreviewPdf"
                            src=""
                            class="tc-file-preview-modal__iframe"
                            title="PDF preview"></iframe>
                </div>

                <?php /* Video preview */ ?>
                <div class="tc-file-preview-modal__video-wrap d-none" id="tcPreviewVideoWrap">
                    <video id="tcPreviewVideo"
                           controls
                           class="tc-file-preview-modal__video">
                        <source src="" type="">
                        Your browser does not support video playback.
                    </video>
                </div>

                <?php /* Audio preview */ ?>
                <div class="tc-file-preview-modal__audio-wrap d-none" id="tcPreviewAudioWrap">
                    <div class="tc-file-preview-modal__audio-icon">
                        <i class="ti ti-music"></i>
                    </div>
                    <audio id="tcPreviewAudio" controls class="tc-file-preview-modal__audio">
                        <source src="" type="">
                    </audio>
                </div>

                <?php /* Generic file (no preview available) */ ?>
                <div class="tc-file-preview-modal__generic d-none" id="tcPreviewGeneric">
                    <i class="tc-file-preview-modal__generic-icon" id="tcPreviewGenericIcon"></i>
                    <p class="tc-file-preview-modal__generic-name" id="tcPreviewGenericName"></p>
                    <p class="text-muted small">No preview available for this file type.</p>
                    <a href="#"
                       id="tcPreviewGenericDownload"
                       class="btn btn-primary"
                       download>
                        <i class="ti ti-download me-1"></i>
                        Download File
                    </a>
                </div>

            </div>

            <?php /* Footer: uploader + timestamp */ ?>
            <div class="modal-footer tc-file-preview-modal__footer">
                <span class="text-muted small" id="tcPreviewUploaderInfo"></span>
            </div>

        </div>
    </div>
</div>

<script>
(function () {
    const modal         = document.getElementById('tcFilePreviewModal');
    const loading       = document.getElementById('tcPreviewLoading');
    const imageWrap     = document.getElementById('tcPreviewImageWrap');
    const pdfWrap       = document.getElementById('tcPreviewPdfWrap');
    const videoWrap     = document.getElementById('tcPreviewVideoWrap');
    const audioWrap     = document.getElementById('tcPreviewAudioWrap');
    const genericWrap   = document.getElementById('tcPreviewGeneric');
    const previewImg    = document.getElementById('tcPreviewImage');
    const previewIframe = document.getElementById('tcPreviewPdf');
    const previewVideo  = document.getElementById('tcPreviewVideo');
    const previewAudio  = document.getElementById('tcPreviewAudio');
    const dlBtn         = document.getElementById('tcPreviewDownloadBtn');
    const openBtn       = document.getElementById('tcPreviewOpenBtn');
    const filenameEl    = document.getElementById('tcPreviewFilename');
    const filesizeEl    = document.getElementById('tcPreviewFileSize');
    const uploaderEl    = document.getElementById('tcPreviewUploaderInfo');
    const genericIcon   = document.getElementById('tcPreviewGenericIcon');
    const genericName   = document.getElementById('tcPreviewGenericName');
    const genericDl     = document.getElementById('tcPreviewGenericDownload');

    if (!modal) return;

    // Register global open function
    if (typeof window.TeamChat === 'undefined') window.TeamChat = {};
    window.TeamChat.openFilePreview = function (attachmentId) {
        _reset();
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        // Fetch attachment info — attachment URL is already embedded in message data
        // Find the attachment in the DOM
        const attEl = document.querySelector('[data-attachment-id="' + attachmentId + '"]');
        if (!attEl) { _showGeneric('Unknown file', '#', 'ti ti-file'); return; }

        // Try to find the bubble's data
        const bubble = attEl.closest('.tc-bubble');
        if (!bubble) { _showGeneric('Unknown file', '#', 'ti ti-file'); return; }

        // Read data from the attachment element if available
        const fileUrl  = attEl.dataset.fileUrl  || attEl.querySelector('a')?.href || '#';
        const origName = attEl.dataset.origName  || attEl.querySelector('.tc-file-card__name')?.textContent?.trim() || 'File';
        const mimeType = attEl.dataset.mimeType  || '';
        const fileSize = attEl.dataset.fileSize  || '';
        const thumbUrl = attEl.dataset.thumbUrl  || '';

        _render(fileUrl, thumbUrl, origName, mimeType, fileSize, '');
    };

    // Also allow opening directly with full attachment data
    window.TeamChat.openFilePreviewData = function (data) {
        _reset();
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        _render(
            data.file_url,
            data.thumbnail_url || '',
            data.original_name,
            data.mime_type,
            data.file_size_kb ? data.file_size_kb + ' KB' : '',
            data.uploader_name ? 'Shared by ' + data.uploader_name : ''
        );
    };

    function _render(fileUrl, thumbUrl, origName, mimeType, fileSize, uploaderInfo) {
        loading.classList.remove('d-none');

        filenameEl.textContent  = origName;
        filesizeEl.textContent  = fileSize || '';
        uploaderEl.textContent  = uploaderInfo || '';
        dlBtn.href              = fileUrl;
        dlBtn.download          = origName;
        openBtn.href            = fileUrl;

        const imageTypes = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
        const videoTypes = ['video/mp4','video/webm','video/ogg'];
        const audioTypes = ['audio/mpeg','audio/ogg','audio/wav','audio/webm'];

        loading.classList.add('d-none');

        if (imageTypes.includes(mimeType)) {
            previewImg.src = thumbUrl || fileUrl;
            previewImg.onclick = function () { this.src = fileUrl; }; // Click for full size
            imageWrap.classList.remove('d-none');

        } else if (mimeType === 'application/pdf') {
            previewIframe.src = fileUrl;
            pdfWrap.classList.remove('d-none');

        } else if (videoTypes.includes(mimeType)) {
            previewVideo.querySelector('source').src  = fileUrl;
            previewVideo.querySelector('source').type = mimeType;
            previewVideo.load();
            videoWrap.classList.remove('d-none');

        } else if (audioTypes.includes(mimeType)) {
            previewAudio.querySelector('source').src  = fileUrl;
            previewAudio.querySelector('source').type = mimeType;
            previewAudio.load();
            audioWrap.classList.remove('d-none');

        } else {
            const iconMap = {
                'application/pdf': 'ti ti-file-type-pdf',
                'text/plain': 'ti ti-file-text',
                'text/csv':   'ti ti-file-type-csv',
                'application/zip': 'ti ti-file-zip',
            };
            const iconClass = iconMap[mimeType] || 'ti ti-file';
            genericIcon.className = iconClass + ' tc-file-preview-modal__generic-icon';
            genericName.textContent = origName;
            genericDl.href     = fileUrl;
            genericDl.download = origName;
            genericWrap.classList.remove('d-none');
        }
    }

    function _reset() {
        [imageWrap, pdfWrap, videoWrap, audioWrap, genericWrap].forEach(el => {
            if (el) el.classList.add('d-none');
        });
        loading.classList.remove('d-none');
        if (previewImg)    previewImg.src = '';
        if (previewIframe) previewIframe.src = '';
        if (previewVideo)  previewVideo.pause();
        if (previewAudio)  previewAudio.pause();
        filenameEl.textContent = 'Loading…';
        filesizeEl.textContent = '';
        uploaderEl.textContent = '';
        dlBtn.href  = '#';
        openBtn.href = '#';
        loading.classList.add('d-none');
    }

    // Stop media on modal close
    modal.addEventListener('hidden.bs.modal', function () {
        if (previewVideo) previewVideo.pause();
        if (previewAudio) previewAudio.pause();
        if (previewIframe) previewIframe.src = '';
    });
})();
</script>