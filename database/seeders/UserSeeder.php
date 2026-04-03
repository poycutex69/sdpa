<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@admin.com',
                'password' => 'password',
                'level' => User::LEVEL_ADMIN,
            ],
            [
                'name' => 'Normal User One',
                'email' => 'user1@user.com',
                'password' => 'password',
                'level' => User::LEVEL_USER,
            ],
            [
                'name' => 'Normal User Two',
                'email' => 'user2@user.com',
                'password' => 'password',
                'level' => User::LEVEL_USER,
            ],
            [
                'name' => 'Normal User Three',
                'email' => 'user3@user.com',
                'password' => 'password',
                'level' => User::LEVEL_USER,
            ],
            [
                'name' => 'Normal User Four',
                'email' => 'user4@user.com',
                'password' => 'password',
                'level' => User::LEVEL_USER,
            ],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
