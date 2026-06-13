<?php

namespace Aqayepardakht\PhpSdk\Contracts;

interface HasSubscription {
    public function createSubscription();
    public function cancelSubscription();
}