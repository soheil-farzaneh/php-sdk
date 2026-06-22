<?php

namespace Aqayepardakht\PhpSdk\Contracts;

use Aqayepardakht\PhpSdk\DTOs\AuthorizeRequestDto;
use Aqayepardakht\PhpSdk\Response;

interface Authorizable {
    public function authorize(AuthorizeRequestDto $request) : Response;
}