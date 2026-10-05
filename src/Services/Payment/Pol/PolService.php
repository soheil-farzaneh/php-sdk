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
    PaymentInfoDto,
    InquiryRefundDto,
    RequestRefundDto,
};

use Aqayepardakht\PhpSdk\Contracts\HttpClient;
use Aqayepardakht\PhpSdk\Infrastructure\Http\CurlHttpClient;
use Aqayepardakht\PhpSdk\Enums\EndPoints;

use Aqayepardakht\PhpSdk\DTOs\Pol\RequestToPayDto;
use Aqayepardakht\PhpSdk\DTOs\Pol\MandateOtpDto;
use Aqayepardakht\PhpSdk\DTOs\Pol\CreateMandateDto;
use Aqayepardakht\PhpSdk\DTOs\Pol\MandateReferenceDto;
use Aqayepardakht\PhpSdk\DTOs\Pol\DirectDebitDto;

class PolService
{
    public function __construct(
        private string $pin,
        private ?HttpClient $http = null,
        private string $baseUrl = EndPoints::POl_PRODUCTION,
        private ?string $accessToken = null,
    ) {
        $this->http ??= new CurlHttpClient();
        if ($this->accessToken !== null) {
            \Aqayepardakht\PhpSdk\Domain\Payment\PaymentValidation::accessToken(
                $this->accessToken,
            );
        }
    }

    public function withAccessToken(string $accessToken): self
    {
        \Aqayepardakht\PhpSdk\Domain\Payment\PaymentValidation::accessToken(
            $accessToken,
        );
        $service = clone $this;
        $service->accessToken = $accessToken;
        return $service;
    }

    public function authorize(AuthorizeRequestDto $request): Response
    {
        return $this->process(
            PolStrategyFactory::make(
                PolAction::AUTHORIZE,
                $this->pin,
                $request,
                $this->http,
                $this->baseUrl,
                $this->accessToken,
            ),
        );
    }

    public function getOtp(RequestPaymentDto $request): Response
    {
        $response = $this->process(
            PolStrategyFactory::make(
                PolAction::OTP,
                $this->pin,
                $request,
                $this->http,
                $this->baseUrl,
                $this->accessToken,
            ),
        );

        return $response;
    }

    public function verify(VerifyPaymentDto $request): Response
    {
        return $this->process(
            PolStrategyFactory::make(
                PolAction::VERIFY,
                $this->pin,
                $request,
                $this->http,
                $this->baseUrl,
                $this->accessToken,
            ),
        );
    }

    public function info(PaymentInfoDto $request): Response
    {
        return $this->process(
            PolStrategyFactory::make(
                PolAction::INFO,
                $this->pin,
                $request,
                $this->http,
                $this->baseUrl,
                $this->accessToken,
            ),
        );
    }

    public function requestRefund(RequestRefundDto $request): Response
    {
        return $this->process(
            PolStrategyFactory::make(
                PolAction::REQUESTREFUND,
                $this->pin,
                $request,
                $this->http,
                $this->baseUrl,
                $this->accessToken,
            ),
        );
    }

    public function inquiryRefund(InquiryRefundDto $request): Response
    {
        return $this->process(
            PolStrategyFactory::make(
                PolAction::INQUIRYREFUND,
                $this->pin,
                $request,
                $this->http,
                $this->baseUrl,
                $this->accessToken,
            ),
        );
    }

    public function requestToPay(RequestToPayDto $request): Response
    {
        return $this->execute(PolAction::REQUEST_TO_PAY, $request);
    }

    public function requestMandateOtp(MandateOtpDto $request): Response
    {
        return $this->execute(PolAction::MANDATE_OTP, $request);
    }

    public function createMandate(CreateMandateDto $request): Response
    {
        return $this->execute(PolAction::MANDATE_CREATE, $request);
    }

    public function mandateInfo(MandateReferenceDto $request): Response
    {
        return $this->execute(PolAction::MANDATE_INFO, $request);
    }

    public function cancelMandate(MandateReferenceDto $request): Response
    {
        return $this->execute(PolAction::MANDATE_CANCEL, $request);
    }

    public function paymentStatus(PaymentInfoDto $request): Response
    {
        return $this->execute(PolAction::PAYMENT_STATUS, $request);
    }

    public function retryPayment(PaymentInfoDto $request): Response
    {
        return $this->execute(PolAction::RETRY_PAYMENT, $request);
    }

    public function directDebit(DirectDebitDto $request): Response
    {
        return $this->execute(PolAction::DIRECT_DEBIT, $request);
    }

    private function execute(
        PolAction $action,
        \Aqayepardakht\PhpSdk\Interfaces\ValidatableDto $request,
    ): Response {
        return $this->process(
            PolStrategyFactory::make(
                $action,
                $this->pin,
                $request,
                $this->http,
                $this->baseUrl,
                $this->accessToken,
            ),
        );
    }

    protected function process(PaymentStrategy $strategy): Response
    {
        return $strategy->process();
    }
}
