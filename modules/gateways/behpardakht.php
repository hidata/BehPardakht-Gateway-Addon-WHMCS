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
        'testMode' => [
            'FriendlyName' => 'حالت تست',
            'Type' => 'yesno',
            'Description' => 'برای استفاده از محیط تست فعال کنید',
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
            $table->enum('status', ['pending','completed','failed'])->default('pending');
            $table->string('error_code', 16)->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
        });
    } catch (\Exception $e) {
        // اگر جدول قبلا وجود داشت، نادیده بگیر
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
    $testMode     = !empty($params['testMode']) && $params['testMode'] === 'on';

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

    $apiUrl = $testMode
        ? 'https://sandbox.banktest.ir/mellat/bpm.shaparak.ir/pgwchannel/services/pgw?wsdl'
        : 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';

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
            'orderId'         => time(),              // شناسه جدید برای درخواست ریفاند
            'saleOrderId'     => (int)$tx->order_id,  // orderId تراکنش اصلی
            'saleReferenceId' => $saleReferenceId,    // کد مرجع بانکی
            'refundAmount'    => $refundAmountRial,
            'localDate'       => date('Ymd'),
            'localTime'       => date('His'),
        ];

        $res = $soap->bpRefundRequest($req);
        if (($res->return ?? null) === '0') {
            return ['status' => 'success', 'rawdata' => '0', 'transid' => $saleReferenceId];
        }
        return ['status' => 'declined', 'rawdata' => behpardakht_getErrorMessage((string)$res->return)];
    } catch (Exception $e) {
        return ['status' => 'error', 'rawdata' => $e->getMessage()];
    }
}