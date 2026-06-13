<?php

namespace Aqayepardakht\PhpSdk\Contracts;

use Aqayepardakht\PhpSdk\Invoice;

interface PaymentGateway{
    public function requestPayment(Invoice $invoice) : void|string;
    public function verifyPayment(array $params);
}