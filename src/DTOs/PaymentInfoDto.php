<?php

namespace Aqayepardakht\PhpSdk\DTOs;

use Aqayepardakht\PhpSdk\Helper;
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
        $amount = floatval(Helper::faToEnNumbers($this->amount));

        if ($amount <= 1000 || $amount >= 100000000) {
            throw new \InvalidArgumentException('invalid amount');
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'tracking_code' => $this->tracking_code,
            'amount'        => $this->amount,
        ], fn ($value) => $value !== null);
    }
}