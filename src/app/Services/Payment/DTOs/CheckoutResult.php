<?php

namespace App\Services\Payment\DTOs;

class CheckoutResult
{
    public function __construct(
        public string $paymentUrl,
        public string $checkoutId,
        public ?string $gatewayReference = null
    ) {}
}
