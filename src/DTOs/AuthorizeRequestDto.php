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

        public ?string $name = null,
        public ?string $ip = null,
        public ?string $mobile = null,
        public ?string $description = null,
        public ?string $purpose = null,
    ) {
    }

    public function validate(): void
    {
        $this->validateAmount();
        $this->validateMobile();
    }

    private function validateAmount(): void
    {
        $amount = floatval(Helper::faToEnNumbers($this->amount));

        if ($amount <= 1000 || $amount >= 100000000) {
            throw new \InvalidArgumentException(
                'مبلغ باید بیشتر از 1000 و کمتر از 100,000,000 باشد'
            );
        }
    }

    private function validateMobile(): void
    {
        if ($this->mobile) {
            Helper::validateMobileNumber($this->mobile);
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'amount'      => $this->amount,
            'callback'    => $this->callback,
            'identifier'  => $this->identifier,
            'name'        => $this->name,
            'ip'          => $this->ip,
            'mobile'      => $this->mobile,
            'description' => $this->description,
            'purpose'     => $this->purpose,
        ], fn ($value) => $value !== null);
    }
}