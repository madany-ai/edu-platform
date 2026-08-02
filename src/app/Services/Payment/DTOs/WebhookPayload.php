<?php

namespace App\Services\Payment\DTOs;

class WebhookPayload
{
    public function __construct(
        public string $gatewayReference,
        public string $status, // 'paid', 'failed', 'refunded'
        public int $amountCents,
        public ?string $transactionId = null,
        public array $rawPayload = []
    ) {}
}
