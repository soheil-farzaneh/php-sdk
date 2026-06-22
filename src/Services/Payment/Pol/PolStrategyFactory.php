<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol;

use Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies\{
    PolAuthorizeStrategy,
    PolOtpStrategy,
    PolVerifyStrategy,
    PolInfoStrategy,
    PolRequestRefundStrategy,
    PolInquiryRefundStrategy
};
use Aqayepardakht\PhpSdk\Enums\PolAction;
use Aqayepardakht\PhpSdk\Interfaces\PaymentStrategy;

class PolStrategyFactory
{
    public static function make(string|PolAction $type, ...$params): PaymentStrategy
    {
        if ($type instanceof PolAction) {
            $type = $type->value;
        }

        return match ($type) {
            PolAction::AUTHORIZE->value      => new PolAuthorizeStrategy(...$params),
            PolAction::OTP->value            => new PolOtpStrategy(...$params),
            PolAction::VERIFY->value         => new PolVerifyStrategy(...$params),
            PolAction::INFO->value           => new PolInfoStrategy(...$params),
            PolAction::REQUESTREFUND->value  => new PolRequestRefundStrategy(...$params),
            PolAction::INQUIRYREFUND->value  => new PolInquiryRefundStrategy(...$params),
            default => throw new \InvalidArgumentException("Invalid payment strategy type: $type"),
        };
    }
}