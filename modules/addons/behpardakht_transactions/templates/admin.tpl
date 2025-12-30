{assign var="pageCount" value=$transactions|@count}

{assign var="sortLabel" value="تاریخ ایجاد"}
{if $sort eq 'id'}{assign var="sortLabel" value="شناسه"}{/if}
{if $sort eq 'invoice_id'}{assign var="sortLabel" value="شماره فاکتور"}{/if}
{if $sort eq 'order_id'}{assign var="sortLabel" value="شماره سفارش"}{/if}
{if $sort eq 'amount_rial'}{assign var="sortLabel" value="مبلغ"}{/if}
{if $sort eq 'status'}{assign var="sortLabel" value="وضعیت"}{/if}

<div class="bp-admin">
    <section class="bp-hero">
        <div class="bp-hero__grid">
            <div class="bp-hero__text">
                <p class="bp-kicker">مدیریت تراکنش به‌پرداخت ملت</p>
                <h1>کنترل کامل تراکنش‌ها</h1>
                <p class="bp-lead">پایش و مدیریت تراکنش‌های بانکی با چیدمان تازه، تمرکز روی داده‌های کلیدی و تجربه تجاری.</p>
                <div class="bp-chip-group">
                    <span class="bp-chip"><i class="fas fa-database"></i> {$total|number_format} تراکنش</span>
                    <span class="bp-chip bp-chip--soft"><i class="fas fa-layer-group"></i> صفحه {$page} از {$totalPages}</span>
                    <span class="bp-chip bp-chip--ghost"><i class="fas fa-sort-amount-down-alt"></i> مرتب‌سازی: {$sortLabel}</span>
                </div>
            </div>
            <div class="bp-hero__stats">
                <div class="bp-stat">
                    <div class="bp-stat__label">تراکنش‌های این صفحه</div>
                    <div class="bp-stat__value">{$pageCount}</div>
                    <div class="bp-stat__hint">فیلترها و مرتب‌سازی فعلی</div>
                </div>
                <div class="bp-stat">
                    <div class="bp-stat__label">تراکنش‌های ثبت‌شده</div>
                    <div class="bp-stat__value">{$total|number_format}</div>
                    <div class="bp-stat__hint">نمای کلی از کل سوابق</div>
                </div>
            </div>
        </div>
    </section>

    <div class="bp-card bp-card--filters">
        <div class="bp-card__header">
            <div>
                <p class="bp-kicker">جستجو</p>
                <h2>فیلتر و مرتب‌سازی</h2>
            </div>
            <div class="bp-actions">
                <a href="{$modulelink}" class="bp-btn bp-btn--ghost"><i class="fas fa-redo"></i> پاک کردن فیلترها</a>
                {if $enable_export}
                    <a class="bp-btn bp-btn--success"
                       href="{$modulelink}&action=export&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                        <i class="fas fa-file-excel"></i> خروجی اکسل
                    </a>
                {/if}
            </div>
        </div>

        <form method="GET" action="{$modulelink}" class="bp-filters">
            <label class="bp-field">
                <span class="bp-field__label"><i class="fas fa-search"></i> جستجوی سریع</span>
                <input type="text" name="search" value="{$search}" placeholder="شماره فاکتور، سفارش یا شناسه تراکنش">
            </label>

            <label class="bp-field">
                <span class="bp-field__label"><i class="fas fa-tasks"></i> وضعیت</span>
                <select name="status">
                    <option value="">همه</option>
                    <option value="completed" {if $status_filter eq 'completed'}selected{/if}>موفق</option>
                    <option value="pending" {if $status_filter eq 'pending'}selected{/if}>در انتظار</option>
                    <option value="failed" {if $status_filter eq 'failed'}selected{/if}>ناموفق</option>
                </select>
            </label>

            <label class="bp-field">
                <span class="bp-field__label"><i class="fas fa-calendar-alt"></i> از تاریخ</span>
                <input type="date" name="date_from" value="{$date_from}">
            </label>

            <label class="bp-field">
                <span class="bp-field__label"><i class="fas fa-calendar-alt"></i> تا تاریخ</span>
                <input type="date" name="date_to" value="{$date_to}">
            </label>

            <div class="bp-filters__submit">
                <button type="submit" class="bp-btn bp-btn--primary"><i class="fas fa-search"></i> اعمال فیلتر</button>
            </div>
        </form>
    </div>

    <div class="bp-card bp-card--table">
        <div class="bp-card__header">
            <div>
                <p class="bp-kicker">گزارش تراکنش</p>
                <h2>لیست تراکنش‌ها</h2>
                <p class="bp-muted">نمایش فاکتور، شناسه سفارش، وضعیت و تاریخ با دسترسی سریع به جزئیات.</p>
            </div>
            <div class="bp-chip bp-chip--ghost">
                <i class="fas fa-info-circle"></i>
                مبلغ‌ها بر اساس تومان نمایش داده می‌شوند.
            </div>
        </div>

        <div class="bp-table__wrap">
            <table class="bp-table">
                <thead>
                    <tr>
                        <th class="bp-table__th--id">
                            <a class="bp-sortable {if $sort eq 'id'}is-active is-{$order}{/if}"
                               href="{$modulelink}&sort=id&order={if $sort eq 'id' && $order eq 'desc'}asc{else}desc{/if}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                شناسه
                            </a>
                        </th>
                        <th>مشتری</th>
                        <th>
                            <a class="bp-sortable {if $sort eq 'invoice_id'}is-active is-{$order}{/if}"
                               href="{$modulelink}&sort=invoice_id&order={if $sort eq 'invoice_id' && $order eq 'desc'}asc{else}desc{/if}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                فاکتور
                            </a>
                        </th>
                        <th>سفارش</th>
                        <th>تراکنش</th>
                        <th>
                            <a class="bp-sortable {if $sort eq 'amount_rial'}is-active is-{$order}{/if}"
                               href="{$modulelink}&sort=amount_rial&order={if $sort eq 'amount_rial' && $order eq 'desc'}asc{else}desc{/if}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                مبلغ
                            </a>
                        </th>
                        <th>
                            <a class="bp-sortable {if $sort eq 'status'}is-active is-{$order}{/if}"
                               href="{$modulelink}&sort=status&order={if $sort eq 'status' && $order eq 'desc'}asc{else}desc{/if}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                وضعیت
                            </a>
                        </th>
                        <th>
                            <a class="bp-sortable {if $sort eq 'created_at'}is-active is-{$order}{/if}"
                               href="{$modulelink}&sort=created_at&order={if $sort eq 'created_at' && $order eq 'desc'}asc{else}desc{/if}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">
                                تاریخ
                            </a>
                        </th>
                        <th class="bp-table__th--actions">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    {if $transactions && count($transactions) > 0}
                        {foreach from=$transactions item=transaction}
                            <tr class="bp-row bp-row--{$transaction->status}">
                                <td><span class="bp-pill">#{$transaction->id}</span></td>
                                <td>
                                    {if $transaction->client_id}
                                        <a href="clientssummary.php?userid={$transaction->client_id}" target="_blank" class="bp-link"><i class="fas fa-user"></i> {$transaction->client_name|escape:'html'}</a>
                                    {else}
                                        <span class="bp-muted">{$transaction->client_name|escape:'html'}</span>
                                    {/if}
                                </td>
                                <td>
                                    <a class="bp-badge" href="invoices.php?action=edit&id={$transaction->invoice_id}" target="_blank">#{$transaction->invoice_id}</a>
                                </td>
                                <td><span class="bp-code">{$transaction->order_id|escape:'html'}</span></td>
                                <td>
                                    {if $transaction->ref_id}
                                        <button type="button" class="bp-copy" data-toggle="tooltip" title="کپی شناسه" onclick="copyToClipboard('{$transaction->ref_id}', this)">
                                            <i class="fas fa-copy"></i>
                                            <span>{$transaction->ref_id|truncate:18:"...":true}</span>
                                        </button>
                                    {else}
                                        <span class="bp-muted">-</span>
                                    {/if}
                                </td>
                                <td>
                                    <div class="bp-amount">
                                        <span>{($transaction->amount_rial/10)|number_format:0:",":"."}</span>
                                        <small>تومان</small>
                                    </div>
                                </td>
                                <td>
                                    {if $transaction->status eq 'completed'}
                                        <span class="bp-status bp-status--success">موفق</span>
                                    {elseif $transaction->status eq 'pending'}
                                        <span class="bp-status bp-status--warning">در انتظار</span>
                                    {else}
                                        <span class="bp-status bp-status--danger">ناموفق</span>
                                    {/if}
                                </td>
                                <td>
                                    <div class="bp-date">
                                        <strong>{$transaction->created_at|date_format:"%Y/%m/%d"}</strong>
                                        <span>{$transaction->created_at|date_format:"%H:%M"}</span>
                                    </div>
                                </td>
                                <td class="bp-actions-col">
                                    <a class="bp-icon-btn" href="invoices.php?action=edit&id={$transaction->invoice_id}" target="_blank" data-toggle="tooltip" title="مشاهده فاکتور">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        {/foreach}
                    {else}
                        <tr>
                            <td colspan="9">
                                <div class="bp-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>هیچ تراکنشی پیدا نشد. فیلترها را تغییر دهید یا مجدداً تلاش کنید.</p>
                                </div>
                            </td>
                        </tr>
                    {/if}
                </tbody>
            </table>
        </div>

        {if $totalPages > 1}
            <div class="bp-pager">
                <div class="bp-pager__info">
                    نمایش <strong>{($page-1)*$limit+1}</strong> تا <strong>{min($page*$limit, $total)}</strong> از <strong>{$total|number_format}</strong>
                </div>
                <ul class="bp-pager__list">
                    <li class="{if $page <= 1}is-disabled{/if}">
                        <a href="{if $page > 1}{$modulelink}&page={$page-1}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}{else}javascript:void(0){/if}"><i class="fas fa-chevron-right"></i></a>
                    </li>

                    {assign var="startPage" value=max(1, $page-2)}
                    {assign var="endPage" value=min($totalPages, $page+2)}

                    {if $startPage > 1}
                        <li><a href="{$modulelink}&page=1&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">1</a></li>
                        {if $startPage > 2}
                            <li class="is-ellipsis">...</li>
                        {/if}
                    {/if}

                    {for $i=$startPage to $endPage}
                        <li class="{if $i == $page}is-active{/if}"><a href="{$modulelink}&page={$i}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">{$i}</a></li>
                    {/for}

                    {if $endPage < $totalPages}
                        {if $endPage < $totalPages - 1}
                            <li class="is-ellipsis">...</li>
                        {/if}
                        <li><a href="{$modulelink}&page={$totalPages}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">{$totalPages}</a></li>
                    {/if}

                    <li class="{if $page >= $totalPages}is-disabled{/if}">
                        <a href="{if $page < $totalPages}{$modulelink}&page={$page+1}&search={$search|escape:'url'}&status={$status_filter|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}{else}javascript:void(0){/if}"><i class="fas fa-chevron-left"></i></a>
                    </li>
                </ul>
            </div>
        {/if}
    </div>
</div>

{literal}
<script>
jQuery(function($) {
    $('[data-toggle="tooltip"]').tooltip();

    window.copyToClipboard = function(text, element) {
        var $el = $(element);

        function showFeedback() {
            $el.addClass('is-copied');
            setTimeout(function() {
                $el.removeClass('is-copied');
            }, 1300);
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(showFeedback).catch(function() {
                fallbackCopy(text);
                showFeedback();
            });
        } else {
            fallbackCopy(text);
            showFeedback();
        }
    };

    function fallbackCopy(text) {
        var $temp = $('<textarea style="position:absolute;left:-9999px;">');
        $('body').append($temp);
        $temp.val(text).select();
        try {
            document.execCommand('copy');
        } catch (err) {
            console.error('خطا در کپی:', err);
        }
        $temp.remove();
    }
});
</script>
{/literal}
