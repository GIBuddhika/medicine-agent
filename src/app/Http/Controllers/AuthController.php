<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Handlers\AuthHandler;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function createAccount(Request $request)
    {
        $data = [
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => $request->password,
            'password_confirmation' => $request->confirm_password,
            'is_admin' => $request->isAdmin,
        ];

        try {
            $this
                ->getAuthHandler()
                ->createAccount($data);

            $authSession = $this
                ->getAuthHandler()
                ->login($data);

            return $authSession;
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        }
    }

    public function login(Request $request)
    {
        $data = [
            'email' => $request->email,
            'password' => $request->password,
            'is_admin' => $request->isAdmin,
        ];

        try {
            $authSession = $this
                ->getAuthHandler()
                ->login($data);

            return $authSession;
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        } catch (ModelNotFoundException $ex) {
            return response(null, 404);
        }
    }

    public function passwordResetRequest(Request $request)
    {
        $data = [
            'email' => $request->email,
            'is_admin' => $request->is_admin,
        ];

        try {
            $this
                ->getAuthHandler()
                ->sendPasswordResetLink($data);

            return response([], 200);
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        } catch (ModelNotFoundException $ex) {
            return response(null, 404);
        }
    }

    public function resetPassword(Request $request)
    {
        $data = [
            'email' => $request->email,
            'password' => $request->password,
            'password_confirmation' => $request->confirm_password,
            'token' => $request->token,
            'is_admin' => $request->is_admin,
        ];

        try {
            $this
                ->getAuthHandler()
                ->resetPassword($data);

            return response([], 200);
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        } catch (ModelNotFoundException $ex) {
            return response(['error_message' => $ex->getMessage()], 404);
        } catch (Exception $ex) {
            return response(null, 500);
        }
    }

    public function validateToken(Request $request)
    {
        try {
            $authSession = $this
                ->getAuthHandler()
                ->validate($request->toArray());

            return $authSession;
        } catch (ModelNotFoundException $ex) {
            return false;
        }
    }

    private function getAuthHandler(): AuthHandler
    {
        return app(AuthHandler::class);
    }
}
