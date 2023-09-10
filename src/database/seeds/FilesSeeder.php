<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FilesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('files')->insert([
            [
                'id' => 1,
                'item_id' => 1,
                'name' => 'panadol',
                'location' => '/images/items/16795871631',
                'is_default' => true,
            ],
            [
                'id' => 2,
                'item_id' => 2,
                'name' => 'wheelchair',
                'location' => '/images/items/16833803931',
                'is_default' => true,
            ],
        ]);
    }
}
