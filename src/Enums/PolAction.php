<?php

namespace Aqayepardakht\PhpSdk\Enums;

enum PolAction: string
{
    case AUTHORIZE = 'authorize';
    case OTP = 'otp';
    case VERIFY = 'verify';
    case INFO = 'info';
    case REQUESTREFUND = 'request_refund';
    case INQUIRYREFUND = 'inquiry_refund';
}