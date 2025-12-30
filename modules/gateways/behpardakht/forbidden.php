<html>
    <title>Forbidden!</title>
    <meta charset="UTF-8">
    <body>
        <center>
            <br>
        <h2>درگاه بانک ملت تنها برای  مشتریان داخل ایران مجاز است</h2>
درصورتی که از سرویس های عبور از فیلتر استفاده می کنید، آن را غیرفعال کنید.
<br><br>
Mellat Bank is only allowed for Iranian customers.
Please change gateway to PayPal in invoice.
<?php $referer = filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL);
	echo '<h3><a href="'. $referer .'" title="بازگشت">&laquo; بازگشت - Back</a></h3';
?>
</center>
    </body>
</html>
