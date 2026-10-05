<?php
namespace Aqayepardakht\PhpSdk\Contracts;

/** Transport boundary: implementations return a decoded JSON object or throw. */
interface HttpClient
{
    public function post(string $url, array $parameters, array $headers = []): object;
}
