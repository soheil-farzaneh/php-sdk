<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

final class PolMandateOtpStrategy extends AbstractPolStrategy
{
    protected function endpointAction(): string
    {
        return 'mandates/otp';
    }
}
