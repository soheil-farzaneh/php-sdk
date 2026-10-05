<?php

namespace Aqayepardakht\PhpSdk\Adapters;

use Aqayepardakht\PhpSdk\Contracts\{
    PaymentGateway,
    Authorizable,
    PaymentInquiry,
    Refundable,
};
use Aqayepardakht\PhpSdk\DTOs\{
    AuthorizeRequestDto,
    RequestPaymentDto,
    VerifyPaymentDto,
    PaymentInfoDto,
    RequestRefundDto,
    InquiryRefundDto,
};
use Aqayepardakht\PhpSdk\Services\Payment\Pol\PolService;
use Aqayepardakht\PhpSdk\Response;
use Aqayepardakht\PhpSdk\Contracts\HttpClient;
use Aqayepardakht\PhpSdk\Enums\EndPoints;

use Aqayepardakht\PhpSdk\DTOs\Pol\RequestToPayDto;
use Aqayepardakht\PhpSdk\DTOs\Pol\MandateOtpDto;
use Aqayepardakht\PhpSdk\DTOs\Pol\CreateMandateDto;
use Aqayepardakht\PhpSdk\DTOs\Pol\MandateReferenceDto;
use Aqayepardakht\PhpSdk\DTOs\Pol\DirectDebitDto;

class PolPaymentAdapter implements
    PaymentGateway,
    Authorizable,
    PaymentInquiry,
    Refundable,
    \Aqayepardakht\PhpSdk\Contracts\RequestToPay,
    \Aqayepardakht\PhpSdk\Contracts\MandateManageable,
    \Aqayepardakht\PhpSdk\Contracts\DirectDebitGateway,
    \Aqayepardakht\PhpSdk\Contracts\PaymentRecoverable
{
    private PolService $paymentService;

    public function __construct(
        string $pin,
        ?HttpClient $http = null,
        string $baseUrl = EndPoints::POl_PRODUCTION,
    ) {
        $this->paymentService = new PolService($pin, $http, $baseUrl);
    }

    /** Returns a new adapter; does not mutate another payment session's authentication. */
    public function withAccessToken(string $accessToken): self
    {
        $adapter = clone $this;
        $adapter->paymentService = $this->paymentService->withAccessToken(
            $accessToken,
        );
        return $adapter;
    }

    public function authorize(AuthorizeRequestDto $request): Response
    {
        return $this->service()->authorize($request);
    }

    public function requestPayment(RequestPaymentDto $request): Response
    {
        return $this->service()->getOtp($request);
    }

    public function verifyPayment(VerifyPaymentDto $request): Response
    {
        return $this->service()->verify($request);
    }

    public function paymentInquiry(PaymentInfoDto $request): Response
    {
        return $this->service()->info($request);
    }

    public function requestRefund(RequestRefundDto $request): Response
    {
        return $this->service()->requestRefund($request);
    }

    public function inquiryRefund(InquiryRefundDto $request): Response
    {
        return $this->service()->inquiryRefund($request);
    }

    public function requestToPay(RequestToPayDto $request): Response
    {
        return $this->service()->requestToPay($request);
    }

    public function requestMandateOtp(MandateOtpDto $request): Response
    {
        return $this->service()->requestMandateOtp($request);
    }

    public function createMandate(CreateMandateDto $request): Response
    {
        return $this->service()->createMandate($request);
    }

    public function mandateInfo(MandateReferenceDto $request): Response
    {
        return $this->service()->mandateInfo($request);
    }

    public function cancelMandate(MandateReferenceDto $request): Response
    {
        return $this->service()->cancelMandate($request);
    }

    public function paymentStatus(PaymentInfoDto $request): Response
    {
        return $this->service()->paymentStatus($request);
    }

    public function retryPayment(PaymentInfoDto $request): Response
    {
        return $this->service()->retryPayment($request);
    }

    public function directDebit(DirectDebitDto $request): Response
    {
        return $this->service()->directDebit($request);
    }

    private function service(): PolService
    {
        return $this->paymentService;
    }
}
