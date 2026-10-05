<?php
namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;
final class PolPaymentStatusStrategy extends PolInfoStrategy
{
    protected function endpointAction(): string
    {
        return "payment-status";
    }
}
