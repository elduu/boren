<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Building;
use App\Models\Floor;

class FloorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $commercialCategory = Category::where('name', 'Commercial')->first();

        if ($commercialCategory) {
            // Get buildings under the Commercial category
            $commercialBuildings = Building::where('category_id', $commercialCategory->id)->get();

            foreach ($commercialBuildings as $building) {
                Floor::create([
                    'building_id' => $building->id,
                    'category_id' => $commercialCategory->id,
                    'name' => 'Ground Floor',
                ]);
                // Create only Floor 1 for each building
                Floor::create([
                    'building_id' => $building->id,
                    'category_id' => $commercialCategory->id,
                    'name' => 'Floor 1',
                ]);

                Floor::create([
                    'building_id' => $building->id,
                    'category_id' => $commercialCategory->id,
                    'name' => 'Floor 2',
                ]);
            }
        }
    }
}