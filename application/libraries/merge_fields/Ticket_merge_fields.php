<?php defined('BASEPATH') or exit('No direct script access allowed');

class Ticket_merge_fields implements MergeFieldProvider
{
    public function name(): string
    {
        return 'ticket';
    }

    public function fields(): array
    {
        return [
            ['name' => 'Ticket ID',      'key' => '{ticket.id}',         'available' => ['ticket','other']],
            ['name' => 'Ticket Number',  'key' => '{ticket.number}',     'available' => ['ticket','other']],
            ['name' => 'Subject',        'key' => '{ticket.subject}',    'available' => ['ticket','other']],
            ['name' => 'Status',         'key' => '{ticket.status}',     'available' => ['ticket','other']],
            ['name' => 'Department',     'key' => '{ticket.department}', 'available' => ['ticket','other']],
            ['name' => 'Created At',     'key' => '{ticket.created_at}', 'available' => ['ticket','other']],
            ['name' => 'Updated At',     'key' => '{ticket.updated_at}', 'available' => ['ticket','other']],
            ['name' => 'Ticket URL',     'key' => '{ticket.url}',        'available' => ['ticket','other']],
        ];
    }

    public function format(array $ctx): array
    {
        $CI =& get_instance();

        $ticketId = (int)($ctx['ticket_id'] ?? 0);
        if ($ticketId <= 0) {
            return [];
        }

        // Pull what we need based on your schema (code exists, public_number does not)
        $t = $CI->db->select('t.id, t.code, t.subject, t.status, t.created_at, t.updated_at, t.department_id, d.name AS department_name')
                    ->from('support_tickets t')
                    ->join('departments d', 'd.id = t.department_id', 'left')
                    ->where('t.id', $ticketId)
                    ->limit(1)
                    ->get()
                    ->row_array() ?: [];

        // Ticket number preference: code → #ID
        $ticketNumber = '';
        if (!empty($t['code'])) {
            $ticketNumber = (string)$t['code'];
        } elseif (!empty($t['id'])) {
            $ticketNumber = '#' . (string)$t['id'];
        }

        return [
            '{ticket.id}'         => isset($t['id']) ? (string)$t['id'] : '',
            '{ticket.number}'     => $ticketNumber,
            '{ticket.subject}'    => (string)($t['subject'] ?? ''),
            '{ticket.status}'     => (string)($t['status'] ?? ''),
            '{ticket.department}' => (string)($t['department_name'] ?? ''),
            '{ticket.created_at}' => (string)($t['created_at'] ?? ''),
            '{ticket.updated_at}' => (string)($t['updated_at'] ?? ''),
            '{ticket.url}'        => site_url('support/view/' . (int)($t['id'] ?? 0)),
        ];
    }
}
