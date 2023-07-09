<?php

namespace App\Http\Handlers;

use App\Constants\ValidationMessageConstants;
use App\Models\PersonalListing;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PersonalListingHandler
{
    public function create($data)
    {
        try {
            $rules = [
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
                throw new ValidationException($validator);
            }

            $personalListing = new PersonalListing();
            $personalListing->user_id = $data['user_id'];
            $personalListing->address = $data['address'];
            $personalListing->latitude = $data['latitude'];
            $personalListing->longitude = $data['longitude'];
            $personalListing->save();

            return $personalListing;
        } catch (ValidationException $th) {
            throw new ValidationException($validator);
        } catch (\Throwable $th) {
            throw new Exception($th);
        }
    }
}
