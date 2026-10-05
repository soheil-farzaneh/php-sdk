# راهنمای Request2Pay و Direct Debit در PHP SDK

این تغییر براساس قرارداد API سرویس Laravel ریفکتور شده در همین گفتگو انجام شده است. SDK به سرویس `https://aqp-p.shaparak.ir/api` متصل می‌شود؛ JWT بانکی، تبادل ID Token، انتخاب حساب، Callback و مدیریت وضعیت نهایی در سمت سرویس انجام می‌شوند.

## قرارداد عمومی

```php
use Aqayepardakht\PhpSdk\PaymentManager;
use Aqayepardakht\PhpSdk\DTOs\AuthorizeRequestDto;
use Aqayepardakht\PhpSdk\DTOs\PaymentInfoDto;
use Aqayepardakht\PhpSdk\DTOs\Pol\{
    RequestToPayDto,
    MandateOtpDto,
    CreateMandateDto,
    MandateReferenceDto,
    DirectDebitDto,
};

$base = PaymentManager::make('pol', $pin);
// Optional custom API base URL:
// $base = PaymentManager::make('pol', $pin, baseUrl: 'https://your-service.example/api');
```

- `authorize()` فقط PIN دارد. شش عملیات جدید و `paymentInquiry()` به PIN و Bearer نیاز دارند.
- `withAccessToken()` یک نسخه جدید برمی‌گرداند؛ خروجی آن را نگه دارید. توکن از `authorize` دریافت می‌شود، نه OAuth بانکی و نه کد OTP.
- PIN را SDK به Body اضافه می‌کند. Bearer در هدر `Authorization` ارسال می‌شود.
- مبلغ بر اساس واحد API سرور، صحیح و بین **۱۰۰۰ تا ۱٬۰۰۰٬۰۰۰٬۰۰۰ با احتساب دو مرز** است. SDK تبدیل واحد انجام نمی‌دهد.
- Gateway و نوع جریان از PIN در سرویس مشخص می‌شوند؛ SDK نمی‌تواند نوع Gateway را تغییر دهد.
- خطای API در `Response::success = false` و `message/code` برمی‌گردد. خطای شبکه/JSON به صورت `TransportException` و خطای ورودی به صورت `InvalidArgumentException` است. عملیات وابسته را پس از خطا ادامه ندهید.
- `Response::success = true` موفقیت فراخوانی API است؛ پرداخت نهایی یا فعال‌بودن مجوز را تضمین نمی‌کند.
- در عملیات جدید، تمام فیلدهای پاسخ سرویس حفظ می‌شوند. برای پاسخ‌های تو در تو، `$result->get('data')` همان داده JSON Decode شده سرویس است؛ در HttpClient پیش‌فرض، Object خواهد بود.

## متدها و روت‌ها

همه روت‌های این جدول POST هستند و به Bearer نیاز دارند. `pin` خودکار اضافه می‌شود.

| متد SDK | روت | DTO | فیلدهای اجباری | فیلدهای اختیاری |
|---|---|---|---|---|
| `requestToPay()` | `/api/create` | `RequestToPayDto` | `callback`, `invoice_id`, `amount` | `purpose`, `due_date`, `receiver_identifier` |
| `requestMandateOtp()` | `/api/mandates/otp` | `MandateOtpDto` | `tracking_code`, `max_amount`, `start_date`, `end_date`, `count_per_period`, `period`, `mandate_reason`, `description` | `cancelable`، پیش‌فرض true |
| `createMandate()` | `/api/mandates/create` | `CreateMandateDto` | `tracking_code`, `amount`, `code` | — |
| `mandateInfo()` | `/api/mandates/info` | `MandateReferenceDto` | `tracking_code` | — |
| `cancelMandate()` | `/api/mandates/cancel` | `MandateReferenceDto` | `tracking_code` | — |
| `directDebit()` | `/api/direct/withdraw` | `DirectDebitDto` | `tracking_code`, `amount` | — |
| `paymentInquiry()` | `/api/payment-info` | `PaymentInfoDto` | `tracking_code`, `amount` | — |

`authorize(AuthorizeRequestDto)` نیز POST `/api/authorize` است: `callback`, `identifier`, `amount`, `phone`, `invoice_id` اجباری‌اند. `name`, `ip`, `description`, `purpose`, `settlement_type` اختیاری‌اند. فیلد جدید `settlement_type` به انتهای Constructor اضافه شده و Named Argumentهای قبلی تغییر نکرده‌اند. `purpose` پیش‌فرض سرور POSA و `settlement_type` پیش‌فرض سرور INS است؛ مقادیر مجاز تسویه INS/CYC/EOD هستند.

## ۱. Request2Pay

Gateway باید `request-pay-interface-base` باشد. این جریان OAuth مشتری و OTP پرداخت ندارد.

```php
$authorized = $base->authorize(new AuthorizeRequestDto(
    callback: 'https://merchant.example/result',
    identifier: 'CUSTOMER-1001',
    amount: 10000,
    phone: '09123456789',
    invoice_id: 'R2P-ORDER-1001',
    description: 'Order 1001',
    purpose: 'GPPC',
    settlement_type: 'INS',
));

if (!$authorized->success) {
    throw new RuntimeException($authorized->message ?? 'Authorize failed');
}

$tracking = $authorized->get('tracking_code');
$gateway = $base->withAccessToken($authorized->get('access_token'));
// redirecturi === null is valid for this flow. No browser redirect is required.

$submitted = $gateway->requestToPay(new RequestToPayDto(
    callback: 'https://merchant.example/result',
    invoice_id: 'R2P-ORDER-1001',
    amount: 10000,
    purpose: 'GPPC',
    receiver_identifier: '09123456789',
    // due_date: 'YYYY-MM-DD',
));

if (!$submitted->success) {
    throw new RuntimeException($submitted->message ?? 'Request2Pay submission failed');
}

$providerReference = $submitted->get('reference_id'); // R2P-...
$info = $gateway->paymentInquiry(new PaymentInfoDto($tracking, 10000));
if (!$info->success) {
    throw new RuntimeException($info->message ?? 'Inquiry failed');
}
$payment = $info->get('data');
$localStatus = $payment->status;
$providerStatus = $payment->provider->status ?? null;
```

`callback`, `invoice_id`, `amount` باید همان مقادیر authorize باشند. سرویس از فاکتور موجود استفاده می‌کند؛ `create` سفارش مستقل ایجاد نمی‌کند. مبلغ یا callback متفاوت می‌تواند باعث 404 شود.

`due_date` میلادی به شکل Y-m-d است؛ سرور تاریخ بعد از امروز و حداکثر ۱۴ روز بعد را می‌پذیرد. اگر ارسال نشود، سرور فردا را استفاده می‌کند. SDK صحت قالب/تاریخ را بررسی می‌کند و بازه وابسته به «امروز» را به ساعت/منطقه زمانی سرور واگذار می‌کند.

اگر `receiver_identifier` ارسال نشود، سرویس از شماره موبایل فاکتور/مشتری استفاده می‌کند. این فیلد گیرنده Request2Pay است؛ با `identifier` محلی مشتری در authorize فرق دارد.

بعد از ثبت، مشتری در سامانه پل‌پی تصمیم می‌گیرد. وضعیت‌هایی مانند INITIATE و AWAITING_APPROVAL موفقیت نهایی نیستند. نتیجه از Callback بانکی در سرویس یا از `paymentInquiry()` مشخص می‌شود. برای این جریان `verifyPayment()` را صدا نزنید. در قرارداد فعلی، `verify_success` محلی نتیجه پذیرفته‌شده پرداخت با شناسه بانکی است.

## ۲. ایجاد مجوز برداشت مستقیم

Gateway باید `direct-interface-base` باشد. ایجاد اولیه مجوز به OAuth مشتری با Scope مربوط به Mandate نیاز دارد.

### مرحله A: authorize و احراز هویت مشتری

```php
$authorized = $base->authorize(new AuthorizeRequestDto(
    callback: 'https://merchant.example/mandate/result',
    identifier: 'CUSTOMER-1001',
    amount: 10000,
    phone: '09123456789',
    invoice_id: 'MANDATE-1001',
    description: 'Subscription setup',
    purpose: 'GPPC',
));

if (!$authorized->success) {
    throw new RuntimeException($authorized->message ?? 'Authorize failed');
}

$mandateTracking = $authorized->get('tracking_code');
$accessToken = $authorized->get('access_token');
$oauthUrl = $authorized->get('redirecturi');
// Persist mandateTracking and accessToken securely on your server.
// Redirect the customer's browser to oauthUrl, then resume after OAuth completes.
```

OAuth بانک به `/out` سرویس برمی‌گردد. سرویس مشتری را به callback پذیرنده با `status=authorized` هدایت می‌کند. این وضعیت، اجازه برداشت فعال نیست. SDK کد OAuth یا state را تولید/ارسال نمی‌کند.

### مرحله B: درخواست OTP مجوز

پس از برگشت مشتری، `$mandateTracking` و `$accessToken` ذخیره‌شده را بازیابی کنید.

```php
$gateway = $base->withAccessToken($accessToken);
$start = new DateTimeImmutable('today', new DateTimeZone('Asia/Tehran'));
$end = $start->modify('+1 year');

$otp = $gateway->requestMandateOtp(new MandateOtpDto(
    tracking_code: $mandateTracking,
    max_amount: 100000,
    start_date: $start->format('Y-m-d'),
    end_date: $end->format('Y-m-d'),
    count_per_period: 2,
    period: 'YEAR',
    mandate_reason: 'CMCN',
    description: 'Subscription mandate',
    cancelable: true,
));

if (!$otp->success) {
    throw new RuntimeException($otp->message ?? 'Mandate OTP failed');
}
$contract = $otp->get('data');
$mandateReference = $contract->reference_id; // MMS-...
$otpTrackId = $contract->otp_track_id;
```

تاریخ‌ها میلادی‌اند؛ سرور start_date امروز یا بعد از آن و end_date برابر/بعد از start_date را می‌پذیرد. `count_per_period` حداقل ۱ است. `max_amount` سقف تعریف‌شده در قرارداد مجوز است؛ `amount` فاکتور authorize جایگزین آن نیست.

دلیل/بازه را از مقادیر معتبر بانک انتخاب کنید. مثال YEAR/CMCN از سناریوی گفتگو است. SDK فهرست بسته‌ای برای این دو کد نمی‌سازد تا کدهای معتبر جدید بانک قابل ارسال باشند؛ محدودیت قالب و طول ۳۲ کاراکتر اعمال می‌شود. reason باید با حرف بزرگ آغاز شود و فقط حروف بزرگ/عدد/underscore داشته باشد.

### مرحله C: ثبت مجوز با OTP مشتری

```php
$created = $gateway->createMandate(new CreateMandateDto(
    tracking_code: $mandateTracking,
    amount: 10000,
    code: $customerOtp,
));

if (!$created->success) {
    throw new RuntimeException($created->message ?? 'Mandate creation failed; inquire before retry');
}
$localContract = $created->get('data');
$localMandateStatus = $localContract->mandate_status; // May be ISSUED.
```

از `tracking_code` محلی authorize استفاده کنید؛ MMS reference، otp_track_id یا mandateId را جایگزین نکنید. code رشته است تا صفرهای ابتدای OTP حفظ شوند.

`amount` در روت فعلی اجباری است؛ سقف مجوز از Contract مرحله OTP خوانده می‌شود. برای سازگاری، همان مبلغ authorize را ارسال کنید. SDK شرایط مجوز را در create دوباره ارسال نمی‌کند.

### مرحله D: استعلام تا روشن‌شدن وضعیت مجوز

```php
$mandate = $gateway->mandateInfo(new MandateReferenceDto($mandateTracking));
if (!$mandate->success) {
    throw new RuntimeException($mandate->message ?? 'Mandate inquiry failed');
}
$providerMandate = $mandate->get('data');
$isActive = $providerMandate->status === 'ACTIVE'
    && is_string($providerMandate->mandateId ?? null)
    && $providerMandate->mandateId !== '';
```

| وضعیت | تفسیر |
|---|---|
| PROVAL_AWAITING | وضعیت اولیه محلی بعد از آماده‌سازی Contract |
| ISSUED | درخواست مجوز در مسیر سرویس ثبت شده؛ فعال‌بودن را از استعلام بررسی کنید |
| AWAITING_APPROVAL | منتظر تأیید؛ mandateId ممکن است null باشد |
| ACTIVE | مجوز فعال؛ برداشت همچنان به تاریخ اعتبار، سقف و شروط سرور/بانک وابسته است |
| INACTIVE / SUSPENDED | مجوز قابل استفاده نیست |
| CANCELLED / EXPIRED | مجوز لغوشده یا منقضی‌شده |

SDK مقدار وضعیت را دستکاری نمی‌کند و مجوز را خودکار فعال نمی‌سازد. برای برداشت، پاسخ ACTIVE و mandateId معتبر لازم‌اند. نوع پاسخ یا ترتیب رسیدن Callback می‌تواند متفاوت باشد؛ استعلام مرجع تصمیم است.

## ۳. برداشت با مجوز فعال

هر برداشت فاکتور جدید، invoice_id جدید و tracking_code همان فاکتور را دارد. مشتری و PIN باید با مشتری/درگاه مجوز فعال سازگار باشند. سرویس فعلی مجوز را از `customer.reference_id` انتخاب می‌کند؛ API عمومی انتخاب یک mandateId دلخواه ندارد.

```php
$withdrawAuth = $base->authorize(new AuthorizeRequestDto(
    callback: 'https://merchant.example/direct/result',
    identifier: 'CUSTOMER-1001',
    amount: 10000,
    phone: '09123456789',
    invoice_id: 'WITHDRAW-1001',
    description: 'Subscription payment',
    purpose: 'GPPC',
));

if (!$withdrawAuth->success) {
    throw new RuntimeException($withdrawAuth->message ?? 'Withdrawal authorize failed');
}

$withdrawTracking = $withdrawAuth->get('tracking_code');
$withdrawGateway = $base->withAccessToken($withdrawAuth->get('access_token'));
// When a valid active mandate already exists, direct/withdraw does not use
// this new invoice's OAuth redirect. The server rechecks the existing mandate.

$debit = $withdrawGateway->directDebit(new DirectDebitDto(
    tracking_code: $withdrawTracking,
    amount: 10000,
));

if (!$debit->success) {
    throw new RuntimeException($debit->message ?? 'Withdrawal submission failed; inquire before retry');
}
$directReference = $debit->get('reference_id'); // DDO-...

$info = $withdrawGateway->paymentInquiry(new PaymentInfoDto($withdrawTracking, 10000));
if (!$info->success) {
    throw new RuntimeException($info->message ?? 'Withdrawal inquiry failed');
}
$payment = $info->get('data');
$isPaid = $payment->status === 'verify_success';
```

ثبت DDO موفقیت نهایی برداشت نیست. نتیجه نهایی را با استعلام یا Callback بانکی سرویس مشخص کنید. `verifyPayment()`، کد OTP، MMS reference و mandateId برای این روت ارسال نمی‌شوند. برای تکرار برداشت، فاکتور جدید ایجاد کنید؛ از فاکتور پرداخت‌شده مجدداً استفاده نکنید. SDK و سرویس فعلی زمان‌بندی خودکار برداشت ندارند.

## ۴. درخواست لغو مجوز

```php
$cancel = $gateway->cancelMandate(new MandateReferenceDto($mandateTracking));
if (!$cancel->success) {
    throw new RuntimeException($cancel->message ?? 'Cancel request failed');
}
$cancelRequested = $cancel->get('data')->cancelRequested ?? false;
// Later, inquire using the original mandateTracking and its customer token.
$latest = $gateway->mandateInfo(new MandateReferenceDto($mandateTracking));
```

`cancelRequested=true` فقط ثبت درخواست لغو است. تا تأیید وضعیت نهایی، CANCELLED فرض نکنید. tracking_code استعلام/لغو مربوط به فاکتور ایجاد مجوز است، نه فاکتور آخرین برداشت.

## ۵. شناسه‌ها و رفتار خطا

| شناسه | کاربرد |
|---|---|
| invoice_id | شناسه سفارش پذیرنده برای authorize/create |
| tracking_code در authorize | شناسه محلی پایدار فاکتور؛ برای Mandate و برداشت ضروری است |
| merchant_tracking_code | همان شناسه محلی در پاسخ submission |
| otp_track_id | شناسه OTP بالادست؛ با شناسه محلی فرق دارد |
| reference_id | مرجع بانکی R2P/MMS/DDO؛ ورودی روت‌های SDK نیست |
| mandateId | شناسه مجوز بانک که سرویس در برداشت استفاده می‌کند |

در خطای «use inquiry before submitting again» یا timeout، درخواست ایجاد/برداشت را خودکار دوباره نفرستید. ابتدا با tracking_code و Bearer همان مشتری استعلام بگیرید: `mandateInfo()` برای مجوز و `paymentInquiry()` برای پرداخت. از موفقیت ثبت اولیه یا error عمومی، نتیجه قطعی مالی استنتاج نکنید. SDK هیچ retry خودکار ندارد و تصمیم retry/reconciliation به سرویس و پذیرنده وابسته است.

در سرویس فعلی، Callback نهایی بانک به پذیرنده forward نمی‌شود؛ برگشت مرورگر هم نتیجه نهایی مالی نیست. پذیرنده باید مسیر بررسی نتیجه را با استعلام پیاده کند.

## ۶. سازگاری و اعتبارسنجی

- متدهای قبلی آنلاین/Refund و هسته مشترک SDK تغییر نکرده‌اند. مسیر جدید Request2Pay از `requestToPay()` فراخوانی می‌شود؛ به جای DTO عمومی OTP از DTO مستقل استفاده کنید.
- `authorize()` اکنون پاسخ با redirecturi صریحاً null را می‌پذیرد و tracking_code و سایر شناسه‌های برگشتی را حفظ می‌کند. فقدان خود فیلد redirecturi، توکن خالی و token_type غیر Bearer همچنان خطاست.
- قواعد مبلغ PolPay مستقل از قواعد مشترک قبلی‌اند. authorize، عملیات جدید و paymentInquiry مرزهای صحیح API سرور را می‌پذیرند.
- برای purpose از کدهای بانک استفاده کنید، مانند GPPC/POSA؛ مقدار توصیفی مانند sale مجاز نیست.
- عملیات جدید قابلیت‌های جداگانه `RequestToPay`, `MandateManageable`, `DirectDebitGateway` را روی PolPaymentAdapter اضافه می‌کنند. Contract قدیمی و استفاده‌نشده DirectDebitable تغییر نکرده است.
- شکل get('data') در استعلام و متدهای Mandate حفظ شده است. پاسخ‌های مسطح R2P/DDO با get('reference_id') و get('tracking_code') خوانده می‌شوند.

## ۷. پیش‌نیازهای سرویس و نتیجه بررسی

اصلاحات OAuth state در سمت سرویس (مقدار تصادفی ۲۰ کاراکتری طبق تجربه همین گفتگو) و پشتیبانی از AWAITING_APPROVAL در MandateStatus باید در نسخه دیپلوی‌شده اعمال شده باشند. SDK نمی‌تواند این دو رفتار سرور را اصلاح کند. PIN باید نوع Gateway مناسب داشته باشد و تنظیمات بانک/OAuth/Passport سرویس معتبر باشند.

تست‌های خودکار با HttpClient جعلی روی PHP 8.3.6 اجرا شدند: **۹۷ تست و ۲۶۹ assertion موفق**. مسیرها، Body و Bearer، حفظ false/null و شناسه‌ها، خطای ورودی، پاسخ‌های pending/final و عدم retry بررسی شدند. هیچ درخواست پرداخت واقعی برای این بررسی ارسال نشده است؛ تست Production باید با سرویس و حساب تست خودتان انجام شود.

فایل `POLPAY-REFACTOR.md` خلاصه تغییرات و دامنه آن‌ها را دارد.
