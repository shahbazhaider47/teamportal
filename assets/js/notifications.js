// ======================================================================
// Basic toast close (if you use .app-toast somewhere else)
// ======================================================================
(function($) {
    'use strict';

    $(document).on('click', '.toast-close', function() {
        toastclose();
    });

    window.toastclose = function() {
        $('.app-toast').addClass('d-none');
    };

    window.handleToast = function(ele) {
        $('.' + ele.id).removeClass('d-none');
        setTimeout(function() {
            $('.' + ele.id).addClass('d-none');
        }, 5000);
    };

})(jQuery);

// ======================================================================
// Realtime Notifications + Toast + Favicon Dot
// ======================================================================
;(function($, window, document) {
    'use strict';

    // ------------------------------------------------------------
    // Favicon dot utility
    // ------------------------------------------------------------
    var FaviconNotifier = (function() {
        var originalHref = null;
        var currentHref  = null;
        var color        = '#ff4b4b'; // red dot – change if you want

        function getFaviconLink() {
            var link = document.querySelector("link[rel*='icon']");
            if (!link) {
                // create if missing
                link = document.createElement('link');
                link.rel = 'icon';
                document.head.appendChild(link);
            }
            return link;
        }

        function setDot() {
            var link = getFaviconLink();

            if (originalHref === null) {
                originalHref = link.href || '';
            }

            // Build 32x32 canvas with transparent background + colored circle
            var canvas = document.createElement('canvas');
            canvas.width  = 32;
            canvas.height = 32;

            var ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, 32, 32);

            // Draw the dot
            ctx.beginPath();
            ctx.arc(24, 8, 6, 0, 2 * Math.PI, false);
            ctx.fillStyle = color;
            ctx.fill();

            currentHref = canvas.toDataURL('image/png');
            link.href   = currentHref;
        }

        function clearDot() {
            var link = getFaviconLink();
            if (originalHref !== null) {
                link.href = originalHref;
            }
        }

        return {
            setDot: setDot,
            clearDot: clearDot
        };
    })();

    function updateFaviconDotFromBadge() {
        var $badge = $('#top-notifications-count');
        if (!$badge.length) return;

        var count = parseInt($badge.text(), 10);
        if (isNaN(count) || count <= 0) {
            FaviconNotifier.clearDot();
        } else {
            FaviconNotifier.setDot();
        }
    }

    // ------------------------------------------------------------
    // Pusher realtime subscription
    // ------------------------------------------------------------
    function initRealtimeNotifications() {
        if (!window.APP_REALTIME) {
            console.warn('[Notifications] APP_REALTIME not defined');
            return;
        }
        if (!window.APP_REALTIME.pusherEnabled) {
            console.warn('[Notifications] Pusher disabled in settings');
            return;
        }
        if (!window.APP_REALTIME.userId) {
            console.warn('[Notifications] No userId on APP_REALTIME');
            return;
        }

        if (typeof Pusher === 'undefined') {
            console.error('[Notifications] Pusher library not loaded');
            return;
        }

        var key     = window.APP_REALTIME.pusherKey;
        var cluster = window.APP_REALTIME.pusherCluster || 'mt1';

        if (!key) {
            console.warn('[Notifications] Missing Pusher key');
            return;
        }

        // You can enable this while debugging, then turn off:
        // Pusher.logToConsole = true;

        var pusher = new Pusher(key, {
            cluster: cluster,
            forceTLS: true
        });

        var channelName = 'user-' + window.APP_REALTIME.userId;
        console.log('[Notifications] Subscribing to channel:', channelName);

        var channel = pusher.subscribe(channelName);

        channel.bind('pusher:subscription_succeeded', function() {
            console.log('[Notifications] Subscribed successfully to ' + channelName);
        });

        channel.bind('pusher:subscription_error', function(status) {
            console.error('[Notifications] Subscription error:', status);
        });

        channel.bind('notification.created', function(data) {
            console.log('[Notifications] Incoming notification payload:', data);
            try {
                handleIncomingNotification(data);
            } catch (e) {
                console.error('[Notifications] Error handling notification', e);
            }
        });

        // expose handler for manual testing
        window.handleIncomingNotification = handleIncomingNotification;
    }

    // ------------------------------------------------------------
    // Handle one incoming notification event
    // ------------------------------------------------------------
    function handleIncomingNotification(notification) {
        // 0) Play notification sound (if allowed by browser)
        try {
            var audio = document.getElementById('notif-sound');
            if (audio) {
                audio.currentTime = 0;
                audio.play().catch(function (err) {
                    console.warn('[Notifications] Audio play blocked:', err);
                });
            }
        } catch (e) {
            console.warn('[Notifications] Audio error:', e);
        }

        // 1) Update badge count
        var $badge = $('#top-notifications-count');
        if ($badge.length) {
            var current = parseInt($badge.text(), 10);
            if (isNaN(current)) current = 0;
            var next = current + 1;
            $badge.text(next).removeClass('d-none');
        } else {
            console.warn('[Notifications] Badge element #top-notifications-count not found');
        }

        // 2) Update tab icon dot based on badge
        updateFaviconDotFromBadge();

        // 3) Inject into dropdown list
        var $list = $('#top-notifications-list');
        if ($list.length) {
            // Remove "No New Notifications" placeholder if present
            $list.find('.hidden-massage').remove();

            var itemHtml = buildNotificationListItem(notification);
            $list.prepend(itemHtml);
        } else {
            console.warn('[Notifications] List element #top-notifications-list not found');
        }

        // 4) Toast popup (top-right)
        if (typeof window.showNotificationToast === 'function') {
            window.showNotificationToast(notification);
        }
    }

    function buildNotificationListItem(notification) {
        var link  = notification.link || '#';
        var title = notification.title || 'New notification';
        var desc  = notification.description || '';
        var icon  = notification.icon || 'ti ti-bell';
        var ts    = notification.created_at || notification.date || '';

        return '' +
            '<a href="' + link + '" ' +
            '   class="head-box d-flex align-items-start justify-content-between px-3 py-2 border-bottom text-decoration-none text-dark">' +
            '  <div class="d-flex align-items-center">' +
            '    <span class="bg-secondary h-35 w-35 d-flex-center b-r-50 position-relative me-2">' +
            '      <i class="' + icon + ' text-white"></i>' +
            '    </span>' +
            '    <div>' +
            '      <h6 class="mb-1 f-s-13 text-truncate" style="max-width: 180px;">' +
            '        ' + escapeHtml(title) +
            '      </h6>' +
            '      <p class="text-secondary f-s-11 mb-0">' + escapeHtml(desc) + '</p>' +
            '    </div>' +
            '  </div>' +
            '</a>';
    }

    // Simple HTML escape to avoid breaking the dropdown
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // ------------------------------------------------------------
    // Global toast helper (top-right popup)
    // ------------------------------------------------------------
    window.showNotificationToast = function(notification) {
        if (typeof Toastify === 'undefined') return;

        var title = notification.title || 'New notification';
        var desc  = notification.description || '';
        var text  = title + (desc ? ' — ' + desc : '');

        Toastify({
            text: text,
            duration: 5000,
            gravity: "top",
            position: "right",
            close: true,
            stopOnFocus: true,
            onClick: function() {
                if (notification.link) {
                    window.location.href = notification.link;
                }
            },
            style: {
                background: "linear-gradient(135deg, #2563eb 0%, #06b6d4 100%)"
            }
        }).showToast();
    };

    // ------------------------------------------------------------
    // Init on DOM ready
    // ------------------------------------------------------------
    $(function() {
        // Existing unread count from PHP should control tab dot on load
        updateFaviconDotFromBadge();
        // Realtime
        initRealtimeNotifications();
    });

})(jQuery, window, document);
