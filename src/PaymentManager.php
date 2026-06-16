<?php

namespace Aqayepardakht\PhpSdk;

use Aqayepardakht\PhpSdk\Adapters\PolPaymentAdapter;
//use Aqayepardakht\PhpSdk\Adapters\AqpPaymentAdapter;

class PaymentManager {
    
    public static function make(string $driver, string $pin) {
        switch ($driver) {
            case 'pol':
                return new PolPaymentAdapter($pin);
            // case 'Aqp':
            //     return new AqpPaymentAdapter($pin);
            default:
                throw new \InvalidArgumentException("درگاه نامعتبر است");
        }
    }
}