<?php 

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

use Aqayepardakht\PhpSdk\Interfaces\PaymentStrategy;
use Aqayepardakht\Http\Client;
use Aqayepardakht\PhpSdk\Helper;
use Aqayepardakht\PhpSdk\Invoice;

class PolOtpStrategy implements PaymentStrategy {

    protected $pin;

    protected $invoice;

    public function __construct(string $pin, Invoice $invoice) {
        $this->pin     = $pin;
        $this->invoice = $invoice;
    }

    public function process() {
        Helper::validateUrl($this->invoice->callback);
 
        $params        = $this->invoice->getItems();
        $params["pin"] = $this->pin;

        $response = (new Client())->post(Helper::getBaseUrl('create'), $params);

        $response = $response->json();

        if (!$response) {
            throw new \Exception("Error: مشکلی در اتصال به آقای پرداخت وجود دارد لطفا دوباره تلاش کنید", 0);
        }

        if ($response->status == 'error') {
            throw new \Exception("Error: ".$response->message, $response->code);
        }

        return $response;
   
    }
}