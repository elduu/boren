<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $categories = [
            ['name' => 'Commercial'],
            ['name' => 'Apartment_buyer'],
            ['name' => 'utilities'],
          
          
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
