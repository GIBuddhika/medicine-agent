<?php

use App\Models\District;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictsSeeder extends Seeder
{
    public function run()
    {
        DB::table('districts')->insert([
            ['id' => 1, 'name' => 'Colombo'],
            ['id' => 2, 'name' => 'Gampaha'],
            ['id' => 3, 'name' => 'Kandy'],
            ['id' => 4, 'name' => 'Kurunegala'],
            ['id' => 5, 'name' => 'Kalutara'],
            ['id' => 6, 'name' => 'Galle'],
            ['id' => 7, 'name' => 'Ratnapura'],
            ['id' => 8, 'name' => 'Matara'],
            ['id' => 9, 'name' => 'Anuradhapura'],
            ['id' => 10, 'name' => 'Puttalam'],
            ['id' => 11, 'name' => 'Kegalle'],
            ['id' => 12, 'name' => 'Jaffna'],
            ['id' => 13, 'name' => 'Badulla'],
            ['id' => 14, 'name' => 'Ampara'],
            ['id' => 15, 'name' => 'Hambantota'],
            ['id' => 16, 'name' => 'Batticaloa'],
            ['id' => 17, 'name' => 'Matale'],
            ['id' => 18, 'name' => 'Trincomalee'],
            ['id' => 19, 'name' => 'Moneragala'],
            ['id' => 20, 'name' => 'Polonnaruwa'],
            ['id' => 21, 'name' => 'Nuwara Eliya'],
            ['id' => 22, 'name' => 'Vavuniya'],
            ['id' => 23, 'name' => 'Kilinochchi'],
            ['id' => 24, 'name' => 'Mannar'],
            ['id' => 25, 'name' => 'Mullativu']
        ]);
    }
}
