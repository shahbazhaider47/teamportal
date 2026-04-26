<?php defined('BASEPATH') or exit('No direct script access allowed');

class Merge_fields
{
    protected $ci;
    protected $registered = [];
    protected $instances = [];
    protected $catalog = null;
    protected $ctx = [];

    public function __construct()
    {
        $this->ci = &get_instance();
        
        // Load the interface first
        $interfacePath = APPPATH . 'libraries/merge_fields/MergeFieldProvider.php';
        if (file_exists($interfacePath)) {
            require_once $interfacePath;
        } else {
            log_message('error', 'MergeFieldProvider interface not found at: ' . $interfacePath);
        }

        //log_message('debug', 'Merge_fields constructor called');

        // Core registrations from config
        $core = $this->ci->config->item('merge_field_providers');
        //log_message('debug', 'Config providers: ' . print_r($core, true));
        
        if (is_array($core)) { 
            $this->registered = array_values(array_filter($core)); 
        }

        // Allow modules to register providers via hook
        $hooked = [];
        if (function_exists('hooks')) {
            $hooked = hooks()->apply_filters('register_merge_fields', []);
            //log_message('debug', 'Hook providers: ' . print_r($hooked, true));
        }
        
        if (is_array($hooked) && $hooked) {
            $this->registered = array_merge($this->registered, array_values(array_filter($hooked)));
        }

        // De-dup
        $this->registered = array_values(array_unique($this->registered));
        //log_message('debug', 'Final registered providers: ' . print_r($this->registered, true));
        
        // Pre-load all providers
        $this->providers();
    
    }

    public function context(array $ctx)
    {
        $this->ctx = $ctx;
        return $this;
    }

    public function map(): array
    {
        $map = [];
        $providers = $this->providers();
        //log_message('debug', 'Map called with providers: ' . print_r(array_keys($providers), true));
        
        foreach ($providers as $classBase => $provider) {
            try {
                //log_message('debug', 'Processing provider: ' . $classBase);
                
                if (!method_exists($provider, 'format')) { 
                    log_message('debug', 'Provider ' . $classBase . ' has no format method');
                    continue; 
                }
                
                $out = (array)$provider->format($this->ctx);
                //log_message('debug', 'Provider ' . $classBase . ' returned: ' . print_r($out, true));
                
                if ($out) { 
                    $map = array_merge($map, $out); 
                }
            } catch (\Throwable $e) {
                //log_message('error', '[merge_fields] format() failed in '.$classBase.': '.$e->getMessage());
            }
        }
        
        //log_message('debug', 'Final map: ' . print_r($map, true));
        return $map;
    }

    public function replace($subject, $body, array $map): array
    {
        $subject = (string)$subject;
        $body    = (string)$body;
        if ($map) {
            $subject = strtr($subject, $map);
            $body    = strtr($body, $map);
        }
        return [$subject, $body];
    }

    public function catalog($refresh = false): array
    {
        if ($this->catalog !== null && !$refresh) {
            return $this->catalog;
        }

        //log_message('debug', 'Building catalog...');
        $out = [];
        $providers = $this->providers();
        
        foreach ($providers as $classBase => $provider) {
            try {
                //log_message('debug', 'Catalog processing: ' . $classBase);
                
                if (!method_exists($provider, 'name') || !method_exists($provider, 'fields')) { 
                    log_message('debug', 'Provider ' . $classBase . ' missing name() or fields()');
                    continue; 
                }
                
                $group = (string)$provider->name();
                $fields = (array)$provider->fields();
                
                //log_message('debug', 'Group: ' . $group . ' with fields: ' . print_r($fields, true));
                
                if (!isset($out[$group])) {
                    $out[$group] = [];
                }
                
                foreach ($fields as $f) {
                    if (!isset($f['name'], $f['key'])) {
                        log_message('debug', 'Skipping invalid field: ' . print_r($f, true));
                        continue;
                    }
                    
                    $f['available'] = isset($f['available']) && is_array($f['available']) ? $f['available'] : [$group];
                    $out[$group][] = $f;
                }
            } catch (\Throwable $e) {
                //log_message('error', '[merge_fields] catalog failed in '.$classBase.': '.$e->getMessage());
            }
        }
        
        $this->catalog = $out;
        //log_message('debug', 'Final catalog: ' . print_r($out, true));
        return $out;
    }

    protected function providers(): array
    {
        if ($this->instances) {
            log_message('debug', 'Returning cached instances: ' . print_r(array_keys($this->instances), true));
            return $this->instances;
        }

        //log_message('debug', 'Loading providers from: ' . print_r($this->registered, true));
        
        foreach ($this->registered as $path) {
            $base = basename($path);
            $prop = strtolower($base);
            
            //log_message('debug', 'Attempting to load: ' . $path . ' as ' . $prop);
            
            if (isset($this->instances[$prop])) {
                log_message('debug', 'Already loaded: ' . $prop);
                continue;
            }

            $instance = $this->loadProvider($path, $base, $prop);
            if ($instance) {
                //log_message('debug', 'Successfully loaded: ' . $prop);
                $this->instances[$prop] = $instance;
            } else {
                //log_message('debug', 'Failed to load: ' . $prop);
            }
        }

        //log_message('debug', 'Final instances: ' . print_r(array_keys($this->instances), true));
        return $this->instances;
    }

    private function loadProvider(string $path, string $base, string $prop): ?object
    {
        // Try CI loader first
        try {
            //log_message('debug', 'Trying CI loader for: ' . $path);
            $this->ci->load->library($path);
            
            if (isset($this->ci->{$prop})) {
                log_message('debug', 'CI loader successful for: ' . $prop);
                return $this->ci->{$prop};
            }
        } catch (\Throwable $e) {
            //log_message('debug', 'CI loader failed for ' . $path . ': ' . $e->getMessage());
        }

        // Try direct class instantiation
        if (class_exists($base, false)) {
            //log_message('debug', 'Direct class exists: ' . $base);
            $instance = new $base();
            $this->ci->{$prop} = $instance;
            return $instance;
        }

        // Try module loading as last resort
        //log_message('debug', 'Trying module loading for: ' . $base);
        $instance = $this->loadFromModules($base, $path);
        if ($instance) {
            return $instance;
        }

        //log_message('error', "Failed to load merge field provider: {$path}");
        return null;
    }

    private function loadFromModules(string $base, string $hintPath): ?object
    {
        $moduleLocations = $this->getModuleLocations();
        //log_message('debug', 'Module locations: ' . print_r($moduleLocations, true));

        foreach ($moduleLocations as $loc) {
            $pattern = $loc . '*/libraries/merge_fields/' . $base . '.php';
            //log_message('debug', 'Looking for pattern: ' . $pattern);
            
            foreach (glob($pattern) ?: [] as $file) {
                log_message('debug', 'Found file: ' . $file);
                $module = basename(dirname(dirname(dirname($file))));
                $libPath = $module . '/merge_fields/' . $base;
                
                //log_message('debug', 'Module: ' . $module . ', Lib path: ' . $libPath);
                
                try {
                    $this->ci->load->library($libPath);
                    $prop = strtolower($base);
                    if (isset($this->ci->{$prop})) {
                        //log_message('debug', 'Module loader successful for: ' . $prop);
                        return $this->ci->{$prop};
                    }
                } catch (\Throwable $e) {
                    //log_message('debug', 'Module loader failed, trying direct require: ' . $e->getMessage());
                    
                    if (!class_exists($base, false)) {
                        require_once $file;
                    }
                    if (class_exists($base, false)) {
                        //log_message('debug', 'Direct require successful for: ' . $base);
                        $instance = new $base();
                        $this->ci->{strtolower($base)} = $instance;
                        return $instance;
                    }
                }
            }
        }
        
        //log_message('debug', 'Module loading completely failed for: ' . $base);
        return null;
    }

    private function getModuleLocations(): array
    {
        if (isset($this->ci->config) && method_exists($this->ci->config, 'item')) {
            $ml = $this->ci->config->item('modules_locations');
            if (is_array($ml) && $ml) {
                return array_keys($ml);
            }
        }
        
        return [
            APPPATH . 'modules/',
            FCPATH . 'modules/',
        ];
    }
}