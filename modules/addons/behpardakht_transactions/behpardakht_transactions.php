<?php
/**
 * افزونه مدیریت تراکنش‌های بهپرداخت ملت
 * سازگار با WHMCS 8.11+
 * نسخه بهینه شده و ایمن
 *
 * مسیر: /modules/addons/behpardakht_transactions/behpardakht_transactions.php
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

/**
 * پیکربندی ماژول
 */
function behpardakht_transactions_config() {
    return [
        "name" => "مدیریت تراکنش‌های بهپرداخت ملت",
        "description" => "نمایش و مدیریت تراکنش‌های درگاه پرداخت به‌پرداخت ملت",
        "version" => "2.0",
        "author" => "Behpardakht Manager",
        "language" => "farsi",
        "fields" => [
            "records_per_page" => [
                "FriendlyName" => "تعداد رکورد در هر صفحه",
                "Type" => "text",
                "Size" => "5",
                "Default" => "25",
                "Description" => "تعداد تراکنش‌ها در هر صفحه (پیشنهادی: 25)",
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
        'description' => 'ماژول مدیریت تراکنش‌های بهپرداخت با موفقیت فعال شد. این ماژول از جدول ایجاد شده توسط درگاه پرداخت استفاده می‌کند.'
    ];
}

/**
 * غیرفعالسازی ماژول
 */
function behpardakht_transactions_deactivate() {
    return [
        'status' => 'success',
        'description' => 'ماژول مدیریت تراکنش‌های بهپرداخت غیرفعال شد.'
    ];
}

/**
 * تابع کمکی برای دریافت اطلاعات مشتری از فاکتور
 *
 * @param array $invoiceIds آرایه شناسه فاکتورها
 * @return array آرایه اطلاعات مشتریان با کلید invoice_id
 */
function behpardakht_get_clients_info($invoiceIds) {
    if (empty($invoiceIds)) {
        return [];
    }

    $clients = [];

    $results = Capsule::table('tblinvoices')
        ->select('tblinvoices.id as invoice_id', 'tblinvoices.userid',
                 Capsule::raw("CONCAT(tblclients.firstname, ' ', tblclients.lastname) as fullname"))
        ->join('tblclients', 'tblinvoices.userid', '=', 'tblclients.id')
        ->whereIn('tblinvoices.id', $invoiceIds)
        ->get();

    foreach ($results as $row) {
        $clients[$row->invoice_id] = [
            'client_id' => $row->userid,
            'client_name' => $row->fullname
        ];
    }

    return $clients;
}

/**
 * خروجی صفحه اصلی ماژول
 */
function behpardakht_transactions_output($vars) {

    $modulelink = $vars['modulelink'];
    $version = $vars['version'];

    // بررسی درخواست خروجی اکسل
    if (isset($_GET['action']) && $_GET['action'] == 'export' && $vars['enable_export']) {
        behpardakht_export_excel();
        exit;
    }

    // دریافت و اعتبارسنجی پارامترها
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = max(10, min(100, intval($vars['records_per_page'] ?: 25))); // محدوده 10-100
    $offset = ($page - 1) * $limit;

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
    $date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
    $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'created_at';
    $order = isset($_GET['order']) && strtolower($_GET['order']) == 'asc' ? 'asc' : 'desc';

    // لیست ستون‌های مجاز برای مرتب‌سازی
    $validSorts = ['id', 'invoice_id', 'order_id', 'amount_rial', 'status', 'created_at'];
    if (!in_array($sort, $validSorts)) {
        $sort = 'created_at';
    }

    // ساخت کوئری اصلی
    $query = Capsule::table('mod_behpardakht_transactions');

    // اعمال فیلترها با استفاده از Prepared Statements (ایمن در برابر SQL Injection)
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('invoice_id', 'LIKE', '%' . $search . '%')
              ->orWhere('order_id', 'LIKE', '%' . $search . '%')
              ->orWhere('ref_id', 'LIKE', '%' . $search . '%');
        });
    }

    // فیلتر وضعیت (Whitelist approach)
    $allowedStatuses = ['pending', 'completed', 'failed'];
    if ($status_filter && in_array($status_filter, $allowedStatuses)) {
        $query->where('status', $status_filter);
    }

    // فیلتر تاریخ
    if ($date_from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
        $query->where('created_at', '>=', $date_from . ' 00:00:00');
    }

    if ($date_to && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
        $query->where('created_at', '<=', $date_to . ' 23:59:59');
    }

    // شمارش کل رکوردها
    $total = $query->count();
    $totalPages = $total > 0 ? ceil($total / $limit) : 1;

    // دریافت رکوردها با مرتب‌سازی
    $transactions = $query->orderBy($sort, $order)
                          ->offset($offset)
                          ->limit($limit)
                          ->get();

    // دریافت اطلاعات مشتریان به صورت یکجا (بهینه‌تر از query های مجزا)
    $invoiceIds = [];
    foreach ($transactions as $transaction) {
        if ($transaction->invoice_id) {
            $invoiceIds[] = $transaction->invoice_id;
        }
    }

    $clientsInfo = behpardakht_get_clients_info(array_unique($invoiceIds));

    // اضافه کردن اطلاعات مشتری به تراکنش‌ها
    foreach ($transactions as &$transaction) {
        if (isset($clientsInfo[$transaction->invoice_id])) {
            $transaction->client_id = $clientsInfo[$transaction->invoice_id]['client_id'];
            $transaction->client_name = $clientsInfo[$transaction->invoice_id]['client_name'];
        } else {
            $transaction->client_id = 0;
            $transaction->client_name = 'نامشخص';
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
        'search' => htmlspecialchars($search, ENT_QUOTES, 'UTF-8'),
        'status_filter' => htmlspecialchars($status_filter, ENT_QUOTES, 'UTF-8'),
        'date_from' => htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8'),
        'date_to' => htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8'),
        'sort' => $sort,
        'order' => $order,
        'enable_export' => $vars['enable_export'],
    ];

    // بارگذاری استایل CSS
    $systemURL = rtrim(\WHMCS\Config\Setting::getValue('SystemURL'), '/');
    $cssPath = $systemURL . '/modules/addons/behpardakht_transactions/css/style.css';
    echo '<link rel="stylesheet" href="' . htmlspecialchars($cssPath, ENT_QUOTES, 'UTF-8') . '?v=' . $version . '">';

    // استفاده از Smarty
    $smarty = new Smarty();
    $smarty->setTemplateDir(__DIR__ . '/templates');
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

    // دریافت و اعتبارسنجی فیلترها
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
    $date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

    // ساخت کوئری
    $query = Capsule::table('mod_behpardakht_transactions');

    // اعمال فیلترها
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('invoice_id', 'LIKE', '%' . $search . '%')
              ->orWhere('order_id', 'LIKE', '%' . $search . '%')
              ->orWhere('ref_id', 'LIKE', '%' . $search . '%');
        });
    }

    $allowedStatuses = ['pending', 'completed', 'failed'];
    if ($status_filter && in_array($status_filter, $allowedStatuses)) {
        $query->where('status', $status_filter);
    }

    if ($date_from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
        $query->where('created_at', '>=', $date_from . ' 00:00:00');
    }

    if ($date_to && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
        $query->where('created_at', '<=', $date_to . ' 23:59:59');
    }

    $transactions = $query->orderBy('created_at', 'desc')
                          ->limit(10000) // محدودیت برای جلوگیری از مصرف زیاد حافظه
                          ->get();

    // دریافت اطلاعات مشتریان
    $invoiceIds = [];
    foreach ($transactions as $transaction) {
        if ($transaction->invoice_id) {
            $invoiceIds[] = $transaction->invoice_id;
        }
    }

    $clientsInfo = behpardakht_get_clients_info(array_unique($invoiceIds));

    // اضافه کردن نام مشتری به هر تراکنش
    foreach ($transactions as &$transaction) {
        if (isset($clientsInfo[$transaction->invoice_id])) {
            $transaction->client_name = $clientsInfo[$transaction->invoice_id]['client_name'];
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
        table {
            border-collapse: collapse;
            direction: rtl;
            width: 100%;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .number {
            mso-number-format: "0";
        }
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
                <th>مبلغ (تومان)</th>
                <th>وضعیت</th>
                <th>تاریخ ایجاد</th>
            </tr>
        </thead>
        <tbody>';

    $statusLabels = [
        'completed' => 'موفق',
        'pending' => 'در انتظار',
        'failed' => 'ناموفق'
    ];

    foreach ($transactions as $t) {
        $status = isset($statusLabels[$t->status]) ? $statusLabels[$t->status] : $t->status;
        $amountToman = $t->amount_rial / 10;

        echo '<tr>
            <td class="number">' . htmlspecialchars($t->id, ENT_QUOTES, 'UTF-8') . '</td>
            <td>' . htmlspecialchars($t->client_name, ENT_QUOTES, 'UTF-8') . '</td>
            <td class="number">' . htmlspecialchars($t->invoice_id, ENT_QUOTES, 'UTF-8') . '</td>
            <td class="number">' . htmlspecialchars($t->order_id, ENT_QUOTES, 'UTF-8') . '</td>
            <td>' . htmlspecialchars($t->ref_id ?: '-', ENT_QUOTES, 'UTF-8') . '</td>
            <td class="number">' . number_format($t->amount_rial, 0, '', '') . '</td>
            <td class="number">' . number_format($amountToman, 0, '', '') . '</td>
            <td>' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</td>
            <td>' . htmlspecialchars($t->created_at, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>';
    }

    echo '    </tbody>
    </table>
</body>
</html>';
}

/**
 * Sidebar Output
 */
function behpardakht_transactions_sidebar($vars) {
    // دریافت آمار
    try {
        $totalTransactions = Capsule::table('mod_behpardakht_transactions')->count();
        $completedCount = Capsule::table('mod_behpardakht_transactions')->where('status', 'completed')->count();
        $pendingCount = Capsule::table('mod_behpardakht_transactions')->where('status', 'pending')->count();
        $failedCount = Capsule::table('mod_behpardakht_transactions')->where('status', 'failed')->count();

        $totalAmount = Capsule::table('mod_behpardakht_transactions')
            ->where('status', 'completed')
            ->sum('amount_rial');

        $totalAmountToman = $totalAmount ? number_format($totalAmount / 10, 0, '', ',') : '0';

    } catch (Exception $e) {
        $totalTransactions = $completedCount = $pendingCount = $failedCount = 0;
        $totalAmountToman = '0';
    }

    $sidebarHtml = '
    <div class="panel panel-default" style="margin-top: 0;">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="fas fa-chart-bar"></i> آمار تراکنش‌ها
            </h3>
        </div>
        <div class="panel-body">
            <div class="list-group" style="margin-bottom: 0;">
                <div class="list-group-item">
                    <span class="badge badge-default">' . number_format($totalTransactions) . '</span>
                    <i class="fas fa-list"></i> کل تراکنش‌ها
                </div>
                <div class="list-group-item">
                    <span class="badge badge-success">' . number_format($completedCount) . '</span>
                    <i class="fas fa-check-circle"></i> تراکنش‌های موفق
                </div>
                <div class="list-group-item">
                    <span class="badge badge-warning">' . number_format($pendingCount) . '</span>
                    <i class="fas fa-clock"></i> در انتظار
                </div>
                <div class="list-group-item">
                    <span class="badge badge-danger">' . number_format($failedCount) . '</span>
                    <i class="fas fa-times-circle"></i> ناموفق
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="fas fa-money-bill-wave"></i> مجموع درآمد
            </h3>
        </div>
        <div class="panel-body text-center">
            <h3 style="color: #ff6600; margin: 10px 0;">
                <strong>' . $totalAmountToman . '</strong>
                <small style="display: block; font-size: 14px; color: #6b7280; margin-top: 5px;">تومان</small>
            </h3>
            <small class="text-muted">از تراکنش‌های موفق</small>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="fas fa-info-circle"></i> راهنمای سریع
            </h3>
        </div>
        <div class="panel-body">
            <ul class="list-unstyled" style="margin: 0;">
                <li style="margin-bottom: 8px;">
                    <i class="fas fa-search text-primary"></i>
                    <small>جستجو بر اساس فاکتور، سفارش یا تراکنش</small>
                </li>
                <li style="margin-bottom: 8px;">
                    <i class="fas fa-filter text-primary"></i>
                    <small>فیلتر بر اساس وضعیت و تاریخ</small>
                </li>
                <li style="margin-bottom: 8px;">
                    <i class="fas fa-file-excel text-success"></i>
                    <small>دریافت خروجی اکسل از تراکنش‌ها</small>
                </li>
                <li>
                    <i class="fas fa-sort text-primary"></i>
                    <small>مرتب‌سازی بر اساس ستون‌های جدول</small>
                </li>
            </ul>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-body text-center">
            <small class="text-muted">
                نسخه 2.0 | بهپرداخت ملت
            </small>
        </div>
    </div>';

    return $sidebarHtml;
}