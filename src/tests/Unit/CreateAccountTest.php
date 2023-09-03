<?php

namespace Tests\Unit;

use App\Http\Handlers\AuthHandler;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

class CreateAccountTest extends TestCase
{
    use DatabaseTransactions;

    public function testCreateAccountSuccess()
    {
        $data = [
            'name' => 'John Doe',
            'phone' => '710255896',
            'is_admin' => true,
            'accountType' => 0,
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $authHandler = new AuthHandler();

        $result = $authHandler->createAccount($data);

        $this->assertInstanceOf(User::class, $result);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    public function testCreateAccountInvalidPhone()
    {
        $data = [
            'name' => 'John Doe',
            'phone' => '0710255896',
            'is_admin' => true,
            'accountType' => 0,
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $authHandler = new AuthHandler();

        $this->expectException(ValidationException::class);

        $authHandler->createAccount($data);
    }

    public function testCreateAccountInvalidEmail()
    {
        $data = [
            'name' => 'John Doe',
            'phone' => '710255896',
            'is_admin' => true,
            'accountType' => 0,
            'email' => 'johnexample.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $authHandler = new AuthHandler();

        $this->expectException(ValidationException::class);

        $authHandler->createAccount($data);
    }

    public function testCreateAccountMismatchPassword()
    {
        $data = [
            'name' => 'John Doe',
            'phone' => '710255896',
            'is_admin' => true,
            'accountType' => 0,
            'email' => 'johnexample.com',
            'password' => 'password123',
            'password_confirmation' => 'password12',
        ];

        $authHandler = new AuthHandler();

        $this->expectException(ValidationException::class);

        $authHandler->createAccount($data);
    }
}
