{*
    قالب مدیریت تراکنش‌های به‌پرداخت
    سازگار با WHMCS 8.11+ - طراحی مینیمال و حرفه‌ای
*}

<div class="behpardakht-container">

    {* سربرگ صفحه *}
    <div class="bp-header">
        <div class="bp-header-content">
            <div class="bp-title-section">
                <h1 class="bp-page-title">
                    <i class="fas fa-credit-card"></i>
                    تراکنش‌های درگاه به‌پرداخت ملت
                </h1>
                <p class="bp-subtitle">مشاهده و مدیریت تمام تراکنش‌های پرداخت</p>
            </div>
            <div class="bp-stats">
                <div class="bp-stat-item">
                    <span class="bp-stat-label">مجموع تراکنش‌ها</span>
                    <span class="bp-stat-value">{$total|number_format}</span>
                </div>
            </div>
        </div>
    </div>

    {* بخش فیلترها *}
    <div class="bp-filters-section">
        <div class="bp-titlebar">
            <span class="bp-title"><i class="fas fa-filter"></i> فیلترهای جستجو</span>
        </div>

        <form method="GET" action="{$modulelink}" class="bp-filter-form">
            <div class="row">

                {* جستجو *}
                <div class="col-md-4 col-sm-6">
                    <div class="form-group">
                        <label><i class="fas fa-search"></i> جستجو</label>
                        <input type="text" name="search" class="form-control"
                               value="{$search}"
                               placeholder="شماره فاکتور، سفارش، تراکنش یا کارت...">
                    </div>
                </div>

                {* فیلتر وضعیت *}
                <div class="col-md-2 col-sm-6">
                    <div class="form-group">
                        <label><i class="fas fa-tasks"></i> وضعیت</label>
                        <select name="status" class="form-control">
                            <option value="">همه</option>
                            <option value="completed" {if $status_filter eq 'completed'}selected{/if}>موفق</option>
                            <option value="pending" {if $status_filter eq 'pending'}selected{/if}>در انتظار</option>
                            <option value="failed" {if $status_filter eq 'failed'}selected{/if}>ناموفق</option>
                        </select>
                    </div>
                </div>

                {* از تاریخ *}
                <div class="col-md-2 col-sm-6">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> از تاریخ</label>
                        <input type="date" name="date_from" class="form-control" value="{$date_from}">
                    </div>
                </div>

                {* تا تاریخ *}
                <div class="col-md-2 col-sm-6">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> تا تاریخ</label>
                        <input type="date" name="date_to" class="form-control" value="{$date_to}">
                    </div>
                </div>

                {* دکمه‌ها *}
                <div class="col-md-2 col-sm-12">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="btn-group-justified">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> جستجو
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            {* ردیف دوم: دکمه‌های اضافی *}
            <div class="row" style="margin-top: 10px;">
                <div class="col-md-12">
                    <div class="btn-group-justified">
                        <a href="{$modulelink}" class="btn btn-default">
                            <i class="fas fa-redo"></i> پاک کردن فیلترها
                        </a>
                        {if $enable_export}
                            <a class="btn btn-success"
                               href="{$modulelink}&action=export&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                <i class="fas fa-file-excel"></i> خروجی اکسل
                            </a>
                        {/if}
                    </div>
                </div>
            </div>
        </form>
    </div>

    {* بخش جدول *}
    <div class="bp-table-section">
        <div class="table-responsive">
            <table class="bp-table">
                <thead>
                    <tr>
                        <th style="width:70px;">
                            <a href="{$modulelink}&sort=id&order={if $sort eq 'id' && $order eq 'desc'}asc{else}desc{/if}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                شناسه {if $sort eq 'id'}<i class="fas fa-sort-{if $order eq 'asc'}up{else}down{/if}"></i>{/if}
                            </a>
                        </th>
                        <th>نام مشتری</th>
                        <th>
                            <a href="{$modulelink}&sort=invoice_id&order={if $sort eq 'invoice_id' && $order eq 'desc'}asc{else}desc{/if}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                فاکتور {if $sort eq 'invoice_id'}<i class="fas fa-sort-{if $order eq 'asc'}up{else}down{/if}"></i>{/if}
                            </a>
                        </th>
                        <th>شماره سفارش</th>
                        <th>شماره تراکنش</th>
                        <th>
                            <a href="{$modulelink}&sort=amount_rial&order={if $sort eq 'amount_rial' && $order eq 'desc'}asc{else}desc{/if}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                مبلغ {if $sort eq 'amount_rial'}<i class="fas fa-sort-{if $order eq 'asc'}up{else}down{/if}"></i>{/if}
                            </a>
                        </th>
                        <th>
                            <a href="{$modulelink}&sort=status&order={if $sort eq 'status' && $order eq 'desc'}asc{else}desc{/if}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                وضعیت {if $sort eq 'status'}<i class="fas fa-sort-{if $order eq 'asc'}up{else}down{/if}"></i>{/if}
                            </a>
                        </th>
                        <th>
                            <a href="{$modulelink}&sort=created_at&order={if $sort eq 'created_at' && $order eq 'desc'}asc{else}desc{/if}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                تاریخ {if $sort eq 'created_at'}<i class="fas fa-sort-{if $order eq 'asc'}up{else}down{/if}"></i>{/if}
                            </a>
                        </th>
                        <th style="width:100px; text-align:center;">عملیات</th>
                    </tr>
                </thead>

                <tbody>
                    {if $transactions && count($transactions) > 0}
                        {foreach from=$transactions item=transaction}
                            <tr class="{if $transaction->status eq 'completed'}row-success{elseif $transaction->status eq 'failed'}row-danger{/if}">

                                {* شناسه *}
                                <td>
                                    <span class="bp-id">#{$transaction->id}</span>
                                </td>

                                {* نام مشتری *}
                                <td>
                                    {if $transaction->client_id}
                                        <a href="clientssummary.php?userid={$transaction->client_id}"
                                           target="_blank"
                                           class="bp-client-link">
                                            <i class="fas fa-user"></i>
                                            {$transaction->client_name|escape:'html'}
                                        </a>
                                    {else}
                                        <span class="text-muted">{$transaction->client_name|escape:'html'}</span>
                                    {/if}
                                </td>

                                {* شماره فاکتور *}
                                <td>
                                    <a class="bp-invoice-link"
                                       href="invoices.php?action=edit&id={$transaction->invoice_id}"
                                       target="_blank">
                                        #{$transaction->invoice_id}
                                    </a>
                                </td>

                                {* شماره سفارش *}
                                <td>
                                    <span class="bp-code">{$transaction->order_id|escape:'html'}</span>
                                </td>

                                {* شماره تراکنش *}
                                <td>
                                    {if $transaction->ref_id}
                                        <span class="bp-transaction-id"
                                              data-toggle="tooltip"
                                              title="کلیک برای کپی"
                                              onclick="copyToClipboard('{$transaction->ref_id}', this)">
                                            <i class="fas fa-copy"></i>
                                            {$transaction->ref_id|truncate:15:"...":true}
                                        </span>
                                    {else}
                                        <span class="text-muted">-</span>
                                    {/if}
                                </td>

                                {* مبلغ *}
                                <td>
                                    <span class="bp-amount">
                                        {($transaction->amount_rial/10)|number_format:0:",":"."}
                                    </span>
                                    <small class="text-muted"> تومان</small>
                                </td>

                                {* وضعیت *}
                                <td>
                                    {if $transaction->status eq 'completed'}
                                        <span class="bp-status bp-status-success">موفق</span>
                                    {elseif $transaction->status eq 'pending'}
                                        <span class="bp-status bp-status-pending">در انتظار</span>
                                    {else}
                                        <span class="bp-status bp-status-failed">ناموفق</span>
                                    {/if}
                                </td>

                                {* تاریخ *}
                                <td>
                                    <div class="bp-date">
                                        <div>{$transaction->created_at|date_format:"%Y/%m/%d"}</div>
                                        <small class="text-muted">{$transaction->created_at|date_format:"%H:%M"}</small>
                                    </div>
                                </td>

                                {* عملیات *}
                                <td style="text-align:center;">
                                    <a class="bp-action-btn"
                                       href="invoices.php?action=edit&id={$transaction->invoice_id}"
                                       target="_blank"
                                       data-toggle="tooltip"
                                       title="مشاهده فاکتور">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        {/foreach}
                    {else}
                        <tr>
                            <td colspan="9">
                                <div class="bp-no-data">
                                    <i class="fas fa-inbox"></i>
                                    <p>هیچ تراکنشی یافت نشد</p>
                                </div>
                            </td>
                        </tr>
                    {/if}
                </tbody>
            </table>
        </div>

        {* صفحه‌بندی *}
        {if $totalPages > 1}
            <div class="bp-pagination">
                <ul class="pagination">
                    {* صفحه قبل *}
                    <li class="{if $page <= 1}disabled{/if}">
                        <a href="{if $page > 1}{$modulelink}&page={$page-1}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}{else}javascript:void(0){/if}">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>

                    {assign var="startPage" value=max(1, $page-2)}
                    {assign var="endPage" value=min($totalPages, $page+2)}

                    {* صفحه اول *}
                    {if $startPage > 1}
                        <li>
                            <a href="{$modulelink}&page=1&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">1</a>
                        </li>
                        {if $startPage > 2}
                            <li class="disabled"><a>...</a></li>
                        {/if}
                    {/if}

                    {* صفحات میانی *}
                    {for $i=$startPage to $endPage}
                        <li class="{if $i == $page}active{/if}">
                            <a href="{$modulelink}&page={$i}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">{$i}</a>
                        </li>
                    {/for}

                    {* صفحه آخر *}
                    {if $endPage < $totalPages}
                        {if $endPage < $totalPages - 1}
                            <li class="disabled"><a>...</a></li>
                        {/if}
                        <li>
                            <a href="{$modulelink}&page={$totalPages}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">{$totalPages}</a>
                        </li>
                    {/if}

                    {* صفحه بعد *}
                    <li class="{if $page >= $totalPages}disabled{/if}">
                        <a href="{if $page < $totalPages}{$modulelink}&page={$page+1}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}{else}javascript:void(0){/if}">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                </ul>

                <div class="bp-pagination-info">
                    نمایش <strong>{($page-1)*$limit+1}</strong>
                    تا <strong>{min($page*$limit, $total)}</strong>
                    از <strong>{$total|number_format}</strong> تراکنش
                </div>
            </div>
        {/if}
    </div>

</div>

{literal}
<script>
jQuery(function($) {
    // فعال‌سازی tooltip
    $('[data-toggle="tooltip"]').tooltip();

    // تابع کپی به کلیپ‌بورد
    window.copyToClipboard = function(text, element) {
        var $el = $(element);

        // تابع بازخورد بصری
        function showFeedback() {
            var $icon = $el.find('i');
            var originalClass = $icon.attr('class');
            $icon.removeClass('fa-copy').addClass('fa-check');
            $el.css('background', 'linear-gradient(135deg, #16a34a, #22c55e)');

            setTimeout(function() {
                $icon.attr('class', originalClass);
                $el.css('background', '');
            }, 1500);
        }

        // استفاده از Clipboard API مدرن
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text)
                .then(showFeedback)
                .catch(function() {
                    fallbackCopy(text);
                    showFeedback();
                });
        } else {
            fallbackCopy(text);
            showFeedback();
        }
    };

    // روش جایگزین برای کپی (برای مرورگرهای قدیمی)
    function fallbackCopy(text) {
        var $temp = $('<textarea style="position:absolute;left:-9999px;">');
        $('body').append($temp);
        $temp.val(text).select();
        try {
            document.execCommand('copy');
        } catch(err) {
            console.error('خطا در کپی:', err);
        }
        $temp.remove();
    }
});
</script>
{/literal}