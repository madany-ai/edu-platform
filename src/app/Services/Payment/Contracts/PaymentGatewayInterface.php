<?php

namespace App\Services\Payment\Contracts;

use App\Models\Order;
use App\Services\Payment\DTOs\CheckoutResult;
use App\Services\Payment\DTOs\WebhookPayload;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function createCheckout(Order $order): CheckoutResult;
    
    public function verifyWebhook(Request $request): WebhookPayload;
}
