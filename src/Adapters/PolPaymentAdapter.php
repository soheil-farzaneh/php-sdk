<?php

namespace Aqayepardakht\PhpSdk\Adapters;

use Aqayepardakht\PhpSdk\Contracts\{PaymentGateway, TwoFactorGateway};
use Aqayepardakht\PhpSdk\Services\Payment\Pol\PolService;
use Aqayepardakht\PhpSdk\Invoice;
use Aqayepardakht\PhpSdk\Response;

class PolPaymentAdapter implements PaymentGateway, TwoFactorGateway
{
  
    public function __construct(
        protected string $pin,
        public Invoice $invoice
    ) {
    }

    public function authorize(): Response
    {
        return $this->service()->authorize();
    }

    public function requestPayment() : Response
    {
        return $this->service()->getOtp();
    }

    public function verifyPayment(string $code) : Response 
    {
        return $this->service()->verify($code);
    }

    private function service() : PolService
    {
        return new PolService($this->pin, $this->invoice);
    }
}