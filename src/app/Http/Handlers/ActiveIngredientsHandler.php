<?php

namespace App\Http\Handlers;

use App\Constants\ValidationMessageConstants;
use App\Models\ActiveIngredient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ActiveIngredientsHandler
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

        $activeIngredients = ActiveIngredient::where('name', 'like', '%' . $searchText . '%')
            ->take(10)
            ->select('name')
            ->get();

        return $activeIngredients->pluck('name');
    }

    public function getOrCreateActiveIngredients(array $activeIngredientList)
    {
        $activeIngredients = DB::table('active_ingredients')
            ->whereIn('name', $activeIngredientList)
            ->get();

        $existingActiveIngredientNames = $activeIngredients->pluck('name')->toArray();

        $activeIngredientIds = $activeIngredients->map(function ($item) {
            return ['active_ingredient_id' => $item->id];
        })->all();

        foreach ($activeIngredientList as $activeIngredient) {
            if (array_search($activeIngredient, $existingActiveIngredientNames) === false) {
                $newActiveIngredient =  $this->create(['name' => $activeIngredient]);
                $activeIngredientIds[]['active_ingredient_id'] = $newActiveIngredient->id;
            }
        }
        return $activeIngredientIds;
    }

    public function create($data): ActiveIngredient
    {
        $activeIngredient = new ActiveIngredient();
        $activeIngredient->name = $data['name'];
        $activeIngredient->save();
        return $activeIngredient;
    }
}
