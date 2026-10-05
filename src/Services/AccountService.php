<?php
namespace Aqayepardakht\PhpSdk\Services;

use Aqayepardakht\PhpSdk\Contracts\HttpClient;

class AccountService
{
    public function __construct(protected $account, protected $code, private ?HttpClient $http = null) {}

    public function transactions($pin = null): TransactionService
    {
        return new TransactionService($this->account, $this->code, $pin, $this->http);
    }
}
