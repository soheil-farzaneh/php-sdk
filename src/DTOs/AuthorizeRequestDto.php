<?php

namespace Aqayepardakht\PhpSdk\DTOs;

use Aqayepardakht\PhpSdk\Interfaces\ValidatableDto;
use Aqayepardakht\PhpSdk\Helper;

final readonly class AuthorizeRequestDto implements ValidatableDto
{
    public function __construct(
        public string $callback,
        public string $identifier,
        public int|float $amount,
        public string $phone,
        public string $invoice_id,

        public ?string $name = null,
        public ?string $ip = null,
        public ?string $description = null,
        public ?string $purpose = null,
        public ?string $settlement_type = null,
    ) {
    }

    public function validate(): void
    {
        \Aqayepardakht\PhpSdk\Domain\Payment\PolValidation::amount($this->amount);
        Helper::validateUrl($this->callback);
        \Aqayepardakht\PhpSdk\Domain\Payment\PolValidation::text($this->phone, 'phone', 32);
        Helper::validateMobileNumber($this->phone);
        \Aqayepardakht\PhpSdk\Domain\Payment\PolValidation::purpose($this->purpose);
        \Aqayepardakht\PhpSdk\Domain\Payment\PolValidation::settlement($this->settlement_type);
        \Aqayepardakht\PhpSdk\Domain\Payment\PolValidation::text($this->identifier, "identifier", 255);
        \Aqayepardakht\PhpSdk\Domain\Payment\PolValidation::text($this->invoice_id, "invoice_id", 64);
    }

    public function toArray(): array
    {
        return array_filter([
            'amount'      => $this->amount,
            'callback'    => $this->callback,
            'identifier'  => $this->identifier,
            'name'        => $this->name,
            'ip'          => $this->ip,
            'phone'       => $this->phone,
            'description' => $this->description,
            'purpose'     => $this->purpose,
            'settlement_type' => $this->settlement_type,
            'invoice_id'  => $this->invoice_id,
        ], fn ($value) => $value !== null);
    }
}
