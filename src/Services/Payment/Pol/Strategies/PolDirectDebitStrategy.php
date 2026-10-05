<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

final class PolDirectDebitStrategy extends AbstractPolStrategy
{
    protected function endpointAction(): string
    {
        return 'direct/withdraw';
    }
}
