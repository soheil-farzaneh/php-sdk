# SDK پرداخت آقای پرداخت و POLPay

SDK مستقل از Laravel با یک مسیر پرداخت: PaymentManager، Adapter، Service و Strategy. ساختار قدیمی Api/gateway/invoice و PaymentService حذف شده است. این تغییر API ناسازگار است؛ پروژه‌های مصرف‌کننده باید به نمونه‌های زیر مهاجرت کنند.

حداقل PHP 8.2 با افزونه‌های curl و json لازم است. فایل‌های vendor در خروجی قرار ندارند. سورس بسته را با Composer در پروژه مصرف‌کننده نصب کنید؛ برای توسعه خود SDK در ریشه همین پوشه `composer install` اجرا کنید.

## آقای پرداخت: ایجاد، شروع و تأیید

```php
use Aqayepardakht\PhpSdk\PaymentManager;
use Aqayepardakht\PhpSdk\DTOs\RequestPaymentDto;
use Aqayepardakht\PhpSdk\DTOs\VerifyPaymentDto;

$gateway = PaymentManager::make('aqp', 'YOUR_PIN');
$created = $gateway->requestPayment(new RequestPaymentDto(
    callback: 'https://merchant.example/payment/callback',
    invoice_id: 'ORDER-123',
    identifier: '',
    amount: 10000,
    mobile: '09120000000',
    cards: [],
    sms: false,
));

if (!$created->success) {
    // خطای اعلام‌شده درگاه در message و code قرار دارد.
    throw new RuntimeException($created->message ?? 'Payment request failed');
}

$trackingCode = (string) $created->get('tracking_code');
// مبلغ و trackingCode را در پروژه مصرف‌کننده برای مرحله Verify نگه دارید.
$url = $gateway->getStartPayUrl($trackingCode);
// URL را با redirect فریم‌ورک خود به مرورگر بدهید.
// یا $gateway->start($trackingCode) را اجرا کنید؛ خروجی JSON/HTML می‌دهد و exit دارد.
```

در مرحله بازگشت از پرداخت، اطلاعات سفارش نگهداری‌شده را استفاده کنید:

```php
$verified = $gateway->verifyPayment(new VerifyPaymentDto(
    tracking_code: $trackingCode,
    amount: 10000,
));

if ($verified->success) {
    $upstreamData = $verified->data;
    $bankTrackingNumber = $verified->get('tracking_number');
}
```

ایجاد موفق درخواست با تأیید موفق پرداخت دو مرحله متفاوت است. SDK state سفارش را ذخیره نمی‌کند؛ پروژه مصرف‌کننده tracking code و مبلغ را نگهداری می‌کند.

Create به `/v3/pay`، Verify به `/v3/verify` و Start مرورگر به `/startpay/{tracking_code}` می‌روند. Start درخواست HTTP به API ارسال نمی‌کند. داده‌های کارت، پیامک، اطلاعات مشتری و سایر فیلدهای DTO در Create حفظ می‌شوند. tracking_code اختیاری در انتهای RequestPaymentDto اضافه شده است تا مرجع ایجاد موجود در Invoice قبلی قابل ارسال بماند. code و purpose در Verify آقای پرداخت ارسال نمی‌شوند.

## پل‌پی

```php
use Aqayepardakht\PhpSdk\DTOs\AuthorizeRequestDto;

$gateway = PaymentManager::make('pol', 'YOUR_PIN');
$authorized = $gateway->authorize(new AuthorizeRequestDto(
    callback: 'https://merchant.example/payment/callback',
    identifier: 'ORDER-123',
    amount: 10000,
    purpose: 'sale',
    invoice_id: 'ORDER-123',
));
```

پاسخ Authorize شامل redirecturi، access_token و token_type است. SDK هر سه فیلد را حفظ می‌کند. برای عملیات بعدی، توکن خام را به withAccessToken بدهید؛ این متد نمونه جدید می‌سازد و نتیجه آن باید نگه داشته شود:

```php
if ($authorized->success) {
    $authenticatedGateway = $gateway->withAccessToken(
        $authorized->get('access_token')
    );
    // پس از تکمیل هدایت کاربر و مرحله بازگشت، با این Adapter درخواست OTP بدهید.
}
```

requestPayment، verifyPayment، paymentInquiry، requestRefund و inquiryRefund پل‌پی به توکن نیاز دارند. بدون توکن، پیش از ارسال HTTP خطا دریافت می‌کنید. هدر به‌صورت Authorization: Bearer <token> ارسال می‌شود؛ عبارت Bearer را به مقدار ورودی متد اضافه نکنید. خود Authorize هدر Bearer نمی‌فرستد، حتی اگر Adapter توکن داشته باشد.

در callback یا درخواست بعدی برنامه، Adapter را مجدداً بسازید و توکن همان پرداخت را از محل امن سمت سرور بازیابی و با withAccessToken تنظیم کنید. SDK ذخیره‌سازی یا refresh خودکار توکن انجام نمی‌دهد. توکن را در log یا خروجی مرورگر چاپ نکنید.

در Authorize، invoice_id نیز الزامی است. آن را صریحاً از اطلاعات سفارش ارسال کنید؛ identifier جایگزین invoice_id نیست.

متدهای authorize، requestPayment برای OTP، verifyPayment، paymentInquiry، requestRefund و inquiryRefund باقی مانده‌اند. OTP به `/api/create`، Verify به `/api/verify` و سایر عملیات به روت‌های خود می‌روند. Verify پل‌پی به code نیاز دارد. فیلد purpose حفظ شده است.

## خطا و تنظیمات

- خطای ورودی: InvalidArgumentException؛ پیش از ارسال درخواست.
- خطای اتصال، HTTP ناموفق یا پاسخ خراب: TransportException.
- خطای اعلام‌شده توسط درگاه: Response با success=false و message/code.

```php
use Aqayepardakht\PhpSdk\Infrastructure\Http\CurlHttpClient;

$http = new CurlHttpClient(timeout: 30, connectTimeout: 10);
$gateway = PaymentManager::make('aqp', 'YOUR_PIN', $http, 'https://your-host.example/v3/');
```

HttpClient قابل جایگزینی است. امضای post اکنون post(string $url, array $parameters, array $headers = []): object است؛ پیاده‌سازی سفارشی باید پارامتر سوم را پشتیبانی کند. ارتباط پیش‌فرض form-encoded است؛ TLS بررسی می‌شود؛ redirect شبکه و retry خودکار انجام نمی‌شود. حدود مبلغ قبلی 1000 < amount < 100000000 حفظ شده‌اند؛ تبدیل واحد پول اضافه نشده است.

## گزارش تراکنش‌های حساب

حذف API قدیمی پرداخت، منطق مستقل گزارش تراکنش را حذف نکرده است:

```php
use Aqayepardakht\PhpSdk\Services\AccountService;

$result = (new AccountService('ACCOUNT_NUMBER', 'ACCOUNT_CODE', $http))
    ->transactions('GATEWAY_PIN')
    ->page(1)
    ->get();
```

کلید legacy درخواست گزارش یعنی accout تا تأیید قرارداد API تغییر نکرده است.

## تست‌ها

PHPUnit 11.5 در require-dev قرار دارد. تست‌های unit از FakeHttpClient استفاده می‌کنند. تست‌های integration، خروجی مرورگر را در فرایند جدا و transport را با سرور HTTP محلی بررسی می‌کنند. هیچ‌کدام به درگاه واقعی وصل نمی‌شوند. قابلیت proc_open و cURL برای integration لازم‌اند؛ در نبود آن‌ها تست مربوطه skip می‌شود.

پس از نصب وابستگی‌های توسعه:

```bash
composer test
composer test:unit
composer test:integration
```

تست‌ها نوشته شده‌اند اما هنوز اجرا نشده‌اند. PHP و Composer در محیط فعلی در دسترس نبودند. مرحله اجرا طبق توافق کاربر پس از تأیید انجام می‌شود.

جزئیات مهاجرت، حذف فایل‌ها و دامنه تست‌ها در REFACTOR.md آمده‌اند.
