<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Team_chat_socket_server
{
    public function verify_token($user_id, $token)
    {
        return hash_equals(team_chat_ws_token((int)$user_id), (string)$token);
    }

    public function run()
    {
        log_message('debug', 'Team chat socket server is not configured in this deployment.');
        return false;
    }
}
