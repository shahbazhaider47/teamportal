<?php
defined('BASEPATH') OR exit('No direct script access allowed');


// application/config/modules_routes.php

$modulesPath = APP_MODULES_PATH;

foreach (glob($modulesPath . '*', GLOB_ONLYDIR) as $modulePath) {
    $routesFile = $modulePath . '/config/routes.php';
    if (file_exists($routesFile)) {
        include_once($routesFile);
    }
}

