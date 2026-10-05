<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;


class PolVerifyStrategy extends AbstractPolStrategy
{

    protected function validateRequest(): void
    {
        parent::validateRequest();
        \Aqayepardakht\PhpSdk\Domain\Payment\PaymentValidation::requiredString($this->request->code, 'code');
    }

    protected function endpointAction(): string
    {
        return 'verify';
    }

    protected function onSuccess(object $response): array
    {
        return [
            'tracking_code' => $response->tracking_code ?? null,
        ];
    }
}
