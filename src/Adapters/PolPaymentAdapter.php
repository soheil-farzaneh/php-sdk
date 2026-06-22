<?php

namespace Aqayepardakht\PhpSdk\Adapters;

use Aqayepardakht\PhpSdk\Contracts\{
    PaymentGateway, 
    Authorizable, 
    PaymentInquiry,
    Refundable
};
use Aqayepardakht\PhpSdk\DTOs\{
    AuthorizeRequestDto,
    RequestPaymentDto,
    VerifyPaymentDto,
    PaymentInfoDto,
    RequestRefundDto,
    InquiryRefundDto
};
use Aqayepardakht\PhpSdk\Services\Payment\Pol\PolService;
use Aqayepardakht\PhpSdk\Response;

class PolPaymentAdapter implements PaymentGateway, Authorizable, PaymentInquiry, Refundable
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

    public function paymentInquiry(PaymentInfoDto $request) : Response 
    {
        return $this->service()->info($request);
    }

    public function requestRefund(RequestRefundDto $request) : Response
    {
        return $this->service()->requestRefund($request);
    }
    
    public function inquiryRefund(InquiryRefundDto $request) : Response
    {
        return $this->service()->inquiryRefund($request);
    }

    private function service() : PolService
    {
        return new PolService($this->pin);
    }
}