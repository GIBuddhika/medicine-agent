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

class AnyRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $token = $request->header(HeaderConstants::SecurityToken);

        try {
            $securityToken = AuthSession::query()
                ->with(['user'])
                ->where('token', '=', $token)
                ->where('expire_at', '>', Carbon::now())
                ->firstOrFail();

            if ($securityToken->user->is_admin == 0) {
                $userRole = UserRoleConstants::CUSTOMER;
            }

            if ($securityToken->user->is_admin == 1 && $securityToken->user->owner_id == null) {
                $userRole = UserRoleConstants::ADMIN;
            }

            if ($securityToken->user->is_admin == 1 && $securityToken->user->owner_id != null) {
                $userRole = UserRoleConstants::SHOP_ADMIN;
            }

            if (in_array($userRole, $roles)) {
                session([
                    SessionConstants::User => $securityToken->user,
                    SessionConstants::UserRole => $userRole,
                ]);
                return $next($request);
            } else {
                throw new ModelNotFoundException();
            }
        } catch (ModelNotFoundException $ex) {
            return response(['error' => 'invalid_security_token'], 401);
        }
    }
}
