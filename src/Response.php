<?php

namespace Aqayepardakht\PhpSdk;


class Response
{
    public function __construct(
        public bool $success,
        public ?string $message = null,
        public $code = null,
        public array $data = [],
    ) {
    }

    public static function success(array $data = [], ?string $message = null): self
    {
        return new self(true, $message, null, $data);
    }

    public static function failure(string $message, $code = null, array $data = []): self
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