<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Utilities extends App_Controller
{
    private $allowed_file_types = ['text/csv', 'application/vnd.ms-excel', 'text/plain'];
    private $max_file_size = 5242880; // 5MB

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('utilities');
        $this->load->model('utilities_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        if (! staff_can('view_global','utilities')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    
        // Tables list for dropdowns
        $tables = $this->utilities_model->get_all_tables();
    
        // DB stats (size, table count)
        $dbStats = [
            'table_count' => 0,
            'db_size_mb'  => 0.0,
        ];
        try {
            $q = $this->db->query('SHOW TABLE STATUS');
            $sizeBytes = 0;
            foreach ($q->result_array() as $row) {
                $sizeBytes += (int)($row['Data_length'] ?? 0) + (int)($row['Index_length'] ?? 0);
            }
            $dbStats['table_count'] = $q->num_rows();
            $dbStats['db_size_mb']  = round($sizeBytes / 1024 / 1024, 2);
        } catch (Throwable $e) {
            log_message('error', 'Utilities index DB stats error: ' . $e->getMessage());
        }
    
        // Backup summary (latest file)
        $backup_dir = FCPATH . 'backup/';
        if (!is_dir($backup_dir)) {
            @mkdir($backup_dir, 0777, true);
        }
        $latest_backup = null;
        $files = @array_diff(@scandir($backup_dir), ['.', '..']) ?: [];
        if (!empty($files)) {
            $latest = null;
            foreach ($files as $f) {
                $p = $backup_dir . $f;
                if (!is_file($p)) continue;
                if ($latest === null || filemtime($p) > filemtime($latest)) {
                    $latest = $p;
                }
            }
            if ($latest) {
                $latest_backup = [
                    'name' => basename($latest),
                    'size' => round(filesize($latest) / 1024, 2), // KB
                    'date' => date("Y-m-d H:i:s", filemtime($latest)),
                ];
            }
        }
    
        $env = [
            'php_version' => PHP_VERSION,
            'ci_version'  => defined('CI_VERSION') ? CI_VERSION : '3.x',
            'base_url'    => base_url(),
            'db_driver'   => $this->db->dbdriver ?? 'mysqli',
        ];
    
        $layout_data = [
            'page_title' => 'Utilities',
            'subview'    => 'utilities/index',
            'view_data'  => [
                'tables'        => $tables,
                'dbStats'       => $dbStats,
                'latest_backup' => $latest_backup,
                'env'           => $env,
            ],
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    /**
     * Export Data (Any Table)
     */
    public function export()
    {

        if (! staff_can('view_global','utilities')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        $tables = $this->utilities_model->get_all_tables();
        $selected_table = $this->input->get('table');
        $rows = [];
        $columns = [];

        if ($selected_table) {
            if (!$this->utilities_model->is_table_allowed($selected_table)) {
                set_alert('danger', 'This table is not available for export.');
                redirect(base_url('utilities/export'));
            }

            $rows = $this->utilities_model->get_all_rows($selected_table);
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
            } else {
                $columns = $this->db->list_fields($selected_table);
            }
        }

        $layout_data = [
            'page_title' => 'Export Data',
            'subview'    => 'utilities/export',
            'view_data'  => [
                'tables'         => $tables,
                'selected_table' => $selected_table,
                'rows'           => $rows,
                'columns'        => $columns,
            ],
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    /**
     * Import Data (Any Table)
     */
    public function import()
    {

        if (! staff_can('view_global','utilities')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        log_message('debug', 'Import handler triggered.');
        $tables = $this->utilities_model->get_all_tables();
        $status = null;
        $status_type = 'info';

        if ($this->input->method() === 'post') {
            log_message('debug', 'POST data: ' . print_r($this->input->post(), true));
            log_message('debug', 'FILES: ' . print_r($_FILES, true));
            $this->form_validation->set_rules('table', 'Table', 'required');
            
            if (empty($_FILES['import_file']['tmp_name'])) {
                $this->form_validation->set_rules('import_file', 'CSV File', 'required');
            }

            if ($this->form_validation->run()) {
                $table = $this->input->post('table');
                
                if (!$this->utilities_model->is_table_allowed($table)) {
                    set_alert('danger', 'This table is not available for import.');
                    redirect(base_url('utilities/import'));
                }

                $file_info = $_FILES['import_file'];
                
                // Validate file type
                if (!in_array($file_info['type'], $this->allowed_file_types)) {
                    $status = 'Invalid file type. Please upload a CSV file.';
                    $status_type = 'danger';
                } 
                // Validate file size
                elseif ($file_info['size'] > $this->max_file_size) {
                    $status = 'File size exceeds maximum limit of 5MB.';
                    $status_type = 'danger';
                } else {
                    $import_result = $this->utilities_model->import_from_csv($table, $file_info['tmp_name']);
                    
                    if ($import_result['success']) {
                        $status = "Successfully imported {$import_result['imported']} rows into {$table}.";
                        if (!empty($import_result['errors'])) {
                            $status .= '<br>' . implode('<br>', $import_result['errors']);
                            $status_type = 'warning';
                        } else {
                            $status_type = 'success';
                        }
                    } else {
                        $status = $import_result['message'];
                        $status_type = 'danger';
                    }
                }
            } else {
                $status = validation_errors('<div>', '</div>');
                $status_type = 'danger';
            }
        }

        $layout_data = [
            'page_title' => 'Import Data',
            'subview'    => 'utilities/import',
            'view_data'  => [
                'tables' => $tables,
                'status' => $status,
                'status_type' => $status_type,
            ],
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    /**
     * Download as CSV
     */
    public function download_csv()
    {

        if (! staff_can('view_global','utilities')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        $table = $this->input->get('table');
        if (!$table || !$this->utilities_model->is_table_allowed($table)) {
            show_error('This table is not available for export.', 403);
        }

        $sample = $this->input->get('sample');
        $rows = $this->utilities_model->get_all_rows($table);
        
        // Generate filename
        $filename = $table . '_' . date('Ymd_His') . '.csv';
        if ($sample) {
            $filename = $table . '_sample.csv';
        }

        // Set headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Output BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        // Get headers
        if (!empty($rows)) {
            $headers = array_keys($rows[0]);
        } else {
            $headers = $this->db->list_fields($table);
        }
        
        fputcsv($output, $headers);
        
        // Output data if not sample
        if (!$sample && !empty($rows)) {
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }



/**
 * Reports: Show categorized report list, open filter modal
 */
public function reports()
{

    if (! staff_can('view_global','utilities')) {
        $html = $this->load->view('errors/html/error_403', [], true);
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    }
        
    $tables = $this->utilities_model->get_all_tables();
    $report_categories = [];

    foreach ($tables as $table) {
        $label = ucwords(str_replace('_', ' ', $table));
        $report_categories[$label][] = [
            'table' => $table,
            'name'  => $label . ' Report', // or "All {$label}"
            'slug'  => $table . '_report'
        ];
    }

    $layout_data = [
        'page_title' => 'Reports',
        'subview'    => 'utilities/reports',
        'view_data'  => [
            'tables'           => $tables,
            'report_categories'=> $report_categories,
        ],
    ];
    $this->load->view('layouts/master', $layout_data);
}


public function backups()
{

    if (! staff_can('view_global','utilities')) {
        $html = $this->load->view('errors/html/error_403', [], true);
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    }    

    $backup_dir = FCPATH . 'backup/';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }

    $files = array_diff(scandir($backup_dir), ['.', '..']);
    $backups = [];

    foreach ($files as $file) {
        $file_path = $backup_dir . $file;
        if (is_file($file_path)) {
            $backups[] = [
                'name' => $file,
                'size' => round(filesize($file_path) / 1024, 2), // KB
                'date' => date("Y-m-d H:i:s", filemtime($file_path)),
            ];
        }
    }

    $layout_data = [
        'page_title' => 'Database Backups',
        'subview'    => 'utilities/backups',
        'view_data'  => ['backups' => $backups],
    ];
    $this->load->view('layouts/master', $layout_data);
}

public function generate_backup()
{

    if (! staff_can('view_global','utilities')) {
        $html = $this->load->view('errors/html/error_403', [], true);
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    }
        
    $this->load->dbutil();

    $prefs = [
        'format'      => 'zip',
        'filename'    => 'db_backup_' . date('Ymd_His') . '.sql'
    ];

    $backup =& $this->dbutil->backup($prefs);
    $backup_name = 'backup_' . date('Ymd_His') . '.zip';
    $backup_path = FCPATH . 'backup/' . $backup_name;

    $this->load->helper('file');
    write_file($backup_path, $backup);

    set_alert('success', 'Backup created: ' . $backup_name);
    redirect('utilities/backups');
}

public function delete_backup($filename)
{

    if (! staff_can('view_global','utilities')) {
        $html = $this->load->view('errors/html/error_403', [], true);
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    }
        
    $file = FCPATH . 'backup/' . basename($filename);
    if (file_exists($file)) {
        unlink($file);
        set_alert('success', 'Backup deleted.');
    } else {
        set_alert('danger', 'Backup file not found.');
    }
    redirect('utilities/backups');
}


    /**
     * Show raw CREATE TABLE SQL for any table
     * Usage: /utilities/show_create_table/users
     */
    public function show_create_table($table = null)
    {
        if (! staff_can('view_global','utilities')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    
        // Also accept ?table=xyz
        if (!$table) {
            $table = $this->input->get('table', true);
        }
    
        if (!$table) {
            set_alert('warning', 'Please select a table first.');
            redirect('utilities'); // back to utilities index
            return;
        }
    
        // Validate table against allowed list
        if (! $this->utilities_model->is_table_allowed($table)) {
            show_error("Table '{$table}' is not available.", 403);
        }
    
        // Now safe to query
        $query = $this->db->query("SHOW CREATE TABLE `{$table}`");
        if ($query->num_rows() === 0) {
            show_error("Table '{$table}' not found in the database.", 404);
        }
    
        $result = $query->row_array();
    
        header("Content-Type: text/plain; charset=utf-8");
        echo "-- CREATE TABLE for `{$table}`:\n\n";
        echo $result['Create Table'];
        exit;
    }



    public function table($table = null)
    {
        if (! staff_can('view_global','utilities')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
    
        // Accept URI segment or ?table=...
        if (!$table) { $table = $this->input->get('table', true); }
        if (!$table) {
            set_alert('warning', 'Please select a table.');
            redirect('utilities'); return;
        }
    
        // Guard against non-allowed tables
        if (! $this->utilities_model->is_table_allowed($table)) {
            show_error("Table '{$table}' is not available.", 403);
        }
    
        $dbName = $this->db->database;
    
        // --- SHOW CREATE TABLE
        $createSql = '';
        $qCreate = $this->db->query("SHOW CREATE TABLE `{$table}`");
        if ($qCreate->num_rows() > 0) {
            $row = $qCreate->row_array();
            // Column name can be 'Create Table' or 'Create View' depending on object
            $createSql = $row['Create Table'] ?? ($row['Create View'] ?? '');
        }
    
        // --- Table status (engine, rows, sizes, dates, collation, comment)
        $status = [];
        $qStatus = $this->db->query("SHOW TABLE STATUS LIKE " . $this->db->escape($table));
        if ($qStatus->num_rows() > 0) {
            $status = $qStatus->row_array();
        }
    
        // --- Columns
        $columns = $this->db->query("
            SELECT
              ORDINAL_POSITION,
              COLUMN_NAME,
              COLUMN_TYPE,
              IS_NULLABLE,
              COLUMN_DEFAULT,
              EXTRA,
              COLUMN_KEY,
              CHARACTER_SET_NAME,
              COLLATION_NAME,
              COLUMN_COMMENT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ", [$dbName, $table])->result_array();
    
        // --- Indexes
        $indexes = $this->db->query("SHOW INDEX FROM `{$table}`")->result_array();
    
        // --- Foreign keys (if any)
        $foreignKeys = $this->db->query("
            SELECT
              kcu.CONSTRAINT_NAME,
              kcu.COLUMN_NAME,
              kcu.REFERENCED_TABLE_NAME,
              kcu.REFERENCED_COLUMN_NAME,
              rc.UPDATE_RULE,
              rc.DELETE_RULE
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
            JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
              ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
             AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
            WHERE kcu.TABLE_SCHEMA = ? AND kcu.TABLE_NAME = ? AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION
        ", [$dbName, $table])->result_array();
    
        // --- Triggers
        $triggers = $this->db->query("SHOW TRIGGERS WHERE `Table` = " . $this->db->escape($table))->result_array();
    
        // --- Lightweight “history”: inspect latest backup ZIPs in FCPATH/backup/
        $history = $this->extract_create_history_from_backups($table, 5); // up to last 5 backups
    
        $layout_data = [
            'page_title' => 'Table: ' . $table,
            'subview'    => 'utilities/show_create_table/table_details',
            'view_data'  => [
                'table'       => $table,
                'createSql'   => $createSql,
                'status'      => $status,
                'columns'     => $columns,
                'indexes'     => $indexes,
                'foreignKeys' => $foreignKeys,
                'triggers'    => $triggers,
                'history'     => $history,
            ],
        ];
        $this->load->view('layouts/master', $layout_data);
    }
    
    /**
     * Download the current CREATE TABLE as a .sql file (inside layout page we’ll link to this).
     */
    public function download_create_sql($table = null)
    {
        if (! staff_can('view_global','utilities')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
    
        if (!$table) { $table = $this->input->get('table', true); }
        if (!$table) { show_error('Table name required', 400); }
    
        if (! $this->utilities_model->is_table_allowed($table)) {
            show_error("Table '{$table}' is not available.", 403);
        }
    
        $q = $this->db->query("SHOW CREATE TABLE `{$table}`");
        if ($q->num_rows() === 0) {
            show_error("Table '{$table}' not found.", 404);
        }
        $row = $q->row_array();
        $sql = $row['Create Table'] ?? ($row['Create View'] ?? '');
        $filename = "{$table}_" . date('Ymd_His') . ".sql";
    
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        echo $sql . ";\n";
        exit;
    }
    
    /**
     * INTERNAL: Scan latest backup zips in FCPATH/backup, and pull the CREATE TABLE snippet for this table.
     * Returns array of ['backup' => name, 'date' => Y-m-d H:i:s, 'snippet' => 'CREATE TABLE ... ;'].
     */
    private function extract_create_history_from_backups(string $table, int $max = 5): array
    {
        $out = [];
        $dir = FCPATH . 'backup/';
        if (!is_dir($dir)) return $out;
    
        $files = array_values(array_filter(
            array_diff(scandir($dir), ['.','..']),
            fn($f) => is_file($dir.$f) && preg_match('/\.zip$/i', $f)
        ));
        if (empty($files)) return $out;
    
        // newest first
        usort($files, fn($a,$b) => filemtime($dir.$b) <=> filemtime($dir.$a));
        $files = array_slice($files, 0, $max);
    
        foreach ($files as $zipName) {
            $zipPath = $dir . $zipName;
            $snippet = '';
            try {
                $zip = new ZipArchive();
                if ($zip->open($zipPath) === TRUE) {
                    // dbutil usually packs a single .sql file
                    for ($i=0; $i<$zip->numFiles; $i++) {
                        $stat = $zip->statIndex($i);
                        $entry = $stat['name'];
                        if (!preg_match('/\.sql$/i', $entry)) continue;
                        $sql = $zip->getFromIndex($i);
                        if (!$sql) continue;
    
                        // naive extract of CREATE TABLE `table` ... ;
                        $pattern = '/CREATE\s+TABLE\s+`'.preg_quote($table,'/').'`\s*\(.*?\)\s*ENGINE=.*?;/is';
                        if (preg_match($pattern, $sql, $m)) {
                            $snippet = $m[0];
                            break;
                        }
                    }
                    $zip->close();
                }
            } catch (Throwable $e) {
                log_message('error', 'Backup parse failed: '.$zipName.' | '.$e->getMessage());
            }
    
            if ($snippet) {
                $out[] = [
                    'backup'  => $zipName,
                    'date'    => date('Y-m-d H:i:s', filemtime($zipPath)),
                    'snippet' => $snippet,
                ];
            }
        }
        return $out;
    }

}