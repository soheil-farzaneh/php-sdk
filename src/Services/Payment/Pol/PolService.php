<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol;

use Aqayepardakht\PhpSdk\Interfaces\PaymentStrategy;
use Aqayepardakht\PhpSdk\Services\Payment\Pol\PolStrategyFactory;
use Aqayepardakht\PhpSdk\Invoice;
use Aqayepardakht\PhpSdk\Response;

class PolService {

    public function __construct(
        private string $pin, 
        public Invoice $invoice
    ) {
    }

    public function authorize(): Response 
    {
        return $this->process(
            PolStrategyFactory::make('authorize', $this->pin, $this->invoice)
        );

    }

    public function getOtp(): Response 
    {
        $response = $this->process(
            PolStrategyFactory::make('otp', $this->pin, $this->invoice)
        );

        return $response;
    }

    public function verify(string $code): Response 
    {
        return $this->process(
            PolStrategyFactory::make('verify', $code, $this->pin, $this->invoice)
        );
    }

    protected function process(PaymentStrategy $strategy) 
    {
        try {
            return $strategy->process();
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to process payment: " . $e->getMessage());
        }
    }
}