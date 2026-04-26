<?php defined('BASEPATH') or exit('No direct script access allowed');

class Signoff_merge_fields implements MergeFieldProvider
{
    public function name(): string 
    { 
        return 'signoff'; 
    }

    public function fields(): array
    {
        return [
            ['name' => 'Signoff ID', 'key' => '{signoff.id}', 'available' => ['signoff', 'other']],
            ['name' => 'Signoff Title', 'key' => '{signoff.title}', 'available' => ['signoff', 'other']],
            ['name' => 'Signoff Status', 'key' => '{signoff.status}', 'available' => ['signoff', 'other']],
            ['name' => 'Signoff Date', 'key' => '{signoff.date}', 'available' => ['signoff', 'other']],
        ];
    }

    public function format(array $ctx): array
    {
        $CI = &get_instance();
        $signoff_id = (int)($ctx['signoff_id'] ?? 0);

        if ($signoff_id <= 0) {
            return [];
        }

        // Replace with your actual signoff table and columns
        $row = $CI->db->select('id, title, status, created_at')
                      ->from('signoffs')
                      ->where('id', $signoff_id)
                      ->limit(1)
                      ->get()
                      ->row_array();

        if (!$row) {
            return [];
        }

        return [
            '{signoff.id}' => (string)($row['id'] ?? ''),
            '{signoff.title}' => (string)($row['title'] ?? ''),
            '{signoff.status}' => (string)($row['status'] ?? ''),
            '{signoff.date}' => (string)($row['created_at'] ?? ''),
        ];
    }
}