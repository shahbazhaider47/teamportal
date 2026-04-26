document.getElementById('applyBulkBtn')?.addEventListener('click', () => {
  const selected = document.getElementById('bulkStatusSelect').value;
  // Only visible input fields should be changed (today by default, all if in edit)
  document.querySelectorAll('.attendance-input')
    .forEach(input => {
      // Only update visible inputs (display !== 'none')
      if (input.offsetParent !== null) {
        input.value = selected;
      }
    });
});


  // Existing bulk status JS...

  // Table scroll with arrows
  document.getElementById('scrollLeft')?.addEventListener('click', function() {
    document.getElementById('attendanceTableScroll').scrollBy({ left: -200, behavior: 'smooth' });
  });

  document.getElementById('scrollRight')?.addEventListener('click', function() {
    document.getElementById('attendanceTableScroll').scrollBy({ left: 200, behavior: 'smooth' });
  });

  // Optional: hide arrows if not needed (advanced, keep it visible for all for simplicity)

document.getElementById('editAttendanceBtn')?.addEventListener('click', function() {
    // Hide all .attendance-box and show all .attendance-input
    document.querySelectorAll('.attendance-box').forEach(box => box.style.display = 'none');
    document.querySelectorAll('.attendance-input').forEach(input => input.style.display = '');
    document.getElementById('editAttendanceBtn').style.display = 'none';
    document.getElementById('cancelEditBtn').style.display = '';
});

document.getElementById('cancelEditBtn')?.addEventListener('click', function() {
    // Show all .attendance-box and hide all .attendance-input (except today)
    document.querySelectorAll('.attendance-box').forEach(box => box.style.display = '');
    document.querySelectorAll('.attendance-input').forEach(input => {
        // If input is NOT a today-input, hide it again
        if (!input.classList.contains('today-input')) {
            input.style.display = 'none';
        }
    });
    document.getElementById('editAttendanceBtn').style.display = '';
    document.getElementById('cancelEditBtn').style.display = 'none';
});