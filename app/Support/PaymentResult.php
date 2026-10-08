<?php

namespace App\Support;

class PaymentResult
{
    public function __construct(
        public readonly string $status, // pending | paid | failed
        public readonly ?string $redirectUrl = null,
        public readonly ?string $reference = null,
        public readonly ?string $message = null,
    ) {}

    public function requiresRedirect(): bool
    {
        return $this->redirectUrl !== null;
    }
}
