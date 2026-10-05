<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol;

use Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies\{
    PolAuthorizeStrategy,
    PolOtpStrategy,
    PolVerifyStrategy,
    PolInfoStrategy,
    PolRequestRefundStrategy,
    PolInquiryRefundStrategy,
    PolRequestToPayStrategy,
    PolMandateOtpStrategy,
    PolMandateCreateStrategy,
    PolMandateInfoStrategy,
    PolMandateCancelStrategy,
    PolDirectDebitStrategy,
    PolPaymentStatusStrategy,
    PolRetryPaymentStrategy,
};
use Aqayepardakht\PhpSdk\Enums\PolAction;
use Aqayepardakht\PhpSdk\Interfaces\PaymentStrategy;

class PolStrategyFactory
{
    public static function make(
        string|PolAction $type,
        ...$params,
    ): PaymentStrategy {
        if ($type instanceof PolAction) {
            $type = $type->value;
        }

        $expected = match ($type) {
            PolAction::AUTHORIZE->value
                => \Aqayepardakht\PhpSdk\DTOs\AuthorizeRequestDto::class,
            PolAction::OTP->value
                => \Aqayepardakht\PhpSdk\DTOs\RequestPaymentDto::class,
            PolAction::VERIFY->value
                => \Aqayepardakht\PhpSdk\DTOs\VerifyPaymentDto::class,
            PolAction::INFO->value
                => \Aqayepardakht\PhpSdk\DTOs\PaymentInfoDto::class,
            PolAction::REQUESTREFUND->value
                => \Aqayepardakht\PhpSdk\DTOs\RequestRefundDto::class,
            PolAction::INQUIRYREFUND->value
                => \Aqayepardakht\PhpSdk\DTOs\InquiryRefundDto::class,
            PolAction::REQUEST_TO_PAY->value
                => \Aqayepardakht\PhpSdk\DTOs\Pol\RequestToPayDto::class,
            PolAction::MANDATE_OTP->value
                => \Aqayepardakht\PhpSdk\DTOs\Pol\MandateOtpDto::class,
            PolAction::MANDATE_CREATE->value
                => \Aqayepardakht\PhpSdk\DTOs\Pol\CreateMandateDto::class,
            PolAction::MANDATE_INFO->value
                => \Aqayepardakht\PhpSdk\DTOs\Pol\MandateReferenceDto::class,
            PolAction::MANDATE_CANCEL->value
                => \Aqayepardakht\PhpSdk\DTOs\Pol\MandateReferenceDto::class,
            PolAction::PAYMENT_STATUS->value,
            PolAction::RETRY_PAYMENT->value
                => \Aqayepardakht\PhpSdk\DTOs\PaymentInfoDto::class,
            PolAction::DIRECT_DEBIT->value
                => \Aqayepardakht\PhpSdk\DTOs\Pol\DirectDebitDto::class,
            default => throw new \InvalidArgumentException(
                "Invalid payment strategy type: " . $type,
            ),
        };
        // The factory's historical positional arguments remain pin, request, transport, URL.
        if (!isset($params[1]) || !($params[1] instanceof $expected)) {
            throw new \InvalidArgumentException(
                "Unexpected request DTO for payment action: " . $type,
            );
        }

        return match ($type) {
            PolAction::AUTHORIZE->value => new PolAuthorizeStrategy(...$params),
            PolAction::OTP->value => new PolOtpStrategy(...$params),
            PolAction::VERIFY->value => new PolVerifyStrategy(...$params),
            PolAction::INFO->value => new PolInfoStrategy(...$params),
            PolAction::REQUESTREFUND->value => new PolRequestRefundStrategy(
                ...$params,
            ),
            PolAction::INQUIRYREFUND->value => new PolInquiryRefundStrategy(
                ...$params,
            ),
            PolAction::REQUEST_TO_PAY->value => new PolRequestToPayStrategy(
                ...$params,
            ),
            PolAction::MANDATE_OTP->value => new PolMandateOtpStrategy(
                ...$params,
            ),
            PolAction::MANDATE_CREATE->value => new PolMandateCreateStrategy(
                ...$params,
            ),
            PolAction::MANDATE_INFO->value => new PolMandateInfoStrategy(
                ...$params,
            ),
            PolAction::MANDATE_CANCEL->value => new PolMandateCancelStrategy(
                ...$params,
            ),
            PolAction::PAYMENT_STATUS->value => new PolPaymentStatusStrategy(
                ...$params,
            ),
            PolAction::RETRY_PAYMENT->value => new PolRetryPaymentStrategy(
                ...$params,
            ),
            PolAction::DIRECT_DEBIT->value => new PolDirectDebitStrategy(
                ...$params,
            ),
            default => throw new \InvalidArgumentException(
                "Invalid payment strategy type: $type",
            ),
        };
    }
}
