<?php
namespace Aqayepardakht\PhpSdk\Tests\Unit;

use Aqayepardakht\PhpSdk\DTOs\{AuthorizeRequestDto, RequestPaymentDto, VerifyPaymentDto, PaymentInfoDto, RequestRefundDto, InquiryRefundDto};
use Aqayepardakht\PhpSdk\Enums\PolAction;
use Aqayepardakht\PhpSdk\Exceptions\TransportException;
use Aqayepardakht\PhpSdk\PaymentManager;
use Aqayepardakht\PhpSdk\Services\Payment\Pol\PolStrategyFactory;
use Aqayepardakht\PhpSdk\Tests\Support\FakeHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PolPaymentTest extends TestCase
{
    #[DataProvider('operations')]
    public function testEveryOperationUsesItsRouteAndPreservesItsResponseMapping(string $method, object $dto, string $route, array $payload, object $upstream, array $expected): void
    {
        $http = new FakeHttpClient([$upstream]);
        $result = PaymentManager::make('pol', 'pin', $http)->withAccessToken('fake-token')->{$method}($dto);
        self::assertTrue($result->success);
        self::assertEquals($expected, $result->data);
        self::assertSame('https://aqp-p.shaparak.ir/api/'.$route, $http->calls[0]['url']);
        self::assertSame(array_merge($payload, ['pin' => 'pin']), $http->calls[0]['parameters']);
        self::assertSame($method === 'authorize' ? [] : ['Authorization'=>'Bearer fake-token'], $http->calls[0]['headers']);
    }

    #[DataProvider('operations')]
    public function testEveryOperationReturnsGatewayErrorsWithoutSuccessProjection(string $method, object $dto, string $route, array $payload, object $upstream, array $expected): void
    {
        $http = new FakeHttpClient([(object) ['status' => 'error', 'message' => 'Rejected', 'code' => 42]]);
        $result = PaymentManager::make('pol', 'pin', $http, 'https://sandbox.example/api')->withAccessToken('fake-token')->{$method}($dto);
        self::assertFalse($result->success);
        self::assertSame('Rejected', $result->message);
        self::assertSame(42, $result->code);
        self::assertSame([], $result->data);
        self::assertSame('https://sandbox.example/api/'.$route, $http->calls[0]['url']);
    }

    public static function operations(): iterable
    {
        yield 'authorize' => ['authorize', new AuthorizeRequestDto('https://merchant.example/callback', 'ID', 10000, purpose: 'GPPC', phone:'09123456789',invoice_id: 'ORDER'), 'authorize',
            ['amount'=>10000,'callback'=>'https://merchant.example/callback','identifier'=>'ID','phone'=>'09123456789','purpose'=>'GPPC','invoice_id'=>'ORDER'],
            (object) ['status'=>'success','redirecturi'=>'https://bank.example/auth','access_token'=>'fake-token','token_type'=>'Bearer'], ['redirecturi'=>'https://bank.example/auth','access_token'=>'fake-token','token_type'=>'Bearer']];
        yield 'otp' => ['requestPayment', new RequestPaymentDto('https://merchant.example/callback', 'ORDER', 'ID', 10000, sms: false), 'create',
            ['amount'=>10000,'callback'=>'https://merchant.example/callback','invoice_id'=>'ORDER','identifier'=>'ID','sms'=>false],
            (object) ['status'=>'success','tracking_code'=>'T'], ['tracking_code'=>'T']];
        yield 'verify' => ['verifyPayment', new VerifyPaymentDto('T', 10000, '12345', 'sale'), 'verify',
            ['tracking_code'=>'T','amount'=>10000,'code'=>'12345','purpose'=>'sale'],
            (object) ['status'=>'success','tracking_code'=>'T'], ['tracking_code'=>'T']];
        yield 'info' => ['paymentInquiry', new PaymentInfoDto('T', 10000), 'payment-info', ['tracking_code'=>'T','amount'=>10000],
            (object) ['status'=>'success','data'=>(object) ['paid'=>true]], ['data'=>(object) ['paid'=>true]]];
        yield 'refund' => ['requestRefund', new RequestRefundDto('T'), 'request-refund', ['tracking_code'=>'T'],
            (object) ['status'=>'success','tracking_code'=>'REF'], ['data'=>'REF']];
        yield 'refund inquiry' => ['inquiryRefund', new InquiryRefundDto('REF'), 'inquiry-refund', ['tracking_code'=>'REF'],
            (object) ['status'=>'success','data'=>(object) ['refunded'=>true]], ['data'=>(object) ['refunded'=>true]]];
    }

    public function testAuthorizeOtpVerifySequenceRetainsIdentifiersAndOtp(): void
    {
        $http = new FakeHttpClient([
            (object) ['status'=>'success','redirecturi'=>'https://bank.example/auth','access_token'=>'issued-token','token_type'=>'Bearer'],
            (object) ['status'=>'success','tracking_code'=>'T-1'],
            (object) ['status'=>'success','tracking_code'=>'T-1'],
        ]);
        $gateway = PaymentManager::make('pol', 'pin', $http);
        $authorized = $gateway->authorize(new AuthorizeRequestDto('https://merchant.example/callback','ID-1',10000,phone:'09123456789',invoice_id:'ORDER-1'));
        self::assertSame('https://bank.example/auth', $authorized->get('redirecturi'));
        $gateway = $gateway->withAccessToken($authorized->get('access_token'));
        $otp = $gateway->requestPayment(new RequestPaymentDto('https://merchant.example/callback','ORDER-1','ID-1',10000));
        self::assertTrue($gateway->verifyPayment(new VerifyPaymentDto($otp->get('tracking_code'),10000,'12345'))->success);
        self::assertSame('ID-1', $http->calls[0]['parameters']['identifier']);
        self::assertSame('ID-1', $http->calls[1]['parameters']['identifier']);
        self::assertSame('ORDER-1', $http->calls[0]['parameters']['invoice_id']);
        self::assertSame('ORDER-1', $http->calls[1]['parameters']['invoice_id']);
        self::assertSame('12345', $http->calls[2]['parameters']['code']);
        self::assertSame([], $http->calls[0]['headers']);
        self::assertSame(['Authorization'=>'Bearer issued-token'], $http->calls[1]['headers']);
        self::assertSame(['Authorization'=>'Bearer issued-token'], $http->calls[2]['headers']);
        self::assertCount(3, $http->calls);
    }

    #[DataProvider('invalidPolRequests')]
    public function testInvalidPolInputIsRejectedBeforeHttp(string $method, object $request): void
    {
        $http = new FakeHttpClient();
        try {
            PaymentManager::make('pol','pin',$http)->withAccessToken('fake-token')->{$method}($request);
            self::fail('Invalid POL input must be rejected.');
        } catch (\InvalidArgumentException) { self::assertSame([], $http->calls); }
    }

    public static function invalidPolRequests(): iterable
    {
        yield 'empty authorize identifier' => ['authorize',new AuthorizeRequestDto('https://merchant.example/callback',' ',10000,phone:'09123456789',invoice_id:'ORDER')];
        yield 'missing authorize invoice' => ['authorize',new AuthorizeRequestDto('https://merchant.example/callback','ID',10000,'09123456789','')];
        yield 'blank authorize invoice' => ['authorize',new AuthorizeRequestDto('https://merchant.example/callback','ID',10000,phone:'09123456789',invoice_id:' ')];
        yield 'empty OTP identifier' => ['requestPayment',new RequestPaymentDto('https://merchant.example/callback','ORDER','',10000)];
        yield 'missing OTP code' => ['verifyPayment',new VerifyPaymentDto('T',10000)];
        yield 'empty refund tracking' => ['requestRefund',new RequestRefundDto('')];
        yield 'empty inquiry tracking' => ['inquiryRefund',new InquiryRefundDto(' ')];
    }

    public function testAuthorizeRejectsMissingRedirectUri(): void
    {
        $gateway=PaymentManager::make('pol','pin',new FakeHttpClient([(object) ['status'=>'success']]));
        $this->expectException(TransportException::class);
        $gateway->authorize(new AuthorizeRequestDto('https://merchant.example/callback','ID',10000,phone:'09123456789',invoice_id:'ORDER'));
    }

    public function testFactoryRejectsAnActionWithTheWrongDto(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PolStrategyFactory::make(PolAction::AUTHORIZE, 'pin', new RequestRefundDto('T'));
    }

    public function testFactoryRejectsUnknownActions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PolStrategyFactory::make('unknown','pin',new RequestRefundDto('T'));
    }
    public function testTransportFailurePropagatesWithoutRetrying(): void
    {
        $http = new FakeHttpClient([new TransportException('Timed out')]);
        try {
            PaymentManager::make('pol','pin',$http)->withAccessToken('fake-token')->verifyPayment(new VerifyPaymentDto('T',10000,'12345'));
            self::fail('Transport exception must propagate.');
        } catch (TransportException $exception) {
            self::assertSame('Timed out',$exception->getMessage());
            self::assertCount(1,$http->calls);
        }
    }

}
