<?php

namespace Aqayepardakht\PhpSdk\Enums;

enum PolAction: string
{
    case AUTHORIZE = "authorize";
    case OTP = "otp";
    case VERIFY = "verify";
    case INFO = "payment-info";
    case REQUESTREFUND = "request_refund";
    case INQUIRYREFUND = "inquiry_refund";
    case REQUEST_TO_PAY = "request_to_pay";
    case MANDATE_OTP = "mandate_otp";
    case MANDATE_CREATE = "mandate_create";
    case MANDATE_INFO = "mandate_info";
    case MANDATE_CANCEL = "mandate_cancel";
    case PAYMENT_STATUS = "payment_status";
    case RETRY_PAYMENT = "retry_payment";
    case DIRECT_DEBIT = "direct_debit";
}
