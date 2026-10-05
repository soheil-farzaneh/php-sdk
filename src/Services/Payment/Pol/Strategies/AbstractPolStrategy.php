<?php
namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

use Aqayepardakht\PhpSdk\Contracts\HttpClient;
use Aqayepardakht\PhpSdk\Enums\EndPoints;
use Aqayepardakht\PhpSdk\Infrastructure\Http\CurlHttpClient;
use Aqayepardakht\PhpSdk\Interfaces\{PaymentStrategy, ValidatableDto};
use Aqayepardakht\PhpSdk\Response;
use Aqayepardakht\PhpSdk\Services\Payment\PaymentApiClient;

abstract class AbstractPolStrategy implements PaymentStrategy
{
    private HttpClient $http;

    public function __construct(
        protected string $pin,
        protected ValidatableDto $request,
        ?HttpClient $http = null,
        protected string $baseUrl = EndPoints::POl_PRODUCTION,
        protected ?string $accessToken = null,
    ) {
        $this->http = $http ?? new CurlHttpClient();
    }

    abstract protected function endpointAction(): string;
    protected function requiresAccessToken(): bool { return true; }
    protected function getPaymentUrl(): string { return $this->baseUrl; }
    protected function onSuccess(object $response): array { return (array) $response; }

    protected function validateRequest(): void { $this->request->validate(); }

    public function process(): Response
    {
        $this->validateRequest();
        $token = $this->requiresAccessToken() ? $this->accessToken : null;
        if ($this->requiresAccessToken() && $token === null) {
            throw new \LogicException('POL operation requires an access token. Call withAccessToken() first.');
        }
        $response = (new PaymentApiClient($this->http, $this->getPaymentUrl(), $this->pin, $token))
            ->request($this->endpointAction(), $this->request->toArray());
        if (!$response->success) { return $response; }
        return Response::success($this->onSuccess((object) $response->data));
    }
}
