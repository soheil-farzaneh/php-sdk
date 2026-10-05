<?php

namespace Aqayepardakht\PhpSdk\Tests\Unit;

use Aqayepardakht\PhpSdk\Adapters\PolPaymentAdapter;
use Aqayepardakht\PhpSdk\Contracts\{DirectDebitGateway, MandateManageable, RequestToPay};
use Aqayepardakht\PhpSdk\DTOs\{AuthorizeRequestDto, PaymentInfoDto};
use Aqayepardakht\PhpSdk\DTOs\Pol\{CreateMandateDto, DirectDebitDto, MandateOtpDto, MandateReferenceDto, RequestToPayDto};
use Aqayepardakht\PhpSdk\Enums\PolAction;
use Aqayepardakht\PhpSdk\Exceptions\TransportException;
use Aqayepardakht\PhpSdk\Interfaces\ValidatableDto;
use Aqayepardakht\PhpSdk\PaymentManager;
use Aqayepardakht\PhpSdk\Services\Payment\Pol\PolStrategyFactory;
use Aqayepardakht\PhpSdk\Tests\Support\FakeHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PolRequestToPayAndDirectDebitTest extends TestCase
{
    public static function operations(): iterable
    {
        yield 'request2pay' => [PolAction::REQUEST_TO_PAY, 'requestToPay', 'create',
            new RequestToPayDto('https://merchant.example/result', 'ORDER', 1000, 'GPPC', '2026-10-10', '09123456789'),
            ['callback' => 'https://merchant.example/result', 'invoice_id' => 'ORDER', 'amount' => 1000,
                'purpose' => 'GPPC', 'due_date' => '2026-10-10', 'receiver_identifier' => '09123456789']];
        yield 'mandate OTP' => [PolAction::MANDATE_OTP, 'requestMandateOtp', 'mandates/otp',
            self::mandateOtp(cancelable: false),
            ['tracking_code' => 'LOCAL', 'max_amount' => 1000000000, 'start_date' => '2026-10-04',
                'end_date' => '2027-10-04', 'count_per_period' => 2, 'period' => 'YEAR',
                'mandate_reason' => 'CMCN', 'description' => 'Subscription', 'cancelable' => false]];
        yield 'mandate create' => [PolAction::MANDATE_CREATE, 'createMandate', 'mandates/create',
            new CreateMandateDto('LOCAL', 10000, '012345'),
            ['tracking_code' => 'LOCAL', 'amount' => 10000, 'code' => '012345']];
        yield 'mandate info' => [PolAction::MANDATE_INFO, 'mandateInfo', 'mandates/info',
            new MandateReferenceDto('LOCAL'), ['tracking_code' => 'LOCAL']];
        yield 'mandate cancel' => [PolAction::MANDATE_CANCEL, 'cancelMandate', 'mandates/cancel',
            new MandateReferenceDto('LOCAL'), ['tracking_code' => 'LOCAL']];
        yield 'direct debit' => [PolAction::DIRECT_DEBIT, 'directDebit', 'direct/withdraw',
            new DirectDebitDto('NEW_INVOICE', 1000000000), ['tracking_code' => 'NEW_INVOICE', 'amount' => 1000000000]];
    }

    private static function mandateOtp(
        string $start = '2026-10-04', string $end = '2027-10-04', int $count = 2,
        string $period = 'YEAR', string $reason = 'CMCN', string $description = 'Subscription', bool $cancelable = true,
    ): MandateOtpDto {
        return new MandateOtpDto('LOCAL', 1000000000, $start, $end, $count, $period, $reason, $description, $cancelable);
    }

    #[DataProvider('operations')]
    public function testRoutesPayloadsAndUnmodifiedProviderData(
        PolAction $action, string $method, string $route, ValidatableDto $dto, array $payload,
    ): void {
        $provider = (object) ['status' => 'success', 'tracking_code' => 'LOCAL',
            'merchant_tracking_code' => 'LOCAL', 'otp_track_id' => null, 'reference_id' => 'PROVIDER_REFERENCE',
            'data' => (object) ['status' => 'AWAITING_APPROVAL', 'mandateId' => null, 'cancelRequested' => true]];
        $http = new FakeHttpClient([$provider]);
        $gateway = PaymentManager::make('pol', 'PIN', $http, 'https://gateway.example/api/')->withAccessToken('TOKEN');
        $result = $gateway->{$method}($dto);
        self::assertTrue($result->success);
        self::assertSame((array) $provider, $result->data);
        self::assertSame('https://gateway.example/api/'.$route, $http->calls[0]['url']);
        self::assertSame([...$payload, 'pin' => 'PIN'], $http->calls[0]['parameters']);
        self::assertSame(['Authorization' => 'Bearer TOKEN'], $http->calls[0]['headers']);
    }

    #[DataProvider('operations')]
    public function testMissingAuthenticationMakesNoRequest(
        PolAction $action, string $method, string $route, ValidatableDto $dto, array $payload,
    ): void {
        $http = new FakeHttpClient();
        try {
            PaymentManager::make('pol', 'PIN', $http)->{$method}($dto);
            self::fail('Access token is required.');
        } catch (\LogicException $e) {
            self::assertStringContainsString('access token', $e->getMessage());
            self::assertSame([], $http->calls);
        }
    }

    #[DataProvider('operations')]
    public function testRejectionPropagatesWithoutResubmission(
        PolAction $action, string $method, string $route, ValidatableDto $dto, array $payload,
    ): void {
        $http = new FakeHttpClient([(object) ['status' => 'error', 'code' => -1,
            'message' => 'POLPay rejected the request; use inquiry before submitting again.']]);
        $result = PaymentManager::make('pol', 'PIN', $http)->withAccessToken('TOKEN')->{$method}($dto);
        self::assertFalse($result->success);
        self::assertSame(-1, $result->code);
        self::assertStringContainsString('inquiry', $result->message);
        self::assertCount(1, $http->calls);
    }

    #[DataProvider('operations')]
    public function testAmbiguousTransportFailureDoesNotRetry(
        PolAction $action, string $method, string $route, ValidatableDto $dto, array $payload,
    ): void {
        $http = new FakeHttpClient([new TransportException('Timed out')]);
        try {
            PaymentManager::make('pol', 'PIN', $http)->withAccessToken('TOKEN')->{$method}($dto);
            self::fail('Exception must propagate.');
        } catch (TransportException $e) {
            self::assertSame('Timed out', $e->getMessage());
            self::assertCount(1, $http->calls);
        }
    }

    #[DataProvider('operations')]
    public function testNewOperationsKeepClonedAuthenticationIsolated(
        PolAction $action, string $method, string $route, ValidatableDto $dto, array $payload,
    ): void {
        $http = new FakeHttpClient([(object) ['status' => 'success'], (object) ['status' => 'success']]);
        $base = PaymentManager::make('pol', 'PIN', $http);
        $a = $base->withAccessToken('A');
        $b = $base->withAccessToken('B');
        $a->{$method}($dto);
        $b->{$method}($dto);
        self::assertSame(['Authorization' => 'Bearer A'], $http->calls[0]['headers']);
        self::assertSame(['Authorization' => 'Bearer B'], $http->calls[1]['headers']);
        try {
            $base->{$method}($dto);
            self::fail('The base adapter must remain unauthenticated.');
        } catch (\LogicException) {
            self::assertCount(2, $http->calls);
        }
    }

    #[DataProvider('operations')]
    public function testFactoryRejectsWrongDto(
        PolAction $action, string $method, string $route, ValidatableDto $dto, array $payload,
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $wrong = $dto instanceof MandateReferenceDto ? new DirectDebitDto('LOCAL', 10000) : new MandateReferenceDto('LOCAL');
        PolStrategyFactory::make($action, 'PIN', $wrong);
    }

    public function testRequest2PaySequenceUsesLocalTrackingAndNoOAuthOrOtpVerification(): void
    {
        $http = new FakeHttpClient([
            (object) ['status' => 'success', 'redirecturi' => null, 'access_token' => 'TOKEN', 'tracking_code' => 'LOCAL'],
            (object) ['status' => 'success', 'tracking_code' => 'LOCAL', 'merchant_tracking_code' => 'LOCAL',
                'otp_track_id' => null, 'reference_id' => 'R2P-REFERENCE'],
            (object) ['status' => 'success', 'data' => (object) ['status' => 'created',
                'provider' => (object) ['status' => 'AWAITING_APPROVAL']]],
        ]);
        $gateway = PaymentManager::make('pol', 'PIN', $http);
        $auth = $gateway->authorize(new AuthorizeRequestDto(
            'https://merchant.example/result', 'CUSTOMER', 10000, '09123456789', 'ORDER', settlement_type: 'CYC',
        ));
        self::assertTrue($auth->success);
        self::assertArrayHasKey('redirecturi', $auth->data);
        self::assertNull($auth->data['redirecturi']);
        self::assertSame('LOCAL', $auth->get('tracking_code'));
        self::assertSame('CYC', $http->calls[0]['parameters']['settlement_type']);
        $gateway = $gateway->withAccessToken($auth->get('access_token'));
        $submitted = $gateway->requestToPay(new RequestToPayDto('https://merchant.example/result', 'ORDER', 10000));
        self::assertSame('R2P-REFERENCE', $submitted->get('reference_id'));
        self::assertNull($submitted->data['otp_track_id']);
        $info = $gateway->paymentInquiry(new PaymentInfoDto($auth->get('tracking_code'), 10000));
        self::assertSame('created', $info->get('data')->status);
        self::assertSame('AWAITING_APPROVAL', $info->get('data')->provider->status);
        self::assertSame([], $http->calls[0]['headers']);
        self::assertCount(3, $http->calls);
        self::assertStringEndsWith('/create', $http->calls[1]['url']);
        self::assertArrayNotHasKey('code', $http->calls[1]['parameters']);
        self::assertArrayNotHasKey('identifier', $http->calls[1]['parameters']);
    }

    public function testMandateAndWithdrawalSequencePreservesPendingAndFinalStates(): void
    {
        $http = new FakeHttpClient([
            (object) ['status' => 'success', 'redirecturi' => 'https://gateway.example/api/start-auth/SESSION',
                'access_token' => 'TOKEN', 'tracking_code' => 'MANDATE_LOCAL'],
            (object) ['status' => 'success', 'data' => (object) ['reference_id' => 'MMS-REF',
                'otp_track_id' => 'OTP-ID', 'mandate_status' => 'PROVAL_AWAITING']],
            (object) ['status' => 'success', 'data' => (object) ['mandate_status' => 'ISSUED']],
            (object) ['status' => 'success', 'data' => (object) ['status' => 'AWAITING_APPROVAL', 'mandateId' => null]],
            (object) ['status' => 'success', 'data' => (object) ['status' => 'ACTIVE', 'mandateId' => 'BANK-ID']],
            (object) ['status' => 'success', 'redirecturi' => 'https://gateway.example/api/start-auth/NEW',
                'access_token' => 'WITHDRAW_TOKEN', 'tracking_code' => 'WITHDRAW_LOCAL'],
            (object) ['status' => 'success', 'tracking_code' => 'WITHDRAW_LOCAL', 'reference_id' => 'DDO-REF', 'otp_track_id' => null],
            (object) ['status' => 'success', 'data' => (object) ['status' => 'verify_success',
                'provider' => (object) ['status' => 'ACCEPTED', 'endToEndId' => 'E2E']]],
            (object) ['status' => 'success', 'data' => (object) ['referenceId' => 'MMS-REF', 'cancelRequested' => true]],
        ]);
        $base = PaymentManager::make('pol', 'PIN', $http);
        self::assertInstanceOf(RequestToPay::class, $base);
        self::assertInstanceOf(MandateManageable::class, $base);
        self::assertInstanceOf(DirectDebitGateway::class, $base);
        $auth = $base->authorize(new AuthorizeRequestDto('https://merchant.example/result', 'CUSTOMER', 10000, '09123456789', 'MANDATE-ORDER'));
        $gateway = $base->withAccessToken($auth->get('access_token'));
        // The browser completes customer OAuth before this API call.
        $otp = $gateway->requestMandateOtp(new MandateOtpDto($auth->get('tracking_code'), 100000, '2026-10-04', '2027-10-04', 2, 'YEAR', 'CMCN', 'Subscription'));
        self::assertSame('OTP-ID', $otp->get('data')->otp_track_id);
        $created = $gateway->createMandate(new CreateMandateDto($auth->get('tracking_code'), 10000, '012345'));
        self::assertSame('ISSUED', $created->get('data')->mandate_status);
        $reference = new MandateReferenceDto($auth->get('tracking_code'));
        $pending = $gateway->mandateInfo($reference);
        self::assertSame('AWAITING_APPROVAL', $pending->get('data')->status);
        self::assertNull($pending->get('data')->mandateId);
        self::assertCount(4, $http->calls); // No automatic withdrawal after issue or pending inquiry.
        self::assertSame('ACTIVE', $gateway->mandateInfo($reference)->get('data')->status);
        $withdrawAuth = $base->authorize(new AuthorizeRequestDto('https://merchant.example/result', 'CUSTOMER', 1000, '09123456789', 'WITHDRAW-ORDER'));
        $withdrawGateway = $base->withAccessToken($withdrawAuth->get('access_token'));
        $submitted = $withdrawGateway->directDebit(new DirectDebitDto($withdrawAuth->get('tracking_code'), 1000));
        self::assertSame('DDO-REF', $submitted->get('reference_id'));
        self::assertArrayNotHasKey('mandateId', $http->calls[6]['parameters']);
        self::assertSame('WITHDRAW_LOCAL', $http->calls[6]['parameters']['tracking_code']);
        $final = $withdrawGateway->paymentInquiry(new PaymentInfoDto($withdrawAuth->get('tracking_code'), 1000));
        self::assertSame('verify_success', $final->get('data')->status);
        self::assertSame('E2E', $final->get('data')->provider->endToEndId);
        self::assertTrue($gateway->cancelMandate($reference)->get('data')->cancelRequested);
        self::assertSame('MANDATE_LOCAL', $http->calls[2]['parameters']['tracking_code']);
        self::assertSame('012345', $http->calls[2]['parameters']['code']);
        self::assertCount(9, $http->calls);
    }

    public static function invalidRequests(): iterable
    {
        yield 'blank tracking' => ['directDebit', new DirectDebitDto(' ', 10000)];
        yield 'fraction amount' => ['directDebit', new DirectDebitDto('LOCAL', 1000.1)];
        yield 'below minimum' => ['directDebit', new DirectDebitDto('LOCAL', 999)];
        yield 'above maximum' => ['directDebit', new DirectDebitDto('LOCAL', 1000000001)];
        yield 'non finite' => ['directDebit', new DirectDebitDto('LOCAL', INF)];
        yield 'invalid amount inquiry' => ['paymentInquiry', new PaymentInfoDto('LOCAL', 1000.1)];
        yield 'long tracking' => ['mandateInfo', new MandateReferenceDto(str_repeat('X', 256))];
        yield 'blank OTP' => ['createMandate', new CreateMandateDto('LOCAL', 10000, '')];
        yield 'long OTP' => ['createMandate', new CreateMandateDto('LOCAL', 10000, str_repeat('1', 33))];
        yield 'blank invoice' => ['requestToPay', new RequestToPayDto('https://merchant.example/result', '', 10000)];
        yield 'bad date' => ['requestToPay', new RequestToPayDto('https://merchant.example/result', 'ORDER', 10000, due_date: '2026-02-30')];
        yield 'bad purpose' => ['requestToPay', new RequestToPayDto('https://merchant.example/result', 'ORDER', 10000, purpose: 'sale')];
        yield 'invalid URL' => ['requestToPay', new RequestToPayDto('javascript:alert(1)', 'ORDER', 10000)];
        yield 'bad mandate dates' => ['requestMandateOtp', self::mandateOtp(end: '2026-10-03')];
        yield 'invalid start date' => ['requestMandateOtp', self::mandateOtp(start: '2026-13-01')];
        yield 'zero count' => ['requestMandateOtp', self::mandateOtp(count: 0)];
        yield 'invalid period' => ['requestMandateOtp', self::mandateOtp(period: 'YEAR!')];
        yield 'invalid reason' => ['requestMandateOtp', self::mandateOtp(reason: 'cmcn')];
        yield 'empty description' => ['requestMandateOtp', self::mandateOtp(description: '')];
        yield 'blank authorize phone' => ['authorize', new AuthorizeRequestDto('https://merchant.example/result', 'CUSTOMER', 10000, '', 'ORDER')];
        yield 'bad settlement' => ['authorize', new AuthorizeRequestDto('https://merchant.example/result', 'CUSTOMER', 10000, '09123456789', 'ORDER', settlement_type: 'INVALID')];
    }

    #[DataProvider('invalidRequests')]
    public function testInvalidRequestNeverReachesHttp(string $method, object $dto): void
    {
        $http = new FakeHttpClient();
        try {
            PaymentManager::make('pol', 'PIN', $http)->withAccessToken('TOKEN')->{$method}($dto);
            self::fail('Invalid input must be rejected.');
        } catch (\InvalidArgumentException) {
            self::assertSame([], $http->calls);
        }
    }

    public function testNewProviderCodesAreForwardedAndFalseIsRetained(): void
    {
        $dto = self::mandateOtp(period: 'BANK_PERIOD_2', reason: 'NEW_REASON', description: str_repeat('س', 255), cancelable: false);
        $dto->validate();
        self::assertSame('BANK_PERIOD_2', $dto->toArray()['period']);
        self::assertFalse($dto->toArray()['cancelable']);
    }

    public function testAuthorizeRetainsTrackingAndRejectsMalformedRedirect(): void
    {
        $http = new FakeHttpClient([(object) ['status' => 'success', 'redirecturi' => 123, 'access_token' => 'TOKEN']]);
        $this->expectException(TransportException::class);
        (new PolPaymentAdapter('PIN', $http))->authorize(new AuthorizeRequestDto('https://merchant.example/result', 'CUSTOMER', 1000, '09123456789', 'ORDER'));
    }
}
