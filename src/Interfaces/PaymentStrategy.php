<?php
namespace Aqayepardakht\PhpSdk\Interfaces;

interface PaymentStrategy
{
    /** @return mixed Legacy AQP and POL operations retain their own result types. */
    public function process();
}
