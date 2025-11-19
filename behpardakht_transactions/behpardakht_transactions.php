<?php
/**
 * افزونه مدیریت تراکنش‌های بهپرداخت ملت
 * سازگار با WHMCS 8.11
 * 
 * مسیر فایل: /modules/addons/behpardakht_transactions/behpardakht_transactions.php
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

/**
 * تابع دریافت پیام خطا بر اساس کد
 */
function behpardakht_get_error_message($errorCode) {
    $errors = [
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
        '22' => 'خطای امنیتی رخ داده است',
        '23' => 'مشکل در پایگاه داده',
        '24' => 'اطلاعات کاربری پذیرنده نادرست است',
        '25' => 'مبلغ نامعتبر است',
        '31' => 'پاسخ نامعتبر است',
        '32' => 'فرمت اطلاعات وارد شده صحیح نیست',
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
        '51' => 'تراکنش تکراری است',
        '54' => 'تراکنش مرجع موجود نیست',
        '55' => 'تراکنش نامعتبر است',
        '61' => 'خطا در واریز',
        '111' => 'صادر کننده کارت نامعتبر است',
        '112' => 'خطای سوییچ صادر کننده کارت',
        '113' => 'پاسخی از صادر کننده کارت دریافت نشد',
        '114' => 'دارنده کارت مجاز به انجام این تراکنش نیست',
        '415' => 'زمان جلسه کاری به پایان رسیده است',
        '416' => 'خطا در ثبت اطلاعات',
        '417' => 'شناسه پرداخت کننده نامعتبر است',
        '418' => 'اشکال در تعریف اطلاعات مشتری',
        '419' => 'تعداد دفعات ورود اطلاعات از حد مجاز گذشته است',
        '421' => 'IP نامعتبر است',
    ];
    
    return isset($errors[$errorCode]) ? $errors[$errorCode] : 'خطای نامشخص';
}

/**
 * پیکربندی ماژول
 */
function behpardakht_transactions_config() {
    return [
        "name" => "مدیریت تراکنش‌های بهپرداخت ملت",
        "description" => "نمایش و مدیریت تراکنش‌های درگاه پرداخت ملت",
        "version" => "1.0",
        "author" => "Behpardakht Manager",
        "language" => "farsi",
        "fields" => [
            "records_per_page" => [
                "FriendlyName" => "تعداد رکورد در هر صفحه",
                "Type" => "text",
                "Size" => "5",
                "Default" => "25",
                "Description" => "تعداد تراکنش‌ها در هر صفحه",
            ],
            "enable_export" => [
                "FriendlyName" => "فعالسازی خروجی اکسل",
                "Type" => "yesno",
                "Description" => "امکان دریافت خروجی اکسل از تراکنش‌ها",
            ],
        ]
    ];
}

/**
 * فعالسازی ماژول
 */
function behpardakht_transactions_activate() {
    return [
        'status' => 'success', 
        'description' => 'ماژول مدیریت تراکنش‌های بهپرداخت با موفقیت فعال شد'
    ];
}

/**
 * غیرفعالسازی ماژول
 */
function behpardakht_transactions_deactivate() {
    return [
        'status' => 'success', 
        'description' => 'ماژول مدیریت تراکنش‌های بهپرداخت غیرفعال شد'
    ];
}

/**
 * خروجی صفحه اصلی ماژول
 */
function behpardakht_transactions_output($vars) {
    
    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
    $_LANG = $vars['_lang'];
    
    // بررسی درخواست خروجی اکسل
    if (isset($_GET['action']) && $_GET['action'] == 'export' && $vars['enable_export']) {
        behpardakht_export_excel();
        exit;
    }
    
    // دریافت پارامترها
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = (int)($vars['records_per_page'] ?: 25);
    $offset = ($page - 1) * $limit;
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $order = isset($_GET['order']) && $_GET['order'] == 'asc' ? 'asc' : 'desc';
    
    // ساخت کوئری اصلی
    $query = Capsule::table('mod_behpardakht_transactions');
    
    // اعمال فیلترها
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('invoice_id', 'LIKE', "%{$search}%")
              ->orWhere('order_id', 'LIKE', "%{$search}%")
              ->orWhere('ref_id', 'LIKE', "%{$search}%")
              ->orWhere('sale_reference_id', 'LIKE', "%{$search}%")
              ->orWhere('card_holder_pan', 'LIKE', "%{$search}%");
        });
    }
    
    if ($status_filter && in_array($status_filter, ['pending', 'completed', 'failed'])) {
        $query->where('status', $status_filter);
    }
    
    if ($date_from) {
        $query->where('created_at', '>=', $date_from . ' 00:00:00');
    }
    
    if ($date_to) {
        $query->where('created_at', '<=', $date_to . ' 23:59:59');
    }
    
    // شمارش کل رکوردها
    $total = $query->count();
    $totalPages = ceil($total / $limit);
    
    // دریافت رکوردها با مرتب‌سازی
    $validSorts = ['id', 'invoice_id', 'order_id', 'amount_rial', 'status', 'created_at'];
    if (!in_array($sort, $validSorts)) {
        $sort = 'created_at';
    }
    
    $transactions = $query->orderBy($sort, $order)
                          ->offset($offset)
                          ->limit($limit)
                          ->get();
    
    // پردازش تراکنش‌ها و افزودن اطلاعات اضافی
    foreach ($transactions as &$transaction) {
        // اضافه کردن پیام خطا
        if ($transaction->error_code) {
            $transaction->error_message = behpardakht_get_error_message($transaction->error_code);
        }
        
        // دریافت اطلاعات مشتری از فاکتور
        $invoice = Capsule::table('tblinvoices')
                          ->select('userid')
                          ->where('id', $transaction->invoice_id)
                          ->first();
        
        if ($invoice) {
            $client = Capsule::table('tblclients')
                             ->select(Capsule::raw("CONCAT(firstname, ' ', lastname) as fullname"))
                             ->where('id', $invoice->userid)
                             ->first();
            
            $transaction->client_name = $client ? $client->fullname : 'نامشخص';
            $transaction->client_id = $invoice->userid;
        } else {
            $transaction->client_name = 'نامشخص';
            $transaction->client_id = 0;
        }
    }
    
    // آماده‌سازی داده‌ها برای قالب
    $templateVars = [
        'modulelink' => $modulelink,
        'transactions' => $transactions,
        'total' => $total,
        'page' => $page,
        'totalPages' => $totalPages,
        'limit' => $limit,
        'search' => htmlspecialchars($search),
        'status_filter' => htmlspecialchars($status_filter),
        'date_from' => htmlspecialchars($date_from),
        'date_to' => htmlspecialchars($date_to),
        'sort' => $sort,
        'order' => $order,
        'enable_export' => $vars['enable_export'],
    ];
    
    // بارگذاری استایل CSS با مسیر صحیح از root
    // روش 1: استفاده از SystemURL
    $systemURL = \WHMCS\Config\Setting::getValue('SystemURL');
    if (!$systemURL) {
        // روش 2: استفاده از configuration
        global $CONFIG;
        $systemURL = $CONFIG['SystemURL'];
    }
    
    // حذف slash اضافی از انتهای URL
    $systemURL = rtrim($systemURL, '/');
    
    // ساخت مسیر کامل CSS
    $cssPath = $systemURL . '/modules/addons/behpardakht_transactions/css/style.css';
    
    // روش 3: اگر هیچکدام کار نکرد، از مسیر نسبی به بالا استفاده کن
    if (!$systemURL) {
        $cssPath = '../modules/addons/behpardakht_transactions/css/style.css';
    }
    
    echo '<link rel="stylesheet" href="' . $cssPath . '?v=' . time() . '">';
    
    // استفاده از Smarty v3 برای WHMCS 8.11
    $smarty = new Smarty();
    $smarty->setTemplateDir(dirname(__FILE__) . '/templates');
    $smarty->setCompileDir($GLOBALS['templates_compiledir']);
    $smarty->setCacheDir($GLOBALS['templates_compiledir']);
    
    // پاس دادن متغیرها به Smarty
    foreach ($templateVars as $key => $value) {
        $smarty->assign($key, $value);
    }
    
    $smarty->display('admin.tpl');
}

/**
 * تابع خروجی اکسل
 */
function behpardakht_export_excel() {
    
    // دریافت فیلترها
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
    
    // ساخت کوئری
    $query = Capsule::table('mod_behpardakht_transactions');
    
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('invoice_id', 'LIKE', "%{$search}%")
              ->orWhere('order_id', 'LIKE', "%{$search}%")
              ->orWhere('ref_id', 'LIKE', "%{$search}%")
              ->orWhere('sale_reference_id', 'LIKE', "%{$search}%");
        });
    }
    
    if ($status_filter) {
        $query->where('status', $status_filter);
    }
    
    if ($date_from) {
        $query->where('created_at', '>=', $date_from . ' 00:00:00');
    }
    
    if ($date_to) {
        $query->where('created_at', '<=', $date_to . ' 23:59:59');
    }
    
    $transactions = $query->orderBy('created_at', 'desc')->get();
    
    // اضافه کردن نام مشتری به هر تراکنش
    foreach ($transactions as &$transaction) {
        $invoice = Capsule::table('tblinvoices')
                          ->select('userid')
                          ->where('id', $transaction->invoice_id)
                          ->first();
        
        if ($invoice) {
            $client = Capsule::table('tblclients')
                             ->select(Capsule::raw("CONCAT(firstname, ' ', lastname) as fullname"))
                             ->where('id', $invoice->userid)
                             ->first();
            
            $transaction->client_name = $client ? $client->fullname : 'نامشخص';
        } else {
            $transaction->client_name = 'نامشخص';
        }
    }
    
    // تنظیم هدرها برای دانلود فایل
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="behpardakht_transactions_' . date('Y-m-d_H-i-s') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // شروع خروجی
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    
    // خروجی HTML برای اکسل
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            table { border-collapse: collapse; direction: rtl; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .number { mso-number-format: "0"; }
        </style>
    </head>
    <body>
        <table>
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>نام مشتری</th>
                    <th>شماره فاکتور</th>
                    <th>شماره سفارش</th>
                    <th>شماره تراکنش</th>
                    <th>مبلغ (ریال)</th>
                    <th>شماره کارت</th>
                    <th>وضعیت</th>
                    <th>کد خطا</th>
                    <th>پیام خطا</th>
                    <th>تاریخ ایجاد</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($transactions as $t) {
        $status = [
            'completed' => 'موفق',
            'pending' => 'در انتظار',
            'failed' => 'ناموفق'
        ][$t->status];
        
        $errorMessage = $t->error_code ? behpardakht_get_error_message($t->error_code) : '';
        
        echo '<tr>
            <td class="number">' . $t->id . '</td>
            <td>' . htmlspecialchars($t->client_name) . '</td>
            <td class="number">' . $t->invoice_id . '</td>
            <td class="number">' . $t->order_id . '</td>
            <td>' . htmlspecialchars($t->sale_reference_id ?: '-') . '</td>
            <td class="number">' . number_format($t->amount_rial, 0, '', '') . '</td>
            <td>' . htmlspecialchars($t->card_holder_pan ?: '-') . '</td>
            <td>' . $status . '</td>
            <td>' . htmlspecialchars($t->error_code ?: '-') . '</td>
            <td>' . htmlspecialchars($errorMessage) . '</td>
            <td>' . $t->created_at . '</td>
        </tr>';
    }
    
    echo '</tbody></table></body></html>';
}

/**
 * Sidebar Output
 */
function behpardakht_transactions_sidebar($vars) {
    $sidebarHtml = '<div class="sidebar-header">
        <i class="fas fa-credit-card"></i> راهنمای سریع
    </div>
    <ul class="menu">
        <li><i class="fas fa-check-circle text-success"></i> تراکنش‌های موفق: تراکنش‌های پرداخت شده</li>
        <li><i class="fas fa-clock text-warning"></i> در انتظار: منتظر تکمیل پرداخت</li>
        <li><i class="fas fa-times-circle text-danger"></i> ناموفق: تراکنش‌های لغو شده</li>
    </ul>
    <hr>
    <div class="text-center">
        <small>نسخه 1.0</small>
    </div>';
    
    return $sidebarHtml;
}