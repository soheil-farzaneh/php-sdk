<?php 

namespace Aqayepardakht\PhpSdk\Services\Payment\Strategy;

use Aqayepardakht\PhpSdk\Helper;
use Aqayepardakht\PhpSdk\Interfaces\PaymentStrategy;

class StartPaymentStrategy implements PaymentStrategy {
    /**
     * Payment Trace Code
     *
     * @var String
    */
    protected $traceCode;

    public function __construct($traceCode) {
        $this->traceCode  = $traceCode;
    }

    public function process() {
        $result = [
            'url' => $this->getStartPayUrl()
        ];
    
        $output = json_encode($result);
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
        } else {
            header('Content-Type: text/html');
            $output = "<script>window.location.href='{$result['url']}'</script>";
        }
    
        echo $output;
        exit;
    }

    public function getStartPayUrl() {
        $url = str_replace('v3/', '' ,Helper::getBaseUrl(config::('apiHttpAqp')));

        return $url . 'startpay/'.$this->traceCode;
    }
}