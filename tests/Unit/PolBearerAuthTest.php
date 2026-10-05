<?php
namespace Aqayepardakht\PhpSdk\Tests\Unit;

use Aqayepardakht\PhpSdk\DTOs\{AuthorizeRequestDto, RequestPaymentDto, VerifyPaymentDto, PaymentInfoDto, RequestRefundDto, InquiryRefundDto};
use Aqayepardakht\PhpSdk\Exceptions\TransportException;
use Aqayepardakht\PhpSdk\PaymentManager;
use Aqayepardakht\PhpSdk\Tests\Support\FakeHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PolBearerAuthTest extends TestCase
{
    #[DataProvider('protectedOperations')]
    public function testEveryProtectedOperationRejectsMissingTokenBeforeHttp(string $method, object $request): void
    {
        $http = new FakeHttpClient();
        try {
            PaymentManager::make('pol', 'pin', $http)->{$method}($request);
            self::fail('POL authentication must be configured.');
        } catch (\LogicException $exception) {
            self::assertStringContainsString('access token', $exception->getMessage());
            self::assertSame([], $http->calls);
        }
    }

    public static function protectedOperations(): iterable
    {
        yield ['requestPayment', new RequestPaymentDto('https://merchant.example/callback','ORDER','ID',10000)];
        yield ['verifyPayment', new VerifyPaymentDto('T',10000,'12345')];
        yield ['paymentInquiry', new PaymentInfoDto('T',10000)];
        yield ['requestRefund', new RequestRefundDto('T')];
        yield ['inquiryRefund', new InquiryRefundDto('REF')];
    }

    public function testSeparateAdaptersDoNotLeakTokensAndAuthorizeStaysUnauthenticated(): void
    {
        $http = new FakeHttpClient([
            (object) ['status'=>'success','tracking_code'=>'A'],
            (object) ['status'=>'success','tracking_code'=>'B'],
            (object) ['status'=>'success','tracking_code'=>'A'],
            (object) ['status'=>'success','redirecturi'=>'https://bank.example/auth','access_token'=>'fresh-token'],
            (object) ['status'=>'success','tracking_code'=>'AQP'],
        ]);
        $base = PaymentManager::make('pol','pin',$http);
        $a = $base->withAccessToken('token-a');
        $b = $base->withAccessToken('token-b');
        $request = new RequestPaymentDto('https://merchant.example/callback','ORDER','ID',10000);
        $a->requestPayment($request);
        $b->requestPayment($request);
        $a->requestPayment($request);
        $result = $a->authorize(new AuthorizeRequestDto('https://merchant.example/callback','ID',10000,phone:'09123456789',invoice_id:'ORDER'));
        PaymentManager::make('aqp','pin',$http)->requestPayment($request);
        self::assertSame(['Authorization'=>'Bearer token-a'],$http->calls[0]['headers']);
        self::assertSame(['Authorization'=>'Bearer token-b'],$http->calls[1]['headers']);
        self::assertSame(['Authorization'=>'Bearer token-a'],$http->calls[2]['headers']);
        self::assertSame([],$http->calls[3]['headers']);
        self::assertSame([],$http->calls[4]['headers']);
        self::assertSame('fresh-token',$result->get('access_token'));
        self::assertSame('Bearer',$result->get('token_type'));
        $this->expectException(\LogicException::class);
        $base->requestPayment($request);
    }

    #[DataProvider('badTokens')]
    public function testEmptyOrUnsafeTokensAreRejected(string $token): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentManager::make('pol','pin',new FakeHttpClient())->withAccessToken($token);
    }
    public static function badTokens(): iterable
    {
        yield ['']; yield [' ']; yield ['Bearer token']; yield ["token\r\nInjected: value"];
    }

    public function testSuccessfulAuthorizeWithoutTokenIsRejected(): void
    {
        $gateway = PaymentManager::make('pol','pin',new FakeHttpClient([
            (object) ['status'=>'success','redirecturi'=>'https://bank.example/auth'],
        ]));
        $this->expectException(TransportException::class);
        $gateway->authorize(new AuthorizeRequestDto('https://merchant.example/callback','ID',10000,phone:'09123456789',invoice_id:'ORDER'));
    }
}
