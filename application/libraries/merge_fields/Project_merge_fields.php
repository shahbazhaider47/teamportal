<?php defined('BASEPATH') or exit('No direct script access allowed');

class Project_merge_fields implements MergeFieldProvider
{
    public function name(): string 
    { 
        return 'project'; 
    }

    public function fields(): array
    {
        return [
            ['name' => 'Project ID', 'key' => '{project.id}', 'available' => ['project', 'other']],
            ['name' => 'Project Name', 'key' => '{project.name}', 'available' => ['project', 'other']],
            ['name' => 'Project Description', 'key' => '{project.description}', 'available' => ['project', 'other']],
            ['name' => 'Project Status', 'key' => '{project.status}', 'available' => ['project', 'other']],
            ['name' => 'Project Start Date', 'key' => '{project.start_date}', 'available' => ['project', 'other']],
            ['name' => 'Project End Date', 'key' => '{project.end_date}', 'available' => ['project', 'other']],
        ];
    }

    public function format(array $ctx): array
    {
        $CI = &get_instance();
        $project_id = (int)($ctx['project_id'] ?? 0);

        if ($project_id <= 0) {
            return [];
        }

        // Replace with your actual project table and columns
        $row = $CI->db->select('id, name, description, status, start_date, end_date')
                      ->from('projects')
                      ->where('id', $project_id)
                      ->limit(1)
                      ->get()
                      ->row_array();

        if (!$row) {
            return [];
        }

        return [
            '{project.id}' => (string)($row['id'] ?? ''),
            '{project.name}' => (string)($row['name'] ?? ''),
            '{project.description}' => (string)($row['description'] ?? ''),
            '{project.status}' => (string)($row['status'] ?? ''),
            '{project.start_date}' => (string)($row['start_date'] ?? ''),
            '{project.end_date}' => (string)($row['end_date'] ?? ''),
        ];
    }
}