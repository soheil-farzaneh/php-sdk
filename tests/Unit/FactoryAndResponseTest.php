<?php
namespace Aqayepardakht\PhpSdk\Tests\Unit;

use Aqayepardakht\PhpSdk\Adapters\{AqpPaymentAdapter,PolPaymentAdapter};
use Aqayepardakht\PhpSdk\Contracts\{Authorizable,PaymentInquiry,Refundable};
use Aqayepardakht\PhpSdk\PaymentManager;
use Aqayepardakht\PhpSdk\Response;
use Aqayepardakht\PhpSdk\Tests\Support\FakeHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FactoryAndResponseTest extends TestCase
{
    #[DataProvider('drivers')]
    public function testDriverResolution(string $driver,string $expected): void
    {
        self::assertInstanceOf($expected,PaymentManager::make($driver,'pin',new FakeHttpClient()));
    }
    public static function drivers(): iterable
    {
        yield ['aqp',AqpPaymentAdapter::class]; yield [' AQP ',AqpPaymentAdapter::class];
        yield ['pol',PolPaymentAdapter::class]; yield ['POL',PolPaymentAdapter::class];
    }
    public function testUnknownDriverIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class); PaymentManager::make('unknown','pin',new FakeHttpClient());
    }
    public function testOptionalPolCapabilitiesStaySeparateFromAqp(): void
    {
        $pol=PaymentManager::make('pol','pin',new FakeHttpClient());
        self::assertInstanceOf(Authorizable::class,$pol); self::assertInstanceOf(PaymentInquiry::class,$pol); self::assertInstanceOf(Refundable::class,$pol);
        $aqp=PaymentManager::make('aqp','pin',new FakeHttpClient());
        self::assertNotInstanceOf(Authorizable::class,$aqp); self::assertNotInstanceOf(Refundable::class,$aqp);
    }
    public function testResponseKeepsCodeDataAndDefaultLookup(): void
    {
        $response=Response::failure('Rejected','E-1',['retry'=>false]);
        self::assertSame(['success'=>false,'message'=>'Rejected','code'=>'E-1','data'=>['retry'=>false]],$response->toArray());
        self::assertFalse($response->get('retry',true)); self::assertSame('fallback',$response->get('missing','fallback'));
        self::assertSame(['success'=>true,'message'=>'Accepted','code'=>null,'data'=>['tracking_code'=>'T']],Response::success(['tracking_code'=>'T'],'Accepted')->toArray());
    }
    #[DataProvider('invalidTimeouts')]
    public function testInvalidTimeoutConfigurationIsRejected(int $timeout,int $connect): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new \Aqayepardakht\PhpSdk\Infrastructure\Http\CurlHttpClient($timeout,$connect);
    }
    public static function invalidTimeouts(): iterable { yield [0,1]; yield [30,0]; yield [5,10]; }

    public function testHeaderInjectionIsRejectedBeforeConnecting(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new \Aqayepardakht\PhpSdk\Infrastructure\Http\CurlHttpClient())->post(
            'https://unreachable.example/',[],['Authorization'=>"Bearer token\r\nInjected: value"]
        );
    }

}
