<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(DistrictsSeeder::class);
        $this->call(CitiesSeeder::class);
        $this->call(UsersSeeder::class);
    }
}
