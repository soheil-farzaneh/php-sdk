<?php

namespace Aqayepardakht\PhpSdk\Contracts;

use Aqayepardakht\PhpSdk\Response;
use Aqayepardakht\PhpSdk\DTOs\PaymentInfoDto;

interface PaymentInquiry {
    public function PaymentInquiry(PaymentInfoDto $request) : Response;
}