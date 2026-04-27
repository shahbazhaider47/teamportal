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
