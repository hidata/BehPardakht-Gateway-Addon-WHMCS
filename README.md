# افزونه درگاه پرداخت به‌پرداخت ملت برای WHMCS

این مخزن شامل ماژول پرداخت **Behpardakht Mellat** برای WHMCS و یک افزونهٔ مدیریتی برای مشاهده و خروجی گرفتن از تراکنش‌ها است. نسخه‌های موجود شامل درگاه (v2.1.2/2.1.4) و افزونهٔ مدیریت تراکنش‌ها (v2.0) می‌شود.

## قابلیت‌ها

- پشتیبانی از PHP 8.1+ و WHMCS 8.11+.  
- ارسال تراکنش از طریق سرویس SOAP بانک ملت با مسیرهای **پرداخت**، **Verify** و **Settle** (به همراه تلاش مجدد و backoff).【F:modules/gateways/callback/behpardakht.php†L82-L145】  
- کنترل اعتبار با HMAC برای جلوگیری از دست‌کاری مبلغ و شناسه فاکتور.【F:modules/gateways/behpardakht.php†L92-L118】【F:modules/gateways/behpardakht/payment.php†L23-L55】  
- ثبت تراکنش‌ها در جدول `mod_behpardakht_transactions` (Invoice, OrderId, RefId, مبلغ ریال، وضعیت و …).【F:modules/gateways/behpardakht.php†L55-L91】  
- پشتیبانی از دو واحد مبلغ سایت (تومان/ریال) و تبدیل خودکار به ریال برای بانک.【F:modules/gateways/behpardakht/payment.php†L43-L76】  
- انتخاب محیط Production/Test برای WSDL و مسیر StartPay بر اساس مستندات جدید.【F:modules/gateways/behpardakht/common.php†L19-L52】【F:modules/gateways/behpardakht/payment.php†L66-L77】  
- محدودسازی پرداخت با کد ملی در حالت‌های payrequest-enc، redirect-ENC و mana-mobile+enc (رمزنگاری DES/ECB).【F:modules/gateways/behpardakht/common.php†L83-L145】【F:modules/gateways/behpardakht/payment.php†L79-L154】  
- حالت تست (Sandbox) و لاگ دیباگ از تراکنش‌ها در WHMCS.【F:modules/gateways/behpardakht/payment.php†L33-L42】【F:modules/gateways/callback/behpardakht.php†L27-L44】  
- پردازش ریفاند از داخل WHMCS با استفاده از `bpRefundRequest`.【F:modules/gateways/behpardakht.php†L119-L182】  
- افزونهٔ مدیریتی برای مرور، فیلتر، مرتب‌سازی، صفحه‌بندی و خروجی اکسل از تراکنش‌ها در محیط ادمین WHMCS.【F:modules/addons/behpardakht_transactions/behpardakht_transactions.php†L57-L220】

## پیش‌نیازها

- PHP 8.1 یا بالاتر با افزونه SOAP فعال.
- WHMCS نسخه 8.11 یا جدیدتر.
- دسترسی به Terminal ID، نام کاربری و رمز عبور به‌پرداخت ملت.
- پشتیبانی واحد پولی سایت از ریال/تومان (کدهای IRR/IRT/TMN/TOMAN).

## نصب و فعال‌سازی درگاه پرداخت

1. کل محتویات مخزن را در ریشهٔ نصب WHMCS کپی کنید تا مسیرهای زیر در دسترس باشند:
   - `modules/gateways/behpardakht.php` و پوشهٔ `modules/gateways/behpardakht/`
   - فایل کال‌بک: `modules/gateways/callback/behpardakht.php`
2. در WHMCS به **System Settings → Payment Gateways** بروید و درگاه «به‌پرداخت ملت» را فعال کنید.
3. مقادیر زیر را تنظیم کنید:
   - **Terminal ID**، **نام کاربری** و **رمز عبور** دریافتی از بانک.
   - **محیط سرویس** (Production یا Test) برای انتخاب WSDL و StartPay مناسب. در صورت استفاده از گزینه قدیمی «حالت تست»، همان رفتار استفاده می‌شود.【F:modules/gateways/behpardakht/common.php†L19-L52】【F:modules/gateways/behpardakht.php†L23-L145】
   - **واحد مبلغ سایت** (تومان/ریال) تا تبدیل مبلغ در `payment.php` درست انجام شود.【F:modules/gateways/behpardakht/payment.php†L43-L76】
   - در صورت نیاز به کنترل کد ملی: **حالت محدودسازی کد ملی** (none/payrequest-enc/redirect-ENC/mana-mobile+enc)، **شناسه فیلد کد ملی** (Custom Field)، **کلید ENC** (هگز ۱۶ کاراکتری، پیش‌فرض بانک) و در حالت مانا، **شناسه فیلد موبایل** یا شماره موبایل پروفایل کاربر.【F:modules/gateways/behpardakht.php†L87-L145】【F:modules/gateways/behpardakht/payment.php†L79-L154】
   - در صورت نیاز، **حالت تست** و **حالت دیباگ** را فعال کنید (در حالت دیباگ لاگ تراکنش‌ها در WHMCS ثبت می‌شود).
4. آدرس بازگشت را در پنل بانک به شکل زیر تنظیم کنید (systemurl را با دامنهٔ خود جایگزین کنید):  
   ```
   https://YOUR-WHMCS-DOMAIN/modules/gateways/callback/behpardakht.php
   ```
5. پس از فعال‌سازی، جدول `mod_behpardakht_transactions` به‌صورت خودکار ایجاد می‌شود و تراکنش‌های جدید در آن ثبت خواهند شد.【F:modules/gateways/behpardakht.php†L55-L91】

### نحوهٔ کار پرداخت

- در صفحهٔ فاکتور، دکمهٔ پرداخت ماژول تنها برای ارزهای IRR/IRT/TMN/TOMAN نمایش داده می‌شود و از طریق HMAC (کلید `CCEncryptionHash` در تنظیمات WHMCS) امضا می‌شود تا امکان تغییر مبلغ یا شناسه وجود نداشته باشد.【F:modules/gateways/behpardakht.php†L92-L118】  
- `payment.php` درخواست `bpPayRequest` را با مبلغ ریالی و شناسهٔ سفارش یکتا ارسال می‌کند و کاربر به صفحهٔ پرداخت بانک هدایت می‌شود.【F:modules/gateways/behpardakht/payment.php†L58-L120】  
- `callback/behpardakht.php` پس از بازگشت، Verify و Settle را انجام می‌دهد (با retry در Settle) و در صورت موفقیت، پرداخت را در فاکتور ثبت می‌کند یا در صورت خطا Reversal را تلاش می‌کند.【F:modules/gateways/callback/behpardakht.php†L82-L214】

### ریفاند

- از صفحهٔ فاکتور در WHMCS، گزینهٔ **Refund via Gateway** با درگاه «behpardakht» قابل استفاده است. ماژول با `bpRefundRequest` مبلغ را بر اساس ارز فاکتور به ریال تبدیل کرده و برای بانک ارسال می‌کند.【F:modules/gateways/behpardakht.php†L119-L182】

## نصب و استفاده از افزونهٔ مدیریت تراکنش‌ها

1. به **System Settings → Addon Modules** بروید و افزونهٔ «مدیریت تراکنش‌های بهپرداخت ملت» را فعال کنید. این افزونه از همان جدول تراکنش درگاه استفاده می‌کند و تغییری در ساختار دیتابیس نمی‌دهد.【F:modules/addons/behpardakht_transactions/behpardakht_transactions.php†L27-L89】
2. در تنظیمات افزونه می‌توانید تعداد رکورد در صفحه و امکان خروجی اکسل را مشخص کنید.【F:modules/addons/behpardakht_transactions/behpardakht_transactions.php†L31-L53】
3. از منوی Addons در WHMCS به صفحهٔ افزونه بروید و با فیلتر، جستجو، مرتب‌سازی و صفحه‌بندی، تراکنش‌ها را مشاهده کنید یا خروجی اکسل بگیرید.【F:modules/addons/behpardakht_transactions/templates/admin.tpl†L1-L194】

## ساختار جدول تراکنش‌ها

جدول `mod_behpardakht_transactions` در هنگام فعال‌سازی ایجاد می‌شود و ستون‌های کلیدی آن عبارت‌اند از:  
`invoice_id`، `order_id`، `ref_id` (Unique)، `sale_reference_id`، `amount_rial`، `card_holder_pan`، `status` (pending/completed/failed)، `error_code`، `created_at`، `updated_at`.【F:modules/gateways/behpardakht.php†L55-L91】

## مسیرها و فایل‌های مهم

- درگاه اصلی: `modules/gateways/behpardakht.php`  
- درخواست پرداخت: `modules/gateways/behpardakht/payment.php`  
- کال‌بک بانک: `modules/gateways/callback/behpardakht.php`  
- پیام محدودیت جغرافیایی نمونه (اختیاری): `modules/gateways/behpardakht/forbidden.php`  
- افزونهٔ مدیریت تراکنش‌ها: `modules/addons/behpardakht_transactions/behpardakht_transactions.php` و قالب/استایل مربوطه در همان پوشه.

## نکات و توصیه‌ها

- برای جلوگیری از هشدارهای TLS در SOAP، از گواهی معتبر روی سرور استفاده کنید یا مسیر CA را در گزینهٔ `stream_context` تنظیم کنید.【F:modules/gateways/behpardakht/payment.php†L91-L113】  
- درخواست‌ها بر اساس تنظیم «محیط سرویس» به آدرس‌های رسمی یا dev شاپرک ارسال می‌شوند.【F:modules/gateways/behpardakht/common.php†L19-L52】  
- در صورت نیاز به مسدودسازی دسترسی کاربرانی خارج از ایران، می‌توانید فایل `forbidden.php` را در وب‌سرور یا فایروال به عنوان صفحهٔ پیش‌فرض استفاده کنید.【F:modules/gateways/behpardakht/forbidden.php†L1-L15】

### محدودسازی کد ملی (ENC/enc)

- برای کد ملی مشتری یک Custom Field تعریف کنید (۱۰ رقم) و شناسه آن را در تنظیمات درگاه وارد کنید.  
- از گزینهٔ **محدودسازی کد ملی** یکی از حالت‌ها را انتخاب کنید:
  - `payrequest-enc`: ارسال پارامتر `enc` در bpPayRequest.  
  - `redirect-ENC`: ارسال فیلد `ENC` همراه فرم Redirect (Strong Auth).  
  - `mana-mobile+enc`: ارسال `MobileNo` (فرمت 98xxxxxxxxxx) و `enc` در Redirect.  
- کلید رمزنگاری پیش‌فرض `2C7D202B960A96AA` مطابق نمونه مستندات است؛ در صورت ارائه کلید اختصاصی، آن را جایگزین کنید.  
【F:modules/gateways/behpardakht/common.php†L83-L145】【F:modules/gateways/behpardakht/payment.php†L79-L154】
