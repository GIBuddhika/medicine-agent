<?php

namespace App\Http\Handlers;

use App\Models\City;
use App\Models\District;

class DistrictsHandler
{
    public function getAll()
    {
        $districts = District::get();
        return $districts;
    }

    public function getCities(int $id)
    {
        $cities = City::where('district_id', $id)
            ->get();
        return $cities;
    }
}
