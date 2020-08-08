<?php

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('base64', function ($attribute, $value, $parameters, $validator) {
            if (strpos($value, "data:image/png;base64,") !== false) {
                $base64Text = str_replace("data:image/png;base64,", "", $value);
                if (strlen($base64Text) % 4 == 0) { //base64 string should divisible by 4
                    return true;
                }
                return false;
            }
            else if (strpos($value, "data:image/jpeg;base64,") !== false) {
                $base64Text = str_replace("data:image/jpeg;base64,", "", $value);
                if (strlen($base64Text) % 4 == 0) { //base64 string should divisible by 4
                    return true;
                }
                return false;
            }
            return false;
        });
    }
}
