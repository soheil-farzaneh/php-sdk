<?php

namespace Aqayepardakht\PhpSdk\Contracts;

use Aqayepardakht\PhpSdk\Invoice;
use Aqayepardakht\PhpSdk\Response;

interface AuthorizableGateway {
    public function authorize(Invoice $invoice) : Response;
}