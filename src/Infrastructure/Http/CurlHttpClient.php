<?php
namespace Aqayepardakht\PhpSdk\Infrastructure\Http;

use Aqayepardakht\PhpSdk\Contracts\HttpClient;
use Aqayepardakht\PhpSdk\Exceptions\TransportException;

/** Sends the same form-encoded POST bodies as the original HTTP dependency. */
final class CurlHttpClient implements HttpClient
{
    public function __construct(
        private int $timeout = 30,
        private int $connectTimeout = 10,
    ) {
        if ($timeout < 1 || $connectTimeout < 1 || $connectTimeout > $timeout) {
            throw new \InvalidArgumentException('Invalid HTTP timeout configuration.');
        }
    }

    public function post(string $url, array $parameters, array $headers = []): object
    {
        $headers = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], $headers);
        $headerLines = [];
        foreach ($headers as $name => $value) {
            if (!is_string($name) || !preg_match('/^[A-Za-z0-9-]+$/D', $name)
                || !is_string($value) || preg_match('/[\x00-\x1F\x7F]/', $value)) {
                throw new \InvalidArgumentException('Invalid HTTP header.');
            }
            $headerLines[] = $name.': '.$value;
        }
        if (!extension_loaded('curl')) {
            throw new TransportException('The PHP cURL extension is required.');
        }
        $handle = curl_init();
        if ($handle === false) {
            throw new TransportException('Unable to initialize the HTTP client.');
        }
        try {
            $configured = curl_setopt_array($handle, [
                CURLOPT_URL => rtrim($url, '/'),
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($parameters),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headerLines,
                CURLOPT_SSL_VERIFYHOST =>0,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP,
            ]);
            if (!$configured) {
                throw new TransportException('Unable to configure the HTTP client.');
            }
            $body = curl_exec($handle);echo($body);
            $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            if ($body === false) {
                // Do not expose URLs, request credentials or upstream response bodies.
                throw new TransportException('HTTP transport failed.', curl_errno($handle));
            }
        } finally {
            curl_close($handle);
        }
        if ($status < 200 || $status >= 300) {
            throw new TransportException('The API returned HTTP status '.$status.'.', $status);
        }
        try {
            $decoded = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new TransportException('The API returned invalid JSON.', 0, $exception);
        }
        if (!is_object($decoded)) {
            throw new TransportException('The API response must be a JSON object.');
        }
        return $decoded;
    }
}
