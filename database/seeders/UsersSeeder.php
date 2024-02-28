<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DDD\Domain\Base\Users\User;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john@doe.com',
                'role' => 'editor',
                'organization_id' => 1,
                'password' => bcrypt('8;lkqthn35k;j6ltng3q5k;sG2'),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
