{*
    قالب مدیریت تراکنش‌های بهپرداخت
    طراحی مینیمال - Google Material Design Style
*}

<div class="behpardakht-container">

    {* Header *}
    <div class="bp-header">
        <h1 class="bp-page-title">تراکنش‌های بهپرداخت ملت</h1>
        <p class="bp-subtitle">مشاهده و مدیریت تراکنش‌های پرداخت</p>
    </div>

    {* Filters *}
    <div class="bp-filters-section">
        <form method="GET" action="{$modulelink}" class="bp-filter-form">
            <div class="row">

                <div class="col-md-4 col-sm-6">
                    <div class="form-group">
                        <label>جستجو</label>
                        <input type="text" name="search" class="form-control"
                               value="{$search}"
                               placeholder="شماره فاکتور، سفارش، تراکنش یا کارت">
                    </div>
                </div>

                <div class="col-md-2 col-sm-6">
                    <div class="form-group">
                        <label>وضعیت</label>
                        <select name="status" class="form-control">
                            <option value="">همه</option>
                            <option value="completed" {if $status_filter eq 'completed'}selected{/if}>موفق</option>
                            <option value="pending" {if $status_filter eq 'pending'}selected{/if}>در انتظار</option>
                            <option value="failed" {if $status_filter eq 'failed'}selected{/if}>ناموفق</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2 col-sm-6">
                    <div class="form-group">
                        <label>از تاریخ</label>
                        <input type="date" name="date_from" class="form-control" value="{$date_from}">
                    </div>
                </div>

                <div class="col-md-2 col-sm-6">
                    <div class="form-group">
                        <label>تا تاریخ</label>
                        <input type="date" name="date_to" class="form-control" value="{$date_to}">
                    </div>
                </div>

                <div class="col-md-2 col-sm-12">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary" style="width:100%">
                            جستجو
                        </button>
                    </div>
                </div>

            </div>

            <div class="row" style="margin-top: 8px;">
                <div class="col-md-12">
                    <div class="btn-group-justified">
                        <a href="{$modulelink}" class="btn btn-default">
                            پاک کردن فیلترها
                        </a>
                        {if $enable_export}
                            <a class="btn btn-success"
                               href="{$modulelink}&action=export&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                خروجی اکسل
                            </a>
                        {/if}
                    </div>
                </div>
            </div>
        </form>
    </div>

    {* Table *}
    <div class="bp-table-section">
        <div class="table-responsive">
            <table class="bp-table">
                <thead>
                    <tr>
                        <th style="width:60px;">
                            <a href="{$modulelink}&sort=id&order={if $sort eq 'id' && $order eq 'desc'}asc{else}desc{/if}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                شناسه {if $sort eq 'id'}<i class="fas fa-sort-{if $order eq 'asc'}up{else}down{/if}"></i>{/if}
                            </a>
                        </th>
                        <th>مشتری</th>
                        <th>
                            <a href="{$modulelink}&sort=invoice_id&order={if $sort eq 'invoice_id' && $order eq 'desc'}asc{else}desc{/if}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                فاکتور {if $sort eq 'invoice_id'}<i class="fas fa-sort-{if $order eq 'asc'}up{else}down{/if}"></i>{/if}
                            </a>
                        </th>
                        <th>سفارش</th>
                        <th>تراکنش</th>
                        <th>
                            <a href="{$modulelink}&sort=amount_rial&order={if $sort eq 'amount_rial' && $order eq 'desc'}asc{else}desc{/if}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                مبلغ {if $sort eq 'amount_rial'}<i class="fas fa-sort-{if $order eq 'asc'}up{else}down{/if}"></i>{/if}
                            </a>
                        </th>
                        <th>شماره کارت</th>
                        <th>
                            <a href="{$modulelink}&sort=status&order={if $sort eq 'status' && $order eq 'desc'}asc{else}desc{/if}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                وضعیت {if $sort eq 'status'}<i class="fas fa-sort-{if $order eq 'asc'}up{else}down{/if}"></i>{/if}
                            </a>
                        </th>
                        <th>خطا</th>
                        <th>
                            <a href="{$modulelink}&sort=created_at&order={if $sort eq 'created_at' && $order eq 'desc'}asc{else}desc{/if}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                تاریخ {if $sort eq 'created_at'}<i class="fas fa-sort-{if $order eq 'asc'}up{else}down{/if}"></i>{/if}
                            </a>
                        </th>
                        <th style="width:60px; text-align:center;">عملیات</th>
                    </tr>
                </thead>

                <tbody>
                    {if $transactions && count($transactions) > 0}
                        {foreach from=$transactions item=transaction}
                            <tr>

                                <td>
                                    <span class="bp-id">#{$transaction->id}</span>
                                </td>

                                <td>
                                    {if $transaction->client_id}
                                        <a href="clientssummary.php?userid={$transaction->client_id}"
                                           target="_blank"
                                           class="bp-client-link">
                                            {$transaction->client_name|escape:'html'}
                                        </a>
                                    {else}
                                        <span style="color: #5f6368;">{$transaction->client_name|escape:'html'}</span>
                                    {/if}
                                </td>

                                <td>
                                    <a class="bp-invoice-link"
                                       href="invoices.php?action=edit&id={$transaction->invoice_id}"
                                       target="_blank">
                                        #{$transaction->invoice_id}
                                    </a>
                                </td>

                                <td>
                                    <span class="bp-code">{$transaction->order_id|escape:'html'}</span>
                                </td>

                                <td>
                                    {if $transaction->ref_id}
                                        <span class="bp-transaction-id"
                                              data-toggle="tooltip"
                                              title="کلیک برای کپی"
                                              onclick="copyToClipboard('{$transaction->ref_id}')">
                                            {$transaction->ref_id|truncate:15:"...":true}
                                        </span>
                                    {else}
                                        <span style="color: #5f6368;">-</span>
                                    {/if}
                                </td>

                                <td>
                                    <span class="bp-amount">
                                        {($transaction->amount_rial/10)|number_format:0:",":"."}
                                    </span>
                                    <small style="color: #5f6368;"> تومان</small>
                                </td>

                                <td>
                                    {if $transaction->card_holder_pan}
                                        <span class="bp-card-number">
                                            {$transaction->card_holder_pan|substr:0:4} **** {$transaction->card_holder_pan|substr:-4}
                                        </span>
                                    {else}
                                        <span style="color: #5f6368;">-</span>
                                    {/if}
                                </td>

                                <td>
                                    {if $transaction->status eq 'completed'}
                                        <span class="bp-status bp-status-success">موفق</span>
                                    {elseif $transaction->status eq 'pending'}
                                        <span class="bp-status bp-status-pending">در انتظار</span>
                                    {else}
                                        <span class="bp-status bp-status-failed">ناموفق</span>
                                    {/if}
                                </td>

                                <td>
                                    {if $transaction->error_code}
                                        <span class="bp-error"
                                              data-toggle="tooltip"
                                              title="{if $transaction->error_message}{$transaction->error_message|escape:'html'}{else}کد خطا: {$transaction->error_code}{/if}">
                                            {$transaction->error_code}
                                        </span>
                                    {else}
                                        <span style="color: #5f6368;">-</span>
                                    {/if}
                                </td>

                                <td>
                                    <div class="bp-date">
                                        {$transaction->created_at|date_format:"%Y/%m/%d"}
                                        <br>
                                        <small>{$transaction->created_at|date_format:"%H:%M"}</small>
                                    </div>
                                </td>

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
                            <td colspan="11">
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

        {* Pagination *}
        {if $totalPages > 1}
            <div class="bp-pagination">
                <div class="bp-pagination-info">
                    نمایش {($page-1)*$limit+1} تا {min($page*$limit, $total)} از {$total|number_format} تراکنش
                </div>

                <ul class="pagination">
                    <li class="{if $page <= 1}disabled{/if}">
                        <a href="{if $page > 1}{$modulelink}&page={$page-1}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}{else}javascript:void(0){/if}">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>

                    {assign var="startPage" value=max(1, $page-2)}
                    {assign var="endPage" value=min($totalPages, $page+2)}

                    {if $startPage > 1}
                        <li>
                            <a href="{$modulelink}&page=1&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">1</a>
                        </li>
                        {if $startPage > 2}
                            <li class="disabled"><a>...</a></li>
                        {/if}
                    {/if}

                    {for $i=$startPage to $endPage}
                        <li class="{if $i == $page}active{/if}">
                            <a href="{$modulelink}&page={$i}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">{$i}</a>
                        </li>
                    {/for}

                    {if $endPage < $totalPages}
                        {if $endPage < $totalPages - 1}
                            <li class="disabled"><a>...</a></li>
                        {/if}
                        <li>
                            <a href="{$modulelink}&page={$totalPages}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">{$totalPages}</a>
                        </li>
                    {/if}

                    <li class="{if $page >= $totalPages}disabled{/if}">
                        <a href="{if $page < $totalPages}{$modulelink}&page={$page+1}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}{else}javascript:void(0){/if}">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                </ul>
            </div>
        {/if}
    </div>

</div>

{literal}
<script>
jQuery(function($) {
    // Tooltip
    $('[data-toggle="tooltip"]').tooltip();

    // Copy to clipboard
    window.copyToClipboard = function(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text);
        } else {
            var $temp = $('<textarea style="position:absolute;left:-9999px;">');
            $('body').append($temp);
            $temp.val(text).select();
            try { document.execCommand('copy'); } catch(e) {}
            $temp.remove();
        }
    };
});
</script>
{/literal}
