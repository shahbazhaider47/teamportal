<?php

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\zip\Unzip;

class App_module_installer
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * Upload module
     * @return array
     */
public function from_upload()
{
    if (isset($_FILES['module']) && upload_error_message($_FILES['module']['error'])) {
        return [
            'error'   => upload_error_message($_FILES['module']['error']),
            'success' => false,
        ];
    }

    if (isset($_FILES['module']['name'])) {
        hooks()->do_action('pre_upload_module', $_FILES['module']);

        $response = ['success' => false, 'error' => ''];
        $uploadedTmpZipPath = $_FILES['module']['tmp_name'];
        $unzip = new Unzip();
        $moduleTemporaryDir = get_temp_dir() . time() . '/';

        if (!is_dir($moduleTemporaryDir)) {
            mkdir($moduleTemporaryDir, 0755, true);
        }

        try {
            $unzip->extract($uploadedTmpZipPath, $moduleTemporaryDir);

            if ($this->check_module($moduleTemporaryDir) === false) {
                $response['error'] = 'No valid module is found.';
            } else {
                $unzip->extract($uploadedTmpZipPath, APP_MODULES_PATH);

                // NOW re-initialize so new module shows up immediately
                if (method_exists($this->ci->app_modules, 'initialize')) {
                    $this->ci->app_modules->initialize();
                }

log_message('error', 'MODULES PATH: ' . APP_MODULES_PATH);
log_message('error', 'MODULES FOUND: ' . print_r($this->ci->app_modules->get(), true));


                hooks()->do_action('module_installed', $_FILES['module']);
                $response['success'] = true;
            }

            $this->clean_up_dir($moduleTemporaryDir);
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
        }

        return $response;
    }
}


    public function check_module($source)
    {
        // Check the folder contains at least 1 valid module.
        $modules_found = false;

        $files = get_dir_contents($source);

        if ($files) {
            foreach ($files as $file) {
                if (endsWith($file, '.php')) {
                    $info = $this->ci->app_modules->get_headers($file);
                    if (isset($info['module_name']) && !empty($info['module_name'])) {
                        $modules_found = true;

                        break;
                    }
                }
            }
        }

        if (!$modules_found) {
            return false;
        }

        return $source;
    }

    private function clean_up_dir($source)
    {
        delete_files($source);
        delete_dir($source);
    }
}