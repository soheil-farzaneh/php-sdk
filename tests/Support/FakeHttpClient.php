<?php
namespace Aqayepardakht\PhpSdk\Tests\Support;

use Aqayepardakht\PhpSdk\Contracts\HttpClient;

final class FakeHttpClient implements HttpClient
{
    public array $calls = [];

    /** @param list<object|\Throwable> $responses */
    public function __construct(private array $responses = []) {}

    public function post(string $url, array $parameters, array $headers = []): object
    {
        $this->calls[] = ['url' => $url, 'parameters' => $parameters, 'headers' => $headers];
        if ($this->responses === []) {
            throw new \LogicException('Unexpected HTTP request: fake response queue is empty.');
        }
        $response = array_shift($this->responses);
        if ($response instanceof \Throwable) { throw $response; }
        return $response;
    }
}
