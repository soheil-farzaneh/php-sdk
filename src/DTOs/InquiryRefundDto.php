<?php

namespace Aqayepardakht\PhpSdk\DTOs;

use Aqayepardakht\PhpSdk\Interfaces\ValidatableDto;

final readonly class InquiryRefundDto implements ValidatableDto
{
    public function __construct(
        public string $tracking_code,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'tracking_code' => $this->tracking_code,
        ], fn ($value) => $value !== null);
    }
}