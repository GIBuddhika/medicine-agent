<?php

namespace App\Http\Handlers;

use App\Constants\SessionConstants;
use App\Constants\ValidationMessageConstants;
use App\Models\PersonalListing;
use App\Models\UserMeta;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PersonalListingHandler
{
    public function create($data)
    {
        try {
            $rules = [
                'city_id' => 'required|integer|exists:cities,id|nullable',
                'address' => 'required',
                'latitude' => array('required', 'numeric', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'),
                'longitude' => array('required', 'numeric', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'),
            ];

            $messages = [
                'required' => ValidationMessageConstants::Required,
                'integer' => ValidationMessageConstants::IntegerValue,
                'exists' => ValidationMessageConstants::NotFound,
                'numeric' => ValidationMessageConstants::Invalid,
                'required_if' => ValidationMessageConstants::Required,
            ];

            $validator = Validator::make($data, $rules, $messages);
            if ($validator->fails()) {
                throw new ValidationException($validator, 400);
            }

            $personalListing = new PersonalListing();
            $personalListing->user_id = $data['user_id'];
            $personalListing->address = $data['address'];
            $personalListing->latitude = $data['latitude'];
            $personalListing->longitude = $data['longitude'];
            $personalListing->save();

            return $personalListing;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
