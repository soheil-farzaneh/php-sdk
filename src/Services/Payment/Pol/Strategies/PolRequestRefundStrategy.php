<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

class PolRequestRefundStrategy extends AbstractPolStrategy
{
    protected function endpointAction(): string
    {
        return 'request-refund';
    }

    protected function onSuccess(object $response): array
    {
        return [
            'data' => $response->tracking_code ?? null,
        ];
    }
}
