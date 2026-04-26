$(document).ready(function () {
    // ─── Add Reminder Recurring Toggle ───────────────────────────────────────────
    $('#is_recurring').change(function () {
        $('#recurring-options').toggle(this.checked);
    });

    // ─── Edit Reminder Recurring Toggle ──────────────────────────────────────────
    $('#edit_is_recurring').change(function () {
        $('#edit_recurring_options').toggle(this.checked);
    });

    // ─── Edit Reminder Button Click Handler ──────────────────────────────────────
    $(document).on('click', '.edit-reminder', function () {
        const modal = $('#edit_reminder_modal');
        const dateString = $(this).data('date') || '';
        const dateParts = dateString.split(' ');

        modal.find('#edit_id').val($(this).data('id'));
        modal.find('#edit_title').val($(this).data('title'));
        modal.find('#edit_description').val($(this).data('description'));
        modal.find('#edit_date').val(dateParts[0] || '');
        modal.find('#edit_time').val((dateParts[1] || '09:00').substring(0, 5));
        
        const isRecurring = $(this).data('is_recurring') == 1;
        modal.find('#edit_is_recurring').prop('checked', isRecurring);
        modal.find('#edit_recurring_options').toggle(isRecurring);
        modal.find('#edit_recurring_frequency').val($(this).data('recurring_frequency') || 'daily');
        modal.find('#edit_recurring_duration').val($(this).data('recurring_duration') || '');

        modal.modal('show');
    });

    // ─── Filter Reminders ────────────────────────────────────────────────────────
    $('.filter-option').click(function (e) {
        e.preventDefault();
        const filter = $(this).data('filter');
        if (filter === 'all') {
            $('#reminders-table tbody tr').show();
        } else {
            $('#reminders-table tbody tr').hide();
            $('#reminders-table tbody tr[data-status="' + filter + '"]').show();
        }
    });

    // ─── Search Reminders ────────────────────────────────────────────────────────
    $('#search-btn').click(function () {
        const searchText = $('#reminder-search').val().toLowerCase();
        $('#reminders-table tbody tr').each(function () {
            const rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.includes(searchText));
        });
    });

    // ─── Date Picker Minimum Date ────────────────────────────────────────────────
    const today = new Date().toISOString().split('T')[0];
    $('#date').attr('min', today);
    $('#edit_date').attr('min', today);
});
