<?php

namespace Aqayepardakht\PhpSdk\Contracts;

use Aqayepardakht\PhpSdk\DTOs\Pol\DirectDebitDto;
use Aqayepardakht\PhpSdk\Response;

interface DirectDebitGateway
{
    public function directDebit(DirectDebitDto $request): Response;
}
