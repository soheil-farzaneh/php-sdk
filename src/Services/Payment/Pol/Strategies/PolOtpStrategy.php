<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

class PolOtpStrategy extends AbstractPolStrategy
{
    protected function validateRequest(): void
    {
        parent::validateRequest();
        \Aqayepardakht\PhpSdk\Domain\Payment\PaymentValidation::requiredString($this->request->identifier, 'identifier');
    }

    protected function endpointAction(): string
    {
        return 'create';
    }

    protected function onSuccess(object $response): array
    {
        return [
            'tracking_code' => $response->tracking_code ?? null,
        ];
    }
}
