<?php
namespace Aqayepardakht\PhpSdk\Adapters;

use Aqayepardakht\PhpSdk\Contracts\{PaymentGateway, HttpClient};
use Aqayepardakht\PhpSdk\DTOs\{RequestPaymentDto, VerifyPaymentDto};
use Aqayepardakht\PhpSdk\Enums\EndPoints;
use Aqayepardakht\PhpSdk\Infrastructure\Browser\PaymentRedirector;
use Aqayepardakht\PhpSdk\Response;
use Aqayepardakht\PhpSdk\Services\Payment\Aqp\AqpService;

class AqpPaymentAdapter implements PaymentGateway
{
    private AqpService $service;

    public function __construct(string $pin, ?HttpClient $http = null, string $baseUrl = EndPoints::AQP_PRODUCTION)
    {
        $this->service = new AqpService($pin, $http, $baseUrl);
    }

    public function requestPayment(RequestPaymentDto $request): Response
    {
        return $this->service->create($request);
    }

    public function getStartPayUrl(string $trackingCode): string
    {
        return $this->service->getStartPayUrl($trackingCode);
    }

    public function start(string $trackingCode): never
    {
        (new PaymentRedirector())->redirect($this->getStartPayUrl($trackingCode));
    }

    public function verifyPayment(VerifyPaymentDto $request): Response
    {
        return $this->service->verify($request);
    }
}
