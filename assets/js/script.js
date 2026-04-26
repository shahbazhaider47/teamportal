//  -----------------------------------------------------------------------------------

//     Template Name: AdminX Admin
//     Template URI: https://phpstack-959325-3347777.cloudwaysapps.com/laadmin/template/landing.html
//     Description: This is Admin theme
//     Author: la-themes
//     Author URI: https://themeforest.net/user/la-themes

// -----------------------------------------------------------------------------------


// 01 Flag  Icon Js
// 02. copy js
// 03. sidebar toggle js
// 04.  List page js
// 05 Sidebar scroll js
// 06. Loader JS
// 07. tap on top
// 08. flag dropdown
// 09. hide-show
// 10. dark mode js
// 11 close on click js




// >>-- 01 Flag  Icon Js --<<
// Horizontal Nav css
let navBar = $(".main-nav");
let size = "150px";
let leftsideLimit = -100;
let navbarSize;
let containerWidth;
let maxNavbarLimit;

function setUpHorizontalHeader() {
  navbarSize = navBar.width();
  containerWidth = ($(".simplebar-content").width())
  maxNavbarLimit = -(navbarSize - containerWidth);
  if ($("nav").hasClass("horizontal-sidebar")) {
    $(".menu-next").removeClass("d-none");
    $(".menu-previous").removeClass("d-none");
  } else {
    navBar.css("marginLeft",0)
    $(".menu-next").addClass("d-none");
    $(".menu-previous").addClass("d-none");
  }
  $(".horizontal-sidebar .show").removeClass("show");
}

$(document).on('click', '.menu-previous', function (e) {
  let attribute = layoutOption == 'ltr' ? 'marginLeft' : 'marginRight';
  let currentPosition = parseInt(navBar.css(attribute));
  if (currentPosition < 0) {
    navBar.css(`${attribute}`, "+=" + size)
    $(".menu-next").removeClass("d-none");
    $(".menu-previous").removeClass("d-none");
    if (currentPosition >= leftsideLimit) {
      $(this).addClass("d-none");
    }
  }
})


$(document).on('click', '.menu-next', function (e) {
  let attribute = layoutOption == 'ltr' ? 'marginLeft' : 'marginRight';
  let currentPosition = parseInt(navBar.css(attribute));
  if (currentPosition >= maxNavbarLimit) {
    $(".menu-next").removeClass("d-none");
    $(".menu-previous").removeClass("d-none");
    navBar.css(`${attribute}`, "-=" + size)
    if (currentPosition - parseInt(size) <= maxNavbarLimit) {
      $(this).addClass("d-none");
    }
  }
})





//  **------flag dropdown**
$(function () {
  var text = $(".selected i").attr('class')
  $(".flag i").prop('class', text);
  $(document).on('click', '.lang', function () {
    $(".lang").removeClass("selected");
    $(this).addClass("selected");
    text = $(".selected i").attr('class')
    $(".flag i").prop('class', text);
  });
})



// >>-- 02 copy Js --<<
function copyvalue() {
  var temp = document.createElement('input');
  var texttoCopy = document.getElementById('copyText2').innerHTML;
  temp.type = 'input';
  temp.setAttribute('value', texttoCopy);
  document.body.appendChild(temp);
  temp.select();
  document.execCommand("copy");
  temp.remove();
  console.timeEnd('time2');
}



// >>-- 03 sidebar toggle js --<<
$(document).on('click', '.header-toggle', function () {
  $("nav").toggleClass("semi-nav");
});
$(".toggle-semi-nav").on("click", function () {
  $("nav").removeClass("semi-nav");
});


// >>-- 04 List page js --<<
$(".contact-listbox").on("click", function () {
  $(this).toggleClass("stared");
});

function resize() {
  var $window = $(window),
    $nav = $('nav');

  $nav.removeClass('semi-nav');
  if ($window.width() < 768) {
    // $nav.removeClass('semi-nav');

  } else if ($window.width() < 1199) {
    $nav.addClass('semi-nav');
  }
}

$(function () {
  resize();
});
window.addEventListener("resize", () => {
  resize();
});


// >>-- 05 Sidebar scroll js --<<
var myElement = document.getElementById('app-simple-bar');
new SimpleBar(myElement, { autoHide: true });



// Sidebar active class js
$(function () {
  let current = location.pathname;
  current = current.substring((current.lastIndexOf('/')) + 1);
  $('.main-nav li a').each(function () {
    var $this = $(this);
    if (current === $this.attr("href").split('/').pop()) {
      if ($this.parent().parent().parent().hasClass("another-level")) {
        $this.parent().parent().parent().parent().closest('li').children().addClass('show').attr("aria-expanded", "true");
      }
      $this.parent().parent().parent().children().addClass('show');
      $this.parent().parent().parent().children().attr("aria-expanded", "true");
      $this.parent('li').addClass('active');
    }
  })
})



// >>-- 07 tap on top --<<
let calcScrollValue = () => {
  let scrollProgress = document.getElementsByClassName("go-top");
  let progressValue = document.getElementsByClassName("progress-value");
  let pos = document.documentElement.scrollTop;
  let calcHeight =
    document.documentElement.scrollHeight -
    document.documentElement.clientHeight;
  let scrollValue = Math.round((pos * 100) / calcHeight);
  if (pos > 100) {
    scrollProgress[0].style.display = 'grid';
  } else {
    scrollProgress[0].style.display = 'none';
  }

  scrollProgress[0].addEventListener("click", () => {
    document.documentElement.scrollTop = 0;
  });

  scrollProgress[0].style.background = `conic-gradient( rgba(var(--primary),1) ${scrollValue}%, var(--light-gray) ${scrollValue}%)`;
};

window.onscroll = calcScrollValue;



// >>-- 08 flag dropdown --<<
$(function () {
  var text = $(".selected img").attr('src')
  $(".flag img").prop('src', text);
  $(document).on('click', '.lang', function () {
    $(".lang").removeClass("selected");
    $(this).addClass("selected");
    text = $(".selected img").attr('src')
    $(".flag img").prop('src', text);
  });
})
$(function () {
  var text = $(".selected i").attr('class')
  $(".flag i").prop('class', text);
  $(document).on('click', '.lang', function () {
    $(".lang").removeClass("selected");
    $(this).addClass("selected");
    text = $(".selected i").attr('class')
    $(".flag i").prop('class', text);
  });
})


// >>-- 09 hide-show --<<

function myFunction() {
  var x = document.getElementById("myapp");
  if (x.style.display === "none") {
    x.style.display = "block";
    let buttoncontent = $("#button-content").html().replace(/</g, "&lt;").replace(/>/g, "&gt;");
    $("#button-code").html(buttoncontent)
  } else {
    x.style.display = "none";
    $("#button-code").html("")
  }
}


// >>-- 10 dark mode js --<<
function appendHtml() {
  var div = document.getElementsByClassName('app-wrapper');
  div.innerHTML += '<p>This is some HTML code</p>';
}
window.onload = function () {
  appendHtml();
}


// >>-- 11 close on click js --<<

$(document).on('click', '.close-btn', function () {
  let targetItem = $(this).closest(".head-box");
  let targetParent = targetItem.parent();
  $(this).parent().parent().remove();
  if (targetParent.find(".head-box").length <= 0) {
    targetParent.parent().parent().find('.card-footer').addClass('d-none');
  }
});



var closeCollaps = document.querySelectorAll('.main-nav li a[data-bs-toggle="collapse"]');
closeCollaps.forEach(function (element) {
  element.addEventListener('click', function () {
    var parent = element.closest('.collapse');
    var all = document.querySelectorAll('.collapse');
    all.forEach(function (e) {
      if (e !== parent) {
        e.classList.remove('show');
        var ariaexpand = e.previousElementSibling;
        if (ariaexpand) ariaexpand.setAttribute('aria-expanded', 'false');
      }
    });
    parent?.classList.add('show');
    var ariaexpand = element;
    if (ariaexpand) ariaexpand.setAttribute('aria-expanded', 'true');
  });
});





// qick add to tod from any view file with single click b0utton
// Quick To-Do add from any module
(function ($) {
  'use strict';

  // Helper to resolve URL
  function appUrl(path) {
    if (window.APP_BASE_URL) {
      return window.APP_BASE_URL + path.replace(/^\/+/, '');
    }
    return path; // fallback – but ideally APP_BASE_URL is set
  }

  // 1) Todo toggle (from todo listing)
  $(document).on('change', '.todo-toggle', function () {
    var checkbox = $(this);
    var row      = checkbox.closest('tr');
    var todoId   = checkbox.data('id');
    var checked  = checkbox.is(':checked') ? 1 : 0;
    var url      = checkbox.data('toggle-url') || appUrl('todo/toggle_status');

    checkbox.prop('disabled', true);

    $.ajax({
      url: url,
      method: 'POST',
      dataType: 'json',
      data: { id: todoId, status: checked },
    })
    .done(function (resp) {
      if (resp && resp.success) {
        var label = checkbox.siblings('label');
        var badge = row.find('.badge');

        if (checked) {
          label.removeClass('text-primary')
               .addClass('text-muted text-decoration-line-through');
          badge.removeClass('text-light-danger')
               .addClass('text-light-success')
               .text('Completed');
        } else {
          label.removeClass('text-muted text-decoration-line-through')
               .addClass('text-primary');
          badge.removeClass('text-light-success')
               .addClass('text-light-danger')
               .text('Pending');
        }
      } else {
        alert('Failed to update status.');
        checkbox.prop('checked', !checked);
      }
    })
    .fail(function () {
      alert('Failed to update status.');
      checkbox.prop('checked', !checked);
    })
    .always(function () {
      checkbox.prop('disabled', false);
    });
  });

  // 2) Generic Quick Add To-Do from any module
  $(document).on('click', '.js-add-todo', function (e) {
    e.preventDefault();

    var btn       = $(this);
    var relType   = (btn.data('rel-type') || '').toString();
    var relId     = parseInt(btn.data('rel-id'), 10) || 0;
    var todoTitle = (btn.data('todo-name') || '').toString().trim();

    if (!relType || !relId) {
      console.warn('Missing rel_type or rel_id on .js-add-todo button');
      return;
    }
    if (!todoTitle) {
      todoTitle = 'Follow up ' + relType + ' #' + relId;
    }

    btn.prop('disabled', true).addClass('disabled');

    $.ajax({
      url: appUrl('todo/quick_add'),
      method: 'POST',
      dataType: 'json',
      data: {
        rel_type:  relType,
        rel_id:    relId,
        todo_name: todoTitle
      }
    })
    .done(function (resp) {
      if (resp && resp.success) {
        if (window.toastr) {
          toastr.success('Added to your To-Do list.');
        } else if (window.appNotify) {
          appNotify('Added to your To-Do list.', 'success');
        } else {
          console.log('To-Do added.');
        }
      } else {
        alert((resp && resp.message) || 'Failed to add To-Do.');
      }
    })
    .fail(function () {
      alert('Failed to add To-Do.');
    })
    .always(function () {
      btn.prop('disabled', false).removeClass('disabled');
    });
  });

})(jQuery);




// add quick todo from any view file or form the modules: 
(function ($) {
  // Inject minimal toast CSS only once
  function ensureTodoToastStyle() {
    if (document.getElementById('todo-toast-style')) return;
    var css = `
      .todo-toast{
        position:fixed;
        right:16px;
        bottom:16px;
        z-index:9999;
        min-width:220px;
        max-width:320px;
        padding:8px 12px;
        border-radius:6px;
        font-size:.8rem;
        box-shadow:0 5px 18px rgba(15,23,42,.25);
        opacity:0;
        transform:translateY(10px);
        transition:all .2s ease-out;
        display:flex;
        align-items:center;
        gap:8px;
      }
      .todo-toast.is-visible{
        opacity:1;
        transform:translateY(0);
      }
      .todo-toast--success{
        background:#0f766e;
        color:#ecfdf5;
      }
      .todo-toast--error{
        background:#b91c1c;
        color:#fee2e2;
      }
    `;
    var style = document.createElement('style');
    style.id = 'todo-toast-style';
    style.type = 'text/css';
    style.appendChild(document.createTextNode(css));
    document.head.appendChild(style);
  }

  function showTodoToast(type, message) {
    ensureTodoToastStyle();

    var existing = document.getElementById('todo-toast');
    if (existing) existing.remove();

    var div = document.createElement('div');
    div.id = 'todo-toast';
    div.className = 'todo-toast todo-toast--' + type;
    div.innerHTML = '<span>' + message + '</span>';
    document.body.appendChild(div);

    // Animate in
    setTimeout(function () {
      div.classList.add('is-visible');
    }, 10);

    // Auto hide
    setTimeout(function () {
      div.classList.remove('is-visible');
      setTimeout(function () {
        if (div && div.parentNode) div.parentNode.removeChild(div);
      }, 300);
    }, 3000);
  }

  $(document).on('click', '.js-quick-todo', function (e) {
    e.preventDefault();
    var $btn    = $(this);
    if ($btn.data('busy')) return;

    var title   = ($btn.data('todo-name') || '').toString().trim();
    var relType = ($btn.data('rel-type') || '').toString().trim();
    var relId   = $btn.data('rel-id') || '';

    if (!title) {
      showTodoToast('error', 'Missing To-Do title.');
      return;
    }

    $btn.data('busy', true).prop('disabled', true);

    $.ajax({
      url: (window.TODO_QUICK_ADD_URL || '/todo/quick_add'),
      type: 'POST',
      dataType: 'json',
      data: {
        todo_name: title,
        rel_type:  relType,
        rel_id:    relId
      }
    })
    .done(function (resp) {
      if (resp && resp.success) {
        showTodoToast('success', 'To-Do added to your list.');
      } else {
        showTodoToast('error', (resp && resp.message) ? resp.message : 'Failed to add To-Do.');
      }
    })
    .fail(function (xhr) {
      var msg = 'Failed to add To-Do.';
      if (xhr && xhr.status === 401) {
        msg = 'Your session expired. Please log in again.';
      }
      showTodoToast('error', msg);
    })
    .always(function () {
      $btn.data('busy', false).prop('disabled', false);
    });
  });

})(jQuery);



// App Table Filters 

// Table Filter - Global Filter 
document.addEventListener('DOMContentLoaded', function () {
    const filterBlocks = document.querySelectorAll('.app-table-filter[data-table-id]');

    // Add CSS for dropdown options
    const style = document.createElement('style');
    style.textContent = `
        .app-table-filter select.form-select-sm option {
            font-size: 0.75rem !important;
            padding: 0.25rem 0.5rem !important;
            border: none;
        }
        .app-table-filter select.form-select-sm {
            font-size: 0.75rem !important;
            padding: 0.25rem 0.5rem !important;
        }        
        .app-table-filter select.form-select-sm:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    `;
    document.head.appendChild(style);

    filterBlocks.forEach(block => {
        const tableId = block.getAttribute('data-table-id');
        const table = document.getElementById(tableId);
        if (!table) return;

        const showGlobalSearch  = block.getAttribute('data-show-global-search') === '1';
        const showColumnFilters = block.getAttribute('data-show-column-filters') === '1';

        const thead = table.querySelector('thead');
        const tbody = table.querySelector('tbody');
        if (!thead || !tbody) return;

        const headerCells = Array.from(thead.querySelectorAll('th'));
        const rows        = Array.from(tbody.querySelectorAll('tr'));

        // ------------------------------------------------------------------
        // Exclusions (from data-* on the filter block)
        // ------------------------------------------------------------------
        const excludeColsAttr = block.getAttribute('data-exclude-columns') || '';
        const excludeIdxAttr  = block.getAttribute('data-exclude-indexes') || '';

        const excludeCols = excludeColsAttr
            .split('||')
            .map(s => s.trim())
            .filter(s => s.length > 0);

        const excludeIndexes = excludeIdxAttr
            .split(',')
            .map(s => s.trim())
            .filter(s => s !== '')
            .map(s => parseInt(s, 10))
            .filter(n => !isNaN(n));

        // ------------------------------------------------------------------
        // Global search
        // ------------------------------------------------------------------
        let globalSearchInput = null;

        if (showGlobalSearch) {
            globalSearchInput = block.querySelector('.js-table-global-search');

            if (globalSearchInput) {
                globalSearchInput.addEventListener('input', function () {
                    const term = this.value.toLowerCase();
                    applyFilters();
                });
            }
        }

        // ------------------------------------------------------------------
        // Column filters
        // ------------------------------------------------------------------
        const columnFilterContainer = block.querySelector('.js-table-column-filters');
        const columnFilters         = [];

        if (showColumnFilters && columnFilterContainer) {
            headerCells.forEach((th, colIndex) => {
                const label = th.textContent.trim();
                if (!label) return;

                // Skip if this column is excluded by index
                if (excludeIndexes.includes(colIndex)) {
                    return;
                }

                // Skip if this column is excluded by label text
                if (excludeCols.includes(label)) {
                    return;
                }

                // Skip if this <th> explicitly disables filtering
                const thFilterAttr = (th.getAttribute('data-filter') || '').toLowerCase();
                if (thFilterAttr === 'none') {
                    return;
                }

                // Collect unique values for this column
                const valuesSet = new Set();
                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    if (!cells[colIndex]) return;
                    const text = cells[colIndex].textContent.trim();
                    if (text !== '') {
                        valuesSet.add(text);
                    }
                });

                if (valuesSet.size === 0) {
                    return;
                }

                const values = Array.from(valuesSet).sort((a, b) => a.localeCompare(b));

                // Build select
                const wrapper = document.createElement('div');
                wrapper.className = 'd-flex flex-column';

                const labelEl = document.createElement('label');
                labelEl.className = 'form-label mb-1';
                labelEl.textContent = label;
                wrapper.appendChild(labelEl);

                const select = document.createElement('select');
                select.className = 'form-select form-select-sm js-table-col-filter w-auto';
                select.setAttribute('data-col-index', String(colIndex));
                select.style.fontSize = '0.875rem'; // Add inline style for select text

                const optAll = document.createElement('option');
                optAll.value = '';
                optAll.textContent = 'All';
                optAll.style.fontSize = '0.875rem'; // Style the option
                select.appendChild(optAll);

                values.forEach(val => {
                    const o = document.createElement('option');
                    o.value = val;
                    o.textContent = val;
                    o.style.fontSize = '0.875rem'; // Style each option
                    select.appendChild(o);
                });

                select.addEventListener('change', applyFilters);

                wrapper.appendChild(select);
                columnFilterContainer.appendChild(wrapper);

                columnFilters.push(select);
            });
        }

        // ------------------------------------------------------------------
        // Apply filters
        // ------------------------------------------------------------------
        function applyFilters() {
            const globalTerm = globalSearchInput ? globalSearchInput.value.toLowerCase() : '';

            const activeColFilters = {};
            columnFilters.forEach(select => {
                const colIndex = parseInt(select.getAttribute('data-col-index'), 10);
                const val = select.value;
                if (val !== '') {
                    activeColFilters[colIndex] = val;
                }
            });

            rows.forEach(row => {
                let visible = true;

                // Global search
                if (globalTerm) {
                    const rowText = row.textContent.toLowerCase();
                    if (!rowText.includes(globalTerm)) {
                        visible = false;
                    }
                }

                // Column filters
                if (visible && Object.keys(activeColFilters).length > 0) {
                    const cells = row.querySelectorAll('td');
                    for (const [colIndexStr, expected] of Object.entries(activeColFilters)) {
                        const colIndex = parseInt(colIndexStr, 10);
                        const cell = cells[colIndex];
                        if (!cell) {
                            visible = false;
                            break;
                        }
                        const text = cell.textContent.trim();
                        if (text !== expected) {
                            visible = false;
                            break;
                        }
                    }
                }

                row.style.display = visible ? '' : 'none';
            });
        }
    });
});