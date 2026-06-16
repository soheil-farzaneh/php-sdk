<?php

namespace Aqayepardakht\PhpSdk\Adapters;

use Aqayepardakht\PhpSdk\Contracts\{PaymentGateway, AuthorizableGateway};
use Aqayepardakht\PhpSdk\Services\Payment\Pol\PolService;
use Aqayepardakht\PhpSdk\Invoice;
use Aqayepardakht\PhpSdk\Response;

class PolPaymentAdapter implements PaymentGateway, AuthorizableGateway
{
  
    public function __construct(
        protected string $pin,
    ) {
    }

    public function authorize(Invoice $invoice): Response
    {
        return $this->service($invoice)->authorize();
    }

    public function requestPayment(Invoice $invoice) : Response
    {
        return $this->service($invoice)->getOtp();
    }

    public function verifyPayment(Invoice $invoice,string $code) : Response 
    {
        return $this->service($invoice)->verify($code);
    }

    private function service($invoice) : PolService
    {
        return new PolService($this->pin, $invoice);
    }
}