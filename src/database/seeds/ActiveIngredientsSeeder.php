<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActiveIngredientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('active_ingredients')->insert([
            [
                'id' => 1,
                'name' => 'Paracetemol',
            ],
            [
                'id' => 2,
                'name' => 'Ascorbic Acid',
            ],
            [
                'id' => 3,
                'name' => 'Chlorphenamine',
            ],
            [
                'id' => 4,
                'name' => 'Framycetin',
            ],
            [
                'id' => 5,
                'name' => 'Amoxicillin',
            ],
        ]);
    }
}
