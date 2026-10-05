<?php
namespace Aqayepardakht\PhpSdk\Tests\Unit;

use Aqayepardakht\PhpSdk\Exceptions\TransportException;
use Aqayepardakht\PhpSdk\Services\Payment\PaymentApiClient;
use Aqayepardakht\PhpSdk\Tests\Support\FakeHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PaymentApiClientTest extends TestCase
{
    public function testJoinsRoutesOverridesUntrustedPinAndPreservesFalseValues(): void
    {
        $http = new FakeHttpClient([(object) ['status'=>'success','data'=>(object) ['nested'=>1]]]);
        $response=(new PaymentApiClient($http,'https://api.example/v3/','real-pin'))->request('/pay',['pin'=>'untrusted','sms'=>false]);
        self::assertTrue($response->success);
        self::assertSame('https://api.example/v3/pay',$http->calls[0]['url']);
        self::assertSame(['pin'=>'real-pin','sms'=>false],$http->calls[0]['parameters']);
        self::assertEquals((object) ['nested'=>1],$response->get('data'));
    }

    #[DataProvider('malformedResponses')]
    public function testMissingOrInvalidStatusIsNotSuccess(object $response): void
    {
        $api=new PaymentApiClient(new FakeHttpClient([$response]),'https://api.example/','pin');
        $this->expectException(TransportException::class);
        $api->request('pay',[]);
    }

    public static function malformedResponses(): iterable
    {
        yield [(object) []]; yield [(object) ['status'=>null]]; yield [(object) ['status'=>'']];
        yield [(object) ['status'=>true]]; yield [(object) ['status'=>[]]];
    }

    public function testAnErrorWithoutMessageUsesTheFallbackAndKeepsItsCode(): void
    {
        $result=(new PaymentApiClient(new FakeHttpClient([(object) ['status'=>'error','code'=>'E']]),'https://api.example/','pin'))->request('pay',[]);
        self::assertFalse($result->success);
        self::assertSame('خطای نامشخص',$result->message);
        self::assertSame('E',$result->code);
    }

    public function testMissingPinIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentApiClient(new FakeHttpClient(),'https://api.example/',' ');
    }

    public function testInvalidBaseUrlIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentApiClient(new FakeHttpClient(),'file:///tmp/payment','pin');
    }
    public function testBearerUsesAuthorizationHeaderRatherThanTheFormBody(): void
    {
        $http = new FakeHttpClient([(object) ['status'=>'success']]);
        (new PaymentApiClient($http,'https://api.example/','pin','fake-token'))->request('verify',['tracking_code'=>'T']);
        self::assertSame(['Authorization'=>'Bearer fake-token'],$http->calls[0]['headers']);
        self::assertSame(['tracking_code'=>'T','pin'=>'pin'],$http->calls[0]['parameters']);
    }

}
