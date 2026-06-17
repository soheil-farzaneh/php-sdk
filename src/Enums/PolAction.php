<?php

namespace Aqayepardakht\PhpSdk\Enums;

enum PolAction: string
{
    case AUTHORIZE = 'authorize';
    case OTP = 'otp';
    case VERIFY = 'verify';
    case INFO = 'info';
}