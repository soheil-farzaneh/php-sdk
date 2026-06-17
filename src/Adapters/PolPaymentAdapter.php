<?php

namespace Aqayepardakht\PhpSdk\Adapters;

use Aqayepardakht\PhpSdk\Contracts\{
    PaymentGateway, 
    AuthorizableGateway, 
    PaymentInfoGateway
};
use Aqayepardakht\PhpSdk\DTOs\{
    AuthorizeRequestDto,
    RequestPaymentDto,
    VerifyPaymentDto,
    PaymentInfoDto
};
use Aqayepardakht\PhpSdk\Services\Payment\Pol\PolService;
use Aqayepardakht\PhpSdk\Response;

class PolPaymentAdapter implements PaymentGateway, AuthorizableGateway, PaymentInfoGateway
{
  
    public function __construct(
        protected string $pin,
    ) {
    }

    public function authorize(AuthorizeRequestDto $request): Response
    {
        return $this->service()->authorize($request);
    }

    public function requestPayment(RequestPaymentDto $request) : Response
    {
        return $this->service()->getOtp($request);
    }

    public function verifyPayment(VerifyPaymentDto $request) : Response 
    {
        return $this->service()->verify($request);
    }

    public function paymentInfo(PaymentInfoDto $request) : Response 
    {
        return $this->service()->info($request);
    }

    private function service() : PolService
    {
        return new PolService($this->pin);
    }
}