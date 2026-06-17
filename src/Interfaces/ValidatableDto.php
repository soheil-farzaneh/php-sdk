<?php 

namespace Aqayepardakht\PhpSdk\Interfaces;

interface ValidatableDto
{
    public function validate(): void;
    public function toArray(): array;
}