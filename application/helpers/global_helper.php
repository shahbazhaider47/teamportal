<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('company_info')) {
    
    function company_info()
    {
        $CI =& get_instance();
        $CI->load->model('Company_info_model');
        return $CI->Company_info_model->get_all_values();
    }
}

if ( ! function_exists('get_company_data'))
{
    function get_company_data()
    {
        $CI =& get_instance();
        $CI->load->model('Company_info_model');
        return $CI->Company_info_model->get_all_values();
    }
}

if (!function_exists('todo_add')) {
    function todo_add($todo_name, $rel_type = null, $rel_id = null)
    {
        $CI = &get_instance();
        $CI->load->model('Todo_model');

        $user_id = (int) $CI->session->userdata('user_id');
        if (!$user_id) return false;

        $data = [
            'user_id'   => $user_id,
            'todo_name' => $todo_name,
            'rel_type'  => $rel_type,
            'rel_id'    => $rel_id,
            'status'    => 0,
            'dateadded' => date('Y-m-d H:i:s'),
        ];

        return $CI->Todo_model->insert_quick($data);
    }
}

if (!function_exists('render_todo_ai_button')) {
    function render_todo_ai_button(?string $rawText, array $options = []): void
    {
        $CI = &get_instance();

        if (!isset($CI->todo_ai) || !method_exists($CI->todo_ai, 'should_trigger_button')) {
            return;
        }

        $rawText = (string) $rawText;

        $plainText = trim(strip_tags(html_entity_decode($rawText, ENT_QUOTES, 'UTF-8')));
        if ($plainText === '') {
            return;
        }

        if (!$CI->todo_ai->should_trigger_button($plainText)) {
            return;
        }

        $relType  = isset($options['rel_type']) ? (string) $options['rel_type'] : 'task';
        $relId    = isset($options['rel_id']) ? (int) $options['rel_id'] : 0;
        $title    = isset($options['title']) ? (string) $options['title'] : 'Add to My To-Do';
        $btnClass = isset($options['class']) ? (string) $options['class'] : 'btn btn-light-info btn-ssm js-quick-todo';
        $maxLen   = isset($options['max_length']) ? (int) $options['max_length'] : 80;
        $todoSource = isset($options['todo_name']) ? (string) $options['todo_name'] : $rawText;
        $todoName   = mb_substr($todoSource, 0, $maxLen, 'UTF-8');
        $todoNameEsc = htmlspecialchars($todoName, ENT_QUOTES, 'UTF-8');
        $relTypeEsc  = htmlspecialchars($relType, ENT_QUOTES, 'UTF-8');
        $titleEsc    = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        echo '<button type="button"
                class="' . $btnClass . '"
                data-todo-name="' . $todoNameEsc . '"
                data-rel-type="' . $relTypeEsc . '"
                data-rel-id="' . $relId . '"
                title="' . $titleEsc . '">
                <i class="ti ti-checklist"></i>
              </button>';
    }
}

if ( ! function_exists('_l'))
{
    function _l($key, array $params = [])
    {
        $CI =& get_instance();
        $line = $CI->lang->line($key);

        if ($line === false || $line === '')
        {
            return ! empty($params)
                ? vsprintf($key, $params)
                : $key;
        }

        if (! empty($params))
        {
            return vsprintf($line, $params);
        }

        return $line;
    }

if (!function_exists('e')) {
    function e(?string $str): string
    {
        return html_escape($str ?? '');
    }
}

if (!function_exists('priority_class')) {
    function priority_class($priority)
    {
        switch (strtolower(trim($priority))) {

            case 'urgent':
            case 'critical':
                return 'danger';

            case 'high':
                return 'warning';

            case 'normal':
            case 'medium':
                return 'info';

            case 'low':
                return 'secondary';

            case 'info':
            case 'optional':
                return 'success';

            default:
                return 'info';
        }
    }
}

function set_alert($type, $message)
{
    $CI = &get_instance();
    $alerts = $CI->session->userdata('alerts') ?? [];
    $alerts[] = ['type' => $type, 'message' => $message];
    $CI->session->set_userdata('alerts', $alerts);
}

function get_alerts()
{
    $CI = &get_instance();
    $alerts = $CI->session->userdata('alerts');
    if ($alerts) {
        $CI->session->unset_userdata('alerts');
        return $alerts;
    }
    return null;
}

function get_alert_color($type)
{
    switch ($type) {
        case 'success': return '#4a8a6c';
        case 'danger':  return '#db6963';
        case 'warning': return '#ffd75e';
        case 'info':    return '#056464';
        default:        return '#056464';
    }
}



}

if (!function_exists('_deprecated_function')) {
    function _deprecated_function($function, $version, $replacement = null) {
        // Optional: log, trigger_error, or ignore
        // For production, just do nothing or log if you want to track usage
        // Example (uncomment if needed):
        // error_log("Deprecated function {$function} called since version {$version}." . ($replacement ? " Use {$replacement} instead." : ''));
    }
}

if (!function_exists('name_initials')) {
    function name_initials($name) {
        $name = trim((string)$name);
        if ($name === '') return 'U';
        $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        $first = mb_substr($parts[0], 0, 1);
        $second = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '';
        return mb_strtoupper($first . $second);
    }
}

if (!function_exists('user_avatar_url')) {
    function user_avatar_url($user = null)
    {
        $defaultRel = 'assets/images/default.png';
        $uploadsDir = 'uploads/users/profile/';
        $CI =& get_instance();

        $profile_image = '';
        if (is_numeric($user)) {

            $uid = (int) $user;

            if ($uid > 0) {
                $row = $CI->db
                    ->select('profile_image')
                    ->from('users')
                    ->where('id', $uid)
                    ->limit(1)
                    ->get()
                    ->row_array();

                if (is_array($row) && isset($row['profile_image']) && is_string($row['profile_image'])) {
                    $profile_image = trim($row['profile_image']);
                }
            }
        }

        elseif (is_array($user)) {

            if (isset($user['profile_image']) && is_string($user['profile_image'])) {
                $profile_image = trim($user['profile_image']);
            }
        }

        elseif (is_string($user)) {

            $s = trim($user);

            if ($s !== '' && preg_match('#^https?://#i', $s)) {
                return $s;
            }

            if ($s !== '' && preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i', $s)) {
                $profile_image = $s;
            }
            else {
                $row = $CI->db
                    ->select('profile_image')
                    ->from('users')
                    ->where('fullname', $s)
                    ->limit(1)
                    ->get()
                    ->row_array();

                if (is_array($row) && isset($row['profile_image']) && is_string($row['profile_image'])) {
                    $profile_image = trim($row['profile_image']);
                }
            }
        }

        if ($profile_image !== '') {

            $profile_image = ltrim($profile_image, '/\\');
            $rel = $uploadsDir . $profile_image;
            $abs = rtrim(FCPATH, '/\\') . '/' . $rel;

            if (is_file($abs)) {
                return base_url($rel);
            }
        }

        return base_url($defaultRel);
    }
}

if (!function_exists('user_profile_image')) {
    function user_profile_image($user = null, $attrs = []): string
    {
        $CI = get_instance();

        if (!is_array($attrs)) {
            $attrs = ['class' => (string)$attrs];
        }

        $defaults = [
            'class' => 'rounded-circle',
            'alt'   => 'User',
            'style' => 'width:24px;height:24px;object-fit:cover;',
        ];

        $attrs = array_merge($defaults, array_filter($attrs, function ($v) {
            return $v !== null && $v !== '';
        }));

        $row = [
            'firstname'      => '',
            'lastname'       => '',
            'fullname'       => '',
            'profile_image'  => '',
        ];

        if (is_array($user)) {
            $row['firstname']     = trim((string)($user['firstname'] ?? ''));
            $row['lastname']      = trim((string)($user['lastname'] ?? ''));
            $row['fullname']      = trim((string)($user['fullname'] ?? ''));
            $row['profile_image'] = trim((string)($user['profile_image'] ?? ''));
        }
        elseif (is_int($user) || ctype_digit((string)$user)) {
            $uid = (int)$user;
            if ($uid > 0) {
                $dbRow = $CI->db->select('firstname, lastname, fullname, profile_image')
                    ->from('users')
                    ->where('id', $uid)
                    ->limit(1)
                    ->get()
                    ->row_array();

                if ($dbRow) {
                    $row['firstname']     = trim((string)($dbRow['firstname'] ?? ''));
                    $row['lastname']      = trim((string)($dbRow['lastname'] ?? ''));
                    $row['fullname']      = trim((string)($dbRow['fullname'] ?? ''));
                    $row['profile_image'] = trim((string)($dbRow['profile_image'] ?? ''));
                }
            }
        }
        elseif (is_string($user)) {
            $s = trim($user);

            if ($s !== '' && preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i', $s)) {
                $row['profile_image'] = $s;
            } else {
                if ($s !== '') {
                    $dbRow = $CI->db->select('firstname, lastname, fullname, profile_image')
                        ->from('users')
                        ->where('fullname', $s)
                        ->limit(1)
                        ->get()
                        ->row_array();

                    if ($dbRow) {
                        $row['firstname']     = trim((string)($dbRow['firstname'] ?? ''));
                        $row['lastname']      = trim((string)($dbRow['lastname'] ?? ''));
                        $row['fullname']      = trim((string)($dbRow['fullname'] ?? ''));
                        $row['profile_image'] = trim((string)($dbRow['profile_image'] ?? ''));
                    } else {
                        $row['fullname'] = $s;
                    }
                }
            }
        }

        $displayName = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
        if ($displayName === '') {
            $displayName = trim((string)($row['fullname'] ?? ''));
        }
        if ($displayName === '') {
            $displayName = 'User';
        }

        if (empty($attrs['title'])) {
            $attrs['title'] = $displayName;
        }
        
        $src = user_avatar_url($row['profile_image'] !== '' ? $row['profile_image'] : $user);
        $html = '<img src="' . html_escape($src) . '"';

        foreach ($attrs as $key => $val) {
            $html .= ' ' . html_escape($key) . '="' . html_escape($val) . '"';
        }

        $html .= ' />';
        $html .= ' <span class="ms-1">' . html_escape($displayName) . '</span>';

        return $html;
    }
}


    
    if (!function_exists('user_profile_small')) {
        function user_profile_small($user = null, $attrs = []): string
        {
            $CI = get_instance();
            if (!is_array($attrs)) {
                $attrs = ['class' => (string)$attrs];
            }
    
            $defaults = [
                'class' => 'rounded-circle',
                'alt'   => 'User',
                'style' => 'width:15px;height:15px;object-fit:cover;',
            ];
    
            $attrs = array_merge($defaults, array_filter($attrs, function ($v) {
                return $v !== null && $v !== '';
            }));

            $row = [
                'firstname'      => '',
                'lastname'       => '',
                'fullname'       => '',
                'profile_image'  => '',
            ];
    
            if (is_array($user)) {
                $row['firstname']     = trim((string)($user['firstname'] ?? ''));
                $row['lastname']      = trim((string)($user['lastname'] ?? ''));
                $row['fullname']      = trim((string)($user['fullname'] ?? ''));
                $row['profile_image'] = trim((string)($user['profile_image'] ?? ''));
            }
            elseif (is_int($user) || ctype_digit((string)$user)) {
                $uid = (int)$user;
                if ($uid > 0) {
                    $dbRow = $CI->db->select('firstname, lastname, fullname, profile_image')
                        ->from('users')
                        ->where('id', $uid)
                        ->limit(1)
                        ->get()
                        ->row_array();
    
                    if ($dbRow) {
                        $row['firstname']     = trim((string)($dbRow['firstname'] ?? ''));
                        $row['lastname']      = trim((string)($dbRow['lastname'] ?? ''));
                        $row['fullname']      = trim((string)($dbRow['fullname'] ?? ''));
                        $row['profile_image'] = trim((string)($dbRow['profile_image'] ?? ''));
                    }
                }
            }
            elseif (is_string($user)) {
                $s = trim($user);
    
                if ($s !== '' && preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i', $s)) {
                    $row['profile_image'] = $s;
                } else {
                    if ($s !== '') {
                        $dbRow = $CI->db->select('firstname, lastname, fullname, profile_image')
                            ->from('users')
                            ->where('fullname', $s)
                            ->limit(1)
                            ->get()
                            ->row_array();
    
                        if ($dbRow) {
                            $row['firstname']     = trim((string)($dbRow['firstname'] ?? ''));
                            $row['lastname']      = trim((string)($dbRow['lastname'] ?? ''));
                            $row['fullname']      = trim((string)($dbRow['fullname'] ?? ''));
                            $row['profile_image'] = trim((string)($dbRow['profile_image'] ?? ''));
                        } else {
                            $row['fullname'] = $s;
                        }
                    }
                }
            }

            $displayName = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
            if ($displayName === '') {
                $displayName = trim((string)($row['fullname'] ?? ''));
            }
            if ($displayName === '') {
                $displayName = 'User';
            }
    
            if (empty($attrs['title'])) {
                $attrs['title'] = $displayName;
            }

            $src = user_avatar_url($row['profile_image'] !== '' ? $row['profile_image'] : $user);
            $html = '<img src="' . html_escape($src) . '"';
    
            foreach ($attrs as $key => $val) {
                $html .= ' ' . html_escape($key) . '="' . html_escape($val) . '"';
            }
    
            $html .= ' />';
            $html .= ' <span class="ms-1 small text-muted">' . html_escape($displayName) . '</span>';
    
            return $html;
        }
    }

if (!function_exists('user_profile')) {

    function user_profile($user = null, $attrs = []): string
    {
        $CI = get_instance();
        if (!is_array($attrs)) {
            $attrs = ['class' => (string)$attrs];
        }
        $defaults = [
            'class' => 'rounded-circle',
            'alt'   => 'User',
            'style' => 'width:24px;height:24px;object-fit:cover;',
        ];
        $attrs = array_merge($defaults, array_filter($attrs, function ($v) {
            return $v !== null && $v !== '';
        }));
        $row = [
            'firstname'      => '',
            'lastname'       => '',
            'fullname'       => '',
            'profile_image'  => '',
        ];
        if (is_array($user)) {
            $row['firstname']     = trim((string)($user['firstname'] ?? ''));
            $row['lastname']      = trim((string)($user['lastname'] ?? ''));
            $row['fullname']      = trim((string)($user['fullname'] ?? ''));
            $row['profile_image'] = trim((string)($user['profile_image'] ?? ''));
        }
        elseif (is_int($user) || ctype_digit((string)$user)) {
            $uid = (int)$user;
            if ($uid > 0) {
                $dbRow = $CI->db->select('firstname, lastname, fullname, profile_image')
                    ->from('users')
                    ->where('id', $uid)
                    ->limit(1)
                    ->get()
                    ->row_array();
                if ($dbRow) {
                    $row['firstname']     = trim((string)($dbRow['firstname'] ?? ''));
                    $row['lastname']      = trim((string)($dbRow['lastname'] ?? ''));
                    $row['fullname']      = trim((string)($dbRow['fullname'] ?? ''));
                    $row['profile_image'] = trim((string)($dbRow['profile_image'] ?? ''));
                }
            }
        }
        elseif (is_string($user)) {
            $s = trim($user);
            if ($s !== '' && preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i', $s)) {
                $row['profile_image'] = $s;
            } else {
                if ($s !== '') {
                    $dbRow = $CI->db->select('firstname, lastname, fullname, profile_image')
                        ->from('users')
                        ->where('fullname', $s)
                        ->limit(1)
                        ->get()
                        ->row_array();
                    if ($dbRow) {
                        $row['firstname']     = trim((string)($dbRow['firstname'] ?? ''));
                        $row['lastname']      = trim((string)($dbRow['lastname'] ?? ''));
                        $row['fullname']      = trim((string)($dbRow['fullname'] ?? ''));
                        $row['profile_image'] = trim((string)($dbRow['profile_image'] ?? ''));
                    } else {
                        $row['fullname'] = $s;
                    }
                }
            }
        }
        $displayName = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
        if ($displayName === '') {
            $displayName = trim((string)($row['fullname'] ?? ''));
        }
        if ($displayName === '') {
            $displayName = 'User';
        }
        $src = user_avatar_url($row['profile_image'] !== '' ? $row['profile_image'] : $user);
        if (empty($attrs['alt'])) {
            $attrs['alt'] = $displayName;
        }
        if (empty($attrs['title'])) {
            $attrs['title'] = $displayName;
        }
        $html = '<img src="' . html_escape($src) . '"';
        foreach ($attrs as $key => $val) {
            $html .= ' ' . html_escape($key) . '="' . html_escape($val) . '"';
        }
        $html .= ' />';
        return $html;
    }
}

if (!function_exists('get_user_full_name')) {
    function get_user_full_name($user)
    {
        if (is_array($user)) {
            return trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
        }

        $CI =& get_instance();
        $CI->load->model('User_model');
        $record = $CI->User_model->get_user_by_id((int)$user);
        return $record ? trim($record['firstname'] . ' ' . $record['lastname']) : 'Unknown';
    }
}

if (!function_exists('get_hr_department_users')) {
    function get_hr_department_users(): array
    {
        $CI =& get_instance();
        $CI->db->select('u.id, u.fullname, u.firstname, u.lastname');
        $CI->db->from('users AS u');
        $CI->db->join('departments AS d', 'd.id = u.emp_department', 'inner');
        $CI->db->where('u.is_active', 1);
        $CI->db->group_start();
        $CI->db->where('d.name', 'HR');
        $CI->db->or_where('d.name', 'Human Resource');
        $CI->db->or_where('d.name', 'Human Resources');
        $CI->db->group_end();

        $CI->db->order_by('u.firstname', 'ASC');

        $query = $CI->db->get();
        return $query->result_array();
    }
}

if (!function_exists('delete_link')) {
    function delete_link(array $opts = [])
    {
        if (empty($opts['url'])) {
            return '';
        }

        $label   = $opts['label']   ?? 'Delete';
        $class   = $opts['class']   ?? 'btn btn-outline-secondary';
        $icon    = $opts['icon']    ?? '<i class="ti ti-trash"></i>';
        $message = $opts['message']
            ?? 'This action cannot be undone. Are you sure?';

        return sprintf(
            '<a href="%s"
               class="%s app-delete-trigger"
               data-delete-url="%s"
               data-delete-message="%s">
               %s %s
            </a>',
            site_url($opts['url']),
            $class,
            site_url($opts['url']),
            html_escape($message),
            $icon,
            html_escape($label)
        );
    }
}

if (!function_exists('get_team_members')) {
    function get_team_members($team_id = null, $exclude_user = null): array
    {
        if (empty($team_id)) {
            return [];
        }

        $CI =& get_instance();

        $CI->db->select('
            id,
            emp_id,
            firstname,
            lastname,
            fullname,
            user_role,
            emp_team,
            emp_teamlead,
            emp_manager,
            emp_reporting,
            profile_image,
            emp_title
        ');
        $CI->db->from('users');
        $CI->db->where('emp_team', (int)$team_id);
        $CI->db->where('is_active', 1);

        if (!empty($exclude_user)) {
            $CI->db->where('id !=', (int)$exclude_user);
        }

        $CI->db->order_by('firstname', 'ASC');
        $CI->db->order_by('lastname', 'ASC');

        return $CI->db->get()->result_array();
    }
}

if (!function_exists('render_table_filter')) {
    function render_table_filter(string $table_id, array $options = []): void
    {
        $defaults = [
            'placeholder'         => 'Search...',
            'search_label'        => 'Search', 
            'show_global_search'  => true,
            'show_column_filters' => true,
            'exclude_columns'     => [], 
            'exclude_indexes'     => [], 
        ];

        $opts = array_merge($defaults, $options);
        $table_id = trim($table_id) !== '' ? $table_id : 'dataTable';
        $excludeCols    = array_map('strval', $opts['exclude_columns']);
        $excludeIndexes = array_map('intval', $opts['exclude_indexes']);
        $dataExcludeCols    = !empty($excludeCols)    ? implode('||', $excludeCols)   : '';
        $dataExcludeIndexes = !empty($excludeIndexes) ? implode(',', $excludeIndexes) : '';
        ?>
        <div class="app-table-filter d-flex justify-content-start align-items-start gap-3 flex-wrap"
             data-table-id="<?= html_escape($table_id); ?>"
             data-show-global-search="<?= $opts['show_global_search'] ? '1' : '0'; ?>"
             data-show-column-filters="<?= $opts['show_column_filters'] ? '1' : '0'; ?>"
             data-exclude-columns="<?= html_escape($dataExcludeCols); ?>"
             data-exclude-indexes="<?= html_escape($dataExcludeIndexes); ?>">

            <?php if ($opts['show_global_search']): ?>
                <div class="d-flex flex-column me-2">
                    <label class="form-label text-muted mb-1 app-table-filter-lables text-primary">
                        <?= html_escape($opts['search_label']); ?>
                    </label>
                    <div class="input-group input-group-sm app-table-filter-search" style="width: 250px;">
                        <input type="text"
                               class="form-control form-control-sm small js-table-global-search"
                               placeholder="<?= html_escape($opts['placeholder']); ?>"
                               aria-label="<?= html_escape($opts['search_label']); ?>">
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($opts['show_column_filters']): ?>
                <div class="d-flex flex-wrap gap-3 js-table-column-filters mb-0 app-table-filter-lables text-primary"></div>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('app_table_filter')) {
    function app_table_filter(string $table_id = 'plansTable', array $options = []): void
    {
        render_table_filter($table_id, $options);
    }
}

if (!function_exists('render_export_buttons')) {
    function render_export_buttons(array $options = [])
    {
        $canExport = function_exists('staff_can') && staff_can('export', 'general');
        $filename   = $options['filename']    ?? 'export';
        $showFilter = $options['show_filter'] ?? true;
        $showExcel  = $options['show_excel']  ?? true;
        $showCsv    = $options['show_csv']    ?? true;
        $showPdf    = $options['show_pdf']    ?? true;
        $filterId   = $options['filter_id']   ?? 'showFilter';
        ?>

        <?php if ($showFilter): ?>
            <a class="btn btn-light-primary icon-btn b-r-4"
               data-bs-toggle="collapse"
               href="#<?= html_escape($filterId); ?>"
               role="button"
               aria-expanded="false"
               aria-controls="<?= html_escape($filterId); ?>"
               title="Show Filter">
               <i class="ti ti-filter"></i>
            </a>
        <?php endif; ?>

        <?php if ($canExport): ?>

            <?php if ($showExcel): ?>
                <button type="button"
                        class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                        title="Export to Excel"
                        data-export-filename="<?= html_escape($filename); ?>">
                    <i class="fa-solid fa-file-excel fa-fw"></i>
                </button>
            <?php endif; ?>

            <?php if ($showCsv): ?>
                <button type="button"
                        class="btn btn-light-primary icon-btn b-r-4 btn-csv-table"
                        title="Export to CSV"
                        data-export-filename="<?= html_escape($filename); ?>">
                    <i class="fas fa-file-csv"></i>
                </button>
            <?php endif; ?>

            <?php if ($showPdf): ?>
                <button type="button"
                        class="btn btn-light-primary icon-btn b-r-4 btn-pdf-table"
                        title="Export to PDF"
                        data-export-filename="<?= html_escape($filename); ?>">
                    <i class="ti ti-printer"></i>
                </button>
            <?php endif; ?>

        <?php endif; ?>

        <?php
    }
}

if (!function_exists('render_safe_html')) {
  function render_safe_html($html)
  {
    $html = (string)$html;
    $html = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $html);
    $html = preg_replace('/\son\w+\s*=\s*"[^"]*"/i', '', $html);
    $html = preg_replace("/\son\w+\s*=\s*'[^']*'/i", '', $html);
    $html = preg_replace('/\son\w+\s*=\s*[^>\s"]+/i', '', $html);
    $html = preg_replace('/\s(href|src)\s*=\s*([\'"])\s*(javascript:|data:)[^\'"]*\2/i', ' $1="#"', $html);
    $html = preg_replace('/\s(href|src)\s*=\s*(javascript:|data:)[^\s>]+/i', ' $1="#"', $html);
    $html = strip_tags($html, '<p><br><b><strong><i><em><u><span><a><ul><ol><li><div>');
    $html = preg_replace_callback('/<a\b[^>]*>/i', function ($m) {
      $tag = $m[0];

      if (!preg_match('/\shref\s*=\s*([\'"])(.*?)\1/i', $tag, $hm)) {
        $tag = preg_replace('/\s*target\s*=\s*([\'"])[^\'"]*\1/i', '', $tag);
        return rtrim($tag, '>') . '>';
      }

      $href = trim($hm[2]);
      if ($href !== '' && !preg_match('#^(https?://|mailto:|#)#i', $href)) {
        $tag = preg_replace('/\shref\s*=\s*([\'"])(.*?)\1/i', '', $tag);
      }

      if (stripos($tag, ' target=') === false) {
        $tag = rtrim($tag, '>') . ' target="_blank"';
      }
      if (stripos($tag, ' rel=') === false) {
        $tag = rtrim($tag, '>') . ' rel="noopener noreferrer"';
      }
      return rtrim($tag, '>') . '>';
    }, $html);

    return $html;
  }
}

if ( ! function_exists('bytesToSize'))
{
    function bytesToSize($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max(0, (int) $bytes);
        $pow   = $bytes > 0 ? floor(log($bytes) / log(1024)) : 0;
        $pow   = min($pow, count($units) - 1);

        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}


//////////////////////////////////////////////////////////////////////////////////////////////////////
