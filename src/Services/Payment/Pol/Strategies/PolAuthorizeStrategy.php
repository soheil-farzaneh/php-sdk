<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

class PolAuthorizeStrategy extends AbstractPolStrategy
{
    protected function endpointAction(): string
    {
        return 'authorize';
    }

    protected function getPaymentUrl(): string
    {
        return config('paymentUrl.pol');
    }

    protected function onSuccess(object $response): array
    {
        return [
            'redirecturi' => $response->redirecturi
        ];
    }
}