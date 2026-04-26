<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $leadId = (int)($lead['id'] ?? 0); ?>

<div class="modal fade" id="deleteLeadModal" tabindex="-1" aria-labelledby="deleteLeadModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= site_url('crm/leads/delete/' . $leadId) ?>" method="post">

                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white" id="deleteLeadModalLabel">
                        <i class="ti ti-alert-triangle me-2"></i>Delete Lead
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to delete this lead? This action can not be undone</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light-primary btn-header" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-header">
                        <i class="ti ti-trash me-1"></i>Delete Lead
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>