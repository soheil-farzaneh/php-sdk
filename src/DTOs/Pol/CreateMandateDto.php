<?php

namespace Aqayepardakht\PhpSdk\DTOs\Pol;

use Aqayepardakht\PhpSdk\Domain\Payment\PolValidation;
use Aqayepardakht\PhpSdk\Interfaces\ValidatableDto;

/** amount is required by the local route; mandate ceiling comes from mandates/otp. */
final readonly class CreateMandateDto implements ValidatableDto
{
    public function __construct(
        public string $tracking_code,
        public int|float $amount,
        public string $code,
    ) {}

    public function validate(): void
    {
        PolValidation::text($this->tracking_code, 'tracking_code', 255);
        PolValidation::amount($this->amount);
        PolValidation::text($this->code, 'code', 32);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
