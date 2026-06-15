<?php

namespace Aqayepardakht\PhpSdk\Contracts;

use Aqayepardakht\PhpSdk\Response;

interface PaymentGateway{
    public function requestPayment() : Response;
    public function verifyPayment(string $code) : Response;
}