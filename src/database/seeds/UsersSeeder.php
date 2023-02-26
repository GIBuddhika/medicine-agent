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
            ['id' => 1, 'email' => 'admin1@test.com', 'phone' => '71022345', 'password' => Hash::make('123'), 'is_admin' => 1],
            ['id' => 2, 'email' => 'customer1@test.com', 'phone' => '71022345', 'password' => Hash::make('123'), 'is_admin' => 0],
        ]);
    }
}
