<?php

namespace Aqayepardakht\PhpSdk\Contracts;

use Aqayepardakht\PhpSdk\Response;
use Aqayepardakht\PhpSdk\DTOs\PaymentInfoDto;

interface PaymentInfoGateway {
    public function PaymentInfo(PaymentInfoDto $request) : Response;
}