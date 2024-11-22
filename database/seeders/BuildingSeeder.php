<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Building;
class BuildingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $categories = Category::all();

        foreach ($categories as $category) {
            Building::create([
                'category_id' => $category->id,
                'name' => 'Building 1',
            ]);

            Building::create([
                'category_id' => $category->id,
                'name' => 'Building 2',
            ]);
        }
    }
}