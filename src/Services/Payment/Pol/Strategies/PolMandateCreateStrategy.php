<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

final class PolMandateCreateStrategy extends AbstractPolStrategy
{
    protected function endpointAction(): string
    {
        return 'mandates/create';
    }
}
