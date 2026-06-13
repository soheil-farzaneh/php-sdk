<?php

namespace Aqayepardakht\PhpSdk\Adapters;

use Aqayepardakht\PhpSdk\Contracts\{PaymentGateway, TwoFactorGateway};
use Aqayepardakht\PhpSdk\Services\Payment\Pol\PolService;
use Aqayepardakht\PhpSdk\Invoice;

class PolPaymentAdapter implements PaymentGateway, TwoFactorGateway
{
    protected $pin;

    public function __construct(string $pin) {

        $this->pin = $pin;
    }

    public function requestPayment(Invoice $invoice): Void {

        $polService = new PolService($this->pin, $invoice);
        $polService->authorize();

    }

    public function requestOtp(Invoice $invoice) {

        $polService = new PolService($this->pin, $invoice);
        return $polService->getOtp();
    }


    public function verifyPayment(Invoice $invoice, string $code) {

        $polService = new PolService($this->pin, $invoice);
        return $polService->verify($code);
    }
}