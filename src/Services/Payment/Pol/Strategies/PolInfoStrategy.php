<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

class PolInfoStrategy extends AbstractPolStrategy
{
    protected function endpointAction(): string
    {
        return 'info';
    }

    protected function getPaymentUrl(): string
    {
        return config('paymentUrl.Pol');
    }

    protected function onSuccess(object $response): array
    {
        return [
            'data' => $response->data ?? null,
        ];
    }
}