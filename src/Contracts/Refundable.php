<?php

namespace Aqayepardakht\PhpSdk\Contracts;

use Aqayepardakht\PhpSdk\Response;
use Aqayepardakht\PhpSdk\DTOs\{
    RequestRefundDto,
    InquiryRefundDto,
};

interface Refundable{
    public function requestRefund(RequestRefundDto $request) : Response;
    public function inquiryRefund(InquiryRefundDto $request) : Response;
}