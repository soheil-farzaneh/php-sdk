<?php

namespace Aqayepardakht\PhpSdk\DTOs;

use Aqayepardakht\PhpSdk\Interfaces\ValidatableDto;

final readonly class PaymentInfoDto implements ValidatableDto
{
    public function __construct(
        public string $tracking_code,
        public int|float $amount,
    ) {
    }

    public function validate(): void
    {
        \Aqayepardakht\PhpSdk\Domain\Payment\PaymentValidation::amount($this->amount);
        \Aqayepardakht\PhpSdk\Domain\Payment\PaymentValidation::requiredString($this->tracking_code, "tracking_code");
    }

    public function toArray(): array
    {
        return array_filter([
            'tracking_code' => $this->tracking_code,
            'amount'        => $this->amount,
        ], fn ($value) => $value !== null);
    }
}
