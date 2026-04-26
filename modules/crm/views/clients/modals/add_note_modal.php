<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="addClientNoteModal" tabindex="-1" aria-labelledby="addClientNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= site_url('crm/client_add_note/' . (int)$client['id']) ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClientNoteModalLabel">
                        <i class="ti ti-plus me-2 text-primary"></i>Add Client Note
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Note <span class="text-danger">*</span></label>
                        <textarea name="note"
                                  class="form-control"
                                  rows="6"
                                  placeholder="Write note here..."
                                  required></textarea>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Visibility</label>
                        <select name="is_internal" class="form-select">
                            <option value="1" selected>Internal</option>
                            <option value="0">Visible</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> Save Note
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>