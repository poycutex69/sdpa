<?php

namespace Database\Seeders;

use App\Models\Issue;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(CategorySeeder::class);

        if (Issue::query()->doesntExist()) {
            $this->call(IssueSeeder::class);
        }
    }
}
