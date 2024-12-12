<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
       
            $this->call(CategorySeeder::class);
            $this->call(BuildingSeeder::class);
            $this->call(FloorSeeder::class);
            $this->call(RolesAndPermissionsSeeder::class);
            $this->call(UserSeeder::class);
            //$this->call( EmailTemplateSeeder::class);

            
    }
}
