<?php
namespace Aqayepardakht\PhpSdk\Tests\Unit;
use PHPUnit\Framework\TestCase;
use Aqayepardakht\PhpSdk\Tests\Support\FakeHttpClient;
use Aqayepardakht\PhpSdk\PaymentManager;
use Aqayepardakht\PhpSdk\DTOs\PaymentInfoDto;
use Aqayepardakht\PhpSdk\Contracts\PaymentRecoverable;
class PolPaymentRecoveryTest extends TestCase
{
    public function test_status_preserves_pending_review_and_retry_flags(): void
    {
        foreach (["pending", "review", "paid", "failed"] as $state) {
            $body = (object) [
                "result" => $state,
                "can_retry" => $state === "failed",
                "order_invoice_id" => "ORDER",
                "tracking_code" => "LATEST",
            ];
            $http = new FakeHttpClient([
                (object) ["status" => "success", "data" => $body],
            ]);
            $gateway = PaymentManager::make(
                "pol",
                "PIN",
                $http,
            )->withAccessToken("TOKEN");
            self::assertInstanceOf(PaymentRecoverable::class, $gateway);
            $r = $gateway->paymentStatus(new PaymentInfoDto("ROOT", 1000));
            self::assertSame($body, $r->get("data"));
            self::assertStringEndsWith(
                "/payment-status",
                $http->calls[0]["url"],
            );
            self::assertSame(
                ["Authorization" => "Bearer TOKEN"],
                $http->calls[0]["headers"],
            );
        }
    }
    public function test_retry_returns_new_invoice_tracking_and_oauth_without_changing_root(): void
    {
        $http = new FakeHttpClient([
            (object) [
                "status" => "success",
                "tracking_code" => "CHILD",
                "invoice_id" => "RETRY-ORDER",
                "redirecturi" => "https://bank.example/auth",
                "access_token" => "TOKEN",
                "data" => (object) ["order_invoice_id" => "ROOT-ORDER"],
            ],
        ]);
        $r = PaymentManager::make("pol", "PIN", $http)
            ->withAccessToken("TOKEN")
            ->retryPayment(new PaymentInfoDto("ROOT", 10000));
        self::assertSame("CHILD", $r->get("tracking_code"));
        self::assertSame("ROOT-ORDER", $r->get("data")->order_invoice_id);
        self::assertStringEndsWith("/payments/retry", $http->calls[0]["url"]);
        self::assertSame(
            ["tracking_code" => "ROOT", "amount" => 10000, "pin" => "PIN"],
            $http->calls[0]["parameters"],
        );
    }
    public function test_recovery_operations_require_bearer_and_reject_invalid_amount(): void
    {
        foreach (["paymentStatus", "retryPayment"] as $method) {
            $http = new FakeHttpClient();
            $gateway = PaymentManager::make("pol", "PIN", $http);
            try {
                $gateway->{$method}(new PaymentInfoDto("ROOT", 10000));
                self::fail("Missing bearer accepted");
            } catch (\LogicException $e) {
                self::assertSame([], $http->calls);
            }
            try {
                $gateway
                    ->withAccessToken("TOKEN")
                    ->{$method}(new PaymentInfoDto("ROOT", 1000.5));
                self::fail("Fraction accepted");
            } catch (\InvalidArgumentException $e) {
                self::assertSame([], $http->calls);
            }
        }
    }
    public function test_rejected_retry_is_not_repeated(): void
    {
        $http = new FakeHttpClient([
            (object) [
                "status" => "error",
                "code" => -1,
                "message" => "Outcome is not confirmed as failed",
            ],
        ]);
        $r = PaymentManager::make("pol", "PIN", $http)
            ->withAccessToken("TOKEN")
            ->retryPayment(new PaymentInfoDto("ROOT", 10000));
        self::assertFalse($r->success);
        self::assertSame(-1, $r->code);
        self::assertCount(1, $http->calls);
    }
}
