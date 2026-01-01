<?php
/**
 * Behpardakht Callback Handler
 * Path: /modules/gateways/callback/behpardakht.php
 * @version 2.2.0
 * @requires PHP >= 8.1
 * @requires WHMCS >= 8.11
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once __DIR__ . '/../behpardakht/common.php';

use WHMCS\Database\Capsule;

$gatewayModuleName = 'behpardakht';
$gatewayParams = getGatewayVariables($gatewayModuleName);
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$debugMode = !empty($gatewayParams['debugMode']) && $gatewayParams['debugMode'] === 'on';
$unit = strtolower((string)($gatewayParams['unit'] ?? 'toman'));
if (!in_array($unit, ['toman', 'rial'], true)) {
    $unit = 'toman';
}

$endpoints = behpardakht_getEndpoints($gatewayParams);
$systemUrl = rtrim($gatewayParams['systemurl'] ?? '/', '/') . '/';

// داده‌های برگشتی بانک
$refId           = trim((string)($_POST['RefId'] ?? ''));
$resCode         = trim((string)($_POST['ResCode'] ?? ''));
$saleOrderId     = trim((string)($_POST['SaleOrderId'] ?? ''));
$saleReferenceId = trim((string)($_POST['SaleReferenceId'] ?? ''));
$cardHolderPan   = trim((string)($_POST['CardHolderPan'] ?? ''));
$finalAmount     = trim((string)($_POST['FinalAmount'] ?? '')); // ریال

if ($debugMode) {
    logTransaction($gatewayModuleName, [
        'action' => 'callback_received',
        'RefId' => $refId,
        'ResCode' => $resCode,
        'SaleOrderId' => $saleOrderId,
        'SaleReferenceId' => $saleReferenceId,
        'CardHolderPan' => $cardHolderPan,
        'FinalAmountRial' => $finalAmount,
        'unit' => $unit,
    ], 'Callback Received');
}

// یافتن رکورد تراکنش
$tx = null;
if ($refId !== '') {
    $tx = Capsule::table('mod_behpardakht_transactions')->where('ref_id', $refId)->first();
}
if (!$tx && $saleOrderId !== '') {
    $tx = Capsule::table('mod_behpardakht_transactions')->where('order_id', (int)$saleOrderId)->first();
}
if (!$tx) {
    if ($debugMode) {
        logTransaction($gatewayModuleName, ['action' => 'callback_error', 'msg' => 'Transaction not found', 'RefId' => $refId, 'SaleOrderId' => $saleOrderId], 'Transaction Not Found');
    }
    header('Location: ' . $systemUrl . 'clientarea.php?action=invoices&status=unpaid&error=' . urlencode('تراکنش یافت نشد'));
    exit;
}

$invoiceId = (int)$tx->invoice_id;

// بررسی تطبیق الزامی RefId و SaleOrderId
if ($refId === '' || $refId !== $tx->ref_id || $saleOrderId === '' || (string)$saleOrderId !== (string)$tx->order_id) {
    Capsule::table('mod_behpardakht_transactions')
        ->where('id', $tx->id)
        ->update([
            'status' => 'failed',
            'error_code' => 'MISMATCH',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    header('Location: ' . $systemUrl . 'viewinvoice.php?id=' . $invoiceId . '&paymentfailed=true&failurereason=' . urlencode('تطبیق تراکنش نامعتبر است'));
    exit;
}

// پرداخت قبلاً نهایی شده
if (($tx->status ?? '') === 'completed') {
    header('Location: ' . $systemUrl . 'viewinvoice.php?id=' . $invoiceId . '&paymentsuccess=true');
    exit;
}

// رد یا خطای کاربر
if ($resCode !== '0') {
    Capsule::table('mod_behpardakht_transactions')
        ->where('id', $tx->id)
        ->update([
            'status' => 'failed',
            'error_code' => $resCode,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    $msg = behpardakht_getErrorMessage($resCode);
    header('Location: ' . $systemUrl . 'viewinvoice.php?id=' . $invoiceId . '&paymentfailed=true&failurereason=' . urlencode($msg));
    exit;
}

// حتماً باید SaleReferenceId داشته باشیم
if ($saleReferenceId === '') {
    Capsule::table('mod_behpardakht_transactions')
        ->where('id', $tx->id)
        ->update([
            'status' => 'failed',
            'error_code' => 'NO_REFERENCE',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    header('Location: ' . $systemUrl . 'viewinvoice.php?id=' . $invoiceId . '&paymentfailed=true&failurereason=' . urlencode('کد مرجع بانکی ارسال نشده است'));
    exit;
}

// کنترل مبلغ نهایی در صورت ارسال
$storedAmount = (int)$tx->amount_rial;
if ($finalAmount !== '' && (int)$finalAmount !== $storedAmount) {
    Capsule::table('mod_behpardakht_transactions')
        ->where('id', $tx->id)
        ->update([
            'status' => 'failed',
            'error_code' => 'AMOUNT_MISMATCH',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    header('Location: ' . $systemUrl . 'viewinvoice.php?id=' . $invoiceId . '&paymentfailed=true&failurereason=' . urlencode('مبلغ تراکنش معتبر نیست'));
    exit;
}

checkCbInvoiceID($invoiceId, $gatewayModuleName);

$apiUrl = $endpoints['wsdl'];

try {
    $soap = new SoapClient($apiUrl, [
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
            ]
        ])
    ]);

    $verifyParams = [
        'terminalId'      => $gatewayParams['terminalId'],
        'userName'        => $gatewayParams['userName'],
        'userPassword'    => $gatewayParams['userPassword'],
        'orderId'         => (int)$tx->order_id,
        'saleOrderId'     => (int)$saleOrderId,
        'saleReferenceId' => (int)$saleReferenceId,
    ];

    $verifyCode = behpardakht_verifySettleWithRetry($soap, $verifyParams, $debugMode);

    $settleOk = false;
    $lastCode = $verifyCode;

    if (in_array($verifyCode, ['0', '45'], true)) {
        $settleOk = true;
    } elseif ($verifyCode === '43') {
        // fallback به settle کلاسیک
        $settleCode = behpardakht_settleWithRetry($soap, $verifyParams, $debugMode);
        $lastCode = $settleCode;
        if (in_array($settleCode, ['0', '45'], true)) {
            $settleOk = true;
        }
    }

    if ($settleOk) {
        $finalAmountRial = $finalAmount !== '' ? (int)$finalAmount : $storedAmount;
        Capsule::table('mod_behpardakht_transactions')
            ->where('id', $tx->id)
            ->update([
                'status' => 'completed',
                'sale_reference_id' => $saleReferenceId,
                'card_holder_pan' => $cardHolderPan !== '' ? $cardHolderPan : null,
                'final_amount_rial' => $finalAmountRial,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $amountSite = $unit === 'toman' ? $finalAmountRial / 10 : $finalAmountRial;
        checkCbTransID($saleReferenceId);
        addInvoicePayment(
            $invoiceId,
            $saleReferenceId,
            $amountSite,
            0,
            $gatewayModuleName
        );

        if ($debugMode) {
            logTransaction($gatewayModuleName, [
                'action' => 'payment_success',
                'RefId' => $refId,
                'SaleReferenceId' => $saleReferenceId,
                'FinalAmountRial' => $finalAmountRial,
                'RecordedAmount' => $amountSite,
                'unit' => $unit,
            ], 'Success');
        }

        header('Location: ' . $systemUrl . 'viewinvoice.php?id=' . $invoiceId . '&paymentsuccess=true');
        exit;
    }

    behpardakht_attemptReversal($apiUrl, $gatewayParams, (int)$tx->order_id, (int)$saleOrderId, (int)$saleReferenceId, $debugMode, $gatewayModuleName);
    $msg = behpardakht_getErrorMessage((string)$lastCode);
    Capsule::table('mod_behpardakht_transactions')
        ->where('id', $tx->id)
        ->update([
            'status' => 'failed',
            'error_code' => $lastCode,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    header('Location: ' . $systemUrl . 'viewinvoice.php?id=' . $invoiceId . '&paymentfailed=true&failurereason=' . urlencode($msg));
    exit;
} catch (Exception $e) {
    if ($debugMode) {
        logTransaction($gatewayModuleName, ['action' => 'callback_exception', 'error' => $e->getMessage()], 'Exception');
    }
    header('Location: ' . $systemUrl . 'viewinvoice.php?id=' . $invoiceId . '&paymentfailed=true&failurereason=' . urlencode('خطای سیستمی'));
    exit;
}

/**
 * Verify + Settle یکپارچه با ریتری
 */
function behpardakht_verifySettleWithRetry(SoapClient $soap, array $params, bool $debugMode): string
{
    $gatewayModuleName = 'behpardakht';
    $retryable = ['34', '31', '32', '46', '61', '421'];
    $attempt = 0;
    $lastCode = '';

    while ($attempt < 3) {
        $attempt++;
        if ($debugMode) {
            $safe = $params; $safe['userPassword'] = '***';
            logTransaction($gatewayModuleName, ['action' => 'verify_settle_attempt', 'attempt' => $attempt, 'params' => $safe], 'VerifySettle Attempt');
        }

        $result = $soap->bpVerifySettleRequest($params);
        $code = trim((string)($result->return ?? ''));
        $lastCode = $code;

        if ($debugMode) {
            logTransaction($gatewayModuleName, ['action' => 'verify_settle_response', 'attempt' => $attempt, 'code' => $code], 'VerifySettle Response');
        }

        if (!in_array($code, $retryable, true)) {
            break;
        }

        sleep(1 << ($attempt - 1)); // 1,2,4
    }

    return $lastCode;
}

/**
 * Settle کلاسیک با ریتری (برای کد 43)
 */
function behpardakht_settleWithRetry(SoapClient $soap, array $params, bool $debugMode): string
{
    $gatewayModuleName = 'behpardakht';
    $retryable = ['34', '46', '61', '421', '31', '32'];
    $attempt = 0;
    $lastCode = '';

    while ($attempt < 3) {
        $attempt++;
        if ($debugMode) {
            $safe = $params; $safe['userPassword'] = '***';
            logTransaction($gatewayModuleName, ['action' => 'settle_attempt', 'attempt' => $attempt, 'params' => $safe], 'Settle Attempt');
        }

        $response = $soap->bpSettleRequest($params);
        $code = trim((string)($response->return ?? ''));
        $lastCode = $code;

        if ($debugMode) {
            logTransaction($gatewayModuleName, ['action' => 'settle_response', 'attempt' => $attempt, 'code' => $code], 'Settle Response');
        }

        if (!in_array($code, $retryable, true)) {
            break;
        }
        sleep(1 << ($attempt - 1));
    }

    return $lastCode;
}

/**
 * Reverse در صورت خطا (Best-effort)
 */
function behpardakht_attemptReversal(string $apiUrl, array $gatewayParams, int $orderId, int $saleOrderId, int $saleReferenceId, bool $debugMode, string $moduleName): void
{
    if (!$saleReferenceId) {
        return;
    }

    try {
        $soap = new SoapClient($apiUrl, [
            'exceptions' => true,
            'connection_timeout' => 60,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'encoding' => 'UTF-8',
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer'       => true,
                    'verify_peer_name'  => true,
                    'allow_self_signed' => false,
                ]
            ])
        ]);

        $params = [
            'terminalId'      => $gatewayParams['terminalId'],
            'userName'        => $gatewayParams['userName'],
            'userPassword'    => $gatewayParams['userPassword'],
            'orderId'         => $orderId,
            'saleOrderId'     => $saleOrderId,
            'saleReferenceId' => $saleReferenceId,
        ];

        $result = $soap->bpReversalRequest($params);
        if ($debugMode) {
            logTransaction($moduleName, ['action' => 'reversal_request', 'result' => $result->return ?? null], 'Reverse Attempt');
        }
    } catch (Exception $e) {
        if ($debugMode) {
            logTransaction($moduleName, ['action' => 'reversal_exception', 'error' => $e->getMessage()], 'Reverse Exception');
        }
    }
}
