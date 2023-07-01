<?php

namespace App\Http\Middleware;

use App\Constants\HeaderConstants;
use App\Constants\SessionConstants;
use App\Constants\UserRoleConstants;
use App\Models\AuthSession;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ShopAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header(HeaderConstants::SecurityToken);

        try {
            $securityToken = AuthSession::query()
                ->with(['user'])
                ->where('token', '=', $token)
                ->where('expire_at', '>', Carbon::now())
                ->whereHas('user', function ($query) {
                    $query->where('is_admin', 1)
                        ->whereNotNull('owner_id');
                })
                ->firstOrFail();
        } catch (ModelNotFoundException $ex) {
            return response(['error' => 'invalid_security_token'], 401);
        }

        session([
            SessionConstants::User => $securityToken->user,
            SessionConstants::UserRole => UserRoleConstants::SHOP_ADMIN,
        ]);

        return $next($request);
    }
}
