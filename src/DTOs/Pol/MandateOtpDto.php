<?php

namespace Aqayepardakht\PhpSdk\DTOs\Pol;

use Aqayepardakht\PhpSdk\Domain\Payment\PolValidation;
use Aqayepardakht\PhpSdk\Interfaces\ValidatableDto;

final readonly class MandateOtpDto implements ValidatableDto
{
    public function __construct(
        public string $tracking_code,
        public int|float $max_amount,
        public string $start_date,
        public string $end_date,
        public int $count_per_period,
        public string $period,
        public string $mandate_reason,
        public string $description,
        public bool $cancelable = true,
    ) {}

    public function validate(): void
    {
        PolValidation::text($this->tracking_code, 'tracking_code', 255);
        PolValidation::amount($this->max_amount);
        PolValidation::date($this->start_date, 'start_date');
        PolValidation::date($this->end_date, 'end_date');
        if ($this->end_date < $this->start_date) {
            throw new \InvalidArgumentException('end_date must be on or after start_date.');
        }
        if ($this->count_per_period < 1) {
            throw new \InvalidArgumentException('count_per_period must be at least 1.');
        }
        PolValidation::code($this->period, 'period');
        PolValidation::code($this->mandate_reason, 'mandate_reason', true);
        PolValidation::text($this->description, 'description', 255);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
