<?php
/**
 * Behpardakht Callback Handler
 * Path: /modules/gateways/callback/behpardakht.php
 * @version 2.1.4
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
$testMode  = !empty($gatewayParams['testMode']) && $gatewayParams['testMode'] === 'on';

// واحد مبلغ سایت از تنظیمات درگاه: 'toman' یا 'rial'
$unit = strtolower((string)($gatewayParams['unit'] ?? 'toman'));
if (!in_array($unit, ['toman','rial'], true)) {
    $unit = 'toman';
}

// داده‌های برگشتی بانک
$refId           = $_POST['RefId'] ?? '';
$resCode         = $_POST['ResCode'] ?? '';
$saleOrderId     = $_POST['SaleOrderId'] ?? '';
$saleReferenceId = $_POST['SaleReferenceId'] ?? '';
$cardHolderInfo  = $_POST['CardHolderInfo'] ?? '';
$cardHolderPan   = $_POST['CardHolderPan'] ?? '';
$finalAmount     = isset($_POST['FinalAmount']) ? (int)$_POST['FinalAmount'] : 0; // همیشه «ریال»

if ($debugMode) {
    logTransaction($gatewayModuleName, [
        'action' => 'callback_received',
        'RefId' => $refId,
        'ResCode' => $resCode,
        'SaleOrderId' => $saleOrderId,
        'SaleReferenceId' => $saleReferenceId,
        'CardHolderInfo' => $cardHolderInfo,
        'CardHolderPan' => $cardHolderPan,
        'FinalAmountRial' => $finalAmount,
        'unit' => $unit,
    ], 'Callback Received');
}

// یافتن رکورد تراکنش
$tx = null;
if ($refId) {
    $tx = Capsule::table('mod_behpardakht_transactions')->where('ref_id', $refId)->first();
}
if (!$tx && $saleOrderId) {
    $tx = Capsule::table('mod_behpardakht_transactions')->where('order_id', (int)$saleOrderId)->first();
}
if (!$tx) {
    if ($debugMode) {
        logTransaction($gatewayModuleName, ['action'=>'callback_error','msg'=>'Transaction not found','RefId'=>$refId,'SaleOrderId'=>$saleOrderId], 'Transaction Not Found');
    }
    header('Location: ' . $gatewayParams['systemurl'] . 'clientarea.php?action=invoices&status=unpaid&error=' . urlencode('تراکنش یافت نشد'));
    exit;
}
$invoiceId = (int)$tx->invoice_id;

// باید SaleReferenceId داشته باشیم
if ($resCode === '0' && empty($saleReferenceId)) {
    if ($debugMode) {
        logTransaction($gatewayModuleName, ['action'=>'callback_error','msg'=>'Missing SaleReferenceId','RefId'=>$refId], 'Missing Reference');
    }
    header('Location: ' . $gatewayParams['systemurl'] . 'viewinvoice.php?id=' . $invoiceId . '&paymentfailed=true&failurereason=' . urlencode('کد مرجع بانکی نامعتبر است'));
    exit;
}

// WSDL
$apiUrl = $testMode
    ? 'https://sandbox.banktest.ir/mellat/bpm.shaparak.ir/pgwchannel/services/pgw?wsdl'
    : 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';

// کاربر پرداخت را تأیید کرده
if ($resCode === '0') {
    checkCbInvoiceID($invoiceId, $gatewayModuleName);
    checkCbTransID($saleReferenceId);

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
                    // 'cafile' => '/etc/ssl/certs/ca-certificates.crt',
                ]
            ])
        ]);

        // Verify
        $verifyParams = [
            'terminalId'      => $gatewayParams['terminalId'],
            'userName'        => $gatewayParams['userName'],
            'userPassword'    => $gatewayParams['userPassword'],
            'orderId'         => (int)$tx->order_id,
            'saleOrderId'     => (int)$tx->order_id,
            'saleReferenceId' => $saleReferenceId
        ];
        if ($debugMode) {
            logTransaction($gatewayModuleName, ['action'=>'verify_request','params'=>$verifyParams], 'Verify Request');
        }
        $vr = $soap->bpVerifyRequest($verifyParams);
        $vcode = $vr->return ?? null;

        if ($vcode === '0' || $vcode === '43') { // 43: قبلاً Verify شده
            // Settle با retry
            $settleParams = [
                'terminalId'      => $gatewayParams['terminalId'],
                'userName'        => $gatewayParams['userName'],
                'userPassword'    => $gatewayParams['userPassword'],
                'orderId'         => (int)$tx->order_id,
                'saleOrderId'     => (int)$tx->order_id,
                'saleReferenceId' => $saleReferenceId
            ];
            [$settled, $scode] = behpardakht_attemptSettleWithRetry($soap, $settleParams, $debugMode);

            if ($settled) {
                // به‌روزرسانی رکورد
                Capsule::table('mod_behpardakht_transactions')
                    ->where('ref_id', $refId)
                    ->update([
                        'status' => 'completed',
                        'sale_reference_id' => $saleReferenceId,
                        'card_holder_pan' => $cardHolderPan,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                // FinalAmount بانک «ریال» است → تبدیل به واحد سایت
                $amountInSiteUnit = $finalAmount
                    ? ($unit === 'toman' ? $finalAmount / 10 : $finalAmount)
                    : ($unit === 'toman' ? (int)round(((int)$tx->amount_rial) / 10) : (int)$tx->amount_rial);

                // ثبت پرداخت
                addInvoicePayment(
                    $invoiceId,
                    $saleReferenceId,
                    $amountInSiteUnit,
                    0,
                    $gatewayModuleName
                );

                if ($debugMode) {
                    logTransaction($gatewayModuleName, [
                        'action'=>'payment_success',
                        'RefId'=>$refId,
                        'SaleReferenceId'=>$saleReferenceId,
                        'FinalAmountRial'=>$finalAmount,
                        'RecordedAmount'=>$amountInSiteUnit,
                        'unit'=>$unit
                    ], 'Success');
                }

                header('Location: ' . $gatewayParams['systemurl'] . 'viewinvoice.php?id=' . $invoiceId . '&paymentsuccess=true');
                exit;
            } else {
                // خطای settle
                behpardakht_handleErrorAndMaybeReverse(
                    $apiUrl, $gatewayParams, $invoiceId, $saleReferenceId, (int)$tx->order_id, $scode, $debugMode
                );
            }
        } else {
            // Verify ناموفق
            behpardakht_handleErrorAndMaybeReverse(
                $apiUrl, $gatewayParams, $invoiceId, $saleReferenceId, (int)$tx->order_id, $vcode, $debugMode
            );
        }

    } catch (Exception $e) {
        if ($debugMode) {
            logTransaction($gatewayModuleName, ['action'=>'callback_exception','error'=>$e->getMessage()], 'Exception');
        }
        header('Location: ' . $gatewayParams['systemurl'] . 'viewinvoice.php?id=' . $invoiceId . '&paymentfailed=true&failurereason=' . urlencode('خطای سیستمی'));
        exit;
    }

} else {
    // لغو/خطای کاربر
    $msg = behpardakht_getErrorMessage($resCode);
    if ($refId) {
        Capsule::table('mod_behpardakht_transactions')
            ->where('ref_id', $refId)
            ->update([
                'status' => 'failed',
                'error_code' => $resCode,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
    header('Location: ' . $gatewayParams['systemurl'] . 'viewinvoice.php?id=' . $invoiceId . '&paymentfailed=true&failurereason=' . urlencode($msg));
    exit;
}

/**
 * Settle با retry و backoff: خروجی [bool $settled, string|null $lastCode]
 */
function behpardakht_attemptSettleWithRetry(SoapClient $soap, array $settleParams, bool $debugMode, int $maxAttempts = 3): array
{
    $gatewayModuleName = 'behpardakht';

    $retryable = ['34','46','61','421','31','32'];
    $success   = ['0','45']; // 45 = قبلاً settle شده

    $attempt = 0;
    $lastCode = null;

    while ($attempt < $maxAttempts) {
        $attempt++;
        if ($debugMode) {
            logTransaction($gatewayModuleName, ['action'=>'settle_attempt','attempt'=>$attempt,'params'=>$settleParams], 'Settle Attempt');
        }

        try {
            $sr = $soap->bpSettleRequest($settleParams);
            $code = $sr->return ?? null;
            $lastCode = $code;

            if (in_array($code, $success, true)) {
                if ($debugMode) {
                    logTransaction($gatewayModuleName, ['action'=>'settle_response','attempt'=>$attempt,'code'=>$code], 'Settle OK');
                }
                return [true, $code];
            }

            if (!in_array($code, $retryable, true)) {
                if ($debugMode) {
                    logTransaction($gatewayModuleName, ['action'=>'settle_response','attempt'=>$attempt,'code'=>$code], 'Settle Non-Retryable');
                }
                return [false, $code];
            }

            $sleep = 1 << ($attempt - 1); // 1,2,4
            if ($debugMode) {
                logTransaction($gatewayModuleName, ['action'=>'settle_retry_wait','seconds'=>$sleep], 'Settle Retry Wait');
            }
            sleep($sleep);
        } catch (Exception $e) {
            if ($debugMode) {
                logTransaction($gatewayModuleName, ['action'=>'settle_exception','attempt'=>$attempt,'error'=>$e->getMessage()], 'Settle Exception');
            }
            if ($attempt >= $maxAttempts) {
                return [false, $lastCode];
            }
            sleep(1 << ($attempt - 1));
        }
    }
    return [false, $lastCode];
}

/**
 * مدیریت خطا + تلاش برای Reverse (در صورت وجود SaleReferenceId)
 */
function behpardakht_handleErrorAndMaybeReverse(
    string $apiUrl,
    array $gatewayParams,
    int $invoiceId,
    string $saleReferenceId,
    int $orderId,
    ?string $errCode,
    bool $debug
): void {
    $gatewayModuleName = 'behpardakht';
    $msg = behpardakht_getErrorMessage((string)$errCode);

    if ($debug) {
        logTransaction($gatewayModuleName, ['action'=>'payment_error','code'=>$errCode,'msg'=>$msg], 'Payment Error');
    }

    if ($saleReferenceId) {
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
                        // 'cafile' => '/etc/ssl/certs/ca-certificates.crt',
                    ]
                ])
            ]);
            $revParams = [
                'terminalId'      => $gatewayParams['terminalId'],
                'userName'        => $gatewayParams['userName'],
                'userPassword'    => $gatewayParams['userPassword'],
                'orderId'         => $orderId,
                'saleOrderId'     => $orderId,
                'saleReferenceId' => $saleReferenceId
            ];
            $rr = $soap->bpReversalRequest($revParams);
            if ($debug) {
                logTransaction($gatewayModuleName, ['action'=>'reverse_request','result'=>$rr->return ?? null], 'Reverse Request');
            }
        } catch (Exception $e) {
            if ($debug) {
                logTransaction($gatewayModuleName, ['action'=>'reverse_exception','error'=>$e->getMessage()], 'Reverse Exception');
            }
        }
    }

    header('Location: ' . $gatewayParams['systemurl'] . 'viewinvoice.php?id=' . $invoiceId . '&paymentfailed=true&failurereason=' . urlencode($msg));
    exit;
}
