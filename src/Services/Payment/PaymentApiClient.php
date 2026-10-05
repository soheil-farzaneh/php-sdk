<?php
namespace Aqayepardakht\PhpSdk\Services\Payment;

use Aqayepardakht\PhpSdk\Contracts\HttpClient;
use Aqayepardakht\PhpSdk\Exceptions\TransportException;
use Aqayepardakht\PhpSdk\Helper;
use Aqayepardakht\PhpSdk\Response;

/** Application boundary for gateway responses; transport failures remain exceptions. */
final class PaymentApiClient
{
    public function __construct(
        private HttpClient $http,
        private string $baseUrl,
        private string $pin,
        private ?string $accessToken = null,
    ) {
        if (trim($pin) === '') {
            throw new \InvalidArgumentException('Gateway pin is required.');
        }
        Helper::validateUrl($baseUrl);
        if ($accessToken !== null) {
            \Aqayepardakht\PhpSdk\Domain\Payment\PaymentValidation::accessToken($accessToken);
        }
    }

    public function request(string $action, array $parameters): Response
    {
        $response = $this->http->post(
            Helper::getBaseUrl($this->baseUrl, $action),
            array_merge($parameters, ['pin' => $this->pin]),
            $this->accessToken === null ? [] : ['Authorization' => 'Bearer '.$this->accessToken],
        );
        if (!isset($response->status) || !is_string($response->status) || $response->status === '') {
            throw new TransportException('The API response is missing its status.');
        }
        if ($response->status === 'error') {
            return Response::failure(
                is_string($response->message ?? null) ? $response->message : 'خطای نامشخص',
                $response->code ?? null,
            );
        }
        // Preserve upstream status semantics; do not infer a new gateway status vocabulary.
        return Response::success((array) $response);
    }
}
