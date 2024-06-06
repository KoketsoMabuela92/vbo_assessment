<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    /**
     * A create payment feature test.
     */
    public function testCreatePayment() 
    {
        $response = $this->postJson('/api/payments', [
            'amount' => 100.00,
            'currency' => 'ZAR',
            'customer_email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure(['redirect_url']);
    }

    /**
     * A handle IPN feature test.
     */
    public function testHandleIPN() 
    {
        $payment = Payment::factory()->create([
            'transaction_id' => '12345',
            'amount' => 100.00,
            'currency' => 'ZAR',
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/payments/ipn', [
            'pf_payment_id' => '12345',
            'amount_gross' => 100.00,
        ]);

        $response->assertStatus(200)
                ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('payments', [
            'transaction_id' => '12345',
            'status' => 'completed',
        ]);
    }

}
