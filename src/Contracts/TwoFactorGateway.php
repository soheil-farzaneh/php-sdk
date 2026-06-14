<?php

namespace Aqayepardakht\PhpSdk\Contracts;

use Aqayepardakht\PhpSdk\Invoice;
use Aqayepardakht\PhpSdk\Response;

interface TwoFactorGateway {
    public function requestOtp(Invoice $invoice) : Response;
}