<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ShopsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('shops')->insert([
            [
                'id' => 1,
                'user_id' => 1,
                'city_id' => 8,
                'name' => 'Royal pharmacy',
                'slug' => 'royal-pharmacy',
                'address' => '325/1, Main st, Borella.',
                'phone' => '710455879',
                'website' => 'rotalpharma.com',
                'latitude' => '6.914899',
                'longitude' => '79.877629',
                'image_path' => '/images/shops/royal',
            ],
            [
                'id' => 2,
                'user_id' => 1,
                'city_id' => 192,
                'name' => 'Induwara pharmacy',
                'slug' => 'induwara-pharmacy',
                'address' => '325/1, Main st, Galle.',
                'phone' => '710455879',
                'website' => null,
                'latitude' => '6.037298',
                'longitude' => '80.216119',
                'image_path' => '/images/shops/induwara',
            ],
        ]);
    }
}
