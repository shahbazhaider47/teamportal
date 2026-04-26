<?php defined('BASEPATH') or exit('No direct script access allowed');

class User_merge_fields implements MergeFieldProvider
{
    public function name(): string 
    { 
        return 'user'; 
    }

    public function fields(): array
    {
        return [
            ['name' => 'User ID',      'key' => '{user.id}',       'available' => ['user', 'other']],
            ['name' => 'Full Name',    'key' => '{user.fullname}', 'available' => ['user', 'other']],
            ['name' => 'First Name',   'key' => '{user.firstname}','available' => ['user', 'other']],
            ['name' => 'Last Name',    'key' => '{user.lastname}', 'available' => ['user', 'other']],
            ['name' => 'Email',        'key' => '{user.email}',    'available' => ['user', 'other']],
            ['name' => 'Phone',        'key' => '{user.phone}',    'available' => ['user', 'other']],
        ];
    }

    public function format(array $ctx): array
    {
        $CI = &get_instance();
        $user_id = (int)($ctx['user_id'] ?? 0);

        if ($user_id <= 0) {
            return [];
        }

        $row = $CI->db->select('id, firstname, lastname, email, emp_phone as phone')
                      ->from('users')
                      ->where('id', $user_id)
                      ->limit(1)
                      ->get()
                      ->row_array();

        if (!$row) {
            return [];
        }

        $fn = trim((string)($row['firstname'] ?? ''));
        $ln = trim((string)($row['lastname'] ?? ''));
        $full = trim(($fn . ' ' . $ln));

        return [
            '{user.id}'       => (string)($row['id'] ?? ''),
            '{user.fullname}' => $full,
            '{user.firstname}'=> $fn,
            '{user.lastname}' => $ln,
            '{user.email}'    => (string)($row['email'] ?? ''),
            '{user.phone}'    => (string)($row['phone'] ?? ''),
        ];
    }
}