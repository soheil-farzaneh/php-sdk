<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

class PolOtpStrategy extends AbstractPolStrategy
{
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