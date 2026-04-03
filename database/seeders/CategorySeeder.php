<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = ['Technical', 'Billing', 'Operations', 'Bugs', 'Feature Requests'];

        foreach ($defaults as $name) {
            Category::query()->firstOrCreate(['name' => $name]);
        }
    }
}
