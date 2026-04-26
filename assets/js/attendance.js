/* ============================================================
 * Attendance Grid JS - UPDATED VERSION (Modified for today-only bulk apply)
 * ------------------------------------------------------------
 * Features:
 * - Only unlocks cells that controller allows (via data-locked attribute)
 * - Today's cell is always visible even in non-edit mode
 * - Bulk apply works ONLY on TODAY's unlocked cells
 * - Cancel restores original values
 * ============================================================ */

(function () {
  'use strict';

  /* ---------------- DOM ---------------- */

  const table       = document.getElementById('attendanceTable');
  const scrollWrap  = document.getElementById('attendanceTableScroll');
  const editBtn     = document.getElementById('editAttendanceBtn');
  const cancelBtn   = document.getElementById('cancelEditBtn');
  const bulkSelect  = document.getElementById('bulkStatusSelect');
  const bulkBtn     = document.getElementById('applyBulkBtn');
  const scrollLeft  = document.getElementById('scrollLeft');
  const scrollRight = document.getElementById('scrollRight');

  if (!table) return;

  /* ---------------- constants ---------------- */

  const ALLOWED = ['P', 'C', 'M', 'S', 'A'];

  // Get all input elements
  const inputs = () =>
    table.querySelectorAll('input.attendance-input');
    
  // Get only TODAY's input elements
  const todayInputs = () =>
    table.querySelectorAll('input.attendance-input.today-input');

  let editMode = false;
  const originalValues = new Map();

  /* ---------------- helpers ---------------- */

  function isLocked(input) {
    return input.dataset.locked === '1';
  }

  function storeOriginal() {
    originalValues.clear();
    inputs().forEach(i => originalValues.set(i, i.value));
  }

  function restoreOriginal() {
    originalValues.forEach((v, i) => { 
      if (!isLocked(i)) {
        i.value = v; 
      }
    });
  }

  function showEditableInputs() {
    // Hide all status boxes
    document.querySelectorAll('.attendance-box')
      .forEach(box => box.style.display = 'none');

    // Show all inputs (locked ones will still be disabled)
    inputs().forEach(i => {
      i.style.display = '';
      
      // Only enable if not locked
      if (!isLocked(i)) {
        i.disabled = false;
      }
    });
  }

  function hideEditableInputs() {
    // Show all status boxes
    document.querySelectorAll('.attendance-box')
      .forEach(box => box.style.display = '');

    // Hide all inputs except today's
    inputs().forEach(i => {
      // Always keep today's input visible but disabled when not in edit mode
      if (i.classList.contains('today-input')) {
        i.style.display = '';
        i.disabled = !editMode;
      } else {
        i.style.display = 'none';
        i.disabled = true;
      }
    });
  }

  /* ---------------- edit / cancel ---------------- */

  if (editBtn && cancelBtn) {
    editBtn.addEventListener('click', () => {
      editMode = true;
      storeOriginal();
      showEditableInputs();
      editBtn.style.display = 'none';
      cancelBtn.style.display = 'inline-flex';
    });

    cancelBtn.addEventListener('click', () => {
      editMode = false;
      restoreOriginal();
      hideEditableInputs();
      cancelBtn.style.display = 'none';
      editBtn.style.display = 'inline-flex';
    });
  }

  /* ---------------- input validation ---------------- */

  table.addEventListener('input', e => {
    const i = e.target;
    if (!i.classList.contains('attendance-input')) return;

    let v = i.value.toUpperCase().trim();
    if (v.length > 1) v = v.slice(-1);
    if (v && !ALLOWED.includes(v)) v = '';
    i.value = v;
  });

  table.addEventListener('keydown', e => {
    const i = e.target;
    if (!i.classList.contains('attendance-input')) return;

    if (
      e.key === 'Backspace' ||
      e.key === 'Delete' ||
      e.key === 'Tab' ||
      e.key.startsWith('Arrow')
    ) return;

    if (!ALLOWED.includes(e.key.toUpperCase())) {
      e.preventDefault();
    }
  });

  /* ---------------- bulk apply (MODIFIED FOR TODAY-ONLY) ---------------- */

  if (bulkBtn && bulkSelect) {
    bulkBtn.addEventListener('click', () => {
      if (!editMode) {
        alert('Click Edit before applying bulk status.');
        return;
      }

      const val = bulkSelect.value;
      if (!ALLOWED.includes(val)) return;

      // OLD CODE: Applied to all unlocked inputs
      /*
      inputs().forEach(i => {
        // Apply to all unlocked inputs that are visible
        if (!isLocked(i) && i.style.display !== 'none') {
          i.value = val;
        }
      });
      */
      
      // NEW CODE: Apply ONLY to today's inputs
      todayInputs().forEach(i => {
        // Apply to today's cells that are unlocked and visible
        if (!isLocked(i) && i.style.display !== 'none') {
          i.value = val;
        }
      });
    });
  }

  /* ---------------- scrolling ---------------- */

  scrollLeft?.addEventListener('click', () =>
    scrollWrap.scrollBy({ left: -300, behavior: 'smooth' })
  );

  scrollRight?.addEventListener('click', () =>
    scrollWrap.scrollBy({ left: 300, behavior: 'smooth' })
  );

  /* ---------------- init ---------------- */

  // Initial state: only today's input visible
  hideEditableInputs();

})();