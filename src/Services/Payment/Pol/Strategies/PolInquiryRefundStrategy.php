<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

class PolInquiryRefundStrategy extends AbstractPolStrategy
{
    protected function endpointAction(): string
    {
        return 'inquiry-refund';
    }

    protected function onSuccess(object $response): array
    {
        return [
            'data' => $response->data ?? null,
        ];
    }
}
