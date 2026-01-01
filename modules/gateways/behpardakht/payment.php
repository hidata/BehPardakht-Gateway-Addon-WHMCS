<?php
/**
 * Behpardakht Payment Processing
 * Path: /modules/gateways/behpardakht/payment.php
 * @version 2.1.4
 * @requires PHP >= 8.1
 * @requires WHMCS >= 8.11
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once __DIR__ . '/common.php';

use WHMCS\Database\Capsule;
use WHMCS\Config\Setting;

$gatewayModuleName = 'behpardakht';
$gatewayParams = getGatewayVariables($gatewayModuleName);
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// ورودی‌های فرم
$invoiceId    = isset($_POST['invoiceId']) ? (int)$_POST['invoiceId'] : 0;
$postedAmount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0; // مبلغ به «واحد سایت» طبق تنظیم درگاه
$nonce        = $_POST['nonce'] ?? '';
$sig          = $_POST['sig'] ?? '';

// اعتبارسنجی HMAC
$secret  = Setting::getValue('CCEncryptionHash') ?: 'behpardakht';
$payload = $invoiceId . '|' . number_format($postedAmount, 2, '.', '') . '|' . $nonce;
$calcSig = hash_hmac('sha256', $payload, $secret);

$systemUrl = rtrim($gatewayParams['systemurl'] ?? '/', '/') . '/';
$debugMode = !empty($gatewayParams['debugMode']) && $gatewayParams['debugMode'] === 'on';
if (!$invoiceId || !$nonce || !$sig || !hash_equals($calcSig, $sig)) {
    $reason = $debugMode ? 'درخواست نامعتبر (HMAC).' : 'درخواست نامعتبر است. لطفاً دوباره تلاش کنید.';
    header('Location: ' . $systemUrl . 'viewinvoice.php?id=' . $invoiceId . '&paymentfailed=true&failurereason=' . urlencode($reason));
    exit;
}

// اعتبارسنجی فاکتور (فقط وجودش)
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayModuleName);
$invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
if (!$invoice) {
    die("Invalid Invoice ID");
}
$clientId = (int)($invoice->userid ?? 0);

// پارامترهای گیت‌وی
$terminalId   = $gatewayParams['terminalId'];
$userName     = $gatewayParams['userName'];
$userPassword = $gatewayParams['userPassword'];
$callbackOverride = trim((string)($gatewayParams['callbackUrlOverride'] ?? ''));
$paymentLanguage = strtolower((string)($gatewayParams['paymentLanguage'] ?? 'fa'));
$enableNationalAuth = !empty($gatewayParams['enable_national_code_auth']) && $gatewayParams['enable_national_code_auth'] === 'on';
$nationalCodeFieldId = (int)($gatewayParams['national_code_customfield'] ?? 0);
$nationalCodeKeyHex = trim((string)($gatewayParams['national_code_auth_key_hex'] ?? ''));

// واحد مبلغ سایت از تنظیمات درگاه: 'toman' یا 'rial'
$unit = strtolower((string)($gatewayParams['unit'] ?? 'toman'));
if (!in_array($unit, ['toman','rial'], true)) {
    $unit = 'toman';
}

$callBackUrl = $callbackOverride !== ''
    ? $callbackOverride
    : $systemUrl . 'modules/gateways/callback/behpardakht.php';
$endpoints = behpardakht_getEndpoints($gatewayParams);
$apiUrl = $endpoints['wsdl'];
$paymentUrl = $paymentLanguage === 'en'
    ? $endpoints['startpay_en']
    : $endpoints['startpay_fa'];

// تبدیل مبلغ به «ریال» برای بانک
$amountRial = (int)round($postedAmount * ($unit === 'toman' ? 10 : 1));

// orderId عددی و یکتا (حداکثر ~18 رقم)
$orderId = (int)substr(time() . str_pad((string)$invoiceId, 6, '0', STR_PAD_LEFT), 0, 18);
$nationalCode = null;
$encNationalCode = null;

if ($enableNationalAuth) {
    $nationalCode = behpardakht_normalizeNationalCode(
        behpardakht_getCustomFieldValue($clientId, $nationalCodeFieldId)
    );

    if ($nationalCode === null) {
        $reason = 'کد ملی نامعتبر است. لطفا کد ملی 10 رقمی را در پروفایل خود ثبت کنید و سپس پرداخت را انجام دهید.';
        header('Location: ' . $systemUrl . 'viewinvoice.php?id=' . $invoiceId . '&paymentfailed=true&failurereason=' . urlencode($reason));
        exit;
    }

    $keyHex = $nationalCodeKeyHex !== '' ? $nationalCodeKeyHex : '2C7D202B960A96AA';

    try {
        $encNationalCode = behpardakht_encryptNationalCode($nationalCode, $keyHex);
    } catch (RuntimeException $e) {
        $reason = $e->getMessage();
        if (stripos($reason, 'DES cipher not available') !== false) {
            $reason = 'رمزنگاری DES روی سرور فعال نیست؛ لطفاً OpenSSL legacy provider را فعال یا phpseclib3 را نصب کنید.';
        }
        header('Location: ' . $systemUrl . 'viewinvoice.php?id=' . $invoiceId . '&paymentfailed=true&failurereason=' . urlencode($reason));
        exit;
    }
}

try {
    $soapOptions = [
        'exceptions' => true,
        'connection_timeout' => 60,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'encoding' => 'UTF-8',
        'trace' => $debugMode,
        'stream_context' => stream_context_create([
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
                // در صورت نیاز: 'cafile' => '/etc/ssl/certs/ca-certificates.crt',
            ]
        ])
    ];
    $soapClient = new SoapClient($apiUrl, $soapOptions);

    $parameters = [
        'terminalId'     => $terminalId,
        'userName'       => $userName,
        'userPassword'   => $userPassword,
        'orderId'        => $orderId,
        'amount'         => $amountRial,
        'localDate'      => date('Ymd'),
        'localTime'      => date('His'),
        'additionalData' => (string)$invoiceId,
        'callBackUrl'    => $callBackUrl,
        'payerId'        => 0
    ];

    if ($enableNationalAuth && $encNationalCode) {
        $parameters['enc'] = $encNationalCode;
    }

    if ($debugMode) {
        $safe = $parameters; $safe['userPassword'] = '***';
        if (isset($safe['enc'])) {
            $safe['enc'] = behpardakht_mask($safe['enc']);
        }
        logTransaction($gatewayModuleName, [
            'action'=>'payment_request',
            'parameters'=>$safe,
            'unit'=>$unit,
            'national_code_auth'=> $enableNationalAuth ? 'on' : 'off',
            'enc_sent'=> $encNationalCode ? 'yes' : 'no'
        ], 'Request Sent');
    }

    $result = $soapClient->bpPayRequest($parameters);
    $resultString = trim((string)($result->return ?? ''));
    [$code, $refId] = array_map('trim', explode(',', $resultString, 2) + ['', '']);

    if ($code === '0' && $refId !== '') {
        Capsule::table('mod_behpardakht_transactions')->insert([
            'invoice_id'  => $invoiceId,
            'order_id'    => $orderId,
            'ref_id'      => $refId,
            'amount_rial' => $amountRial,
            'status'      => 'pending',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        echo '<!DOCTYPE html>
        <html lang="fa"><head><meta charset="UTF-8"><title>در حال انتقال به بانک...</title>
        <style>body{font-family:Tahoma,Arial;text-align:center;padding:50px;direction:rtl}</style>
        </head><body>
            <h2>در حال انتقال به درگاه بانک ملت</h2>
            <form id="pay" method="post" action="'.$paymentUrl.'">
                <input type="hidden" name="RefId" value="'.htmlspecialchars($refId, ENT_QUOTES, 'UTF-8').'">';
                echo '
            </form>
            <script>document.getElementById("pay").submit();</script>
        </body></html>';
        exit;
    } else {
        $msg = behpardakht_getErrorMessage($code ?? 'unknown');
        if ($debugMode) {
            logTransaction($gatewayModuleName, [
                'action'=>'payment_request_failed',
                'response'=>$resultString,
                'message'=>$msg
            ], 'Request Failed');
        }
        header('Location: ' . $systemUrl . 'viewinvoice.php?id=' . $invoiceId . '&paymentfailed=true&failurereason=' . urlencode($msg));
        exit;
    }

} catch (Exception $e) {
    if ($debugMode) {
        logTransaction($gatewayModuleName, ['action'=>'payment_exception','error'=>$e->getMessage()], 'Exception');
        $reason = 'خطای سیستمی: ' . $e->getMessage();
    } else {
        $reason = 'خطای سیستمی';
    }
    header('Location: ' . $systemUrl . 'viewinvoice.php?id=' . $invoiceId . '&paymentfailed=true&failurereason=' . urlencode($reason));
    exit;
}
