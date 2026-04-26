<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Treegen extends App_Controller
{
    public function index()
    {
        $targetDir = FCPATH; // public root folder, or use APPPATH for application dir
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Directory structure audit for: " . realpath($targetDir) . PHP_EOL;
        echo str_repeat('=', 70) . PHP_EOL;
        $this->buildTree($targetDir);
    }

    private function buildTree($dir, $prefix = '')
    {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            echo $prefix . '|-- ' . $item . PHP_EOL;
            if (is_dir($path)) {
                $this->buildTree($path, $prefix . '    ');
            }
        }
    }

    public function test_pusher()
{
    $this->load->library('app_pusher');

    $this->app_pusher->trigger('private-user-245', 'new-notification', [
        'title'   => 'Test Alert',
        'message' => 'This is a real-time test notification from controller.'
    ]);

    echo "Notification sent!";
}


}
