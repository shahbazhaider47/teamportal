<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Team_chat_push_service
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function broadcast($event, array $payload = [], $conversation_id = null)
    {
        log_message('debug', 'Team chat event: ' . $event . ' conversation=' . (int)$conversation_id);
        return true;
    }

    public function message_created(array $message)
    {
        return $this->broadcast('message.created', ['message' => $message], $message['conversation_id'] ?? null);
    }

    public function conversation_updated(array $conversation)
    {
        return $this->broadcast('conversation.updated', ['conversation' => $conversation], $conversation['id'] ?? null);
    }
}
