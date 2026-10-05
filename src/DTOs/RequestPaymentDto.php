<?php

namespace Aqayepardakht\PhpSdk\DTOs;

use Aqayepardakht\PhpSdk\Helper;
use Aqayepardakht\PhpSdk\Interfaces\ValidatableDto;

final readonly class RequestPaymentDto implements ValidatableDto
{
    public function __construct(
        public string $callback,
        public string $invoice_id,
        public string $identifier,
        public int|float $amount,

        public ?string $name = null,
        public ?string $ip = null,
        public ?string $mobile = null,
        public ?string $email = null,
        public ?string $description = null,
        public ?bool $sms = null,
        public ?bool $autoverify = null,
        public ?string $method = null,
        public ?string $national_code = null,
        public array $cards = [],
        public ?string $tracking_code = null,
    ) {
    }

    public function validate(): void
    {
        \Aqayepardakht\PhpSdk\Domain\Payment\PaymentValidation::amount($this->amount);
        Helper::validateUrl($this->callback);
        Helper::validateMobileNumber($this->mobile);
        Helper::validateEmail($this->email);
        foreach ($this->cards as $card) { Helper::validateCardsNumber($card); }
    }

    public function toArray(): array
    {
        return array_filter([
            'amount'        => $this->amount,
            'callback'      => $this->callback,
            'invoice_id'    => $this->invoice_id,
            'identifier'    => $this->identifier,
            'name'          => $this->name,
            'ip'            => $this->ip,
            'mobile'        => $this->mobile,
            'email'         => $this->email,
            'description'   => $this->description,
            'sms'           => $this->sms,
            'autoverify'    => $this->autoverify,
            'method'        => $this->method,
            'national_code' => $this->national_code,
            'cards'         => $this->cards,
            'tracking_code' => $this->tracking_code,
        ], fn ($value) => $value !== null && $value !== []);
    }
}
