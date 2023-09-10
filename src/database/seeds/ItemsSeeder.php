<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('personal_listings')->insert([
            [
                'id' => 1,
                'user_id' => 4,
                'address' => '325/1, Main st, Borella.',
                'latitude' => '6.914899',
                'longitude' => '79.877629',
            ],
        ]);

        DB::table('items')->insert([
            [
                'id' => 1,
                'user_id' => 1,
                'city_id' => 8,
                'is_a_shop_listing' => true,
                'shop_id' => 1,
                'personal_listing_id' => null,
                'name' => 'Panadol',
                'slug' => 'panadol',
                'description' => 'This is panadol 500mg tablet. Each pack has 20 tablets.',
                'category_id' => 1,
                'quantity' => 100,
                'created_at' => Carbon::now(),
            ],
            [
                'id' => 2,
                'user_id' => 4,
                'city_id' => 8,
                'is_a_shop_listing' => false,
                'shop_id' => null,
                'personal_listing_id' => 1,
                'name' => 'Wheelchair',
                'slug' => 'Wheelchair',
                'description' => 'This is wheelchair bought in 2010. Brand ABC.',
                'category_id' => 2,
                'quantity' => 1,
                'created_at' => Carbon::now(),
            ],
        ]);

        DB::table('sellable_items')->insert([
            [
                'id' => 1,
                'item_id' => 1,
                'retail_price' => 25,
                'wholesale_price' => 20,
                'wholesale_minimum_quantity' => 20,
            ],
        ]);

        DB::table('rentable_items')->insert([
            [
                'id' => 1,
                'item_id' => 2,
                'price_per_month' => 1500,
            ],
        ]);
    }
}
