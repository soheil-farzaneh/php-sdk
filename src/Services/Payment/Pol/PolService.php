<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol;

use Aqayepardakht\PhpSdk\Interfaces\PaymentStrategy;
use Aqayepardakht\PhpSdk\Services\Payment\Pol\PolStrategyFactory;
use Aqayepardakht\PhpSdk\Invoice;

class PolService {
    private string $pin;
    public Invoice $invoice;

    public function __construct(string $pin, Invoice $invoice) {
        $this->pin = $pin;
        $this->invoice = $invoice;
    }

    public function authorize(): self {
        $this->process(
            PolStrategyFactory::make('authorize', $this->pin, $this->invoice)
        );

        return $this;
    }

    public function getOtp(): self {
        $response = $this->process(
            PolStrategyFactory::make('otp', $this->pin, $this->invoice)
        );

        if ($response->status == 'success') {
            $trackingCode = $response->tracking_code;
            $this->invoice->setTrackingCode($trackingCode);
        }

        return $this;
    }

    public function verify(string $code): self {

        $this->process(PolStrategyFactory::make('verify', $code, $this->pin, $this->invoice));

        return $this;
    }

    protected function process(PaymentStrategy $strategy) {
        try {
            return $strategy->process();
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to process payment: " . $e->getMessage());
        }
    }

    public function getInvoice(): Invoice {
        return $this->invoice;
    }
}