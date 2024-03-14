<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DDD\Domain\Connections\Connection;

class LocalConnectionsSeeder extends Seeder
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
                'account_name' => 'CUofGeorgia - BloomCU',
                'name' => 'cuofga.org - GA4',
                'uid' => 'properties/312479292',
                'token' => [
                    'scope' => 'https://www.googleapis.com/auth/analytics.readonly openid https://www.googleapis.com/auth/userinfo.email',
                    'created'=> 1710448802, 
                    'id_token'=> config('seeding.connection_id_token'), 
                    'expires_in'=> 3599, 
                    'token_type'=> 'Bearer', 
                    'access_token'=> config('seeding.connection_access_token'), 
                    'refresh_token'=> config('seeding.connection_refresh_token')
                ],
            ],
        ];

        foreach ($connections as $connection) {
            Connection::create($connection);
        }
    }
}
