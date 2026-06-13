<?php

namespace Aqayepardakht\PhpSdk\Contracts;

interface TwoFactorGateway {
    public function requestOtp();
}