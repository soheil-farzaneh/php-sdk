<?php
namespace Aqayepardakht\PhpSdk\Tests\Unit;

use Aqayepardakht\PhpSdk\DTOs\{AuthorizeRequestDto, RequestPaymentDto, VerifyPaymentDto, PaymentInfoDto, RequestRefundDto, InquiryRefundDto};
use Aqayepardakht\PhpSdk\Domain\Payment\PaymentValidation;
use Aqayepardakht\PhpSdk\Helper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    #[DataProvider('invalidAmounts')]
    public function testEveryMonetaryDtoEnforcesExistingAmountBounds(string $type, int|float $amount): void
    {
        $dto=match($type) {
            'authorize'=>new AuthorizeRequestDto('https://merchant.example/callback','ID',$amount,invoice_id:'ORDER'),
            'request'=>new RequestPaymentDto('https://merchant.example/callback','ORDER','ID',$amount),
            'verify'=>new VerifyPaymentDto('T',$amount,'12345'),
            'info'=>new PaymentInfoDto('T',$amount),
        };
        $this->expectException(\InvalidArgumentException::class);
        $dto->validate();
    }

    public static function invalidAmounts(): iterable
    {
        foreach (['authorize','request','verify','info'] as $type) {
            foreach ([-1,0,1000,100000000,INF,NAN] as $index=>$amount) { yield $type.'-'.$index=>[$type,$amount]; }
        }
    }

    #[DataProvider('validAmounts')]
    public function testAmountsInsideTheExistingBoundsAreAccepted(int|float $amount): void
    {
        PaymentValidation::amount($amount);
        self::assertTrue(true);
    }
    public static function validAmounts(): iterable { yield [1001]; yield [99999999]; yield [1000.5]; }

    #[DataProvider('invalidRequests')]
    public function testRequestValidationRejectsBadFields(object $dto): void
    {
        $this->expectException(\InvalidArgumentException::class); $dto->validate();
    }
    public static function invalidRequests(): iterable
    {
        yield 'bad callback'=>[new RequestPaymentDto('www.example.com','ORDER','',10000)];
        yield 'bad email'=>[new RequestPaymentDto('https://merchant.example/callback','ORDER','',10000,email:'invalid')];
        yield 'bad mobile'=>[new RequestPaymentDto('https://merchant.example/callback','ORDER','',10000,mobile:'123')];
        yield 'bad card'=>[new RequestPaymentDto('https://merchant.example/callback','ORDER','',10000,cards:['123'])];
        yield 'empty verify reference'=>[new VerifyPaymentDto('',10000)];
        yield 'empty info reference'=>[new PaymentInfoDto(' ',10000)];
        yield 'empty refund reference'=>[new RequestRefundDto('')];
        yield 'empty refund inquiry reference'=>[new InquiryRefundDto(' ')];
    }

    public function testDtoSerializationPreservesFalseAndOptionalCreationReference(): void
    {
        $dto=new RequestPaymentDto('https://merchant.example/callback','ORDER','',10000,sms:false,autoverify:false,tracking_code:'OLD');
        self::assertSame([
            'amount'=>10000,'callback'=>'https://merchant.example/callback','invoice_id'=>'ORDER','identifier'=>'',
            'sms'=>false,'autoverify'=>false,'tracking_code'=>'OLD',
        ],$dto->toArray());
    }

    #[DataProvider('mobileNumbers')]
    public function testAcceptedIranianMobileFormats(string $mobile): void
    {
        Helper::validateMobileNumber($mobile); self::assertTrue(true);
    }
    public static function mobileNumbers(): iterable
    {
        foreach(['09120000000','9120000000','989120000000','+989120000000','00989120000000','۰۹۱۲۰۰۰۰۰۰۰'] as $number) { yield [$number]; }
    }

    public function testRepeatedMobileNumbersAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class); Helper::validateMobileNumber('0912000000009120000000');
    }

    public function testPersianAndArabicDigitsAreNormalized(): void
    {
        self::assertSame('01234567890123456789',Helper::faToEnNumbers('۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩'));
    }
}
