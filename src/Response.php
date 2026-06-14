<?php

namespace Aqayepardakht\PhpSdk;


class Response
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $message = null,
        public readonly ?int $code = null,
        public readonly array $data = [],
    ) {
    }

    public static function success(array $data = [], ?string $message = null): self
    {
        return new self(true, $message, null, $data);
    }

    public static function failure(string $message, ?int $code = null, array $data = []): self
    {
        return new self(false, $message, $code, $data);
    }

   
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'code'    => $this->code,
            'data'    => $this->data,
        ];
    }
}