<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

class PolInfoStrategy extends AbstractPolStrategy
{
    protected function validateRequest(): void
    {
        \Aqayepardakht\PhpSdk\Domain\Payment\PolValidation::amount($this->request->amount);
        \Aqayepardakht\PhpSdk\Domain\Payment\PolValidation::text($this->request->tracking_code, 'tracking_code', 255);
    }

    protected function endpointAction(): string
    {
        return 'payment-info';
    }

    protected function onSuccess(object $response): array
    {
        return [
            'data' => $response->data ?? null,
        ];
    }
}
