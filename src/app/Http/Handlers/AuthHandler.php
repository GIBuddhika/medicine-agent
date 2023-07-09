<?php

namespace App\Http\Handlers;

use App\Constants\AccountTypeConstants;
use App\Constants\UserRoleConstants;
use App\Constants\ValidationMessageConstants;
use App\Jobs\ForgotPasswordMailJob;
use App\Models\AuthSession;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Rules\Phone;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthHandler
{
    public function createAccount($data)
    {
        $accountTypes  = [
            AccountTypeConstants::SHOP,
            AccountTypeConstants::PERSONAL,
            AccountTypeConstants::SHOP_AND_PERSONAL,
        ];

        $rules = [
            'name' => 'required',
            'phone' => ['required', 'numeric', new Phone],
            'accountType' => ['required', 'numeric', Rule::in($accountTypes)],
            'email' => 'required|unique:users,email,null,id,is_admin,' . $data['is_admin'],
            'password' => 'required|confirmed',
            'is_admin' => 'required|boolean',
        ];
        $messages = [
            'required' => ValidationMessageConstants::Required,
            'confirmed' => ValidationMessageConstants::Confirmed,
            'unique' => ValidationMessageConstants::Duplicate,
            'numeric' => ValidationMessageConstants::Invalid,
            'in' => ValidationMessageConstants::Invalid,
        ];

        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator, 400);
        }

        $user = new User();
        $user->name = $data['name'];
        $user->phone = $data['phone'];
        $user->admin_account_type = $data['accountType'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->is_admin = $data['is_admin'];
        $user->save();

        return $user;
    }

    public function login($data)
    {
        try {
            $rules = [
                'email' => 'required',
                'password' => 'required',
                'is_admin' => 'required|boolean',
            ];
            $messages = [
                'required' => ValidationMessageConstants::Required,
            ];

            $validator = Validator::make($data, $rules, $messages);
            if ($validator->fails()) {
                throw new ValidationException($validator, 400);
            }

            $email = $data['email'];
            $password = $data['password'];
            $isAdmin = $data['is_admin'];

            $user = User::query()
                ->where('email', $email)
                ->where('is_admin', $isAdmin)
                ->firstOrFail();

            $passwordMatches = $this
                ->getHasher()
                ->check($password, $user->password);

            if (!$passwordMatches) {
                throw new ModelNotFoundException();
            }

            $tokenBase = $user->email . time();
            $token = $this
                ->getHasher()
                ->make($tokenBase);

            $securityToken = new AuthSession();
            $securityToken->user_id = $user->id;
            $securityToken->token = $token;

            $securityTokenExpireAt = now()->addHours(24);
            $securityToken->expire_at = $securityTokenExpireAt;

            $securityToken->save();

            return [
                'authSession' => $securityToken,
                'user' => $user
            ];
        } catch (NotFoundHttpException $ex) {
            throw $ex;
        }
    }

    public function validate(array $data)
    {
        $isValidQ = AuthSession::with('user')->where('token', $data['token'])
            ->whereDate('expire_at', '>', Carbon::now());

        if ($data['user_role'] == UserRoleConstants::CUSTOMER) {
            $isValidQ->whereHas('user', function ($query) {
                $query->whereNull('is_admin');
                $query->whereNull('owner_id');
            });
        } else if ($data['user_role'] == UserRoleConstants::ADMIN) {
            $isValidQ->whereHas('user', function ($query) {
                $query->where('is_admin', 1);
                $query->whereNull('owner_id');
            });
        } else if ($data['user_role'] == UserRoleConstants::SHOP_ADMIN) {
            $isValidQ->whereHas('user', function ($query) {
                $query->where('is_admin', 1);
                $query->whereNotNull('owner_id');
            });
        }

        $isValid = $isValidQ->exists();
        return $isValid;
    }

    public function sendPasswordResetLink(array $data)
    {
        $details['email'] = $data['email'];

        $user = User::query()
            ->where('email', $data['email'])
            ->where('is_admin', $data['is_admin'])
            ->first();

        if ($user) {
            $token = $this->getHasher()->make($data['email'] . time());

            $resetToken = new PasswordResetRequest();
            $resetToken->user_id = $user->id;
            $resetToken->token = $token;
            $resetToken->save();

            $details['token'] = $token;
            $details['name'] = $user->name;
            $details['email'] = $user->email;
            $details['is_admin'] = $user->is_admin;
            dispatch(new ForgotPasswordMailJob($details));
        }

        return true;
    }

    public function resetPassword(array $data)
    {
        $rules = [
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|confirmed',
            'is_admin' => 'required|boolean',
        ];
        $messages = [
            'required' => ValidationMessageConstants::Required,
            'confirmed' => ValidationMessageConstants::Confirmed,
        ];

        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator, 400);
        }

        try {
            $user = User::where('email', $data['email'])
                ->where('is_admin', $data['is_admin'])
                ->firstOrFail();

            $passwordResetRequest = PasswordResetRequest::where('token', $data['token'])
                ->where('user_id', $user->id)
                ->where('created_at', '>', Carbon::now()->subHours(24))
                ->firstOrFail();
        } catch (ModelNotFoundException $th) {
            throw new ModelNotFoundException('expired_token');
        }

        try {

            $user->password = Hash::make($data['password']);
            $user->save();

            $passwordResetRequest->delete();

            return true;
        } catch (ModelNotFoundException $th) {
            throw $th;
        } catch (Exception $th) {
            throw $th;
        }
    }

    private function getHasher(): Hasher
    {
        return app(Hasher::class);
    }
}
