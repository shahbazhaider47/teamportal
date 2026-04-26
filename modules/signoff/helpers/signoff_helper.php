<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Parse signoff form fields from a POSTed string
 * Accepts comma-separated values or JSON structure for advanced forms.
 *
 * @param string $input
 * @return array
 *
 * Usage Examples:
 *   "Task Completed, Issues Faced, Next Plan"
 *     => [['name'=>'Task Completed', 'type'=>'text', 'label'=>'Task Completed'], ...]
 *   '[{"name":"Task Completed","type":"text"},{"name":"Issues Faced","type":"textarea"}]'
 *     => as parsed, with all keys preserved
 */
if (!function_exists('parse_signoff_fields')) {
    function parse_signoff_fields($input)
    {
        $input = trim($input);

        // Try to parse as JSON if starts with '[' or '{'
        if ($input && (substr($input, 0, 1) === '[' || substr($input, 0, 1) === '{')) {
            $fields = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($fields)) {
                $result = [];
                foreach ($fields as $field) {
                    if (is_array($field) && !empty($field['name'])) {
                        // Always trim the field name and label; preserve all other keys/values
                        $field['name']  = trim($field['name']);
                        if (isset($field['label'])) {
                            $field['label'] = trim($field['label']);
                        }
                        $result[] = $field;
                    }
                }
                return $result;
            }
            // If JSON fails, fall through to CSV parse
        }

        // Otherwise: treat as comma-separated list (for legacy/simple usage)
        $parts = array_filter(array_map('trim', explode(',', $input)));
        $result = [];
        foreach ($parts as $name) {
            if ($name !== '') {
                $result[] = [
                    'name'  => $name,
                    'type'  => 'text',
                    'label' => $name
                ];
            }
        }
        return $result;
    }
}

/**
 * Get readable status badge for submission status
 * @param string $status
 * @return string HTML badge
 */
if (!function_exists('signoff_status_badge')) {
    function signoff_status_badge($status)
    {
        $status = strtolower($status ?? '');
        switch ($status) {
            case 'approved':
                return '<span class="badge bg-success">Approved</span>';
            case 'rejected':
                return '<span class="badge bg-danger">Rejected</span>';
            case 'submitted':
                return '<span class="badge bg-info">Submitted</span>';
            default:
                return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
        }
    }
}

/**
 * Render signoff form fields for user entry (in a view)
 * Use in submit_form.php or similar
 */
if (!function_exists('render_signoff_form_fields')) {
    /**
     * @param array $fields Array from parse_signoff_fields() or json_decode($form['fields'], true)
     * @param array $old Old input (e.g. from $_POST['fields'] or empty)
     * @param bool $readonly
     */
    function render_signoff_form_fields($fields, $old = [], $readonly = false)
    {
        foreach ($fields as $field) {
            $name       = $field['name'];
            $type       = $field['type'] ?? 'text';
            $label      = $field['label'] ?? $name;
            $col        = $field['col'] ?? 'col-md-6';
            $placeholder= $field['placeholder'] ?? '';
            $required   = !empty($field['required']) ? 'required' : '';
            $value      = isset($old[$name]) ? html_escape($old[$name]) : '';
            $options    = $field['options'] ?? [];

            echo '<div class="'.html_escape($col).' mb-3">';
            echo '<label class="form-label" for="field_'.md5($name).'">'.html_escape($label);
            if ($required) echo ' <span class="text-danger">*</span>';
            echo '</label>';

            // Field rendering logic
            switch ($type) {
                case 'textarea':
                    echo '<textarea id="field_'.md5($name).'" name="fields['.html_escape($name).']" class="form-control" rows="3" '.$required.' '.($readonly ? 'readonly' : '').' placeholder="'.html_escape($placeholder).'">'.$value.'</textarea>';
                    break;
                case 'select':
                    echo '<select id="field_'.md5($name).'" name="fields['.html_escape($name).']" class="form-select" '.$required.' '.($readonly ? 'disabled' : '').'>';
                    echo '<option value="">Select</option>';
                    if (is_array($options)) {
                        foreach ($options as $opt) {
                            $selected = ($value === $opt) ? 'selected' : '';
                            echo '<option value="'.html_escape($opt).'" '.$selected.'>'.html_escape($opt).'</option>';
                        }
                    } elseif (is_string($options)) {
                        foreach (explode(',', $options) as $opt) {
                            $selected = ($value === trim($opt)) ? 'selected' : '';
                            echo '<option value="'.html_escape(trim($opt)).'" '.$selected.'>'.html_escape(trim($opt)).'</option>';
                        }
                    }
                    echo '</select>';
                    break;
                case 'radio':
                    if (is_array($options)) {
                        foreach ($options as $opt) {
                            echo '<div class="form-check form-check-inline">';
                            echo '<input class="form-check-input" type="radio" name="fields['.html_escape($name).']" id="field_'.md5($name . $opt).'" value="'.html_escape($opt).'" '.($value == $opt ? 'checked' : '').' '.$required.' '.($readonly ? 'disabled' : '').'>';
                            echo '<label class="form-check-label" for="field_'.md5($name . $opt).'">'.html_escape($opt).'</label>';
                            echo '</div>';
                        }
                    }
                    break;
                case 'checkbox':
                    if (is_array($options)) {
                        foreach ($options as $opt) {
                            echo '<div class="form-check form-check-inline">';
                            echo '<input class="form-check-input" type="checkbox" name="fields['.html_escape($name).'][]" id="field_'.md5($name . $opt).'" value="'.html_escape($opt).'" '.((is_array($value) && in_array($opt, $value)) ? 'checked' : '').' '.($readonly ? 'disabled' : '').'>';
                            echo '<label class="form-check-label" for="field_'.md5($name . $opt).'">'.html_escape($opt).'</label>';
                            echo '</div>';
                        }
                    }
                    break;
                case 'amount':
                case 'number':
                    echo '<input type="number" id="field_'.md5($name).'" name="fields['.html_escape($name).']" class="form-control" value="'.$value.'" '.$required.' '.($readonly ? 'readonly' : '').' placeholder="'.html_escape($placeholder).'">';
                    break;
                case 'email':
                case 'phone':
                case 'date':
                case 'time':
                case 'link':
                case 'color':
                case 'password':
                case 'file':
                    $input_type = $type === 'link' ? 'url' : ($type === 'phone' ? 'tel' : $type);
                    echo '<input type="'.$input_type.'" id="field_'.md5($name).'" name="fields['.html_escape($name).']" class="form-control" value="'.$value.'" '.$required.' '.($readonly ? 'readonly' : '').' placeholder="'.html_escape($placeholder).'">';
                    break;
                case 'hidden':
                    echo '<input type="hidden" id="field_'.md5($name).'" name="fields['.html_escape($name).']" value="'.$value.'">';
                    break;
                default:
                    echo '<input type="text" id="field_'.md5($name).'" name="fields['.html_escape($name).']" class="form-control" value="'.$value.'" '.$required.' '.($readonly ? 'readonly' : '').' placeholder="'.html_escape($placeholder).'">';
                    break;
            }
            echo '</div>';
        }
    }
}
