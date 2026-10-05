<?php

namespace Aqayepardakht\PhpSdk\Contracts;

use Aqayepardakht\PhpSdk\DTOs\Pol\RequestToPayDto;
use Aqayepardakht\PhpSdk\Response;

interface RequestToPay
{
    public function requestToPay(RequestToPayDto $request): Response;
}
