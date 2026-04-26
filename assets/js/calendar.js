// assets/js/calendar.js

$(function () {
    // Initialize Bootstrap tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Cache DOM elements
    var calendarEl = document.getElementById('calendar');
    var draggableEvents = document.getElementById('events-list');
    var dropRemoveCheckbox = document.getElementById('drop-remove');
    var saveEventBtn = $('#saveEvent');
    var deleteEventBtn = $('#deleteEvent');
    var eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
    var eventForm = $('#eventForm');

    // Initialize FullCalendar with Draggable
    if (typeof FullCalendar !== "undefined" && FullCalendar.Draggable && draggableEvents) {
        new FullCalendar.Draggable(draggableEvents, {
            itemSelector: '.list-event',
            eventData: function (eventEl) {
                return {
                    title: eventEl.innerText.trim(),
                    className: eventEl.getAttribute('data-class')
                };
            }
        });
    }

    // --- FullCalendar Initialization ---
    var calendar = new FullCalendar.Calendar(calendarEl, {
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
    },
    initialView: 'dayGridMonth',
    locale: $('html').attr('lang') || 'en',
    navLinks: true,
    editable: true,
    droppable: true,
    selectable: true,
    nowIndicator: true,
    eventSources: [
        {
            url: base_url + 'calendar/get_events',
            method: 'GET',
            failure: function () {
                alert('There was an error fetching events!');
            }
        }
    ],

eventContent: function (arg) {
    const event = arg.event;
    const props = event.extendedProps || {};

    // FullCalendar already tells you if this segment is the start of the event
    // This works reliably in ALL views (month/week/day/list)
    const showTitle = arg.isStart !== false; // if undefined, treat as true

    // For multi-day allDay events, show title only on the first segment
    const isAllDayMulti =
        event.allDay &&
        event.start &&
        event.end &&
        dayjs(event.end).diff(dayjs(event.start), 'day') > 1;

    if (isAllDayMulti && !showTitle) {
        return { domNodes: [] };
    }

    const title = (event.title || '').trim();
    if (!title) {
        // fallback to avoid blank rendering
        return { html: '<div class="fc-event-title" style="font-size:10px;">(No Title)</div>' };
    }

    // Escape HTML to prevent breaking DOM if title contains < > etc.
    const safeTitle = title.replace(/[&<>"']/g, function (m) {
        return ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' })[m];
    });

    return {
        html: `<div class="fc-event-title" style="font-size:10px;">${safeTitle}</div>`
    };
},

    
    eventClick: function (info) {
        const event = info.event;
        const props = event.extendedProps || {};
    
        // Always open VIEW modal
        openViewModal(event);
    
        // Never open edit modal from click
        // Edit is now ONLY via explicit Edit button
    },

eventDrop: function (info) {
    const p = info.event.extendedProps || {};

    if (p.is_birthday || p.is_public_holiday || p.editable === false) {
        info.revert();
        alert('This event cannot be modified.');
        return;
    }

    updateEventFromCalendar(info.event);
},

eventResize: function (info) {
    const p = info.event.extendedProps || {};

    if (p.is_birthday || p.is_public_holiday || p.editable === false) {
        info.revert();
        alert('This event cannot be modified.');
        return;
    }

    updateEventFromCalendar(info.event);
},


    dateClick: function (info) {
        openAddModal(info.dateStr);
    },
    
    eventDidMount: function (info) {
        const event = info.event;
        const props = event.extendedProps || {};

        /* -------------------------------------------------
         * BIRTHDAY STYLING
         * ------------------------------------------------- */
        if (props.is_birthday === true) {
            info.el.classList.add('bg-light-success', 'fw-semibold');
        }

        if (props.is_public_holiday === true) {
            info.el.classList.add('bg-light-info', 'fw-semibold');
        }
        
        /* -------------------------------------------------
         * TOOLTIP
         * ------------------------------------------------- */
        let tooltipContent = '';
    
        if (event.title) {
            tooltipContent += `<div><strong>${event.title}</strong></div>`;
        }
        if (props.description) {
            tooltipContent += `<div>${props.description}</div>`;
        }
    
        if (props.is_birthday) {
            tooltipContent += `<div><strong></strong></div>`;
        }
    
        if (props.is_public_holiday) {
            const h = getHolidayLabelAndIcon(props.category);
            tooltipContent += `<div><strong>${h.label}</strong></div>`;
        }
    
        if (props.is_private) {
            tooltipContent += `<div><strong>Private Event</strong></div>`;
        }
    
        if (tooltipContent) {
            new bootstrap.Tooltip(info.el, {
                title: tooltipContent,
                placement: 'top',
                trigger: 'hover',
                container: 'body',
                html: true
            });
        }
    
        /* -------------------------------------------------
         * ICONS & VISUAL CUES
         * ------------------------------------------------- */
    
        // 🔒 Private event
        if (props.is_private) {
            $(info.el).find('.fc-event-title')
                .append('<i class="ti ti-lock ms-1" style="font-size:0.75em;"></i>');
        }
    
        // 🎂 Birthday
        if (props.is_birthday) {
            $(info.el).find('.fc-event-title');
        }
    
        // 🏖️ Public Holiday
        if (props.is_public_holiday) {
            $(info.el).find('.fc-event-title')
                .prepend('<i class="ti ti-beach me-1"></i>');
        }
    
        /* -------------------------------------------------
         * READ-ONLY ENFORCEMENT (UX + SAFETY)
         * ------------------------------------------------- */
    
        const isReadOnly =
            props.is_birthday === true ||
            props.is_public_holiday === true ||
            props.editable === false;
    
        if (isReadOnly) {
            // Cursor + visual hint
            info.el.style.cursor = 'default';
            info.el.classList.add('fc-event-readonly');
    
            // Hard-block accidental interactions
            info.el.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
        }

    }


});


    calendar.render();

    // --- Event Listeners ---
    $('#addEventBtn').on('click', function () {
        openAddModal();
    });

    saveEventBtn.on('click', function (e) {
        e.preventDefault();
        handleSaveEvent();
    });

    deleteEventBtn.on('click', function (e) {
        e.preventDefault();
        handleDeleteEvent();
    });

function getHolidayLabelAndIcon(categoryRaw) {
    const category = (categoryRaw || 'Holiday').trim();

    // Normalize
    const lower = category.toLowerCase();

    // Prevent "Holiday Holiday"
    const label = lower.includes('holiday')
        ? category
        : `${category} Holiday`;

    // Icon map (normalized)
    const iconMap = {
        local:    'ti ti-map-pin',
        federal: 'ti ti-building',
        religion:'ti ti-moon'
    };

    const key = lower.replace(' holiday', '');
    const icon = iconMap[key] || 'ti ti-calendar-event';

    return {
        label,
        icon
    };
}


function openViewModal(event) {
    const modal = new bootstrap.Modal(document.getElementById('viewEventModal'));
    const props = event.extendedProps || {};

    /* -------------------------------------------------
     * BASIC FIELDS
     * ------------------------------------------------- */
    $('#viewEventTitle').text(event.title || '');
    $('#viewEventDescription').text(props.description || '');

    /* -------------------------------------------------
     * DATES (HUMAN FRIENDLY) 
     * ------------------------------------------------- */
    $('#viewEventStart').text(humanDT(event.start));
    $('#viewEventEnd').text(event.end ? humanDT(event.end) : '—');

    /* -------------------------------------------------
     * TYPE + STATUS (WITH ICONS)
     * ------------------------------------------------- */
    const isHoliday  = !!props.is_public_holiday;
    const isBirthday = !!props.is_birthday;
    const isPrivate  = !!props.is_private;

    let typeLabel = 'Event';
    let typeIcon  = '<i class="ti ti-calendar me-1"></i>';

    if (isBirthday) {
        typeLabel = 'Birthday';
    }

if (isHoliday) {
    const h = getHolidayLabelAndIcon(props.category);
    typeLabel = h.label;
    typeIcon  = `<i class="${h.icon} me-1"></i>`;
}


    $('#viewEventType').html(typeIcon + typeLabel);
    $('#viewEventStatus').text(isPrivate ? 'Private' : 'Public');

    const $actions = $('#viewEventActions');
    $actions.empty();

    const isEditable = props.editable === true;
    const isReadOnly =
        isBirthday ||
        isHoliday ||
        props.editable === false;

    if (isReadOnly) {
        $('<span/>', {
            class: 'badge bg-light-secondary',
            html: '<i class="ti ti-lock me-1"></i> Read-only'
        }).appendTo($actions);

        modal.show();
        return;
    }

    if (isEditable) {

        if (typeof canEditEvent !== 'undefined' && canEditEvent) {
            $('<button/>', {
                class: 'btn btn-primary btn-sm me-2',
                html: '<i class="ti ti-edit me-1"></i> Edit',
                click: function () {
                    modal.hide();
                    openEditModal(event);
                }
            }).appendTo($actions);
        }

        if (typeof canDeleteEvent !== 'undefined' && canDeleteEvent) {
            $('<button/>', {
                class: 'btn btn-danger btn-sm',
                html: '<i class="ti ti-trash me-1"></i> Delete',
                click: function () {
                    modal.hide();
                    $('#eventId').val(event.id);
                    handleDeleteEvent();
                }
            }).appendTo($actions);
        }
    }

    modal.show();
}


// Format Date -> Human readable (Full Month, Day, Year : Time AM/PM)
function humanDT(dateObj, allDay = false) {
    if (!dateObj) return '';
    const d = dayjs(dateObj);
    if (!d.isValid()) return '';

    return allDay
        ? d.format('MMMM D, YYYY')
        : d.format('MMMM D, YYYY : h:mm A');
}

    // --- Modal Functions ---
    function openAddModal(dateStr) {
        $('#eventModalLabel').text(lang.add_new_event);
        eventForm[0].reset();
        $('#eventId').val('');
        
        // Set default start time to next full hour
        var defaultDate = dateStr ? new Date(dateStr) : new Date();
        defaultDate.setHours(defaultDate.getHours() + 1, 0, 0, 0);
        
        $('#eventStart').val(formatDateForInput(defaultDate));
        $('#eventEnd').val('');
        $('#eventColor').val('event-primary');
        $('#eventPrivate').prop('checked', false);
        deleteEventBtn.hide();
        eventModal.show();
        $('#eventTitle').trigger('focus');
    }

    function openEditModal(event) {

        if (event.extendedProps && event.extendedProps.editable !== true) {
            alert('This event cannot be edited.');
            return;
        }
        
        $('#eventModalLabel').text(lang.edit_event);
        $('#eventId').val(event.id);
        $('#eventTitle').val(event.title);
        $('#eventDescription').val(event.extendedProps.description || '');
        $('#eventStart').val(formatDateForInput(event.start));
        $('#eventEnd').val(event.end ? formatDateForInput(event.end) : '');
        $('#eventColor').val(event.classNames[0] || 'event-primary');
        $('#eventPrivate').prop('checked', event.extendedProps.is_private ? true : false);
        deleteEventBtn.show();
        eventModal.show();
        $('#eventTitle').trigger('focus');
    }

    // --- Helper Functions ---
    function formatDateForInput(date) {
        if (!date) return '';
        var d = typeof date === 'string' ? new Date(date) : date;
        // Convert to datetime-local format (YYYY-MM-DDTHH:mm)
        return d.toISOString().slice(0, 16);
    }

function formatDateForDB(dateVal) {
    if (!dateVal) return null;

    // Accept Date object OR string
    var d = (dateVal instanceof Date) ? dateVal : new Date(dateVal);

    // MySQL datetime: YYYY-MM-DD HH:MM:SS
    return d.toISOString().slice(0, 19).replace('T', ' ');
}


    // --- CRUD Operations ---
    function handleSaveEvent() {
        var eventId = $('#eventId').val();
        var eventData = {
            title: $('#eventTitle').val(),
            description: $('#eventDescription').val(),
            start: formatDateForDB($('#eventStart').val()),
            end: $('#eventEnd').val() ? formatDateForDB($('#eventEnd').val()) : null,
            className: $('#eventColor').val(),
            is_private: $('#eventPrivate').is(':checked') ? 1 : 0
        };

        if (!eventData.title || !eventData.start) {
            alert(lang.event_title_start_required);
            return;
        }

        if (eventId) {
            updateEvent(eventId, eventData);
        } else {
            createEvent(eventData);
        }
    }

function updateEventFromCalendar(fcEvent) {
    const eventId = fcEvent.id;
    if (!eventId) return;

    const eventData = {
        title: fcEvent.title || '',
        description: (fcEvent.extendedProps && fcEvent.extendedProps.description) ? fcEvent.extendedProps.description : '',
        start: formatDateForDB(fcEvent.start),
        end: fcEvent.end ? formatDateForDB(fcEvent.end) : null,
        className: (fcEvent.classNames && fcEvent.classNames.length) ? fcEvent.classNames[0] : 'event-primary',
        is_private: (fcEvent.extendedProps && fcEvent.extendedProps.is_private) ? 1 : 0
    };

    updateEvent(eventId, eventData);
}

    function createEvent(eventData) {
        $.ajax({
            url: base_url + 'calendar/add_event',
            type: 'POST',
            dataType: 'json',
            data: eventData,
            success: function (response) {
                if (response.status === 'success') {
                    calendar.refetchEvents();
                    eventModal.hide();
                } else {
                    alert('Error: ' + (response.error || lang.event_create_error));
                }
            },
            error: function () {
                alert(lang.event_create_error);
            }
        });
    }

    function updateEvent(eventId, eventData) {
        eventData.id = eventId;
        $.ajax({
            url: base_url + 'calendar/update_event',
            type: 'POST',
            dataType: 'json',
            data: eventData,
            success: function (response) {
                if (response.status === 'success') {
                    calendar.refetchEvents();
                    eventModal.hide();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function () {
                alert(lang.event_update_error);
            }
        });
    }

    function handleDeleteEvent() {
        var eventId = $('#eventId').val();
        if (!eventId) return;

        if (!confirm(lang.confirm_delete_event)) return;
        
        $.ajax({
            url: base_url + 'calendar/delete_event',
            type: 'POST',
            dataType: 'json',
            data: { id: eventId },
            success: function (response) {
                if (response.status === 'success') {
                    calendar.refetchEvents();
                    eventModal.hide();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function () {
                alert(lang.event_delete_error);
            }
        });
    }

    // --- Slider for events list (optional) ---
    if ($('.slider-event').length) {
        $('.slider-event').slick({
            dots: false,
            speed: 1000,
            slidesToShow: 1,
            centerMode: true,
            arrows: false,
            vertical: true,
            verticalSwiping: true,
            focusOnSelect: true,
            autoplay: true,
            autoplaySpeed: 1000,
        });
    }
});


