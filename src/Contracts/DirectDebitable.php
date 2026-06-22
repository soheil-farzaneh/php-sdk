<?php

namespace Aqayepardakht\PhpSdk\Contracts;

interface DirectDebitable {
    public function createSubscription();
    public function cancelSubscription();
}