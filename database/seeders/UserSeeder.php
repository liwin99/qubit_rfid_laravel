<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * This seeder will create new user and generate bearer token for the new user
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            [
                'name' => 'Test',
                'email' => 'test@email.com',
            ],
            [
                'password' => Hash::make('Test123')
            ]
        );

        // this will generate Bearer token, default 1 year, change at env
        // to enable this, uncomment line 26 in AuthServiceProvider
        $token = $user->createToken('LongTermToken');
        print_r('Bearer token: ');
        print_r($token->accessToken);
    }
}
