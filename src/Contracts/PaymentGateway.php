<?php

namespace Aqayepardakht\PhpSdk\Contracts;

use Aqayepardakht\PhpSdk\Invoice;
use Aqayepardakht\PhpSdk\Response;

interface PaymentGateway{
    public function requestPayment(Invoice $invoice) : Response;
    public function verifyPayment(Invoice $invoice, string $code) : Response;
}