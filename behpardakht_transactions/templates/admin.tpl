{*
    قالب مدیریت تراکنش‌های به‌پرداخت (نسخه بازطراحی‌شده با استایل‌های پیش‌فرض WHMCS)
    مسیر: /modules/addons/behpardakht_transactions/templates/admin.tpl
*}

<div class="container-fluid" style="direction: rtl;">

    <div class="page-header clearfix" style="margin-top:0;">
        <h2 class="pull-right" style="margin:0;">
            <i class="fas fa-credit-card"></i>
            تراکنش‌های درگاه به‌پرداخت
        </h2>
        <div class="pull-left" style="padding-top:8px;">
            <span class="label label-default">مجموع: {$total|number_format}</span>
        </div>
    </div>

    <!-- فیلترها -->
    <div class="well well-sm" style="margin-bottom:15px;">
        <form method="GET" action="{$modulelink}" class="form-horizontal" role="form" style="margin:0;">
            <div class="row">

                <div class="col-sm-3">
                    <div class="form-group" style="margin-bottom:10px;">
                        <label class="control-label"><i class="fas fa-search"></i> جستجو</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control"
                                   value="{$search}" placeholder="شماره فاکتور، سفارش، تراکنش یا کارت">
                            <span class="input-group-btn">
                                <button type="submit" class="btn btn-primary">
                                    جستجو
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-sm-2">
                    <div class="form-group" style="margin-bottom:10px;">
                        <label class="control-label"><i class="fas fa-filter"></i> وضعیت</label>
                        <select name="status" class="form-control">
                            <option value="">همه</option>
                            <option value="completed" {if $status_filter eq 'completed'}selected{/if}>موفق</option>
                            <option value="pending"   {if $status_filter eq 'pending'}selected{/if}>در انتظار</option>
                            <option value="failed"    {if $status_filter eq 'failed'}selected{/if}>ناموفق</option>
                        </select>
                    </div>
                </div>

                <div class="col-sm-2">
                    <div class="form-group" style="margin-bottom:10px;">
                        <label class="control-label"><i class="fas fa-calendar"></i> از تاریخ</label>
                        <input type="date" name="date_from" class="form-control" value="{$date_from}">
                    </div>
                </div>

                <div class="col-sm-2">
                    <div class="form-group" style="margin-bottom:10px;">
                        <label class="control-label"><i class="fas fa-calendar"></i> تا تاریخ</label>
                        <input type="date" name="date_to" class="form-control" value="{$date_to}">
                    </div>
                </div>

                <div class="col-sm-3">
                    <div class="form-group" style="margin-bottom:10px;">
                        <label class="control-label">&nbsp;</label>
                        <div class="btn-group btn-group-justified">
                            <a href="{$modulelink}" class="btn btn-default">
                                پاک کردن
                            </a>
                            {if $enable_export}
                                <a class="btn btn-success"
                                   href="{$modulelink}
                                         &action=export
                                         &search={$search|escape:'url'}
                                         &status={$status_filter|escape:'url'}
                                         &date_from={$date_from|escape:'url'}
                                         &date_to={$date_to|escape:'url'}">
                                    خروجی اکسل
                                </a>
                            {/if}
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <!-- پنل جدول -->
    <div class="panel panel-default">
        <div class="panel-heading">
            <strong><i class="fas fa-list-ul"></i> لیست تراکنش‌ها</strong>
            <span class="pull-left text-muted" style="padding-top:2px;">
                {if $search} <span class="label label-info">جستجو: {$search|escape:'html'}</span> {/if}
                {if $status_filter} <span class="label label-info">وضعیت: {$status_filter|escape:'html'}</span> {/if}
                {if $date_from} <span class="label label-info">از: {$date_from|escape:'html'}</span> {/if}
                {if $date_to} <span class="label label-info">تا: {$date_to|escape:'html'}</span> {/if}
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover" style="margin-bottom:0;">
                <thead>
                    <tr>
                        <th style="white-space:nowrap; width:70px;">
                            <a href="{$modulelink}
                                    &sort=id
                                    &order={if $sort eq 'id' && $order eq 'desc'}asc{else}desc{/if}
                                    &search={$search|escape:'url'}
                                    &status={$status_filter|escape:'url'}
                                    &date_from={$date_from|escape:'url'}
                                    &date_to={$date_to|escape:'url'}">
                                شناسه {if $sort eq 'id'}<i class="fas fa-sort-{if $order eq 'asc'}up{else}down{/if}"></i>{/if}
                            </a>
                        </th>
                        <th>نام مشتری</th>
                        <th style="white-space:nowrap;">
                            <a href="{$modulelink}
                                    &sort=invoice_id
                                    &order={if $sort eq 'invoice_id' && $order eq 'desc'}asc{else}desc{/if}
                                    &search={$search|escape:'url'}
                                    &status={$status_filter|escape:'url'}
                                    &date_from={$date_from|escape:'url'}
                                    &date_to={$date_to|escape:'url'}">
                                فاکتور {if $sort eq 'invoice_id'}<i class="fas fa-sort-{if $order eq 'asc'}up{else}down{/if}"></i>{/if}
                            </a>
                        </th>
                        <th>شماره سفارش</th>
                        <th>شماره تراکنش</th>
                        <th style="white-space:nowrap;">
                            <a href="{$modulelink}
                                    &sort=amount_rial
                                    &order={if $sort eq 'amount_rial' && $order eq 'desc'}asc{else}desc{/if}
                                    &search={$search|escape:'url'}
                                    &status={$status_filter|escape:'url'}
                                    &date_from={$date_from|escape:'url'}
                                    &date_to={$date_to|escape:'url'}">
                                مبلغ {if $sort eq 'amount_rial'}<i class="fas fa-sort-{if $order eq 'asc'}up{else}down{/if}"></i>{/if}
                            </a>
                        </th>
                        <th>شماره کارت</th>
                        <th style="white-space:nowrap;">
                            <a href="{$modulelink}
                                    &sort=status
                                    &order={if $sort eq 'status' && $order eq 'desc'}asc{else}desc{/if}
                                    &search={$search|escape:'url'}
                                    &status={$status_filter|escape:'url'}
                                    &date_from={$date_from|escape:'url'}
                                    &date_to={$date_to|escape:'url'}">
                                وضعیت {if $sort eq 'status'}<i class="fas fa-sort-{if $order eq 'asc'}up{else}down{/if}"></i>{/if}
                            </a>
                        </th>
                        <th>خطا</th>
                        <th style="white-space:nowrap;">
                            <a href="{$modulelink}
                                    &sort=created_at
                                    &order={if $sort eq 'created_at' && $order eq 'desc'}asc{else}desc{/if}
                                    &search={$search|escape:'url'}
                                    &status={$status_filter|escape:'url'}
                                    &date_from={$date_from|escape:'url'}
                                    &date_to={$date_to|escape:'url'}">
                                تاریخ {if $sort eq 'created_at'}<i class="fas fa-sort-{if $order eq 'asc'}up{else}down{/if}"></i>{/if}
                            </a>
                        </th>
                        <th class="text-center" style="width:60px;">عملیات</th>
                    </tr>
                </thead>

                <tbody>
                    {if $transactions}
                        {foreach from=$transactions item=transaction}
                            <tr class="{if $transaction->status eq 'completed'}success{elseif $transaction->status eq 'failed'}danger{/if}">
                                <td>
                                    <span class="label label-default">#{$transaction->id}</span>
                                </td>
                                <td>
                                    {if $transaction->client_id}
                                        <a href="clientssummary.php?userid={$transaction->client_id}"
                                           target="_blank">
                                            <i class="fas fa-user text-primary"></i>
                                            {$transaction->client_name|escape:'html'}
                                        </a>
                                    {else}
                                        <span class="text-muted">
                                            {$transaction->client_name|escape:'html'}
                                        </span>
                                    {/if}
                                </td>
                                <td>
                                    <a class="label label-primary"
                                       href="invoices.php?action=edit&id={$transaction->invoice_id|escape:'html'}"
                                       target="_blank">#{$transaction->invoice_id|escape:'html'}</a>
                                </td>
                                <td>
                                    <code>{$transaction->order_id|escape:'html'}</code>
                                </td>
                                <td>
                                    {if $transaction->sale_reference_id}
                                        <a href="javascript:void(0);"
                                           class="copy-text"
                                           data-toggle="tooltip"
                                           title="کپی"
                                           data-text="{$transaction->sale_reference_id|escape:'html'}">
                                            <i class="fas fa-copy"></i>
                                            {$transaction->sale_reference_id|truncate:20:"...":true|escape:'html'}
                                        </a>
                                    {else}
                                        <span class="text-muted">-</span>
                                    {/if}
                                </td>
                                <td>
                                    <strong>
                                        {($transaction->amount_rial/10)|number_format:0:',':'.'}
                                    </strong>
                                    <small class="text-muted">تومان</small>
                                </td>
                                <td>
                                    {if $transaction->card_holder_pan}
                                        <span class="label label-default" style="font-family:monospace;">
                                            {$transaction->card_holder_pan|substr:0:4} **** {$transaction->card_holder_pan|substr:-4}
                                        </span>
                                    {else}
                                        <span class="text-muted">-</span>
                                    {/if}
                                </td>
                                <td>
                                    {if $transaction->status eq 'completed'}
                                        <span class="label label-success">موفق</span>
                                    {elseif $transaction->status eq 'pending'}
                                        <span class="label label-warning">در انتظار</span>
                                    {else}
                                        <span class="label label-danger">ناموفق</span>
                                    {/if}
                                </td>
                                <td>
                                    {if $transaction->error_code}
                                        <span class="label label-danger"
                                              data-toggle="tooltip"
                                              title="{if $transaction->error_message}{$transaction->error_message|escape:'html'}{else}کد خطا{/if}">
                                            {$transaction->error_code|escape:'html'}
                                        </span>
                                    {else}
                                        <span class="text-muted">-</span>
                                    {/if}
                                </td>
                                <td>
                                    {$transaction->created_at|date_format:"%Y/%m/%d"}
                                    <small class="text-muted">{$transaction->created_at|date_format:"%H:%M"}</small>
                                </td>
                                <td class="text-center">
                                    <a class="btn btn-default btn-xs"
                                       href="invoices.php?action=edit&id={$transaction->invoice_id|escape:'html'}"
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
                            <td colspan="11" class="text-center">
                                <div class="text-muted" style="padding:35px 0;">
                                    <i class="fas fa-inbox fa-2x"></i>
                                    <div style="margin-top:8px;">هیچ تراکنشی یافت نشد</div>
                                </div>
                            </td>
                        </tr>
                    {/if}
                </tbody>
            </table>
        </div>

        {if $totalPages > 1}
        <div class="panel-footer clearfix">
            <ul class="pagination pull-right" style="margin:0;">
                <li class="{if $page <= 1}disabled{/if}">
                    <a href="{if $page > 1}
                                {$modulelink}
                                &page={$page-1}
                                &search={$search|escape:'url'}
                                &status={$status_filter|escape:'url'}
                                &date_from={$date_from|escape:'url'}
                                &date_to={$date_to|escape:'url'}
                             {else}javascript:void(0){/if}">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>

                {assign var="startPage" value=max(1, $page-2)}
                {assign var="endPage" value=min($totalPages, $page+2)}

                {if $startPage > 1}
                    <li>
                        <a href="{$modulelink}
                                &page=1
                                &search={$search|escape:'url'}
                                &status={$status_filter|escape:'url'}
                                &date_from={$date_from|escape:'url'}
                                &date_to={$date_to|escape:'url'}">1</a>
                    </li>
                    {if $startPage > 2}
                        <li class="disabled"><a>...</a></li>
                    {/if}
                {/if}

                {for $i=$startPage to $endPage}
                    <li class="{if $i == $page}active{/if}">
                        <a href="{$modulelink}
                                &page={$i}
                                &search={$search|escape:'url'}
                                &status={$status_filter|escape:'url'}
                                &date_from={$date_from|escape:'url'}
                                &date_to={$date_to|escape:'url'}">{$i}</a>
                    </li>
                {/for}

                {if $endPage < $totalPages}
                    {if $endPage < $totalPages - 1}
                        <li class="disabled"><a>...</a></li>
                    {/if}
                    <li>
                        <a href="{$modulelink}
                                &page={$totalPages}
                                &search={$search|escape:'url'}
                                &status={$status_filter|escape:'url'}
                                &date_from={$date_from|escape:'url'}
                                &date_to={$date_to|escape:'url'}">{$totalPages}</a>
                    </li>
                {/if}

                <li class="{if $page >= $totalPages}disabled{/if}">
                    <a href="{if $page < $totalPages}
                                {$modulelink}
                                &page={$page+1}
                                &search={$search|escape:'url'}
                                &status={$status_filter|escape:'url'}
                                &date_from={$date_from|escape:'url'}
                                &date_to={$date_to|escape:'url'}
                             {else}javascript:void(0){/if}">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            </ul>

            <div class="pull-left text-muted" style="padding-top:7px;">
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
jQuery(function($){
    $('[data-toggle="tooltip"]').tooltip();

    $('.copy-text').on('click', function(e){
        e.preventDefault();
        var text = $(this).data('text');
        var $self = $(this);

        function feedback(){
            var $icon = $self.find('i');
            $icon.removeClass('fa-copy').addClass('fa-check text-success');
            setTimeout(function(){
                $icon.removeClass('fa-check text-success').addClass('fa-copy');
            }, 1800);
        }

        if (navigator.clipboard && window.isSecureContext){
            navigator.clipboard.writeText(text).then(feedback).catch(fallback);
        } else {
            fallback();
        }

        function fallback(){
            var $temp = $('<input type="text" style="position:absolute;left:-9999px;">');
            $('body').append($temp);
            $temp.val(text).select();
            try { document.execCommand('copy'); } catch(e){}
            $temp.remove();
            feedback();
        }
    });
});
</script>
{/literal}
