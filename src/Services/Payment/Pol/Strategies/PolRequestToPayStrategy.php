<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

final class PolRequestToPayStrategy extends AbstractPolStrategy
{
    protected function endpointAction(): string
    {
        return 'create';
    }
}
