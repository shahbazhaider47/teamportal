<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Utilities_model extends CI_Model
{
    private $skipped_tables = [
        'announcement_dismissals',
        'announcement_recipients',
        'email_queue',
        'login_attempts',
        'notifications',
        'tblmodules',
        'user_dashboard_order',
    ];

    /**
     * Get all tables that are allowed for import/export
     * 
     * @return array List of table names
     */
    public function get_all_tables()
    {
        $all_tables = $this->db->list_tables();
        return array_filter($all_tables, [$this, 'is_table_allowed']);
    }

    /**
     * Check if a table is allowed for import/export
     * 
     * @param string $table Table name
     * @return bool Whether table is allowed
     */
    public function is_table_allowed($table)
    {
        return !in_array($table, $this->skipped_tables);
    }

    /**
     * Get all rows from a table
     * 
     * @param string $table Table name
     * @return array Table rows
     */
    public function get_all_rows($table)
    {
        if (!$this->is_table_allowed($table)) {
            return [];
        }

        return $this->db->get($table)->result_array();
    }

    /**
     * Import data from CSV file into a table
     * 
     * @param string $table Table name
     * @param string $filepath Path to CSV file
     * @return array Import result with status and statistics
     */
public function import_from_csv($table, $filepath)
{
    if (!$this->is_table_allowed($table)) {
        return ['success' => false, 'message' => 'This table is not available for import.'];
    }

    $handle = fopen($filepath, 'r');
    if (!$handle) {
        return ['success' => false, 'message' => 'Failed to open CSV file.'];
    }

    $table_columns = $this->db->list_fields($table);
    $primary_key   = $this->db->primary($table);

    $header = fgetcsv($handle);
    if ($header === false) {
        fclose($handle);
        return ['success' => false, 'message' => 'Empty or invalid CSV file.'];
    }

    // Strip UTF-8 BOM
    if (!empty($header) && substr($header[0], 0, 3) === "\xEF\xBB\xBF") {
        $header[0] = substr($header[0], 3);
    }

    $header_errors = validate_csv_row(array_flip($header), $table_columns);
    if (!empty($header_errors)) {
        fclose($handle);
        return [
            'success' => false,
            'message' => 'CSV header does not match table columns: ' . implode(', ', $header_errors),
        ];
    }

    $date_columns = $this->get_date_columns($table);

    // Build FK map once for this table so we can validate per row
    $fk_map = $this->get_foreign_key_map($table);

    $imported  = 0;
    $updated   = 0;
    $skipped   = 0;
    $errors    = [];
    $row_num   = 1; // header was row 1

    $this->db->trans_start();

    while (($row = fgetcsv($handle)) !== false) {
        $row_num++;

        if (count($row) !== count($header)) {
            $errors[] = "Row {$row_num}: skipped — column count mismatch.";
            $skipped++;
            continue;
        }

        $data = array_combine($header, $row);

        // Remove columns not in table
        foreach ($data as $key => $value) {
            if (!in_array($key, $table_columns)) {
                unset($data[$key]);
            }
        }

        // Auto-fill timestamps
        $now = date('Y-m-d H:i:s');
        if (in_array('created_at', $table_columns) && empty($data['created_at'])) {
            $data['created_at'] = $now;
        }
        if (in_array('updated_at', $table_columns) && empty($data['updated_at'])) {
            $data['updated_at'] = $now;
        }

        // Normalise NULLs and empty strings
        foreach ($data as $key => $value) {
            if (is_string($value) && strtoupper(trim($value)) === 'NULL') {
                $data[$key] = null;
                continue;
            }
            if ($value === '') {
                $data[$key] = null;
                continue;
            }
            if ($this->is_numeric_column($table, $key) && $data[$key] !== null) {
                if (!is_numeric($data[$key])) {
                    $data[$key] = null;
                }
            }
        }

        // Normalise dates
        foreach ($date_columns as $col => $type) {
            if (array_key_exists($col, $data)) {
                $data[$col] = $this->normalize_datetime($data[$col], $type);
            }
        }

        // ── Foreign key pre-flight check ─────────────────────────
        $fk_fail = $this->validate_foreign_keys($data, $fk_map, $row_num);
        if ($fk_fail) {
            $errors[] = $fk_fail;
            $skipped++;
            continue;
        }

        // ── users table: email uniqueness + password hash ─────────
        if ($table === 'users') {
            if (empty($data['email'])) {
                $errors[] = "Row {$row_num}: skipped — empty email.";
                $skipped++;
                continue;
            }
            $existsInDb = $this->db
                ->where('email', $data['email'])
                ->count_all_results($table) > 0;
            if ($existsInDb) {
                $errors[] = "Row {$row_num}: skipped — email already exists ({$data['email']}).";
                $skipped++;
                continue;
            }
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
        }

        // ── Upsert or insert (one row at a time — safer for FK rows) ──
        if ($primary_key && !empty($data[$primary_key])) {
            $exists = $this->db
                ->where($primary_key, $data[$primary_key])
                ->count_all_results($table) > 0;

            if ($exists) {
                try {
                    $this->db->where($primary_key, $data[$primary_key])->update($table, $data);
                    $updated++;
                } catch (Throwable $e) {
                    $errors[] = "Row {$row_num}: update failed — " . $e->getMessage();
                    $skipped++;
                }
                continue;
            }
        }

        // Insert single row so FK errors are isolated and skippable
        try {
            $this->db->insert($table, $data);
            $imported++;
        } catch (Throwable $e) {
            $errors[] = "Row {$row_num}: insert failed — " . $e->getMessage();
            $skipped++;
        }
    }

    fclose($handle);

    $this->db->trans_complete();

    if ($this->db->trans_status() === false) {
        return [
            'success' => false,
            'message' => 'Database transaction failed. Check errors below.',
            'errors'  => $errors,
        ];
    }

    return [
        'success'  => true,
        'imported' => $imported,
        'updated'  => $updated,
        'skipped'  => $skipped,
        'errors'   => $errors,
    ];
}

/**
 * Build a map of FK columns for a table using INFORMATION_SCHEMA.
 * Returns: [ 'user_id' => ['table' => 'users', 'column' => 'id'], ... ]
 */
private function get_foreign_key_map(string $table): array
{
    $map = [];
    try {
        $dbName = $this->db->database;
        $rows = $this->db->query("
            SELECT
                kcu.COLUMN_NAME,
                kcu.REFERENCED_TABLE_NAME,
                kcu.REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
            WHERE kcu.TABLE_SCHEMA = ?
              AND kcu.TABLE_NAME   = ?
              AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
        ", [$dbName, $table])->result_array();

        foreach ($rows as $row) {
            $map[$row['COLUMN_NAME']] = [
                'table'  => $row['REFERENCED_TABLE_NAME'],
                'column' => $row['REFERENCED_COLUMN_NAME'],
            ];
        }
    } catch (Throwable $e) {
        log_message('error', "get_foreign_key_map({$table}): " . $e->getMessage());
    }
    return $map;
}

/**
 * Check every FK column in $data exists in its referenced table.
 * Returns an error string on first failure, or null if all pass.
 */
private function validate_foreign_keys(array $data, array $fk_map, int $row_num): ?string
{
    foreach ($fk_map as $col => $ref) {
        // Only check if the column is present and has a non-null value
        if (!array_key_exists($col, $data) || $data[$col] === null) {
            continue;
        }

        $exists = $this->db
            ->where($ref['column'], $data[$col])
            ->count_all_results($ref['table']) > 0;

        if (!$exists) {
            return "Row {$row_num}: skipped — {$col} = '{$data[$col]}' "
                 . "does not exist in {$ref['table']}.{$ref['column']}.";
        }
    }
    return null;
}
    /**
     * Check if a column is numeric type
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @return bool Whether column is numeric
     */
    private function is_numeric_column($table, $column)
    {
        $field_data = $this->db->field_data($table);
        foreach ($field_data as $field) {
            if ($field->name === $column) {
                return in_array($field->type, ['int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'float', 'double', 'decimal']);
            }
        }
        return false;
    }


    private function get_date_columns(string $table): array
    {
        $cols = [];
        foreach ($this->db->field_data($table) as $field) {
            if (in_array($field->type, ['date', 'datetime', 'timestamp'])) {
                $cols[$field->name] = $field->type;
            }
        }
        return $cols;
    }

    private function normalize_datetime($value, string $type)
    {
        if ($value === null || $value === '') {
            return null;
        }
    
        // Excel serial date (numeric)
        if (is_numeric($value)) {
            $unix = ((float)$value - 25569) * 86400;
            return $type === 'date'
                ? gmdate('Y-m-d', $unix)
                : gmdate('Y-m-d H:i:s', $unix);
        }
    
        // Known Excel / human formats
        $formats = [
            'm/d/y g:i A',
            'm/d/Y g:i A',
            'm/d/y',
            'm/d/Y',
            'Y-m-d H:i:s',
            'Y-m-d',
        ];
    
        foreach ($formats as $fmt) {
            $dt = DateTime::createFromFormat($fmt, trim($value));
            if ($dt !== false) {
                return $type === 'date'
                    ? $dt->format('Y-m-d')
                    : $dt->format('Y-m-d H:i:s');
            }
        }
    
        // Last attempt (strtotime)
        $ts = strtotime($value);
        if ($ts !== false) {
            return $type === 'date'
                ? date('Y-m-d', $ts)
                : date('Y-m-d H:i:s', $ts);
        }
    
        return null; // invalid date → NULL
    }
    
}