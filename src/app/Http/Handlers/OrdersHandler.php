<?php

namespace App\Http\Handlers;

use App\Constants\ProductCategoryConstants;
use App\Constants\SessionConstants;
use App\Constants\ValidationMessageConstants;
use App\Models\City;
use App\Models\File;
use App\Models\Item;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrdersHandler
{
    public function create($data)
    {
        dd($data);
    }
}
