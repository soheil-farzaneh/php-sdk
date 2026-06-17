<?php

namespace Aqayepardakht\PhpSdk\DTOs;

use Aqayepardakht\PhpSdk\Helper;
use Aqayepardakht\PhpSdk\Interfaces\ValidatableDto;

final readonly class VerifyPaymentDto implements ValidatableDto
{
    public function __construct(
        public string $tracking_code,
        public int|float $amount,
        public string $code,
        public ?string $purpose = null,
    ) {
    }

    public function validate(): void
    {
        $this->validateAmount();
    }

    private function validateAmount(): void
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
            'code'          => $this->code,
            'purpose'       => $this->purpose,
        ], fn ($value) => $value !== null);
    }
}