<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('brands')->insert([
            [
                'id' => 1,
                'name' => 'Panadol',
            ],
            [
                'id' => 2,
                'name' => 'Calpol',
            ],
            [
                'id' => 3,
                'name' => 'Celin',
            ],
            [
                'id' => 4,
                'name' => 'Piriton',
            ],
            [
                'id' => 5,
                'name' => 'Soframycin',
            ],
            [
                'id' => 6,
                'name' => 'Amoxil',
            ],
            [
                'id' => 7,
                'name' => 'SKIIDDII',
            ],
        ]);
    }
}
