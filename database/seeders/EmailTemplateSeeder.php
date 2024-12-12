<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
  

    public function run()
    {
        $templates = [
            ['name' => 'Template 1', 'view' => 'emails.template1', 'preview_image' => 'template1.jpg'],
            ['name' => 'Template 2', 'view' => 'emails.template2', 'preview_image' => 'template2.jpg'],
            ['name' => 'Template 3', 'view' => 'emails.template3', 'preview_image' => 'template3.jpg'],
            ['name' => 'Template 4', 'view' => 'emails.template4', 'preview_image' => 'template4.jpg'],
            ['name' => 'Template 5', 'view' => 'emails.template5', 'preview_image' => 'template5.jpg'],
        ];
    
        foreach ($templates as $template) {
            EmailTemplate::create($template);
        }
    }
    
}
