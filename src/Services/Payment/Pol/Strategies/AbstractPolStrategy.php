<?php

namespace Aqayepardakht\PhpSdk\Services\Payment\Pol\Strategies;

use Aqayepardakht\Http\Client;
use Aqayepardakht\PhpSdk\Interfaces\PaymentStrategy;
use Aqayepardakht\PhpSdk\Helper;
use Aqayepardakht\PhpSdk\Invoice;
use Aqayepardakht\PhpSdk\Response;

abstract class AbstractPolStrategy implements PaymentStrategy
{
    public function __construct(
        protected string $pin,
        protected Invoice $invoice,
    ) {
    }

    abstract protected function endpointAction(): string;

    abstract protected function getPaymentUrl(): string;

    protected function extraParams(): array
    {
        return [];
    }

    protected function onSuccess(object $response): array
    {
        return (array) $response;
    }

    public function process()
    {
        Helper::validateUrl($this->invoice->getCallback());

        $params         = $this->invoice->getItems();
        $params['pin']  = $this->pin;
        $params         = array_merge($params, $this->extraParams());

        $url = $this->getPaymentUrl();
        $response = (new Client())->post(Helper::getBaseUrl(
            $url, 
            $this->endpointAction()), 
            $params
        );
        
        $response = $response->json();
        
        if (!$response) {
            return Response::failure('مشکلی در اتصال به وجود آمد، لطفا دوباره تلاش کنید');
        }

        if ($response->status === 'error') {
            return Response::failure(
                $response->message ?? 'خطای نامشخص',
                $response->code ?? null
            );
        }

        return Response::success($this->onSuccess($response));
    }
}