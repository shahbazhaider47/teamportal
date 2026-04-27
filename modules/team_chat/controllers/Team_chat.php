<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Team_chat Controller
 * Handles both page rendering and AJAX endpoints for the Team Chat module.
 */
class Team_chat extends App_Controller
{
    protected $active_module;

    public function __construct()
    {
        parent::__construct();

        $this->_require_login();
        $this->_guard();

        $this->load->helper(['url', 'form', 'team_chat/team_chat']);
        $this->load->library(['session']);

        $this->load->model([
            'User_model',
            'team_chat/Team_chat_conversation_model',
            'team_chat/Team_chat_message_model',
            'team_chat/Team_chat_attachment_model',
            'team_chat/Team_chat_reaction_model',
            'team_chat/Team_chat_notification_model',
        ]);

        $this->active_module = defined('TEAM_CHAT_MODULE_NAME') ? TEAM_CHAT_MODULE_NAME : 'team_chat';
    }

    // ─────────────────────────────────────────────────────────
    // Guards & Helpers
    // ─────────────────────────────────────────────────────────

    protected function _require_login()
    {
        if (!$this->session->userdata('user_id')) {
            redirect('authentication/login');
            exit;
        }
    }

    protected function _guard()
    {
        if (!staff_can('access', 'team_chat')) {
            access_denied('team_chat');
        }
    }

    protected function _user_id(): int
    {
        return (int)$this->session->userdata('user_id');
    }

    // ─────────────────────────────────────────────────────────
    // Render Helper
    // Registers assets then loads ONLY the requested subview.
    // No master layout — the subview is the full page output.
    // ─────────────────────────────────────────────────────────
    protected function _render($title, $subview, $data = [])
    {
        add_module_assets($this->active_module, [
            'css' => ['team_chat.css'],
            'js'  => [
                'modules/team_chat_socket.js',
                'modules/team_chat_conversations.js',
                'modules/team_chat_messages.js',
                'modules/team_chat_threads.js',
                'modules/team_chat_input.js',
                'modules/team_chat_upload.js',
                'modules/team_chat_mentions.js',
                'modules/team_chat_reactions.js',
                'modules/team_chat_search.js',
                'modules/team_chat_members.js',
                'modules/team_chat_notifications.js',
                'team_chat_init.js',
            ],
        ]);

        $this->load->view('layouts/master', [
            'page_title'   => $title,
            'subview'      => $subview,
            'view_data'    => $data,
            'hide_sidebar' => true,
        ] + $data);
    }

    // ─────────────────────────────────────────────────────────
    // JS Config Builder
    // ─────────────────────────────────────────────────────────
    private function _build_js_config($user_id, $active_conversation_id = null)
    {
        $user = $this->db
            ->select('id, fullname, firstname, lastname, profile_image, emp_id')
            ->where('id', $user_id)
            ->get('users')
            ->row_array();

        return [
            'userId'               => (int)$user_id,
            'userFullname'         => htmlspecialchars(team_chat_user_display_name($user ?? []), ENT_QUOTES),
            'userAvatar'           => team_chat_user_avatar_url($user['profile_image'] ?? null),
            'activeConversationId' => (int)$active_conversation_id,
            'baseUrl'              => site_url('team_chat/api'),
            'uploadUrl'            => site_url('team_chat/api/upload'),
            'socketUrl'            => defined('TEAM_CHAT_SOCKET_URL') ? TEAM_CHAT_SOCKET_URL : '',
            'moduleUrl'            => defined('TEAM_CHAT_MODULE_URL') ? TEAM_CHAT_MODULE_URL : '',
            'csrfTokenName'        => $this->security->get_csrf_token_name(),
            'csrfHash'             => $this->security->get_csrf_hash(),
            'wsToken'              => team_chat_ws_token($user_id),
            'maxFileSizeMb'        => 10,
            'canCreateChannel'     => (bool)staff_can('create_channel', 'team_chat'),
            'canManageChannel'     => (bool)staff_can('manage_channel', 'team_chat'),
            'canDeleteAny'         => (bool)staff_can('delete_message', 'team_chat'),
            'canViewAll'           => (bool)staff_can('view_all',       'team_chat'),
        ];
    }

    // =========================================================
    // PAGE RENDERING METHODS
    // =========================================================

    /**
     * GET /team_chat
     */
    public function index()
    {
        $user_id = $this->_user_id();

        $data = [
            'title'               => 'Chat',
            'user_id'             => $user_id,
            'conversations'       => $this->Team_chat_conversation_model->get_user_conversations($user_id),
            'teams'               => $this->db->get('teams')->result_array(),
            'departments'         => $this->db->get('departments')->result_array(),
            'active_conversation' => null,
            'messages'            => [],
            'members'             => [],
            'pinned_messages'     => [],
            'js_config'           => $this->_build_js_config($user_id),
        ];

        $this->_render($data['title'], 'team_chat/index', $data);
    }

    /**
     * GET /team_chat/conversation/{id}
     */
    public function conversation($conversation_id = null)
    {
        if (!$conversation_id) {
            redirect('team_chat');
        }

        $user_id         = $this->_user_id();
        $conversation_id = (int)$conversation_id;

        $is_member = $this->Team_chat_conversation_model->is_member($conversation_id, $user_id);

        if (!$is_member && !staff_can('view_all', 'team_chat')) {
            set_alert('warning', _l('team_chat_access_denied'));
            redirect('team_chat');
        }

        $this->Team_chat_conversation_model->mark_as_read($conversation_id, $user_id);

        $data = [
            'title'               => _l('team_chat'),
            'user_id'             => $user_id,
            'conversations'       => $this->Team_chat_conversation_model->get_user_conversations($user_id),
            'active_conversation' => $this->Team_chat_conversation_model->get_conversation($conversation_id, $user_id),
            'messages'            => $this->Team_chat_message_model->get_messages($conversation_id, $user_id),
            'members'             => $this->Team_chat_conversation_model->get_members($conversation_id),
            'pinned_messages'     => $this->Team_chat_message_model->get_pinned($conversation_id),
            'teams'               => $this->db->get('teams')->result_array(),
            'departments'         => $this->db->get('departments')->result_array(),
            'js_config'           => $this->_build_js_config($user_id, $conversation_id),
        ];

        $this->_render($data['title'], 'team_chat/index', $data);
    }

    /**
     * GET /team_chat/settings
     */
    public function settings()
    {
        if (!staff_can('manage_channel', 'team_chat')) {
            access_denied('team_chat_settings');
        }

        $data = [
            'title' => _l('team_chat_settings'),
        ];

        $this->_render($data['title'], 'team_chat/settings', $data);
    }

    // =========================================================
    // API METHODS (formerly Team_chat_api)
    // All endpoints now accessible via /team_chat/api/{method}
    // =========================================================

    /**
     * API router - routes /team_chat/api/{method} calls
     * This method serves as a dispatcher for all API requests
     */
    public function api($method = null, $param1 = null, $param2 = null)
    {
        if (!module_is_active(TEAM_CHAT_MODULE_NAME)) {
            $this->_json_error('Module inactive', 403);
        }

        switch ($method) {
            case 'conversations':
                $this->_api_conversations();
                break;

            case 'conversation':
                switch ($param1) {
                    case 'create_direct':  $this->_api_create_direct(); break;
                    case 'create_group':   $this->_api_create_group(); break;
                    case 'create_channel': $this->_api_create_channel(); break;
                    case 'update':         $this->_api_update_conversation($param2); break;
                    case 'archive':        $this->_api_archive_conversation($param2); break;
                    default:               $this->_api_conversation($param1); break;
                }
                break;

            case 'members':
                switch ($param1) {
                    case 'add':         $this->_api_add_members(); break;
                    case 'remove':      $this->_api_remove_member(); break;
                    case 'update_role': $this->_api_update_member_role(); break;
                    case 'mute':        $this->_api_mute_conversation(); break;
                    default:            $this->_api_members($param1); break;
                }
                break;

            case 'messages':
                switch ($param1) {
                    case 'send':      $this->_api_send_message(); break;
                    case 'edit':      $this->_api_edit_message($param2); break;
                    case 'delete':    $this->_api_delete_message($param2); break;
                    case 'thread':    $this->_api_thread($param2); break;
                    case 'mark_read': $this->_api_mark_read(); break;
                    case 'search':    $this->_api_search_messages(); break;
                    default:          $this->_api_messages($param1); break;
                }
                break;

            case 'reactions':
                if ($param1 === 'toggle') {
                    $this->_api_toggle_reaction();
                }
                $this->_api_reactions($param1);
                break;

            case 'pins':
                switch ($param1) {
                    case 'add':    $this->_api_pin_message(); break;
                    case 'remove': $this->_api_unpin_message(); break;
                    default:       $this->_api_pinned_messages($param1); break;
                }
                break;

            case 'upload':
                if ($param1 === 'attach') {
                    $this->_api_attach_upload();
                }
                $this->_api_upload();
                break;

            case 'users':
                $this->_api_users($param1);
                break;

            case 'unread':
            case 'unread_counts':
                $this->_api_unread_counts();
                break;

            case 'typing':
                $this->_api_typing();
                break;

            // Backward-compatible flat endpoint names.
            case 'create_direct':        $this->_api_create_direct(); break;
            case 'create_group':         $this->_api_create_group(); break;
            case 'create_channel':       $this->_api_create_channel(); break;
            case 'update_conversation':  $this->_api_update_conversation($param1); break;
            case 'archive_conversation': $this->_api_archive_conversation($param1); break;
            case 'add_members':          $this->_api_add_members(); break;
            case 'remove_member':        $this->_api_remove_member(); break;
            case 'update_member_role':   $this->_api_update_member_role(); break;
            case 'mute_conversation':    $this->_api_mute_conversation(); break;
            case 'send_message':         $this->_api_send_message(); break;
            case 'edit_message':         $this->_api_edit_message($param1); break;
            case 'delete_message':       $this->_api_delete_message($param1); break;
            case 'thread':               $this->_api_thread($param1); break;
            case 'mark_read':            $this->_api_mark_read(); break;
            case 'search_messages':      $this->_api_search_messages(); break;
            case 'toggle_reaction':      $this->_api_toggle_reaction(); break;
            case 'pin_message':          $this->_api_pin_message(); break;
            case 'unpin_message':        $this->_api_unpin_message(); break;
            case 'pinned_messages':      $this->_api_pinned_messages($param1); break;
            case 'delete_attachment':    $this->_api_delete_attachment(); break;

            default:
                $this->_json_error('API method not found', 404);
        }
    }

    // =========================================================
    // PRIVATE API HANDLERS
    // =========================================================

    // ─────────────────────────────────────────────────────────
    // Conversations
    // ─────────────────────────────────────────────────────────

    private function _api_conversations()
    {
        $this->_only_get();
        $this->_json_success(
            $this->Team_chat_conversation_model->get_user_conversations($this->_user_id())
        );
    }

    private function _api_conversation($conversation_id = null)
    {
        $this->_only_get();

        $conversation_id = (int)$conversation_id;
        $user_id         = $this->_user_id();

        if (!$conversation_id) {
            $this->_json_error('Invalid conversation ID');
        }

        $this->_require_member($conversation_id, $user_id);

        $conversation             = $this->Team_chat_conversation_model->get_conversation($conversation_id, $user_id);
        $conversation['members']  = $this->Team_chat_conversation_model->get_members($conversation_id);

        $this->_json_success($conversation);
    }

    private function _api_create_direct()
    {
        $this->_only_post();

        $user_id        = $this->_user_id();
        $target_user_id = (int)$this->input->post('target_user_id');

        if (!$target_user_id || $target_user_id === $user_id) {
            $this->_json_error('Invalid target user');
        }

        $target = $this->User_model->get($target_user_id);

        if (!$target || !$target['is_active']) {
            $this->_json_error('User not found');
        }

        $conversation = $this->Team_chat_conversation_model->get_or_create_direct($user_id, $target_user_id);

        if (!$conversation) {
            $this->_json_error('Could not create conversation');
        }

        $this->_json_success($conversation);
    }

    private function _api_create_group()
    {
        $this->_only_post();

        $user_id    = $this->_user_id();
        $name       = trim($this->input->post('name'));
        $member_ids = $this->input->post('member_ids');

        if (empty($name)) {
            $this->_json_error('Group name is required');
        }

        if (!is_array($member_ids) || count($member_ids) < 1) {
            $this->_json_error('At least one member is required');
        }

        $member_ids   = array_map('intval', $member_ids);
        $member_ids[] = $user_id;
        $member_ids   = array_unique($member_ids);

        $conversation_id = $this->Team_chat_conversation_model->create_conversation([
            'type'       => 'group',
            'name'       => $name,
            'created_by' => $user_id,
        ], $member_ids);

        if (!$conversation_id) {
            $this->_json_error('Could not create group');
        }

        $this->_json_success(
            $this->Team_chat_conversation_model->get_conversation($conversation_id, $user_id)
        );
    }

    private function _api_create_channel()
    {
        $this->_only_post();

        if (!staff_can('create_channel', 'team_chat')) {
            $this->_json_error('Permission denied', 403);
        }

        $user_id       = $this->_user_id();
        $name          = trim($this->input->post('name'));
        $description   = trim($this->input->post('description'));
        $team_id       = (int)$this->input->post('team_id');
        $department_id = (int)$this->input->post('department_id');

        if (empty($name)) {
            $this->_json_error('Channel name is required');
        }

        $member_ids = [$user_id];

        if ($team_id) {
            $rows = $this->db->select('id')->where('emp_team', $team_id)->where('is_active', 1)->get('users')->result_array();
            foreach ($rows as $r) { $member_ids[] = (int)$r['id']; }
        } elseif ($department_id) {
            $rows = $this->db->select('id')->where('emp_department', $department_id)->where('is_active', 1)->get('users')->result_array();
            foreach ($rows as $r) { $member_ids[] = (int)$r['id']; }
        }

        $member_ids = array_unique($member_ids);

        $conversation_id = $this->Team_chat_conversation_model->create_conversation([
            'type'          => 'channel',
            'name'          => $name,
            'slug'          => team_chat_make_slug($name),
            'description'   => $description,
            'team_id'       => $team_id ?: null,
            'department_id' => $department_id ?: null,
            'created_by'    => $user_id,
        ], $member_ids, 'owner');

        if (!$conversation_id) {
            $this->_json_error('Could not create channel');
        }

        $this->_json_success(
            $this->Team_chat_conversation_model->get_conversation($conversation_id, $user_id)
        );
    }

    private function _api_update_conversation($conversation_id = null)
    {
        $this->_only_post();

        $conversation_id = (int)$conversation_id;
        $user_id         = $this->_user_id();

        $this->_require_member($conversation_id, $user_id);
        $this->_require_role($conversation_id, $user_id, ['owner', 'admin']);

        $update = [];

        $name = trim($this->input->post('name'));
        if ($name !== '') {
            $update['name'] = $name;
            $update['slug'] = team_chat_make_slug($name);
        }

        $description = $this->input->post('description');
        if ($description !== null) {
            $update['description'] = trim($description);
        }

        if (empty($update)) {
            $this->_json_error('Nothing to update');
        }

        $update['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $conversation_id)->update('chat_conversations', $update);

        $this->_json_success(
            $this->Team_chat_conversation_model->get_conversation($conversation_id, $user_id)
        );
    }

    private function _api_archive_conversation($conversation_id = null)
    {
        $this->_only_post();

        $conversation_id = (int)$conversation_id;
        $user_id         = $this->_user_id();

        $this->_require_member($conversation_id, $user_id);
        $this->_require_role($conversation_id, $user_id, ['owner', 'admin']);

        $this->db->where('id', $conversation_id)
                 ->update('chat_conversations', ['is_archived' => 1, 'updated_at' => date('Y-m-d H:i:s')]);

        $this->_json_success(['archived' => true]);
    }

    // ─────────────────────────────────────────────────────────
    // Members
    // ─────────────────────────────────────────────────────────

    private function _api_members($conversation_id = null)
    {
        $this->_only_get();

        $conversation_id = (int)$conversation_id;
        $user_id         = $this->_user_id();

        $this->_require_member($conversation_id, $user_id);

        $this->_json_success(
            $this->Team_chat_conversation_model->get_members($conversation_id)
        );
    }

    private function _api_add_members()
    {
        $this->_only_post();

        $conversation_id = (int)$this->input->post('conversation_id');
        $user_ids        = $this->input->post('user_ids');
        $user_id         = $this->_user_id();

        $this->_require_member($conversation_id, $user_id);
        $this->_require_role($conversation_id, $user_id, ['owner', 'admin']);

        if (!is_array($user_ids) || empty($user_ids)) {
            $this->_json_error('No users provided');
        }

        $added = [];
        foreach ($user_ids as $uid) {
            $uid = (int)$uid;
            if ($uid && !$this->Team_chat_conversation_model->is_member($conversation_id, $uid)) {
                $this->Team_chat_conversation_model->add_member($conversation_id, $uid, $user_id);
                $added[] = $uid;
                $this->Team_chat_message_model->create_system_message($conversation_id, $user_id, 'member_added', ['user_id' => $uid]);
            }
        }

        $this->_json_success([
            'added'   => $added,
            'members' => $this->Team_chat_conversation_model->get_members($conversation_id),
        ]);
    }

    private function _api_remove_member()
    {
        $this->_only_post();

        $conversation_id = (int)$this->input->post('conversation_id');
        $target_user_id  = (int)$this->input->post('user_id');
        $user_id         = $this->_user_id();

        $this->_require_member($conversation_id, $user_id);

        if ($target_user_id !== $user_id) {
            $this->_require_role($conversation_id, $user_id, ['owner', 'admin']);
        }

        $this->Team_chat_conversation_model->remove_member($conversation_id, $target_user_id);
        $this->Team_chat_message_model->create_system_message($conversation_id, $user_id, 'member_removed', ['user_id' => $target_user_id]);

        $this->_json_success(['removed' => $target_user_id]);
    }

    private function _api_update_member_role()
    {
        $this->_only_post();

        $conversation_id = (int)$this->input->post('conversation_id');
        $target_user_id  = (int)$this->input->post('user_id');
        $role            = $this->input->post('role');
        $user_id         = $this->_user_id();

        if (!in_array($role, ['owner', 'admin', 'member'])) {
            $this->_json_error('Invalid role');
        }

        $this->_require_member($conversation_id, $user_id);
        $this->_require_role($conversation_id, $user_id, ['owner']);

        $this->db->where('conversation_id', $conversation_id)
                 ->where('user_id', $target_user_id)
                 ->update('chat_members', ['role' => $role]);

        $this->_json_success(['updated' => true, 'role' => $role]);
    }

    private function _api_mute_conversation()
    {
        $this->_only_post();

        $conversation_id = (int)$this->input->post('conversation_id');
        $mute            = (int)$this->input->post('mute');
        $user_id         = $this->_user_id();

        $this->_require_member($conversation_id, $user_id);

        $this->db->where('conversation_id', $conversation_id)
                 ->where('user_id', $user_id)
                 ->update('chat_members', ['is_muted' => ($mute ? 1 : 0)]);

        $this->_json_success(['muted' => (bool)$mute]);
    }

    // ─────────────────────────────────────────────────────────
    // Messages
    // ─────────────────────────────────────────────────────────

    private function _api_messages($conversation_id = null)
    {
        $this->_only_get();

        $conversation_id = (int)$conversation_id;
        $user_id         = $this->_user_id();

        $this->_require_member($conversation_id, $user_id);

        $before_id = (int)$this->input->get('before_id');
        $limit     = min((int)($this->input->get('limit') ?: 50), 100);

        $messages = $this->Team_chat_message_model->get_messages($conversation_id, $user_id, $before_id, $limit);

        if (!$before_id) {
            $this->Team_chat_conversation_model->mark_as_read($conversation_id, $user_id);
        }

        $this->_json_success($messages);
    }

    private function _api_send_message()
    {
        $this->_only_post();

        $conversation_id = (int)$this->input->post('conversation_id');
        $body            = trim($this->input->post('body'));
        $parent_id       = (int)$this->input->post('parent_id');
        $user_id         = $this->_user_id();
        $attachment_ids  = $this->_posted_int_array('attachment_ids');

        $this->_require_member($conversation_id, $user_id);

        if ($parent_id) {
            $parent = $this->db->select('conversation_id')
                               ->where('id', $parent_id)
                               ->where('is_deleted', 0)
                               ->get('chat_messages')
                               ->row_array();

            if (!$parent || (int)$parent['conversation_id'] !== $conversation_id) {
                $this->_json_error('Invalid thread parent');
            }
        }

        $conv = $this->db->select('is_read_only, is_archived')
                         ->where('id', $conversation_id)
                         ->get('chat_conversations')
                         ->row_array();

        if (!$conv)                          { $this->_json_error('Conversation not found'); }
        if ($conv['is_read_only'])           { $this->_json_error('This channel is read-only'); }
        if ($conv['is_archived'])            { $this->_json_error('This conversation is archived'); }
        if (empty($body) && !$attachment_ids) { $this->_json_error('Message body or attachment is required'); }

        $attachments = [];
        $message_type = 'text';
        foreach ($attachment_ids as $attachment_id) {
            $attachment = $this->Team_chat_attachment_model->get($attachment_id);
            if (!$attachment
                || (int)$attachment['conversation_id'] !== $conversation_id
                || (int)$attachment['uploader_id'] !== $user_id
                || !empty($attachment['message_id'])) {
                $this->_json_error('Invalid attachment');
            }
            $attachments[] = $attachment;
        }

        if ($attachments) {
            $message_type = 'file';
            foreach ($attachments as $attachment) {
                if (($attachment['category'] ?? '') === 'image') {
                    $message_type = 'image';
                    break;
                }
            }
        }

        $message_id = $this->Team_chat_message_model->send_message([
            'conversation_id' => $conversation_id,
            'sender_id'       => $user_id,
            'parent_id'       => $parent_id ?: null,
            'type'            => $message_type,
            'body'            => $body,
        ]);

        if (!$message_id) {
            $this->_json_error('Could not send message');
        }

        foreach ($attachments as $attachment) {
            $this->Team_chat_attachment_model->attach_to_message((int)$attachment['id'], $message_id);
        }

        $this->Team_chat_notification_model->process_mentions($body, $message_id, $conversation_id, $user_id);

        $this->db->where('id', $conversation_id)->update('chat_conversations', [
            'last_message_id'  => $message_id,
            'last_activity_at' => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->_json_success(
            $this->Team_chat_message_model->get_message($message_id, $user_id)
        );
    }

    private function _api_edit_message($message_id = null)
    {
        $this->_only_post();

        $message_id = (int)$message_id;
        $body       = trim($this->input->post('body'));
        $user_id    = $this->_user_id();

        if (empty($body)) { $this->_json_error('Body cannot be empty'); }

        $message = $this->db->where('id', $message_id)->where('is_deleted', 0)->get('chat_messages')->row_array();

        if (!$message) { $this->_json_error('Message not found'); }

        $this->_require_member((int)$message['conversation_id'], $user_id);

        if ((int)$message['sender_id'] !== $user_id && !staff_can('delete_message', 'team_chat')) {
            $this->_json_error('Permission denied', 403);
        }

        $this->db->where('id', $message_id)->update('chat_messages', [
            'body'       => $body,
            'is_edited'  => 1,
            'edited_at'  => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->_json_success(
            $this->Team_chat_message_model->get_message($message_id, $user_id)
        );
    }

    private function _api_delete_message($message_id = null)
    {
        $this->_only_post();

        $message_id = (int)$message_id;
        $user_id    = $this->_user_id();

        $message = $this->db->where('id', $message_id)->where('is_deleted', 0)->get('chat_messages')->row_array();

        if (!$message) { $this->_json_error('Message not found'); }

        $this->_require_member((int)$message['conversation_id'], $user_id);

        if ((int)$message['sender_id'] !== $user_id && !staff_can('delete_message', 'team_chat')) {
            $this->_json_error('Permission denied', 403);
        }

        $this->db->where('id', $message_id)->update('chat_messages', [
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $user_id,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->_json_success(['deleted' => true, 'message_id' => $message_id]);
    }

    private function _api_thread($parent_id = null)
    {
        $this->_only_get();

        $parent_id = (int)$parent_id;
        $user_id   = $this->_user_id();

        $parent = $this->db->where('id', $parent_id)->where('is_deleted', 0)->get('chat_messages')->row_array();

        if (!$parent) { $this->_json_error('Message not found'); }

        $this->_require_member((int)$parent['conversation_id'], $user_id);

        $this->_json_success([
            'parent'  => $this->Team_chat_message_model->get_message($parent_id, $user_id),
            'replies' => $this->Team_chat_message_model->get_thread_replies($parent_id, $user_id),
        ]);
    }

    private function _api_mark_read()
    {
        $this->_only_post();

        $conversation_id = (int)$this->input->post('conversation_id');
        $user_id         = $this->_user_id();

        $this->_require_member($conversation_id, $user_id);
        $this->Team_chat_conversation_model->mark_as_read($conversation_id, $user_id);

        $this->_json_success(['marked' => true]);
    }

    private function _api_search_messages()
    {
        $this->_only_get();

        $user_id         = $this->_user_id();
        $query           = trim($this->input->get('q'));
        $conversation_id = (int)$this->input->get('conversation_id');
        $limit           = min((int)($this->input->get('limit') ?: 30), 100);

        if (strlen($query) < 2) {
            $this->_json_error('Search term too short');
        }

        $this->_json_success(
            $this->Team_chat_message_model->search($query, $user_id, $conversation_id, $limit)
        );
    }

    // ─────────────────────────────────────────────────────────
    // Reactions
    // ─────────────────────────────────────────────────────────

    private function _api_toggle_reaction()
    {
        $this->_only_post();

        $message_id = (int)$this->input->post('message_id');
        $emoji      = trim($this->input->post('emoji'));
        $user_id    = $this->_user_id();

        if (!$message_id || !$this->_is_allowed_reaction($emoji)) {
            $this->_json_error('Invalid reaction');
        }

        $message = $this->db->where('id', $message_id)->where('is_deleted', 0)->get('chat_messages')->row_array();

        if (!$message) { $this->_json_error('Message not found'); }

        $this->_require_member((int)$message['conversation_id'], $user_id);

        $action    = $this->Team_chat_reaction_model->toggle($message_id, $user_id, $emoji);
        $reactions = $this->Team_chat_reaction_model->get_for_message($message_id, $user_id);

        $this->_json_success(['action' => $action, 'reactions' => $reactions]);
    }

    private function _api_reactions($message_id = null)
    {
        $this->_only_get();

        $message_id = (int)$message_id;
        $user_id    = $this->_user_id();

        $message = $this->db->where('id', $message_id)->get('chat_messages')->row_array();

        if (!$message) { $this->_json_error('Message not found'); }

        $this->_require_member((int)$message['conversation_id'], $user_id);

        $this->_json_success(
            $this->Team_chat_reaction_model->get_for_message($message_id, $user_id)
        );
    }

    // ─────────────────────────────────────────────────────────
    // Pins
    // ─────────────────────────────────────────────────────────

    private function _api_pin_message()
    {
        $this->_only_post();

        $message_id      = (int)$this->input->post('message_id');
        $conversation_id = (int)$this->input->post('conversation_id');
        $user_id         = $this->_user_id();

        $this->_require_member($conversation_id, $user_id);
        $this->_require_role($conversation_id, $user_id, ['owner', 'admin']);

        $message = $this->db->select('id')
                            ->where('id', $message_id)
                            ->where('conversation_id', $conversation_id)
                            ->where('is_deleted', 0)
                            ->get('chat_messages')
                            ->row_array();

        if (!$message) {
            $this->_json_error('Message not found in this conversation');
        }

        $exists = $this->db->where('message_id', $message_id)
                           ->where('conversation_id', $conversation_id)
                           ->count_all_results('chat_pins');

        if ($exists) { $this->_json_error('Message already pinned'); }

        $this->db->insert('chat_pins', [
            'conversation_id' => $conversation_id,
            'message_id'      => $message_id,
            'pinned_by'       => $user_id,
            'pinned_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->_json_success(
            $this->Team_chat_message_model->get_pinned($conversation_id)
        );
    }

    private function _api_unpin_message()
    {
        $this->_only_post();

        $message_id      = (int)$this->input->post('message_id');
        $conversation_id = (int)$this->input->post('conversation_id');
        $user_id         = $this->_user_id();

        $this->_require_member($conversation_id, $user_id);
        $this->_require_role($conversation_id, $user_id, ['owner', 'admin']);

        $this->db->where('message_id', $message_id)
                 ->where('conversation_id', $conversation_id)
                 ->delete('chat_pins');

        $this->_json_success(
            $this->Team_chat_message_model->get_pinned($conversation_id)
        );
    }

    private function _api_pinned_messages($conversation_id = null)
    {
        $this->_only_get();

        $conversation_id = (int)$conversation_id;
        $user_id         = $this->_user_id();

        $this->_require_member($conversation_id, $user_id);

        $this->_json_success(
            $this->Team_chat_message_model->get_pinned($conversation_id)
        );
    }

    // ─────────────────────────────────────────────────────────
    // File Upload
    // ─────────────────────────────────────────────────────────

    private function _api_upload()
    {
        $this->_only_post();

        $conversation_id = (int)$this->input->post('conversation_id');
        $user_id         = $this->_user_id();

        $this->_require_member($conversation_id, $user_id);

        if (empty($_FILES['file']['name'])) {
            $this->_json_error('No file uploaded');
        }

        $result = $this->Team_chat_attachment_model->upload($conversation_id, $user_id);

        if (!$result['success']) {
            $this->_json_error($result['error']);
        }

        $this->_json_success($result['attachment']);
    }

    private function _api_attach_upload()
    {
        $this->_only_post();

        $attachment_id = (int)$this->input->post('attachment_id');
        $message_id    = (int)$this->input->post('message_id');
        $user_id       = $this->_user_id();

        $attachment = $this->Team_chat_attachment_model->get($attachment_id);
        $message    = $this->db->where('id', $message_id)->where('is_deleted', 0)->get('chat_messages')->row_array();

        if (!$attachment || !$message) {
            $this->_json_error('Attachment or message not found');
        }

        if ((int)$attachment['uploader_id'] !== $user_id || (int)$message['sender_id'] !== $user_id) {
            $this->_json_error('Permission denied', 403);
        }

        if ((int)$attachment['conversation_id'] !== (int)$message['conversation_id']) {
            $this->_json_error('Attachment does not belong to this conversation');
        }

        $this->_require_member((int)$message['conversation_id'], $user_id);

        if (!$this->Team_chat_attachment_model->attach_to_message($attachment_id, $message_id)) {
            $this->_json_error('Could not attach file to message');
        }

        $this->_json_success($this->Team_chat_attachment_model->get($attachment_id));
    }

    private function _api_delete_attachment()
    {
        $this->_only_post();

        $attachment_id = (int)$this->input->post('attachment_id');
        $user_id       = $this->_user_id();

        $attachment = $this->db->where('id', $attachment_id)->where('is_deleted', 0)->get('chat_attachments')->row_array();

        if (!$attachment) { $this->_json_error('Attachment not found'); }

        if ((int)$attachment['uploader_id'] !== $user_id && !staff_can('delete_message', 'team_chat')) {
            $this->_json_error('Permission denied', 403);
        }

        $this->db->where('id', $attachment_id)->update('chat_attachments', ['is_deleted' => 1]);

        $this->_json_success(['deleted' => true]);
    }

    // ─────────────────────────────────────────────────────────
    // Users
    // ─────────────────────────────────────────────────────────

    private function _api_users($action = null)
    {
        switch ($action) {
            case 'search': $this->_api_search_users(); break;
            case 'online': $this->_api_online_users(); break;
            default:       $this->_json_error('User action not found', 404);
        }
    }

    private function _api_search_users()
    {
        $this->_only_get();

        $q       = trim((string)($this->input->get('q') ?? ''));
        $user_id = $this->_user_id();
        $limit   = min(max((int)($this->input->get('limit') ?: 20), 1), 50);

        $users = $this->_search_chat_users($q, $limit);
        $users = array_values(array_filter($users, function ($user) use ($user_id) {
            return (int)$user['id'] !== $user_id
                && strtolower((string)($user['user_role'] ?? '')) !== 'superadmin';
        }));

        $result = array_map([$this, '_normalize_chat_user'], $users);

        $this->_json_success(array_values($result));
    }

    private function _api_online_users()
    {
        $this->_only_get();

        $select = 'id, firstname, lastname, fullname, username, email, emp_id, user_role, profile_image';
        if ($this->db->field_exists('last_seen_at', 'users')) {
            $select .= ', last_seen_at';
        }
        if ($this->db->field_exists('is_online', 'users')) {
            $select .= ', is_online';
            $this->db->where('is_online', 1);
        }

        $users = $this->db->select($select)
                          ->from('users')
                          ->where('is_active', 1)
                          ->limit(50)
                          ->get()
                          ->result_array();

        $this->_json_success(array_map([$this, '_normalize_chat_user'], $users));
    }

    // ─────────────────────────────────────────────────────────
    // Unread
    // ─────────────────────────────────────────────────────────

    private function _api_unread_counts()
    {
        $this->_only_get();

        $user_id = $this->_user_id();

        $this->db->select('mem.conversation_id, COUNT(cm.id) AS unread_count', false);
        $this->db->from('chat_members mem');
        $this->db->join('chat_messages cm',
            'cm.conversation_id = mem.conversation_id
             AND cm.id > COALESCE(mem.last_read_message_id, 0)
             AND cm.sender_id != ' . $user_id . '
             AND cm.is_deleted = 0', 'left');
        $this->db->where('mem.user_id', $user_id);
        $this->db->where('mem.left_at IS NULL', null, false);
        $this->db->group_by('mem.conversation_id');

        $rows   = $this->db->get()->result_array();
        $counts = [];

        foreach ($rows as $row) {
            $counts[(int)$row['conversation_id']] = (int)$row['unread_count'];
        }

        $this->_json_success(['counts' => $counts, 'total' => array_sum($counts)]);
    }

    // ─────────────────────────────────────────────────────────
    // Typing
    // ─────────────────────────────────────────────────────────

    private function _api_typing()
    {
        $this->_only_post();

        $conversation_id = (int)$this->input->post('conversation_id');
        $is_typing       = (int)$this->input->post('is_typing');
        $user_id         = $this->_user_id();

        $this->_require_member($conversation_id, $user_id);

        $this->db->where('id', $user_id)->update('users', [
            'last_activity_at' => date('Y-m-d H:i:s'),
        ]);

        $this->_json_success(['typing' => (bool)$is_typing]);
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    private function _can_access_conversation($conversation_id, $user_id)
    {
        return $this->Team_chat_conversation_model->is_member($conversation_id, $user_id)
            || staff_can('view_all', 'team_chat');
    }

    private function _require_member($conversation_id, $user_id)
    {
        if (!$this->_can_access_conversation($conversation_id, $user_id)) {
            $this->_json_error('You are not a member of this conversation', 403);
        }
    }

    private function _require_role($conversation_id, $user_id, array $roles)
    {
        if (staff_can('manage_channel', 'team_chat')) {
            return;
        }

        $row = $this->db->select('role')
                        ->where('conversation_id', $conversation_id)
                        ->where('user_id', $user_id)
                        ->where('left_at IS NULL', null, false)
                        ->get('chat_members')
                        ->row_array();

        if (!$row || !in_array($row['role'], $roles)) {
            $this->_json_error('Insufficient role for this action', 403);
        }
    }

    private function _is_allowed_reaction($emoji)
    {
        static $allowed = [
            '😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🥸','🤩','🥳',
            '👍','👎','👌','✌️','🤞','🤟','🤘','🤙','👈','👉','👆','👇','☝️','👋','🤚','🖐️','✋','🖖','💪','🤜','🤛','👊','✊','🙌','👐','🤲','🤝','🙏','💅','🤳',
            '❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❤️‍🔥','❤️‍🩹','💕','💞','💓','💗','💖','💘','💝','💟','♥️','🫀',
            '🎉','🎊','🎈','🎀','🎁','🎂','🎆','🎇','🧨','🎏','🎐','🎑','🎃','🎄','🎋','🎍','🎎','🎠','🎡','🎢','🎪','🤹','🎭','🎬','🎤','🎧','🎼','🎹','🎸','🎺',
            '🚀','✅','❌','⚡','🔥','💡','💰','📌','📎','🔑','🔒','🔓','📱','💻','🖥️','🖨️','⌨️','🖱️','💾','📀','📷','📸','📹','🎥','📡','☎️','📞','📺','📻','⏰','⏱️',
            '🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🙈','🙉','🙊','🐔','🐧','🐦','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝',
        ];

        return in_array(trim((string)$emoji), $allowed, true);
    }

    private function _search_chat_users($query, $limit)
    {
        $query = trim((string)$query);
        $limit = min(max((int)$limit, 1), 50);

        if ($query === '') {
            return method_exists($this->User_model, 'search_for_dropdown')
                ? $this->User_model->search_for_dropdown(null, true, $limit)
                : $this->db->select('id, firstname, lastname, fullname, username, email, user_role, is_active, emp_id, profile_image')
                           ->from('users')
                           ->where('is_active', 1)
                           ->order_by('firstname', 'ASC')
                           ->order_by('lastname', 'ASC')
                           ->limit($limit)
                           ->get()
                           ->result_array();
        }

        if (method_exists($this->User_model, 'search_for_dropdown')) {
            return $this->User_model->search_for_dropdown($query, true, $limit);
        }

        return $this->db->select('id, firstname, lastname, fullname, username, email, user_role, is_active, emp_id, profile_image')
                        ->from('users')
                        ->where('is_active', 1)
                        ->group_start()
                            ->like('fullname', $query)
                            ->or_like('firstname', $query)
                            ->or_like('lastname', $query)
                            ->or_like('username', $query)
                            ->or_like('email', $query)
                            ->or_like('emp_id', $query)
                        ->group_end()
                        ->order_by('firstname', 'ASC')
                        ->order_by('lastname', 'ASC')
                        ->limit($limit)
                        ->get()
                        ->result_array();
    }

    private function _normalize_chat_user(array $user)
    {
        $name = trim((string)($user['fullname'] ?? ''));
        if ($name === '') {
            $name = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
        }
        if ($name === '') {
            $name = $user['username'] ?? $user['email'] ?? ('User #' . (int)$user['id']);
        }

        $profile_image = !empty($user['profile_image']) ? ltrim((string)$user['profile_image'], '/') : null;

        return [
            'id'            => (int)$user['id'],
            'fullname'      => $name,
            'firstname'     => $user['firstname'] ?? '',
            'lastname'      => $user['lastname']  ?? '',
            'username'      => $user['username']  ?? '',
            'email'         => $user['email']     ?? '',
            'emp_id'        => $user['emp_id']    ?? '',
            'user_role'     => $user['user_role'] ?? '',
            'profile_image' => $profile_image,
            'avatar_url'    => $this->_chat_avatar_url($profile_image),
            'is_online'     => !empty($user['is_online']),
            'last_seen_at'  => $user['last_seen_at'] ?? null,
        ];
    }

    private function _chat_avatar_url($profile_image)
    {
        $profile_image = trim((string)$profile_image);
        if ($profile_image === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $profile_image)) {
            return $profile_image;
        }

        return base_url('uploads/users/profile/' . ltrim($profile_image, '/'));
    }

    private function _posted_int_array($key)
    {
        $values = $this->input->post($key);
        if ($values === null) {
            $values = $this->input->post($key . '[]');
        }

        if ($values === null || $values === '') {
            return [];
        }

        if (!is_array($values)) {
            $values = [$values];
        }

        return array_values(array_unique(array_filter(array_map('intval', $values))));
    }

    private function _json_success($data = [], $message = 'OK')
    {
        $this->output
             ->set_content_type('application/json')
             ->set_status_header(200)
             ->set_output(json_encode(['success' => true, 'message' => $message, 'data' => $data]));
        exit;
    }

    private function _json_error($message = 'Error', $status = 400)
    {
        $this->output
             ->set_content_type('application/json')
             ->set_status_header($status)
             ->set_output(json_encode(['success' => false, 'message' => $message, 'data' => null]));
        exit;
    }

    private function _only_get()
    {
        if ($this->input->server('REQUEST_METHOD') !== 'GET') {
            $this->_json_error('Method not allowed', 405);
        }
    }

    private function _only_post()
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->_json_error('Method not allowed', 405);
        }
    }
}