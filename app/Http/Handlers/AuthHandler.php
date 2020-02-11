<?php

namespace App\Http\Handlers;

use App\Constants\ValidationMessageConstants;
use App\Models\AuthSession;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthHandler
{
    public function createAccount($data)
    {
        $rules = [
            'email' => 'required|unique:users,email',
            'password' => 'required|confirmed',
        ];
        $messages = [
            'required' => ValidationMessageConstants::Required,
            'confirmed' => ValidationMessageConstants::Confirmed,
            'unique' => ValidationMessageConstants::Duplicate,
        ];

        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator, 400);
        }

        $user = new User();
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->save();

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

        return $securityToken;
    }

    public function signIn($data)
    {
        $rules = [
            'email' => 'required',
            'password' => 'required',
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

        $user = User::query()
            ->where('email', '=', $email)
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

        return $securityToken;
    }

    private function getHasher(): Hasher
    {
        return app(Hasher::class);
    }
}
