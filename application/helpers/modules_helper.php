<?php defined('BASEPATH') or exit('No direct script access allowed');

function register_activation_hook($module, $function)
{
    hooks()->add_action('activate_' . $module . '_module', $function);
}

function register_deactivation_hook($module, $function)
{
    hooks()->add_action('deactivate_' . $module . '_module', $function);
}

function register_uninstall_hook($module, $function)
{
    hooks()->add_action('uninstall_' . $module . '_module', $function);
}

function register_merge_fields($for)
{
    get_instance()->app_merge_fields->register($for);
}

function add_module_support($module_name, $feature)
{
    get_instance()->app_modules->add_supports_feature($module_name, $feature);
}

function module_supports($module_name, $feature)
{
    return get_instance()->app_modules->supports_feature($module_name, $feature);
}

function register_cron_task($function)
{
    hooks()->add_action('after_cron_run', $function);
}

function register_staff_capabilities($feature_id, $config, $name = null)
{
    hooks()->add_filter('staff_permissions', function ($permissions) use ($feature_id, $config, $name) {
        if (!array_key_exists($feature_id, $permissions)) {
            $permissions[$feature_id] = [];

            if (!$name) {
                $name = str_replace('-', ' ', slug_it($feature_id));
                $name = ucwords($feature_id);
            }

            $permissions[$feature_id]['name'] = $name;
        }

        $permissions[$feature_id] = array_merge_recursive_distinct($permissions[$feature_id], $config);

        return $permissions;
    });
}

function modules_list_url()
{
    return admin_url('modules');
}

function register_payment_gateway($id, $module)
{
    $CI = &get_instance();

    if (!class_exists('payment_modes_model', false)) {
        $CI->load->model('payment_modes_model');
    }

    $CI->payment_modes_model->add_payment_gateway($id, $module);
}

function register_theme_assets_hook($function)
{
    if (hooks()->has_action('app_client_assets', $function)) {
        return false;
    }

    return hooks()->add_action('app_client_assets', $function, 1);
}

function module_views_path($module, $concat = '')
{
    return module_dir_path($module) . 'views/' . $concat;
}

function module_libs_path($module, $concat = '')
{
    return module_dir_path($module) . 'libraries/' . $concat;
}

function module_dir_path($module, $concat = '')
{
    return APP_MODULES_PATH . $module . '/' . $concat;
}

function module_dir_url($module, $segment = '')
{
    return site_url(basename(APP_MODULES_PATH) . '/' . $module . '/' . ltrim($segment, '/'));
}

function register_language_files($module, $languages = [])
{

    if (is_null($languages) || count($languages) === 0) {
        $languages = [$module];
    }

    $languageLoader = function ($language) use ($languages, $module) {
        $CI = &get_instance();

        $path = APP_MODULES_PATH . $module . '/language/' . $language . '/';
        foreach ($languages as $file_name) {
            $file_path = $path . $file_name . '_lang' . '.php';
            if (file_exists($file_path)) {
                $CI->lang->load($module . '/' . $file_name, $language);
            } elseif ($language != 'english' && !file_exists($file_path)) {

                $CI->lang->load($module . '/' . $file_name, 'english');
            }
        }
        if (file_exists($path . 'custom_lang.php')) {
            $CI->lang->load($module . '/custom', $language);
        }
    };


}

function uninstallable_modules()
{
    return ['theme_style', 'menu_setup', 'backup', 'surveys', 'goals', 'exports'];
}

function do_action_deprecated($tag, $args, $version, $replacement = false, $message = null)
{
    if (!hooks()->has_action($tag)) {
        return;
    }

    _deprecated_hook($tag, $version, $replacement, $message);

    hooks()->do_action_ref_array($tag, $args);
}

function apply_filters_deprecated($tag, $args, $version, $replacement = false, $message = null)
{
    if (!hooks()->has_filter($tag)) {
        return $args[0];
    }

    _deprecated_hook($tag, $version, $replacement, $message);

    return hooks()->apply_filters_ref_array($tag, $args);
}

if (!function_exists('upload_error_message')) {
    function upload_error_message($error_code)
    {
        $upload_errors = [
            UPLOAD_ERR_OK         => false,
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];
        return isset($upload_errors[$error_code]) ? $upload_errors[$error_code] : 'Unknown upload error.';
    }
}

if (!function_exists('get_temp_dir')) {
    function get_temp_dir()
    {
        $tempDir = FCPATH . 'temp/';

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        return $tempDir;
    }
}

if (!function_exists('add_module_assets')) {
    function add_module_assets($module, $assets = []) {
        $CI = &get_instance();
        if (!isset($CI->module_css_assets)) $CI->module_css_assets = [];
        if (!isset($CI->module_js_assets))  $CI->module_js_assets  = [];

        if (!empty($assets['css'])) {
            foreach ((array)$assets['css'] as $css) {
                $css = trim($css, '/');
                if (strpos($css, 'css/') === 0) {
                    $css = substr($css, 4);
                }
                $path = "modules/{$module}/assets/css/{$css}";
                if (!in_array($path, $CI->module_css_assets, true)) {
                    $CI->module_css_assets[] = $path;
                }
            }
        }
        
        if (!empty($assets['js'])) {
            foreach ((array)$assets['js'] as $js) {
                $js = trim($js, '/');
                if (strpos($js, 'js/') === 0) {
                    $js = substr($js, 3);
                }
                $path = "modules/{$module}/assets/js/{$js}";
                if (!in_array($path, $CI->module_js_assets, true)) {
                    $CI->module_js_assets[] = $path;
                }
            }
        }
    }
}

if (!function_exists('app_styles')) {
    function app_styles() {
        $CI = &get_instance();
        if (!empty($CI->module_css_assets)) {
            foreach ($CI->module_css_assets as $href) {
                $v = file_exists(FCPATH.$href) ? filemtime(FCPATH.$href) : time();
                echo '<link rel="stylesheet" href="' . base_url($href) . '?v=' . $v . '">' . PHP_EOL;
            }
        }
    }
}

if (!function_exists('app_scripts')) {
    function app_scripts() {
        $CI = &get_instance();
        if (!empty($CI->module_js_assets)) {
            foreach ($CI->module_js_assets as $src) {
                $v = file_exists(FCPATH.$src) ? filemtime(FCPATH.$src) : time();
                echo '<script src="' . base_url($src) . '?v=' . $v . '"></script>' . PHP_EOL;
            }
        }
    }
}

if (!function_exists('get_user_profile_tabs')) {
    function get_user_profile_tabs($user = null)
    {
        $payload = ['tabs' => [], 'user' => $user];
        $result = hooks()->apply_filters('user_profile_tabs', $payload);

        if (is_array($result) && isset($result['tabs'])) {
            return $result['tabs'];
        }
        return is_array($result) ? $result : [];
    }
}

if (!function_exists('get_user_profile_tab_contents')) {
    function get_user_profile_tab_contents($user = null)
    {
        $payload = ['contents' => [], 'user' => $user];
        $result = hooks()->apply_filters('user_profile_tab_contents', $payload);

        if (is_array($result) && isset($result['contents'])) {
            return $result['contents'];
        }
        return is_array($result) ? $result : [];
    }
}

if (!function_exists('get_unified_requests')) {
    function get_unified_requests(int $user_id, array $filters = []): array
    {
        if (!function_exists('hooks')) {
            return [];
        }

        $args = [
            'user_id' => $user_id,
            'filters' => $filters,
        ];

        $requests = hooks()->apply_filters('collect_requests', [], $args);
        if (!is_array($requests)) {
            $requests = [];
        }

        $normalized = [];
        foreach ($requests as $req) {
            if (!is_array($req)) {
                continue;
            }

            if (!isset($req['id'], $req['request_type'], $req['requested_by_name'])) {
                continue;
            }

            $req['meta']               = isset($req['meta']) && is_array($req['meta']) ? $req['meta'] : [];
            $req['source']             = $req['source']       ?? '';
            $req['source_table']       = $req['source_table'] ?? '';
            $req['view_url']           = $req['view_url']     ?? '#';
            $req['notes']              = $req['notes']        ?? '';
            $req['status']             = $req['status']       ?? 'pending';
            $req['requested_at']       = $req['requested_at'] ?? null;
            $req['request_type_label'] = $req['request_type_label'] ?? ucfirst((string)$req['request_type']);

            $normalized[] = $req;
        }

        usort($normalized, static function ($a, $b) {
            $aDate = (string)($a['requested_at'] ?? '');
            $bDate = (string)($b['requested_at'] ?? '');
            return strcmp($bDate, $aDate);
        });

        return $normalized;
    }
}

if (!function_exists('build_request_stats')) {
    function build_request_stats(array $requests): array
    {
        $stats = [
            'total'      => 0,
            'by_type'    => [],
            'by_status'  => [],
        ];

        foreach ($requests as $req) {
            if (!is_array($req)) {
                continue;
            }

            $stats['total']++;

            $typeLabel = $req['request_type_label'] ?? $req['request_type'] ?? 'Other';
            $status    = $req['status'] ?? 'unknown';

            if (!isset($stats['by_type'][$typeLabel])) {
                $stats['by_type'][$typeLabel] = 0;
            }
            if (!isset($stats['by_status'][$status])) {
                $stats['by_status'][$status] = 0;
            }

            $stats['by_type'][$typeLabel]++;
            $stats['by_status'][$status]++;
        }

        return $stats;
    }
}

if (!function_exists('render_request_header_buttons')) {
    function render_request_header_buttons(array $context = []): string
    {
        if (!function_exists('hooks')) {
            return '';
        }

        $buttons = hooks()->apply_filters('requests_header_buttons', [], $context);

        if (!is_array($buttons) || empty($buttons)) {
            return '';
        }

        return implode("\n", $buttons);
    }
}

if (!function_exists('render_request_row_actions')) {
    function render_request_row_actions(array $request, string $defaultHtml): string
    {
        if (!function_exists('hooks')) {
            return $defaultHtml;
        }

        $html = hooks()->apply_filters('request_row_actions', $defaultHtml, ['request' => $request]);

        return is_string($html) && $html !== '' ? $html : $defaultHtml;
    }
}

if (!function_exists('get_request_sections')) {
    function get_request_sections(int $user_id, array $filters = []): array
    {
        if (!function_exists('hooks')) {
            return [];
        }

        $args = [
            'user_id' => $user_id,
            'filters' => $filters,
        ];

        $sections = hooks()->apply_filters('requests_sections', [], $args);

        if (!is_array($sections)) {
            $sections = [];
        }

        foreach ($sections as $key => $sec) {
            if (!is_array($sec)) {
                unset($sections[$key]);
                continue;
            }

            $sections[$key]['slug']        = $sec['slug']        ?? $key;
            $sections[$key]['label']       = $sec['label']       ?? ucfirst((string)$sections[$key]['slug']);
            $sections[$key]['description'] = $sec['description'] ?? '';
            $sections[$key]['icon']        = $sec['icon']        ?? 'ti ti-list';
            $sections[$key]['url']         = $sec['url']         ?? site_url('requests/' . $sections[$key]['slug']);
            $sections[$key]['module']      = $sec['module']      ?? '';
            $sections[$key]['total']       = (int)($sec['total'] ?? 0);
            $sections[$key]['pending']     = (int)($sec['pending'] ?? 0);
            $sections[$key]['approved']    = (int)($sec['approved'] ?? 0);
            $sections[$key]['rejected']    = (int)($sec['rejected'] ?? 0);
        }

        uasort($sections, static function ($a, $b) {
            return strcmp(
                (string)($a['label'] ?? ''),
                (string)($b['label'] ?? '')
            );
        });

        return $sections;
    }
}

if (!function_exists('render_request_section_view')) {
    function render_request_section_view(string $slug, array $context = []): string
    {
        if (!function_exists('hooks')) {
            return '';
        }

        $args = array_merge(['slug' => $slug], $context);

        $html = hooks()->apply_filters('requests_section_view', '', $args);

        return is_string($html) ? $html : '';
    }
}

if (!function_exists('app_url')) {
    function app_url($uri = '') {
        return base_url('app/' . ltrim($uri, '/'));
    }
}

if (!function_exists('module_asset_url')) {
    function module_asset_url($module, $path) {
        return base_url("assets/modules/{$module}/" . ltrim($path, '/'));
    }
}

if (!function_exists('is_module_active')) {
    function is_module_active($module_name) {
        $CI =& get_instance();
        static $active_modules = null;

        if ($active_modules === null) {
            $CI->load->model('Modules_model');
            $active_modules = $CI->Modules_model->get_active_modules();
        }

        return in_array($module_name, $active_modules);
    }
}
if (!function_exists('parse_module_metadata')) {
    function parse_module_metadata($initFile)
    {
        $headers = [
            'Module Name'        => '',
            'Module URI'         => '',
            'Version'            => '',
            'Description'        => '',
            'Author'             => '',
            'Author URI'         => '',
            'Requires at least'  => '',
            'Requires Modules'   => '',
            'Settings Icon'      => '',
            'Settings Name'      => '',            
        ];

        $content = file_get_contents($initFile);

        foreach ($headers as $header => &$value) {
            if (preg_match('/' . preg_quote($header, '/') . ':\s*(.*)/i', $content, $match)) {
                $value = trim($match[1]);
            }
        }

        $headers['Requires Modules'] = !empty($headers['Requires Modules'])
            ? array_map('trim', explode(',', $headers['Requires Modules']))
            : [];

        return $headers;
    }
}

if (!function_exists('get_module_version')) {
    function get_module_version($module) {
        $init = module_dir_path($module, "$module.php");
        if (!file_exists($init)) {
            $init = module_dir_path($module, ucfirst($module) . '.php');
        }
        if (!file_exists($init)) return false;

        $meta = parse_module_metadata($init);
        return $meta['Version'] ?? false;
    }
}

if (!function_exists('is_module_enabled')) {
    function is_module_enabled($module) {
        return file_exists(module_dir_path($module, '.enabled'));
    }
}

if (!function_exists('get_enabled_modules')) {
    function get_enabled_modules() {
        $modules = [];
        $dir = FCPATH . 'modules/';

        if (!is_dir($dir)) return [];

        foreach (scandir($dir) as $folder) {
            if ($folder === '.' || $folder === '..') continue;

            $enabled = $dir . $folder . '/.enabled';
            if (is_dir($dir . $folder) && file_exists($enabled)) {
                $modules[] = $folder;
            }
        }

        return $modules;
    }
}

if (!function_exists('run_module_hook')) {
    function run_module_hook($type, $module) {
        hooks()->do_action("{$type}_module_{$module}");
    }
}

if (!function_exists('load_module_init')) {
    function load_module_init($module)
    {
        $initFiles = [
            module_dir_path($module, "$module.php"),
            module_dir_path($module, ucfirst($module) . '.php'),
        ];

        foreach ($initFiles as $file) {
            if (file_exists($file)) {
                include_once($file);
                break;
            }
        }
    }
}

function set_page_titles(&$data, $title)
{
    $data['title'] = $title;
    $data['page_title'] = $title;
}