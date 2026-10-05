<?php
namespace Aqayepardakht\PhpSdk\Services;

use Aqayepardakht\PhpSdk\Contracts\HttpClient;
use Aqayepardakht\PhpSdk\Infrastructure\Http\CurlHttpClient;

class TransactionService
{
    protected $page = null;
    protected $start = null;
    protected $end = null;
    private HttpClient $http;

    public function __construct(protected $account, protected $code, protected $pin = null, ?HttpClient $http = null)
    {
        $this->http = $http ?? new CurlHttpClient();
    }

    public function get(): object
    {
        if ($this->account === null || $this->account === '' || $this->code === null || $this->code === '') {
            throw new \InvalidArgumentException('Transaction queries require account and code credentials.');
        }
        $response = $this->http->post('https://panel.aqayepardakht.ir/api/v2/transactions/'.($this->pin ? 'gate' : 'account'), [
            // Preserve the legacy wire spelling until the API contract is confirmed.
            'accout' => $this->account,
            'code' => $this->code,
            'page' => $this->page,
            'start_date' => $this->start,
            'end_date' => $this->end,
            'pin' => $this->pin ?: null,
        ]);
        if (!isset($response->status)) {
            throw new \Aqayepardakht\PhpSdk\Exceptions\TransportException('The API response is missing its status.');
        }
        if ($response->status === 'error') {
            throw new \RuntimeException((string) ($response->message ?? $response->status), is_numeric($response->code ?? null) ? (int) $response->code : 0);
        }
        return $response;
    }

    public function startDate($date): self { $this->start = $date; return $this; }
    public function endDate($date): self { $this->end = $date; return $this; }
    public function page($page): self { $this->page = $page; return $this; }
}
