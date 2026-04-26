<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * App_merge_fields
 *
 * Lightweight, app-agnostic merge fields manager.
 * - Modules register their merge field classes via the 'register_merge_fields' hook.
 * - Each merge field class must implement:
 *     - name(): string               // canonical group name, e.g. 'user', 'company', 'ticket'
 *     - get():  array                // list of fields with ['name','key','available'=>[groups]]
 *     - format(...$params): array    // (optional) returns key=>value map for replacement context
 *
 * Example registration (core or module init):
 *   hooks()->add_filter('register_merge_fields', function ($paths) {
 *       $paths[] = 'merge_fields/Company_merge_fields';          // application/libraries/merge_fields/...
 *       $paths[] = 'support/merge_fields/Ticket_merge_fields';   // modules/support/libraries/merge_fields/...
 *       return $paths;
 *   });
 */
class App_merge_fields
{
    /** @var CI_Controller */
    protected $ci;

    /** @var array<string,array> Fields loaded from concrete merge field classes keyed by group name */
    protected $fields = [];

    /** @var string[] List of CI loader paths, e.g., 'merge_fields/Company_merge_fields' */
    protected $registered = [];

    /** @var string|null Current "for" name when extended */
    protected $for = null;

    /** @var array|null Cached result of all() */
    protected $all_merge_fields = null;

    /** @var bool */
    private $classes_for_merge_fields_initialized = false;

    public function __construct()
    {
        $this->ci = &get_instance();

        // Allow subclasses to populate via build()
        if (method_exists($this, 'build')) {
            $this->set($this->build());
        } else {
            $registered = hooks()->apply_filters('register_merge_fields', []);
            $this->registered = is_array($registered) ? $registered : [];
        }
    }

    /** Resolve/return fields for a specific group by name */
    public function get_by_name($name)
    {
        foreach ($this->all() as $feature) {
            if (isset($feature[$name])) {
                return $feature[$name];
            }
        }
        return [];
    }

    /**
     * Build a replacement map by calling format() on the matching class.
     *
     * $name can be a group name (e.g., 'company', 'ticket') or a class basename (e.g., 'Company_merge_fields').
     */
    public function format_feature($name, ...$params)
    {
        if ($this->classes_for_merge_fields_initialized === false) {
            $this->all(); // ensure discovery & load
            $this->classes_for_merge_fields_initialized = true;
        }

        $baseName    = basename((string)$name);
        $propName    = $this->prop($baseName);
        $targetGroup = $baseName;

        // If not loaded yet, try to resolve by group or class among registered paths
        if (!isset($this->ci->{$propName})) {
            foreach ($this->registered as $path) {
                $bn  = basename($path);
                $pbn = $this->prop($bn);

                // Load this registration (no-op if already loaded)
                $this->load($path);

                // Map by group name (preferred)
                if (isset($this->ci->{$pbn}) && method_exists($this->ci->{$pbn}, 'name')) {
                    if ($this->ci->{$pbn}->name() === $baseName) {
                        $propName    = $pbn;
                        $targetGroup = $baseName;
                        break;
                    }
                }

                // Or map by class basename
                if ($bn === $baseName && isset($this->ci->{$pbn})) {
                    $propName    = $pbn;
                    $targetGroup = method_exists($this->ci->{$pbn}, 'name') ? $this->ci->{$pbn}->name() : $baseName;
                    break;
                }
            }
        }

        if (!isset($this->ci->{$propName})) {
            return [];
        }

        if (method_exists($this->ci->{$propName}, 'name')) {
            $targetGroup = (string)$this->ci->{$propName}->name();
        }

        $merge_fields     = $this->get_by_name($targetGroup);
        $uniqueFormatters = [];
        $uniqueClassLoad  = [];

        foreach ($merge_fields as $field) {
            if (($field['format']['base_name'] ?? '') === $propName) {
                $uniqueFormatters[]               = $field['format']['base_name'];
                $uniqueClassLoad[$propName]       = $field['format']['file'] ?? null;
            }
        }

        $uniqueFormatters = array_values(array_unique($uniqueFormatters));
        $formatted = [];

        foreach ($uniqueFormatters as $classProp) {
            // Ensure loaded (again) if needed
            if (!isset($this->ci->{$classProp})) {
                $path = $uniqueClassLoad[$classProp] ?? null;
                if ($path) $this->load($path);
            }
            if (isset($this->ci->{$classProp}) && method_exists($this->ci->{$classProp}, 'format')) {
                $map = $this->ci->{$classProp}->format(...$params);
                if (is_array($map)) $formatted = array_merge($formatted, $map);
            }
        }

        return $formatted;
    }

    /** Return fields for the current/explicit "for" group */
    public function get($name = null)
    {
        $for = $name ? $name : $this->name();
        return isset($this->fields[$for]) ? $this->fields[$for] : [];
    }

    /** Set fields for current "for" group */
    public function set($fields)
    {
        $for = $this->name();
        if (!isset($this->fields[$for])) {
            $this->fields[$for] = $fields;
        } else {
            $this->fields[$for][] = $fields;
        }
        return $this;
    }

    /** Register one or many loader paths */
    public function register($loadPath)
    {
        if (is_array($loadPath)) {
            foreach ($loadPath as $p) { $this->register($p); }
            return $this;
        }
        if (is_string($loadPath) && $loadPath !== '') {
            $this->registered[] = $loadPath;
        }
        return $this;
    }

    /** All registered loader paths */
    public function get_registered()
    {
        return $this->registered;
    }

    /**
     * Load and return **all** merge fields grouped by feature name.
     * Structure: [ [ '<groupName>' => [ {name,key,available,format}, ... ] ], ... ]
     */
    public function all($reBuild = false)
    {
        if ($reBuild !== true && $this->all_merge_fields !== null) {
            return $this->all_merge_fields;
        }

        $available  = [];
        $registered = $this->get_registered();

        foreach ($registered as $merge_field) {
            // load() returns CI property name (lowercased)
            $propName = $this->load($merge_field);

            if (!isset($this->ci->{$propName})) {
                log_message('debug', "[merge_fields] Skipped '$merge_field' (class not loaded)");
                continue;
            }

            if (!method_exists($this->ci->{$propName}, 'get') || !method_exists($this->ci->{$propName}, 'name')) {
                log_message('debug', "[merge_fields] Skipped '$merge_field' (missing name()/get())");
                continue;
            }

            $fields = $this->ci->{$propName}->get();
            if (!is_array($fields)) { $fields = []; }

            $groupName = (string)$this->ci->{$propName}->name();
            $index     = $this->merge_field_exists_by_name($available, $groupName);

            // Attach metadata for formatting later
            $formatMeta = [
                'base_name' => $propName,   // property name (lowercased)
                'file'      => $merge_field,
            ];
            foreach ($fields as $k => $field) {
                if (!is_array($fields[$k])) $fields[$k] = [];
                $fields[$k]['format'] = $formatMeta;
            }

            if ($index !== false) {
                $i = (int)$index;
                $available[$i][$groupName] = array_merge($available[$i][$groupName], $fields);
            } else {
                $available[][$groupName] = $fields;
            }
        }

        // Safe/default $format to satisfy downstream calls
        $formatSafe = [
            'base_name' => 'custom_fields',
            'file'      => 'merge_fields/custom_fields',
        ];

        // Optional: enrich with custom fields (no-op if helper absent)
        $available = $this->apply_custom_fields($available, $formatSafe);

        $this->all_merge_fields = $available;
        return hooks()->apply_filters('available_merge_fields', $available);
    }

    /** Infer current group name when class is extended */
    public function name()
    {
        if ($this->for === null) {
            // If subclassed as Something_merge_fields, return 'something'
            $this->for = strtolower(strbefore(get_class($this), '_merge_fields'));
        }
        return $this->for;
    }

    /**
     * Load a merge field library by CI loader path and return its **property** name (lowercased).
     * Example: 'merge_fields/Company_merge_fields' -> loads and exposes $this->ci->company_merge_fields
     */
    public function load($merge_field)
    {
        $baseName = basename($merge_field);
        $propName = $this->prop($baseName);

        // Already loaded?
        if ((class_exists($baseName, false) || isset($this->ci->{$propName})) && isset($this->ci->{$propName})) {
            return $propName;
        }

        // 1) Try via CI loader as provided (works for 'support/merge_fields/Ticket_merge_fields')
        try {
            $this->ci->load->library($merge_field);
            if (isset($this->ci->{$propName}) || class_exists($baseName, false)) {
                if (!isset($this->ci->{$propName}) && class_exists($baseName, false)) {
                    $this->ci->{$propName} = new $baseName();
                }
                return $propName;
            }
        } catch (\Throwable $e) {
            // continue to module scan
        }

        // 2) If no slash given, scan modules/*/libraries/merge_fields/{Class}.php
        if (strpos($merge_field, '/') === false) {
            $moduleLocations = [];
            if (isset($this->ci->config) && method_exists($this->ci->config, 'item')) {
                $ml = $this->ci->config->item('modules_locations');
                if (is_array($ml) && $ml) $moduleLocations = array_keys($ml);
            }
            if (!$moduleLocations) {
                $moduleLocations = [
                    APPPATH . 'modules' . DIRECTORY_SEPARATOR,
                    FCPATH  . 'modules' . DIRECTORY_SEPARATOR,
                ];
            }

            foreach ($moduleLocations as $loc) {
                $pattern = $loc . '*' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'merge_fields' . DIRECTORY_SEPARATOR . $baseName . '.php';
                foreach (glob($pattern) ?: [] as $file) {
                    $moduleName = basename(dirname(dirname(dirname($file)))); // modules/{module}/libraries/merge_fields
                    $libPath    = $moduleName . '/merge_fields/' . $baseName;

                    try {
                        $this->ci->load->library($libPath);
                        if (isset($this->ci->{$propName}) || class_exists($baseName, false)) {
                            if (!isset($this->ci->{$propName}) && class_exists($baseName, false)) {
                                $this->ci->{$propName} = new $baseName();
                            }
                            return $propName;
                        }
                    } catch (\Throwable $e) {
                        if (!class_exists($baseName, false)) {
                            require_once $file;
                        }
                        if (class_exists($baseName, false)) {
                            $this->ci->{$propName} = new $baseName();
                            return $propName;
                        }
                    }
                }
            }
        }

        // 3) Direct file fallback (absolute/relative PHP file)
        if (is_file($merge_field) && pathinfo($merge_field, PATHINFO_EXTENSION) === 'php') {
            require_once $merge_field;
            if (class_exists($baseName, false)) {
                $this->ci->{$propName} = new $baseName();
                return $propName;
            }
        }

        log_message('error', "[merge_fields] Unable to load requested class: {$baseName} from '{$merge_field}'");
        return $propName; // return normalized property name anyway
    }

    /**
     * Return a flat array of groups: [['name','key','available'=>[...] , 'format'=>...], ...]
     * - $primary:     string|string[]  groups you primarily want to show
     * - $additional:  string|string[]  secondary groups (optional)
     * - $exclude_keys:array            keys to exclude (optional)
     */
    public function get_flat($primary, $additional = [], $exclude_keys = [])
    {
        if (!is_array($primary))     { $primary     = [$primary]; }
        if (!is_array($additional))  { $additional  = [$additional]; }
        if (!is_array($exclude_keys)){ $exclude_keys= [$exclude_keys]; }

        $registered = $this->all();
        $flat = [];

        foreach ($registered as $val) {
            foreach ($val as $type => $fields) {
                if (in_array($type, $primary, true)) {
                    if ($availableFields = $this->check_availability($fields, $type, $exclude_keys)) {
                        $flat[] = $availableFields;
                    }
                } elseif (in_array($type, $additional, true)) {
                    if ($type === 'other') {
                        $other = [];
                        foreach ($fields as $field) {
                            if (!in_array($field['key'] ?? '', $exclude_keys, true)) {
                                $other[] = $field;
                            }
                        }
                        $flat[] = $other;
                    } else {
                        if ($availableFields = $this->check_availability($fields, $type, $exclude_keys)) {
                            $flat[] = $availableFields;
                        }
                    }
                }
            }
        }

        return $flat;
    }

    /** Return index if a group already exists in $available */
    private function merge_field_exists_by_name($available, $name)
    {
        foreach ($available as $key => $merge_fields) {
            if (array_key_exists($name, $merge_fields)) {
                return (string)$key;
            }
        }
        return false;
    }

    /** Filter fields by availability + exclusion */
    private function check_availability($fields, $type, $exclude_keys)
    {
        $retVal = [];
        foreach ($fields as $available) {
            $availList = $available['available'] ?? [];
            if (!is_array($availList)) $availList = [];

            foreach ($availList as $av) {
                $hasName = !empty($available['name'] ?? '');
                $key     = (string)($available['key'] ?? '');
                if ($av === $type && $hasName && !in_array($key, $exclude_keys, true)) {
                    $retVal[] = $available;
                }
            }
        }
        return count($retVal) > 0 ? $retVal : false;
    }

    /** Normalize CI library property name */
    private function prop(string $classBase): string
    {
        return strtolower($classBase);
    }
}
