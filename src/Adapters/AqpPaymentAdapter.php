<?php

// namespace Aqayepardakht\PhpSdk\Adapters;

// use Aqayepardakht\PhpSdk\Contracts\PaymentGateway;
// use Aqayepardakht\PhpSdk\Invoice;
// use Aqayepardakht\PhpSdk\Api; 

// class AqpPaymentAdapter implements PaymentGateway
// {
//     protected $api;

//     public function __construct(string $pin) {
//         $this->api = new Api($pin); 
//     }

//     public function requestPayment(Invoice $invoice) {

//         $items = $invoice->getItems();

//         return $this->api->create($items['amount'], $items['callback'], $items['invoice_id']);
//     }

//     public function verifyPayment(array $params) {
   
//         return $this->api->verify($params['amount'], $params['transid']);
//     }
// }