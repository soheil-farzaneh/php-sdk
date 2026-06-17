<?php

namespace Aqayepardakht\PhpSdk\Contracts;

use Aqayepardakht\PhpSdk\Response;
use Aqayepardakht\PhpSdk\DTOs\{
    RequestPaymentDto,
    VerifyPaymentDto,
};

interface PaymentGateway{
    public function requestPayment(RequestPaymentDto $request) : Response;
    public function verifyPayment(VerifyPaymentDto $request) : Response;
}