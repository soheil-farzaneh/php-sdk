<?php

namespace Aqayepardakht\PhpSdk\DTOs\Pol;

use Aqayepardakht\PhpSdk\Domain\Payment\PolValidation;
use Aqayepardakht\PhpSdk\Interfaces\ValidatableDto;

/** Each withdrawal uses its own created invoice; mandate selection belongs to the server. */
final readonly class DirectDebitDto implements ValidatableDto
{
    public function __construct(public string $tracking_code, public int|float $amount) {}

    public function validate(): void
    {
        PolValidation::text($this->tracking_code, 'tracking_code', 255);
        PolValidation::amount($this->amount);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
