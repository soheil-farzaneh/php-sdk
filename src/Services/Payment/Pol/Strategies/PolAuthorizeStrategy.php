<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

class PolAuthorizeStrategy extends AbstractPolStrategy
{
    protected function requiresAccessToken(): bool { return false; }

    protected function endpointAction(): string
    {
        return 'authorize';
    }

    protected function onSuccess(object $response): array
    {
        if (!property_exists($response, 'redirecturi')
            || ($response->redirecturi !== null && (!is_string($response->redirecturi) || $response->redirecturi === ''))) {
            throw new \Aqayepardakht\PhpSdk\Exceptions\TransportException('The API response is missing its redirect URI.');
        }
        if (!isset($response->access_token) || !is_string($response->access_token) || $response->access_token === '') {
            throw new \Aqayepardakht\PhpSdk\Exceptions\TransportException('The API response is missing its access token.');
        }
        $tokenType = $response->token_type ?? 'Bearer';
        if (!is_string($tokenType) || strcasecmp($tokenType, 'Bearer') !== 0) {
            throw new \Aqayepardakht\PhpSdk\Exceptions\TransportException('The API returned an unsupported token type.');
        }
        $data = (array) $response;
        unset($data['status']);
        $data['token_type'] = $tokenType;
        return $data;
    }
}
