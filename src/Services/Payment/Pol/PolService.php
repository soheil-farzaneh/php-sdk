<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol;

use Aqayepardakht\PhpSdk\Enums\PolAction;
use Aqayepardakht\PhpSdk\Interfaces\PaymentStrategy;
use Aqayepardakht\PhpSdk\Services\Payment\Pol\PolStrategyFactory;
use Aqayepardakht\PhpSdk\Response;
use Aqayepardakht\PhpSdk\DTOs\{
    AuthorizeRequestDto,
    RequestPaymentDto,
    VerifyPaymentDto,
    PaymentInfoDto
};

class PolService {

    public function __construct(
        private string $pin
    ) {
    }

    public function authorize(AuthorizeRequestDto $request): Response 
    {
        return $this->process(
            PolStrategyFactory::make(PolAction::AUTHORIZE, $this->pin, $request)
        );

    }

    public function getOtp(RequestPaymentDto $request): Response 
    {
        $response = $this->process(
            PolStrategyFactory::make(PolAction::OTP , $this->pin, $request)
        );

        return $response;
    }

    public function verify(VerifyPaymentDto $request): Response 
    {
        return $this->process(
            PolStrategyFactory::make(PolAction::VERIFY, $this->pin, $request)
        );
    }

    public function info(PaymentInfoDto $request): Response
    {
        return $this->process(
            PolStrategyFactory::make(PolAction::INFO, $this->pin, $request)
        );
    }

    protected function process(PaymentStrategy $strategy) 
    {
        try {
            return $strategy->process();
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to process payment: " . $e->getMessage());
        }
    }
}