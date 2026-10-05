<?php

namespace Aqayepardakht\PhpSdk\DTOs\Pol;

use Aqayepardakht\PhpSdk\Domain\Payment\PolValidation;
use Aqayepardakht\PhpSdk\Helper;
use Aqayepardakht\PhpSdk\Interfaces\ValidatableDto;

/** Identifies the invoice already created by authorize; does not start customer OAuth. */
final readonly class RequestToPayDto implements ValidatableDto
{
    public function __construct(
        public string $callback,
        public string $invoice_id,
        public int|float $amount,
        public ?string $purpose = null,
        public ?string $due_date = null,
        public ?string $receiver_identifier = null,
    ) {}

    public function validate(): void
    {
        PolValidation::amount($this->amount);
        Helper::validateUrl($this->callback);
        PolValidation::text($this->invoice_id, 'invoice_id', 64);
        PolValidation::purpose($this->purpose);
        if ($this->due_date !== null) {
            PolValidation::date($this->due_date, 'due_date');
        }
        if ($this->receiver_identifier !== null) {
            PolValidation::text($this->receiver_identifier, 'receiver_identifier', 255);
        }
    }

    public function toArray(): array
    {
        return array_filter(get_object_vars($this), static fn ($value) => $value !== null);
    }
}
