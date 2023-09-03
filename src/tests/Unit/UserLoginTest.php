<?php

namespace Tests\Unit;

use App\Http\Handlers\AuthHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testUserLoginSuccess()
    {
        $data = [
            'email' => 'admin1@test.com',
            'password' => '123',
            'is_admin' => true,
        ];

        $authHandler = new AuthHandler();

        $result = $authHandler->login($data);

        $this->assertArrayHasKey('authSession', $result);
        $this->assertArrayHasKey('user', $result);
    }

    public function testUserLoginFailed()
    {
        $data = [
            'email' => 'admin2@test.com',
            'password' => '123',
            'is_admin' => true,
        ];

        $authHandler = new AuthHandler();

        $this->expectException(ModelNotFoundException::class);
        $authHandler->login($data);
    }
}
