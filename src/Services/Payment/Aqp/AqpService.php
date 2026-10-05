<?php
namespace Aqayepardakht\PhpSdk\Services\Payment\Aqp;

use Aqayepardakht\PhpSdk\Contracts\HttpClient;
use Aqayepardakht\PhpSdk\DTOs\{RequestPaymentDto, VerifyPaymentDto};
use Aqayepardakht\PhpSdk\Enums\EndPoints;
use Aqayepardakht\PhpSdk\Infrastructure\Http\CurlHttpClient;
use Aqayepardakht\PhpSdk\Interfaces\PaymentStrategy;
use Aqayepardakht\PhpSdk\Response;
use Aqayepardakht\PhpSdk\Services\Payment\PaymentApiClient;

/** Shared AQP application service; every payment operation runs its dedicated strategy. */
final class AqpService
{
    private PaymentApiClient $api;

    public function __construct(
        string $pin,
        ?HttpClient $http = null,
        private string $baseUrl = EndPoints::AQP_PRODUCTION,
    ) {
        $this->api = new PaymentApiClient($http ?? new CurlHttpClient(), $baseUrl, $pin);
    }

    public function create(RequestPaymentDto $request): Response
    {
        return $this->process(AqpStrategyFactory::create($this->api, $request));
    }

    public function verify(VerifyPaymentDto $request): Response
    {
        return $this->process(AqpStrategyFactory::verify($this->api, $request));
    }

    public function getStartPayUrl(string $trackingCode): string
    {
        $response = $this->process(AqpStrategyFactory::start($trackingCode, $this->baseUrl));
        return $response->get('url');
    }

    private function process(PaymentStrategy $strategy): Response
    {
        return $strategy->process();
    }
}
