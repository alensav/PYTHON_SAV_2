<?php
	require_once(dirname(__FILE__)."/../config.php");

	require_once(dirname(__FILE__)."/../htmlMimeMail5/htmlMimeMail5.php");

	$sign = md5($_REQUEST['inv_id'].$_REQUEST['out_summ'].$_REQUEST['email'].$_REQUEST['shp_file'].$custom_sfe_pass);

	if($sign !== $_REQUEST['custom_sfe_sign'])
		die('bad sign');

	unset($_POST['custom_sfe_sign']);
	$data = $_POST;
	ksort($data, SORT_STRING); // сортируем по ключам в алфавитном порядке элементы массива
	$signString = implode('', $data); // конкатенируем значения
	$sign = md5($signString.$custom_sfe_pass); // берем MD5 хэш
	$linkString = http_build_query($data)."&sign=".$sign; // конкатенируем значения
	$accept_link = $script_url."custom_sfe/result.php?".$linkString;

	if(preg_match("/.*?<\s*(.*?)\s*>/i", htmlspecialchars_decode($fxn_website_email, ENT_QUOTES), $match)) {
		$to = $match[1];
	} else {
		$to = $fxn_website_email;
	}

	$mail = new htmlMimeMail5();
	$mail->setHeadCharset("UTF-8");
	$mail->setHTMLCharset("UTF-8");
	$mail->setFrom($to);
	$mail->setSubject(__("Ручной режим"));
	$mail->setHTML(__("Платеж с:")." ".$_POST['custom_requisites']."<br /><br />".__("Файл").": ".$_POST['shp_file']."<br /><br />".__("Email").": ".$_POST['email']."<br /><br />".__("Сумма").": ".$_POST['out_summ']." ".$download_currency_name."<br /><br /><a href='".$accept_link."' target='_blank'>".__("Платеж принят")."</a>");
	$mail->send(array($to));

	require_once(dirname(__FILE__)."/success.php");

?>
