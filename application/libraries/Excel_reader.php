<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'third_party/PhpSpreadsheet/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class Excel_reader {
    
    public function load($file_path) {
        try {
            return IOFactory::load($file_path);
        } catch (Exception $e) {
            throw new Exception("Error loading spreadsheet: " . $e->getMessage());
        }
    }
}