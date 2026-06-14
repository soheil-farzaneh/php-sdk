<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

use Aqayepardakht\PhpSdk\Invoice;

class PolVerifyStrategy extends AbstractPolStrategy
{
    public function __construct(
        protected string $code,
        string $pin,
        Invoice $invoice,
    ) {
        parent::__construct($pin, $invoice);
    }

    protected function endpointAction(): string
    {
        return 'verify';
    }

    protected function extraParams(): array
    {
        return [
            'code' => $this->code,
        ];
    }

    protected function onSuccess(object $response): array
    {
        return [
            'tracking_code' => $response->tracking_code ?? $this->invoice->getTrackingCode(),
        ];
    }
}