<?php
declare(strict_types=1);

if (!function_exists('behpardakht_getErrorMessage')) {
    function behpardakht_getErrorMessage(string $errorCode): string
    {
        $errors = [
            '0' => 'تراکنش با موفقیت انجام شد',
            '11' => 'شماره کارت نامعتبر است',
            '12' => 'موجودی کافی نیست',
            '13' => 'رمز نادرست است',
            '14' => 'تعداد دفعات وارد کردن رمز بیش از حد مجاز است',
            '15' => 'کارت نامعتبر است',
            '16' => 'دفعات برداشت وجه بیش از حد مجاز است',
            '17' => 'کاربر از انجام تراکنش منصرف شده است',
            '18' => 'تاریخ انقضای کارت گذشته است',
            '19' => 'مبلغ برداشت وجه بیش از حد مجاز است',
            '21' => 'پذیرنده نامعتبر است',
            '23' => 'خطای امنیتی رخ داده است',
            '24' => 'اطلاعات کاربری پذیرنده نامعتبر است',
            '25' => 'مبلغ نامعتبر است',
            '31' => 'پاسخ نامعتبر است',
            '32' => 'فرمت اطلاعات وارد شده صحیح نمی‌باشد',
            '33' => 'حساب نامعتبر است',
            '34' => 'خطای سیستمی',
            '35' => 'تاریخ نامعتبر است',
            '41' => 'شماره درخواست تکراری است',
            '42' => 'تراکنش Sale یافت نشد',
            '43' => 'قبلا درخواست Verify داده شده است',
            '44' => 'درخواست Verify یافت نشد',
            '45' => 'تراکنش Settle شده است',
            '46' => 'تراکنش Settle نشده است',
            '47' => 'تراکنش Settle یافت نشد',
            '48' => 'تراکنش Reverse شده است',
            '49' => 'تراکنش Refund یافت نشد',
            '51' => 'تراکنش تکراری است',
            '54' => 'تراکنش مرجع موجود نیست',
            '55' => 'تراکنش نامعتبر است',
            '61' => 'خطا در واریز',
            '111' => 'صادر کننده کارت نامعتبر است',
            '112' => 'خطای سوییچ صادر کننده کارت',
            '113' => 'پاسخی از صادر کننده کارت دریافت نشد',
            '114' => 'دارنده کارت مجاز به انجام این تراکنش نیست',
            '412' => 'شناسه قبض نادرست است',
            '413' => 'شناسه پرداخت نادرست است',
            '414' => 'سازمان صادر کننده قبض نامعتبر است',
            '415' => 'زمان جلسه کاری به پایان رسیده است',
            '416' => 'خطا در ثبت اطلاعات',
            '417' => 'شناسه پرداخت کننده نامعتبر است',
            '418' => 'اشکال در تعریف اطلاعات مشتری',
            '419' => 'تعداد دفعات ورود اطلاعات از حد مجاز گذشته است',
            '421' => 'IP نامعتبر است',
        ];
        return $errors[$errorCode] ?? 'خطای نامشخص از درگاه (کد: ' . $errorCode . ')';
    }
}

if (!function_exists('behpardakht_shouldUseTestEnv')) {
    function behpardakht_shouldUseTestEnv(array $gatewayParams): bool
    {
        $environment = strtolower((string)($gatewayParams['environment'] ?? ''));
        if ($environment === 'test') {
            return true;
        }
        if ($environment === 'production') {
            return false;
        }

        return !empty($gatewayParams['testMode']) && $gatewayParams['testMode'] === 'on';
    }
}

if (!function_exists('behpardakht_getEndpoints')) {
    function behpardakht_getEndpoints(array $gatewayParams): array
    {
        $useTest = behpardakht_shouldUseTestEnv($gatewayParams);

        return [
            'wsdl' => $useTest
                ? 'https://pgw.dev.bpmellat.ir/pgwchannel/services/pgw?wsdl'
                : 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl',
            'startpay_fa' => $useTest
                ? 'https://pgw.dev.bpmellat.ir/pgwchannel/startpay.mellat'
                : 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat',
            'startpay_en' => $useTest
                ? 'https://pgw.dev.bpmellat.ir/pgwchannel/enstartpay.mellat'
                : 'https://bpm.shaparak.ir/pgwchannel/enstartpay.mellat',
        ];
    }
}

if (!function_exists('behpardakht_getCustomFieldValue')) {
    function behpardakht_getCustomFieldValue(int $clientId, int $fieldId): ?string
    {
        if ($clientId <= 0 || $fieldId <= 0) {
            return null;
        }

        $value = \WHMCS\Database\Capsule::table('tblcustomfieldsvalues')
            ->where('fieldid', $fieldId)
            ->where('relid', $clientId)
            ->value('value');

        return is_string($value) ? $value : null;
    }
}

if (!function_exists('behpardakht_normalizeNationalCode')) {
    function behpardakht_normalizeNationalCode(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string)$value);
        if ($digits === '' || strlen($digits) !== 10) {
            return null;
        }

        return $digits;
    }
}

if (!function_exists('behpardakht_normalizeIranMobile')) {
    function behpardakht_normalizeIranMobile(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string)$value);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            return $digits;
        }

        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return '98' . substr($digits, 1);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            return '98' . $digits;
        }

        return null;
    }
}

if (!function_exists('behpardakht_encryptNationalCode')) {
    function behpardakht_encryptNationalCode(string $nationalCode, string $hexKey = '2C7D202B960A96AA'): string
    {
        $nationalCode = trim($nationalCode);
        $keyBin = hex2bin($hexKey);

        if ($keyBin === false || strlen($keyBin) !== 8) {
            throw new \RuntimeException('کلید ENC نامعتبر است (باید 16 کاراکتر هگز، 8 بایت باشد).');
        }

        $ciphertext = openssl_encrypt(
            $nationalCode,
            'DES-ECB',
            $keyBin,
            OPENSSL_RAW_DATA
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('رمزنگاری ENC با DES در این سرور در دسترس نیست یا با خطا مواجه شد.');
        }

        return strtoupper(bin2hex($ciphertext));
    }
}
