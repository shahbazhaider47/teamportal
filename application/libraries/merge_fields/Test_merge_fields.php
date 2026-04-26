<?php defined('BASEPATH') or exit('No direct script access allowed');

class Test_merge_fields implements MergeFieldProvider
{
    public function name(): string 
    { 
        return 'test'; 
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'Test Field', 
                'key' => '{test.field}', 
                'available' => ['test', 'other']
            ],
            [
                'name' => 'Another Test', 
                'key' => '{test.another}', 
                'available' => ['test']
            ],
        ];
    }

    public function format(array $ctx): array
    {
        return [
            '{test.field}' => 'This is a test value',
            '{test.another}' => 'Another test value',
        ];
    }
}