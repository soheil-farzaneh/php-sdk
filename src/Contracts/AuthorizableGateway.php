<?php

namespace Aqayepardakht\PhpSdk\Contracts;

use Aqayepardakht\PhpSdk\Response;

interface AuthorizableGateway {
    public function authorize() : Response;
}