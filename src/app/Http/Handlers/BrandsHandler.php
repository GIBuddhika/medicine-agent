<?php

namespace App\Http\Handlers;

use App\Constants\ValidationMessageConstants;
use App\Models\Brand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BrandsHandler
{
    public function getNames(array $data)
    {
        $rules = [
            'search_text' => 'required',
        ];

        $messages = [
            'required' => ValidationMessageConstants::Required,
        ];

        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator, 400);
        }

        $searchText = $data['search_text'];

        $brands = Brand::where('name', 'like', '%' . $searchText . '%')
            ->take(10)
            ->select('name')
            ->get();

        return $brands->pluck('name');
    }

    public function getOrCreateBrand(string $brandName)
    {
        $brand = DB::table('brands')
            ->where('name', $brandName)
            ->first();

        if (!$brand) {
            $brand = $this->create($brandName);
        }

        return $brand;
    }

    public function create($name): brand
    {
        $brand = new brand();
        $brand->name = $name;
        $brand->save();
        return $brand;
    }
}
