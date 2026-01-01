<?php
/**
 * WHMCS Behpardakht Mellat Payment Gateway Module
 * @version 2.1.2
 * @requires PHP >= 8.1
 * @requires WHMCS >= 8.11
 */

declare(strict_types=1);

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/behpardakht/common.php';

use WHMCS\Database\Capsule;
use WHMCS\Config\Setting;

/**
 * Module metadata
 */
function behpardakht_MetaData(): array
{
    return [
        'DisplayName' => 'به‌پرداخت ملت',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    ];
}

/**
 * Gateway configuration
 */
function behpardakht_config(): array
{
    // لیست فیلدهای سفارشی مشتری برای انتخاب در تنظیمات درگاه
    $customFieldOptions = ['0' => '--- انتخاب نشده ---'];
    try {
        $fields = Capsule::table('tblcustomfields')
            ->where('type', 'client')
            ->orderBy('sortorder')
            ->orderBy('id')
            ->get(['id', 'fieldname']);

        foreach ($fields as $field) {
            $customFieldOptions[(string)$field->id] = '[' . $field->id . '] ' . $field->fieldname;
        }
    } catch (\Throwable $e) {
        // در صورت خطا از گزینه پیش‌فرض استفاده می‌شود
    }

    return [
        'FriendlyName' => [
            'Type'  => 'System',
            'Value' => 'به‌پرداخت ملت',
        ],
        'terminalId' => [
            'FriendlyName' => 'شناسه پایانه (Terminal ID)',
            'Type' => 'text', 'Size' => '20', 'Default' => '',
            'Description' => 'شناسه پایانه دریافتی از بانک ملت',
        ],
        'userName' => [
            'FriendlyName' => 'نام کاربری',
            'Type' => 'text', 'Size' => '25', 'Default' => '',
            'Description' => 'نام کاربری دریافتی از بانک',
        ],
        'userPassword' => [
            'FriendlyName' => 'رمز عبور',
            'Type' => 'password', 'Size' => '25', 'Default' => '',
            'Description' => 'رمز عبور دریافتی از بانک',
        ],
        'environment' => [
            'FriendlyName' => 'محیط سرویس',
            'Type' => 'dropdown',
            'Options' => [
                'production' => 'Production (bpm.shaparak.ir)',
                'test' => 'Test / Dev (pgw.dev.bpmellat.ir)',
            ],
            'Default' => 'production',
            'Description' => 'برای استفاده از WSDL و StartPay تست، گزینه Test را انتخاب کنید.',
        ],
        'callbackUrlOverride' => [
            'FriendlyName' => 'آدرس بازگشت (Callback)',
            'Type' => 'text', 'Size' => '255', 'Default' => '',
            'Description' => 'در صورت خالی بودن، آدرس بازگشت به‌صورت خودکار بر اساس دامنه WHMCS ساخته می‌شود.',
        ],
        'paymentLanguage' => [
            'FriendlyName' => 'زبان پرداخت',
            'Type' => 'dropdown',
            'Options' => [
                'fa' => 'فارسی',
                'en' => 'English',
            ],
            'Default' => 'fa',
            'Description' => 'در صورت انتخاب English، هدایت به صفحه انگلیسی درگاه انجام می‌شود.',
        ],
        'testMode' => [
            'FriendlyName' => 'حالت تست',
            'Type' => 'yesno',
            'Description' => 'برای استفاده از محیط تست فعال کنید (در صورت عدم استفاده از گزینه محیط سرویس).',
        ],
        'debugMode' => [
            'FriendlyName' => 'حالت دیباگ',
            'Type' => 'yesno',
            'Description' => 'برای ثبت لاگ تراکنش‌ها فعال کنید',
        ],
        'unit' => [
            'FriendlyName' => 'واحد مبلغ سایت',
            'Type'        => 'dropdown',
            'Options'     => [
                'toman' => 'تومان',
                'rial'  => 'ریال',
            ],
            'Default'     => 'toman',
            'Description' => 'مشخص کنید مبلغ‌های سایت بر مبنای تومان است یا ریال. (درگاه همیشه با «ریال» کار می‌کند.)',
        ],
        'nationalCodeMode' => [
            'FriendlyName' => 'محدودسازی کد ملی',
            'Type' => 'dropdown',
            'Options' => [
                'none' => 'غیرفعال',
                'payrequest-enc' => 'ارسال enc در PayRequest',
                'redirect-ENC' => 'ارسال ENC در Redirect (Strong Auth)',
                'mana-mobile+enc' => 'MobileNo + enc (مانا)',
            ],
            'Default' => 'none',
            'Description' => 'برای استفاده از ویژگی ENC/enc در مستندات جدید، یکی از حالت‌های بالا را انتخاب کنید.',
        ],
        'nationalCodeFieldId' => [
            'FriendlyName' => 'شناسه فیلد کد ملی (Custom Field)',
            'Type' => 'dropdown',
            'Options' => $customFieldOptions,
            'Default' => '0',
            'Description' => 'یکی از فیلدهای سفارشی مشتری (۱۰ رقمی) را برای دریافت کد ملی انتخاب کنید.',
        ],
        'nationalCodeKey' => [
            'FriendlyName' => 'کلید ENC (هگز 16 کاراکتری)',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '2C7D202B960A96AA',
            'Description' => 'کلید رمزنگاری DES/ECB برای ENC. مقدار پیش‌فرض مطابق مستندات بانک است.',
        ],
        'nationalCodeRequire' => [
            'FriendlyName' => 'الزام کد ملی',
            'Type' => 'yesno',
            'Description' => 'در حالت‌های ENC اگر فعال باشد، بدون کد ملی پرداخت شروع نمی‌شود.',
        ],
        'mobileFieldId' => [
            'FriendlyName' => 'شناسه فیلد موبایل (Custom Field)',
            'Type' => 'dropdown',
            'Options' => $customFieldOptions,
            'Default' => '0',
            'Description' => 'برای حالت mana-mobile+enc، فیلد سفارشی موبایل (یا تلفن پروفایل) را انتخاب کنید.',
        ],
    ];
}

/**
 * نصب جدول های مورد نیاز
 */
function behpardakht_activate(): array
{
    try {
        Capsule::schema()->create('mod_behpardakht_transactions', function ($table) {
            /** @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->unsignedInteger('invoice_id')->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('ref_id', 64)->unique();
            $table->string('sale_reference_id', 64)->nullable()->index();
            $table->unsignedBigInteger('amount_rial');
            $table->string('card_holder_pan', 64)->nullable();
            $table->unsignedBigInteger('final_amount_rial')->nullable();
            $table->enum('status', ['pending','completed','failed'])->default('pending');
            $table->string('error_code', 16)->nullable();
            $table->enum('refund_status', ['none','pending','completed','failed','unknown'])->default('none');
            $table->unsignedBigInteger('refund_order_id')->nullable();
            $table->unsignedBigInteger('refund_amount_rial')->nullable();
            $table->string('refund_reference_number', 128)->nullable();
            $table->string('refund_error_code', 32)->nullable();
            $table->dateTime('refund_created_at')->nullable();
            $table->dateTime('refund_updated_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
        });
    } catch (\Exception $e) {
        // اگر جدول قبلا وجود داشت، نادیده بگیر
        $schema = Capsule::schema();
        if ($schema->hasTable('mod_behpardakht_transactions')) {
            $schema->table('mod_behpardakht_transactions', function ($table) use ($schema) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                if (!$schema->hasColumn('mod_behpardakht_transactions', 'final_amount_rial')) {
                    $table->unsignedBigInteger('final_amount_rial')->nullable()->after('amount_rial');
                }
                if (!$schema->hasColumn('mod_behpardakht_transactions', 'refund_status')) {
                    $table->enum('refund_status', ['none','pending','completed','failed','unknown'])->default('none')->after('error_code');
                }
                if (!$schema->hasColumn('mod_behpardakht_transactions', 'refund_order_id')) {
                    $table->unsignedBigInteger('refund_order_id')->nullable()->after('refund_status');
                }
                if (!$schema->hasColumn('mod_behpardakht_transactions', 'refund_amount_rial')) {
                    $table->unsignedBigInteger('refund_amount_rial')->nullable()->after('refund_order_id');
                }
                if (!$schema->hasColumn('mod_behpardakht_transactions', 'refund_reference_number')) {
                    $table->string('refund_reference_number', 128)->nullable()->after('refund_amount_rial');
                }
                if (!$schema->hasColumn('mod_behpardakht_transactions', 'refund_error_code')) {
                    $table->string('refund_error_code', 32)->nullable()->after('refund_reference_number');
                }
                if (!$schema->hasColumn('mod_behpardakht_transactions', 'refund_created_at')) {
                    $table->dateTime('refund_created_at')->nullable()->after('refund_error_code');
                }
                if (!$schema->hasColumn('mod_behpardakht_transactions', 'refund_updated_at')) {
                    $table->dateTime('refund_updated_at')->nullable()->after('refund_created_at');
                }
            });
        }
    }

    return ['status' => 'success', 'description' => 'به‌پرداخت ملت فعال شد.'];
}

function behpardakht_deactivate(): array
{
    // جدول را حذف نکنیم تا گزارشات باقی بماند
    return ['status' => 'success', 'description' => 'به‌پرداخت ملت غیرفعال شد.'];
}

/**
 * ایجاد لینک پرداخت
 * - کنترل ارز (IRR/IRT/TMN/TOMAN)
 * - فقط invoiceId + amount + nonce + sig ارسال می‌شود (بدون کرِدِنشیال)
 */
function behpardakht_link(array $params): string
{
    $invoiceId = (int)$params['invoiceid'];
    $amount = (float)$params['amount']; // مبلغ قابل پرداخت فعلی (به ارز فاکتور)
    $systemUrl = rtrim($params['systemurl'], '/') . '/';
    $langPayNow = $params['langpaynow'] ?? 'Pay Now';

    // کنترل ارز در سطح UI
    $currencyCode = strtoupper($params['currency'] ?? '');
    $allowed = ['IRR','IRT','TMN','TOMAN'];
    if (!in_array($currencyCode, $allowed, true)) {
        // فقط پیام نشان بده و از ساخت فرم جلوگیری کن
        return '<div class="alert alert-warning" style="direction:rtl;text-align:right">
            این درگاه فقط برای پرداخت با <strong>ریال/تومان</strong> در دسترس است. 
            لطفاً روش پرداخت دیگری را برای ارز فعلی انتخاب کنید.
        </div>';
    }

    // امضای ضد دست‌کاری
    $secret = Setting::getValue('CCEncryptionHash') ?: 'behpardakht';
    $nonce  = bin2hex(random_bytes(8));
    $payload = $invoiceId . '|' . number_format($amount, 2, '.', '') . '|' . $nonce;
    $sig    = hash_hmac('sha256', $payload, $secret);

    $html  = '<form method="post" action="' . $systemUrl . 'modules/gateways/behpardakht/payment.php">';
    $html .= '<input type="hidden" name="invoiceId" value="' . $invoiceId . '">';
    $html .= '<input type="hidden" name="amount" value="' . htmlspecialchars((string)$amount, ENT_QUOTES, 'UTF-8') . '">';
    $html .= '<input type="hidden" name="nonce" value="' . $nonce . '">';
    $html .= '<input type="hidden" name="sig" value="' . $sig . '">';
    $html .= '<button type="submit" class="btn btn-primary">' . $langPayNow . '</button>';
    $html .= '</form>';

    return $html;
}

/**
 * ریفاند تراکنش (Refund via Gateway)
 */
function behpardakht_refund(array $params): array
{
    $terminalId   = $params['terminalId'] ?? '';
    $userName     = $params['userName'] ?? '';
    $userPassword = $params['userPassword'] ?? '';

    $saleReferenceId = $params['transid']; // ترنس‌آی‌دی WHMCS معمولاً SaleReferenceId است
    $currencyCode = strtoupper($params['currency'] ?? ''); // کُد ارز فاکتور
    $looksLikeToman = in_array($currencyCode, ['IRT','TMN','TOMAN'], true)
        || (isset($params['currencyPrefix']) && (stripos($params['currencyPrefix'], 'تومان') !== false || stripos($params['currencyPrefix'], 'Toman') !== false))
        || (isset($params['currencySuffix']) && (stripos($params['currencySuffix'], 'تومان') !== false || stripos($params['currencySuffix'], 'Toman') !== false));

    // مبلغ ریفاند به ریال: تومان ×10، ریال ×1
    $refundAmountRial = (int) round(((float)$params['amount']) * ($looksLikeToman ? 10 : 1));

    // یافتن order_id تراکنش اصلی
    $tx = Capsule::table('mod_behpardakht_transactions')
        ->where('sale_reference_id', $saleReferenceId)
        ->orWhere('ref_id', $saleReferenceId)
        ->orderBy('id', 'desc')
        ->first();

    if (!$tx) {
        return ['status' => 'error', 'rawdata' => 'Original transaction not found for refund'];
    }

    if (($tx->status ?? '') !== 'completed' || empty($tx->sale_reference_id)) {
        return ['status' => 'error', 'rawdata' => 'Refund is only allowed for settled transactions with reference id'];
    }

    $endpoints = behpardakht_getEndpoints($params);
    $apiUrl = $endpoints['wsdl'];

    $refundOrderId = (int) substr((string)time() . str_pad((string)mt_rand(0, 999), 3, '0', STR_PAD_LEFT), 0, 18);
    try {
        $soap = new SoapClient($apiUrl, [
            'exceptions' => true,
            'connection_timeout' => 60,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'encoding' => 'UTF-8',
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false
                ]
            ])
        ]);

        $req = [
            'terminalId'      => $terminalId,
            'userName'        => $userName,
            'userPassword'    => $userPassword,
            'orderId'         => $refundOrderId,      // شناسه جدید برای درخواست ریفاند
            'saleOrderId'     => (int)$tx->order_id,  // orderId تراکنش اصلی
            'saleReferenceId' => $saleReferenceId,    // کد مرجع بانکی
            'refundAmount'    => $refundAmountRial,
        ];

        $res = $soap->bpRefundRequest($req);
        $resultString = trim((string)($res->return ?? ''));
        $parts = explode(',', $resultString, 2);
        $resCode = trim($parts[0] ?? '');
        $refundRefNum = trim($parts[1] ?? '');

        $now = date('Y-m-d H:i:s');
        $update = [
            'refund_order_id' => $refundOrderId,
            'refund_amount_rial' => $refundAmountRial,
            'refund_reference_number' => $refundRefNum !== '' ? $refundRefNum : null,
            'refund_error_code' => $resCode !== '' ? $resCode : null,
            'refund_updated_at' => $now,
        ];

        if ($resCode === '0') {
            $update['refund_status'] = 'pending';
            $update['refund_created_at'] = $now;
            Capsule::table('mod_behpardakht_transactions')
                ->where('id', $tx->id)
                ->update($update);

            return [
                'status' => 'success',
                'rawdata' => $resultString,
                'transid' => $refundRefNum !== '' ? $refundRefNum : (string)$refundOrderId,
            ];
        }

        $update['refund_status'] = $resCode === '' ? 'unknown' : 'failed';
        Capsule::table('mod_behpardakht_transactions')
            ->where('id', $tx->id)
            ->update($update);

        return [
            'status' => 'error',
            'rawdata' => $resultString !== '' ? $resultString : 'No response',
            'transid' => '',
        ];
    } catch (Exception $e) {
        Capsule::table('mod_behpardakht_transactions')
            ->where('id', $tx->id)
            ->update([
                'refund_status' => 'unknown',
                'refund_order_id' => $refundOrderId,
                'refund_amount_rial' => $refundAmountRial,
                'refund_error_code' => 'exception',
                'refund_updated_at' => date('Y-m-d H:i:s'),
            ]);

        return ['status' => 'error', 'rawdata' => $e->getMessage(), 'transid' => ''];
    }
}
