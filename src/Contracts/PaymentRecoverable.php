<?php
namespace Aqayepardakht\PhpSdk\Contracts;
use Aqayepardakht\PhpSdk\DTOs\PaymentInfoDto;
use Aqayepardakht\PhpSdk\Response;
interface PaymentRecoverable
{
    public function paymentStatus(PaymentInfoDto $request): Response;
    public function retryPayment(PaymentInfoDto $request): Response;
}
