<?php
namespace Aqayepardakht\PhpSdk\Tests\Unit;

use Aqayepardakht\PhpSdk\DTOs\{RequestPaymentDto, VerifyPaymentDto};
use Aqayepardakht\PhpSdk\Exceptions\TransportException;
use Aqayepardakht\PhpSdk\PaymentManager;
use Aqayepardakht\PhpSdk\Tests\Support\FakeHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AqpPaymentTest extends TestCase
{
    public function testCompleteCreateStartVerifyScenarioUsesTheCorrectRoutesAndKeepsThePayload(): void
    {
        $http = new FakeHttpClient([
            (object) ['status' => 'success', 'tracking_code' => 'T-123'],
            (object) ['status' => 'success', 'tracking_number' => 'BANK-456', 'card' => 'masked'],
        ]);
        $gateway = PaymentManager::make('aqp', 'test-pin', $http);
        $created = $gateway->requestPayment(new RequestPaymentDto(
            callback: 'https://merchant.example/callback', invoice_id: 'ORDER-1', identifier: 'ID-1', amount: 10000,
            name: 'Customer', ip: '127.0.0.1', mobile: '09120000000', email: 'buyer@example.com',
            description: 'Order payment', sms: false, autoverify: false, method: 'GET', national_code: '1234567890',
            cards: ['6037991234567890'], tracking_code: 'previous-reference',
        ));
        self::assertTrue($created->success);
        self::assertSame('T-123', $created->get('tracking_code'));
        self::assertSame('https://api.aqayepardakht.ir/v3/pay', $http->calls[0]['url']);
        self::assertSame([], $http->calls[0]['headers']);
        self::assertSame([
            'amount' => 10000, 'callback' => 'https://merchant.example/callback', 'invoice_id' => 'ORDER-1',
            'identifier' => 'ID-1', 'name' => 'Customer', 'ip' => '127.0.0.1', 'mobile' => '09120000000',
            'email' => 'buyer@example.com', 'description' => 'Order payment', 'sms' => false, 'autoverify' => false,
            'method' => 'GET', 'national_code' => '1234567890', 'cards' => ['6037991234567890'],
            'tracking_code' => 'previous-reference', 'pin' => 'test-pin',
        ], $http->calls[0]['parameters']);
        self::assertSame('https://api.aqayepardakht.ir/startpay/T-123', $gateway->getStartPayUrl($created->get('tracking_code')));
        self::assertCount(1, $http->calls, 'Resolving Start must not send an HTTP request.');
        $verified = $gateway->verifyPayment(new VerifyPaymentDto('T-123', 10000, 'POL-OTP', 'POL-purpose'));
        self::assertTrue($verified->success);
        self::assertSame('BANK-456', $verified->get('tracking_number'));
        self::assertSame('masked', $verified->get('card'));
        self::assertSame([
            'url' => 'https://api.aqayepardakht.ir/v3/verify',
            'parameters' => ['tracking_code' => 'T-123', 'amount' => 10000, 'pin' => 'test-pin'],
            'headers' => [],
        ], $http->calls[1]);
    }

    #[DataProvider('invalidTrackingResponses')]
    public function testCreateRejectsMissingOrInvalidTrackingCode(object $response): void
    {
        $gateway = PaymentManager::make('aqp', 'test-pin', new FakeHttpClient([$response]));
        $this->expectException(TransportException::class);
        $gateway->requestPayment(new RequestPaymentDto('https://merchant.example/callback', 'ORDER', '', 10000));
    }

    public static function invalidTrackingResponses(): iterable
    {
        yield 'missing' => [(object) ['status' => 'success']];
        yield 'null' => [(object) ['status' => 'success', 'tracking_code' => null]];
        yield 'empty' => [(object) ['status' => 'success', 'tracking_code' => '']];
        yield 'object' => [(object) ['status' => 'success', 'tracking_code' => (object) []]];
    }

    #[DataProvider('paymentOperations')]
    public function testGatewayRejectionIsReturnedAsFailure(string $operation): void
    {
        $http = new FakeHttpClient([(object) ['status' => 'error', 'message' => 'Rejected', 'code' => 'E-42']]);
        $gateway = PaymentManager::make('aqp', 'test-pin', $http);
        $result = $operation === 'create'
            ? $gateway->requestPayment(new RequestPaymentDto('https://merchant.example/callback', 'ORDER', '', 10000))
            : $gateway->verifyPayment(new VerifyPaymentDto('T-1', 10000));
        self::assertFalse($result->success);
        self::assertSame('Rejected', $result->message);
        self::assertSame('E-42', $result->code);
        self::assertCount(1, $http->calls);
    }

    public static function paymentOperations(): iterable { yield ['create']; yield ['verify']; }

    public function testCustomBaseUrlAppliesToCreateStartAndVerify(): void
    {
        $http = new FakeHttpClient([(object) ['status' => 'success', 'tracking_code' => 'T'], (object) ['status' => 'success']]);
        $gateway = PaymentManager::make('aqp', 'pin', $http, 'https://sandbox.example/v3');
        $gateway->requestPayment(new RequestPaymentDto('https://merchant.example/callback', '', '', 10000));
        self::assertSame('https://sandbox.example/v3/pay', $http->calls[0]['url']);
        self::assertSame('https://sandbox.example/startpay/A%2FB%27%3C', $gateway->getStartPayUrl("A/B'<"));
        $gateway->verifyPayment(new VerifyPaymentDto('T', 10000));
        self::assertSame('https://sandbox.example/v3/verify', $http->calls[1]['url']);
    }

    public function testInvalidInputNeverSendsAPayment(): void
    {
        $http = new FakeHttpClient();
        try {
            PaymentManager::make('aqp', 'pin', $http)->requestPayment(new RequestPaymentDto('bad-url', '', '', 10000));
            self::fail('Invalid callback must be rejected.');
        } catch (\InvalidArgumentException) { self::assertSame([], $http->calls); }
    }

    public function testTransportFailurePropagatesWithoutRetrying(): void
    {
        $http = new FakeHttpClient([new TransportException('Timed out')]);
        try {
            PaymentManager::make('aqp', 'pin', $http)->verifyPayment(new VerifyPaymentDto('T', 10000));
            self::fail('Transport exception must propagate.');
        } catch (TransportException $exception) {
            self::assertSame('Timed out', $exception->getMessage());
            self::assertCount(1, $http->calls);
        }
    }
    public function testEmptyStartReferenceIsRejectedWithoutHttp(): void
    {
        $http = new FakeHttpClient();
        try {
            PaymentManager::make('aqp','pin',$http)->getStartPayUrl(' ');
            self::fail('Start requires a tracking reference.');
        } catch (\InvalidArgumentException) { self::assertSame([], $http->calls); }
    }

    public function testInvalidVerifyAmountIsRejectedWithoutHttp(): void
    {
        $http = new FakeHttpClient();
        try {
            PaymentManager::make('aqp','pin',$http)->verifyPayment(new VerifyPaymentDto('T',1000));
            self::fail('Invalid amount must be rejected.');
        } catch (\InvalidArgumentException) { self::assertSame([], $http->calls); }
    }

}
