<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;


class PolVerifyStrategy extends AbstractPolStrategy
{
    
    protected function endpointAction(): string
    {
        return 'verify';
    }

    protected function getPaymentUrl(): string
    {
        return config('paymentUrl.Pol');
    }

    protected function onSuccess(object $response): array
    {
        return [
            'tracking_code' => $response->tracking_code ?? null,
        ];
    }
}