<?php

namespace Aqayepardakht\PhpSdk\DTOs\Pol;

use Aqayepardakht\PhpSdk\Domain\Payment\PolValidation;
use Aqayepardakht\PhpSdk\Interfaces\ValidatableDto;

/** Stable local tracking_code from authorize, never the MMS reference or OTP track ID. */
final readonly class MandateReferenceDto implements ValidatableDto
{
    public function __construct(public string $tracking_code) {}

    public function validate(): void
    {
        PolValidation::text($this->tracking_code, 'tracking_code', 255);
    }

    public function toArray(): array
    {
        return ['tracking_code' => $this->tracking_code];
    }
}
