<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DDD\Domain\Connections\Connection;

class ConnectionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $connections = [
            [
                'organization_id' => 1,
                'user_id' => 1,
                'service' => 'Google Analytics - Property',
                'account_name' => 'BloomCU',
                'name' => 'BloomCU - GA4',
                'uid' => 'properties/372256983',
                'token' => '{"scope": "https://www.googleapis.com/auth/analytics.readonly openid https://www.googleapis.com/auth/userinfo.email", "created": 1709098692, "id_token": "123", "expires_in": 3599, "token_type": "Bearer", "access_token": "123", "refresh_token": "123"}',
            ],
        ];

        foreach ($connections as $connection) {
            Connection::create($connection);
        }
    }
}
