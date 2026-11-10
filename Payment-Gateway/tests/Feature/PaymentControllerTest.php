<?php

use Midtrans\Config;

test('payment redirects to Midtrans and stores the pending order', function () {
    config(['services.midtrans.server_key' => 'test-server-key']);

    \Mockery::mock('alias:Midtrans\Snap')
        ->shouldReceive('createTransaction')
        ->once()
        ->with(\Mockery::on(function (array $parameters): bool {
            expect(Config::$serverKey)->toBe('test-server-key');
            expect($parameters['transaction_details']['gross_amount'])->toBe(10000);

            return true;
        }))
        ->andReturn((object) ['redirect_url' => 'https://payment.test/checkout']);

    $response = $this->post(route('demo-payment.pay'));

    $response->assertRedirect('https://payment.test/checkout');
    $response->assertSessionHas('demo_order', function (array $order): bool {
        return str_starts_with($order['order_code'], 'DEMO-')
            && $order['amount'] === 10000
            && $order['status'] === 'pending'
            && $order['payment_url'] === 'https://payment.test/checkout';
    });

    $this->get(route('demo-payment.index'))
        ->assertOk()
        ->assertSee('Kode pesanan: DEMO-');
});

test('payment page displays gateway errors', function () {
    $this->withSession(['error' => 'Payment gateway error'])
        ->get(route('demo-payment.index'))
        ->assertOk()
        ->assertSee('Payment gateway error');
});

test('a settled payment updates the session order', function () {
    $order = [
        'order_code' => 'DEMO-test-order',
        'amount' => 10000,
        'status' => 'pending',
        'payment_url' => 'https://payment.test/checkout',
    ];

    \Mockery::mock('alias:Midtrans\Transaction')
        ->shouldReceive('status')
        ->once()
        ->with('DEMO-test-order')
        ->andReturn((object) [
            'order_id' => 'DEMO-test-order',
            'gross_amount' => '10000.00',
            'transaction_status' => 'settlement',
        ]);

    $this->withSession(['demo_order' => $order])
        ->post(route('demo-payment.check'))
        ->assertRedirect(route('demo-payment.index'))
        ->assertSessionHas('demo_order.status', 'paid')
        ->assertSessionHas('message', 'Pembayaran berhasil');
});
