<?php
namespace Aqayepardakht\PhpSdk\Tests\Unit;

use Aqayepardakht\PhpSdk\Services\AccountService;
use Aqayepardakht\PhpSdk\Services\TransactionService;
use Aqayepardakht\PhpSdk\Tests\Support\FakeHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TransactionTest extends TestCase
{
    #[DataProvider('credentials')]
    public function testRetainedTransactionReportsKeepTheirPayload(?string $pin,string $route): void
    {
        $http=new FakeHttpClient([(object) ['status'=>'success','transactions'=>[]]]);
        $service=(new AccountService('ACCOUNT','CODE',$http))->transactions($pin)->page(2)->startDate('2026-01-01')->endDate('2026-02-01');
        self::assertSame([], $service->get()->transactions);
        self::assertSame(['url'=>'https://panel.aqayepardakht.ir/api/v2/transactions/'.$route,'parameters'=>[
            'accout'=>'ACCOUNT','code'=>'CODE','page'=>2,'start_date'=>'2026-01-01','end_date'=>'2026-02-01','pin'=>$pin,
        ],'headers'=>[]],$http->calls[0]);
    }
    public static function credentials(): iterable { yield [null,'account']; yield ['pin','gate']; }
    public function testMissingCredentialsAreRejectedWithoutHttp(): void
    {
        $http=new FakeHttpClient();
        try { (new TransactionService(null,null,null,$http))->get(); self::fail('Missing credentials must fail.'); }
        catch (\InvalidArgumentException) { self::assertSame([],$http->calls); }
    }
}
