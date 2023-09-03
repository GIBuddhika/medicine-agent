<?php

namespace Tests\Feature;

use App\Constants\SessionConstants;
use App\Constants\UserRoleConstants;
use App\Http\Handlers\AuthHandler;
use App\Http\Handlers\ItemsHandler;
use App\Http\Handlers\OrdersHandler;
use App\Models\Item;
use App\Models\Order;
use App\PaymentService\PaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class CreateNewOrderTest extends TestCase
{
    use DatabaseTransactions;
    private $paymentServiceMock;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $data = [
            'name' => 'Customer 01',
            'phone' => '710255896',
            'is_admin' => false,
            'email' => 'customer@test.com',
            'password' => '123',
            'password_confirmation' => '123',
        ];

        $authHandler = new AuthHandler();

        $this->user = $authHandler->createAccount($data);

        session([
            SessionConstants::User => $this->user,
            SessionConstants::UserRole => UserRoleConstants::CUSTOMER,
        ]);

        $this->paymentServiceMock = $this->createMock(PaymentService::class);
    }

    public function testItemCreateSuccess()
    {
        $data = [
            'is_a_shop_listing' => false,
            'name' => 'new item',
            'quantity' => 100,
            'price' => 150,
            'pricing_category' => 'sell',
            'address' => 'main st, matara',
            'latitude' => '18.612929',
            'longitude' => '7.946218',
            'city_id' => 1,
        ];

        $itemsHandler = new ItemsHandler();

        $result = $itemsHandler->createItem($data);

        $this->assertInstanceOf(Item::class, $result);

        $this->assertDatabaseHas('items', ['name' => 'new item']);
        $this->assertDatabaseHas('personal_listings', ['user_id' => $this->user->id, 'address' => 'main st, matara']);
    }

    public function testItemCreateInvalidInputName()
    {
        $data = [
            'is_a_shop_listing' => false,
            'name1' => 123,
            'quantity' => 100,
            'price' => 150,
            'pricing_category' => 'sell',
            'address' => 'main st, matara',
            'latitude' => '18.612929',
            'longitude' => '7.946218',
            'city_id' => 1,
        ];

        $itemsHandler = new ItemsHandler();

        $this->expectException(ValidationException::class);

        $itemsHandler->createItem($data);
    }

    public function testOrderCreateSuccess()
    {
        $data = [
            'is_a_shop_listing' => false,
            'name' => 'new item',
            'quantity' => 100,
            'price' => 150,
            'pricing_category' => 'sell',
            'address' => 'main st, matara',
            'latitude' => '18.612929',
            'longitude' => '7.946218',
            'city_id' => 1,
        ];

        $itemsHandler = new ItemsHandler();

        $item = $itemsHandler->createItem($data);

        $orderData = [
            'stripe_token' => 'test_stripe_token',
            'data' => [
                [
                    'item_id' => $item->id,
                    'quantity' => 10,
                    'duration' => null,
                ],
                [
                    'item_id' => '30',
                    'quantity' => 10,
                    'duration' => null,
                ]
            ]
        ];

        $mock = Mockery::mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('processPayment')->once()->andReturn("inv_id_123");
        });

        $this->app->bind(PaymentService::class, function () use ($mock) {
            return $mock;
        });

        $orderHandler = new OrdersHandler();

        $result = $orderHandler->handleCreateOrderRequest($orderData);

        $this->assertInstanceOf(Order::class, $result);
    }
}
