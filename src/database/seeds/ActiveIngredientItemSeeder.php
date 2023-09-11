<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActiveIngredientItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('active_ingredient_item')->insert([
            [
                'id' => 1,
                'item_id' => 1,
                'active_ingredient_id' => 1,
            ],
            [
                'id' => 2,
                'item_id' => 3,
                'active_ingredient_id' => 3,
            ],
            [
                'id' => 3,
                'item_id' => 4,
                'active_ingredient_id' => 4,
            ],
        ]);
    }
}
