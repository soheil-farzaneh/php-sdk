<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

final class PolMandateInfoStrategy extends AbstractPolStrategy
{
    protected function endpointAction(): string
    {
        return 'mandates/info';
    }
}
