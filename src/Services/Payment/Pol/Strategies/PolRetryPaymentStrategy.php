<?php
namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;
final class PolRetryPaymentStrategy extends PolInfoStrategy
{
    protected function endpointAction(): string
    {
        return "payments/retry";
    }
    protected function onSuccess(object $response): array
    {
        $data = (array) $response;
        unset($data["status"]);
        return $data;
    }
}
