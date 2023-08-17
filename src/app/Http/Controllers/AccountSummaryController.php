<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Handlers\AccountSummaryHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AccountSummaryController extends Controller
{
    public function filterAccountSummary(Request $request)
    {
        try {
            $accountSummary = $this
                ->getAccountSummaryHandler()
                ->filter($request->toArray());

            return $accountSummary;
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    private function getAccountSummaryHandler(): AccountSummaryHandler
    {
        return app(AccountSummaryHandler::class);
    }
}
