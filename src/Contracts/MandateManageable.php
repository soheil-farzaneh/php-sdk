<?php

namespace Aqayepardakht\PhpSdk\Contracts;

use Aqayepardakht\PhpSdk\DTOs\Pol\MandateOtpDto;
use Aqayepardakht\PhpSdk\DTOs\Pol\CreateMandateDto;
use Aqayepardakht\PhpSdk\DTOs\Pol\MandateReferenceDto;
use Aqayepardakht\PhpSdk\Response;

interface MandateManageable
{
    public function requestMandateOtp(MandateOtpDto $request): Response;
    public function createMandate(CreateMandateDto $request): Response;
    public function mandateInfo(MandateReferenceDto $request): Response;
    public function cancelMandate(MandateReferenceDto $request): Response;
}
