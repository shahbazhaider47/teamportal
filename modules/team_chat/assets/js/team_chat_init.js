/**
 * team_chat_init.js
 * Bootstrap entry point. Initialises all modules and
 * exposes the global TeamChat API used by views and partials.
 * Loaded last in the asset stack so all modules are available.
 */
(function (window, document) {
    'use strict';

    // Bail if config is missing (module not active on this page)
    if (!window.TeamChatConfig) return;

    const Cfg = window.TeamChatConfig;

    /* ─── Wait for DOM ─────────────────────────────────────── */
    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    ready(function () {

        const shell = document.getElementById('teamChatShell');
        if (!shell) return; // Chat page not loaded

        /* ─── Initialise modules in dependency order ─────── */
        TC_Socket.init(Cfg.socketUrl, Cfg.userId, Cfg.wsToken);
        TC_Conversations.init();
        TC_Messages.init();
        TC_Threads.init();
        TC_Input.init();
        TC_Upload.init();
        TC_Mentions.init();
        TC_Reactions.init();
        TC_Search.init();
        TC_Members.init();
        TC_Notifications.init();

        /* ─── Open active conversation if set ───────────── */
        if (Cfg.activeConversationId) {
            TC_Messages.scrollToBottom();
            TC_Conversations.markActive(Cfg.activeConversationId);
        }

        /* ─── Sidebar mobile toggle ──────────────────────── */
        const sidebar      = document.getElementById('tcSidebar');
        const backBtn      = document.getElementById('tcBackToSidebar');
        const threadPanel  = document.getElementById('tcThreadPanel');

        if (backBtn && sidebar) {
            backBtn.addEventListener('click', function () {
                sidebar.classList.remove('tc-sidebar--hidden');
                if (threadPanel) threadPanel.classList.remove('tc-thread-panel--open');
            });
        }

        /* ─── Global drag-drop on pane ───────────────────── */
        const pane = document.getElementById('tcPane');
        if (pane) {
            pane.addEventListener('dragover', function (e) {
                e.preventDefault();
                TC_Upload.showDropZone();
            });

            pane.addEventListener('dragleave', function (e) {
                if (!pane.contains(e.relatedTarget)) {
                    TC_Upload.hideDropZone();
                }
            });

            pane.addEventListener('drop', function (e) {
                e.preventDefault();
                TC_Upload.hideDropZone();
                if (e.dataTransfer && e.dataTransfer.files.length) {
                    TC_Upload.handleFiles(e.dataTransfer.files);
                }
            });
        }

        /* ─── Sidebar search filter ─────────────────────── */
        const sidebarSearch = document.getElementById('tcSidebarSearch');
        const clearBtn      = document.getElementById('tcSidebarSearchClear');

        if (sidebarSearch) {
            sidebarSearch.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                TC_Conversations.filter(q);
                if (clearBtn) clearBtn.classList.toggle('d-none', !q);
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                sidebarSearch.value = '';
                TC_Conversations.filter('');
                this.classList.add('d-none');
                sidebarSearch.focus();
            });
        }

        /* ─── Pins bar toggle ───────────────────────────── */
        const pinsToggle = document.getElementById('tcTogglePins');
        const pinsBar    = document.getElementById('tcPinsBar');
        const closePins  = document.getElementById('tcClosePinsBar');

        if (pinsToggle && pinsBar) {
            pinsToggle.addEventListener('click', function () {
                pinsBar.classList.toggle('d-none');
            });
        }

        if (closePins && pinsBar) {
            closePins.addEventListener('click', function () {
                pinsBar.classList.add('d-none');
            });
        }

        /* ─── Message search toggle ─────────────────────── */
        const searchBtn       = document.getElementById('tcSearchMessages');
        const searchBar       = document.getElementById('tcMessageSearchBar');
        const closeSearchBtn  = document.getElementById('tcCloseMessageSearch');

        if (searchBtn && searchBar) {
            searchBtn.addEventListener('click', function () {
                searchBar.classList.toggle('d-none');
                if (!searchBar.classList.contains('d-none')) {
                    document.getElementById('tcMessageSearchInput')?.focus();
                }
            });
        }

        if (closeSearchBtn && searchBar) {
            closeSearchBtn.addEventListener('click', function () {
                searchBar.classList.add('d-none');
                document.getElementById('tcMessageSearchResults')?.classList.add('d-none');
            });
        }

        /* ─── Mute toggle ───────────────────────────────── */
        const muteBtn = document.getElementById('tcMuteBtn');
        if (muteBtn) {
            muteBtn.addEventListener('click', function () {
                const convId = parseInt(this.dataset.convId);
                const muted  = this.dataset.muted === '1';
                TeamChat.muteConversation(convId, !muted);
            });
        }

        /* ─── Archive conversation ──────────────────────── */
        const archiveBtn = document.getElementById('tcArchiveConv');
        if (archiveBtn) {
            archiveBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const convId = parseInt(this.dataset.convId);
                if (confirm('Archive this conversation? It will be read-only and hidden from the sidebar.')) {
                    TeamChat.archiveConversation(convId);
                }
            });
        }

        /* ─── Rename conversation ───────────────────────── */
        const renameBtn = document.getElementById('tcRenameConv');
        if (renameBtn) {
            renameBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const currentName = document.getElementById('tcPaneName')?.textContent?.trim();
                const newName     = prompt('Rename conversation:', currentName);
                if (newName && newName.trim() && newName.trim() !== currentName) {
                    TeamChat.renameConversation(newName.trim());
                }
            });
        }

        /* ─── Unread polling (fallback when WS unavailable) */
        TC_Notifications.startPolling();

    }); // end ready()

    /* =========================================================
       GLOBAL TeamChat API
       Called by partials, modals, and PHP-rendered event handlers
    ========================================================= */
    window.TeamChat = {

        /* Conversations */
        openConversation: function (convId) {
            TC_Conversations.open(convId);
        },

        createDirect: function (targetUserId) {
            return TC_Conversations.createDirect(targetUserId);
        },

        createGroup: function (name, memberIds) {
            return TC_Conversations.createGroup(name, memberIds);
        },

        createChannel: function (name, desc, teamId, deptId) {
            return TC_Conversations.createChannel(name, desc, teamId, deptId);
        },

        archiveConversation: function (convId) {
            return TC_Conversations.archive(convId);
        },

        renameConversation: function (name) {
            return TC_Conversations.rename(name);
        },

        muteConversation: function (convId, mute) {
            return TC_Conversations.mute(convId, mute);
        },

        /* Messages */
        sendMessage: function (body, parentId) {
            return TC_Messages.send(body, parentId);
        },

        startEdit: function (msgId) {
            TC_Input.startEdit(msgId);
        },

        deleteMessage: function (msgId) {
            if (confirm('Delete this message?')) {
                TC_Messages.remove(msgId);
            }
        },

        scrollToMessage: function (msgId) {
            TC_Messages.scrollTo(msgId);
        },

        /* Threads */
        openThread: function (parentMsgId) {
            TC_Threads.open(parentMsgId);
        },

        /* Reactions */
        toggleReaction: function (msgId, emoji) {
            TC_Reactions.toggle(msgId, emoji);
        },

        /* Pins */
        pinMessage: function (msgId, convId) {
            TC_Messages.pin(msgId, convId);
        },

        unpinMessage: function (msgId, convId) {
            TC_Messages.unpin(msgId, convId);
        },

        /* Members */
        refreshMembersModal: function () {
            TC_Members.refresh();
        },

        addMember: function (userId) {
            return TC_Members.add(userId);
        },

        removeMember: function (userId) {
            TC_Members.remove(userId);
        },

        updateMemberRole: function (userId, role) {
            TC_Members.updateRole(userId, role);
        },

        leaveConversation: function () {
            TC_Members.leave();
        },

        /* Files */
        openFilePreview: function (attachmentId) {
            // Delegated to _file_preview_modal.php inline script
        },

        openFilePreviewData: function (data) {
            // Delegated to _file_preview_modal.php inline script
        },
    };

})(window, document);