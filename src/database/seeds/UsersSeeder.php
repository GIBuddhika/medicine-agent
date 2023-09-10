<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            [
                'id' => 1,
                'name' => 'Admin one',
                'email' => 'admin1@test.com',
                'phone' => '71022345',
                'password' => Hash::make('123'),
                'is_admin' => 1,
                'admin_account_type' => 0,
                'owner_id' => null,
            ],
            [
                'id' => 2,
                'name' => 'Customer one',
                'email' => 'customer1@test.com',
                'phone' => '71022345',
                'password' => Hash::make('123'),
                'is_admin' => 0,
                'admin_account_type' => null,
                'owner_id' => null,
            ],
            [
                'id' => 3,
                'name' => 'Shop admin one',
                'email' => 'shopadmin1@test.com',
                'phone' => '71022345',
                'password' => Hash::make('123'),
                'is_admin' => 1,
                'owner_id' => 1,
                'admin_account_type' => null
            ],
            [
                'id' => 4,
                'name' => 'Individual seller',
                'email' => 'individualserller@test.com',
                'phone' => '71022345',
                'password' => Hash::make('123'),
                'is_admin' => 1,
                'owner_id' => null,
                'admin_account_type' => 1
            ],
        ]);
    }
}
