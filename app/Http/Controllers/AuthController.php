<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Handlers\AuthHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function createAccount(Request $request)
    {
        $data = [
            'email' => $request->email,
            'password' => $request->password,
            'password_confirmation' => $request->confirm_password,
        ];

        try {
            $this
                ->getAuthHandler()
                ->createAccount($data);

            $authSession = $this
                ->getAuthHandler()
                ->signIn($data);

            return $authSession;
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        }
    }

    public function signIn(Request $request)
    {
        $data = [
            'email' => $request->email,
            'password' => $request->password
        ];

        try {
            $authSession = $this
                ->getAuthHandler()
                ->signIn($data);

            return $authSession;
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        } catch (ModelNotFoundException $ex) {
            return response(null, 404);
        }
    }

    private function getAuthHandler(): AuthHandler
    {
        return app(AuthHandler::class);
    }
}
