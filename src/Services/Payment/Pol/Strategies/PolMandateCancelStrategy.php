<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

final class PolMandateCancelStrategy extends AbstractPolStrategy
{
    protected function endpointAction(): string
    {
        return 'mandates/cancel';
    }
}
