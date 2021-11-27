<?php
	require_once(dirname(__FILE__)."/../config.php");

	require_once(dirname(__FILE__)."/../htmlMimeMail5/htmlMimeMail5.php");

	////////////////////////////////////////////////
	// Проверка статуса
	////////////////////////////////////////////////
	if($_REQUEST['ik_inv_st'] !== 'success')
		die('bad status');

	////////////////////////////////////////////////
	// Проверка id кассы
	////////////////////////////////////////////////
	if($ik_shop_id !== $_REQUEST['ik_co_id'])
		die('Неверный идентификатор кассы');

	$dataSet = $_REQUEST;
	unset($dataSet['ik_sign']);
	ksort($dataSet, SORT_STRING); // сортируем по ключам в алфавитном порядке элементы массива
	array_push($dataSet, $ik_initial_key); // добавляем в конец массива "секретный ключ"
	$signString = implode(':', $dataSet); // конкатенируем значения через символ ":"
	$sign = base64_encode(md5($signString, true)); // берем MD5 хэш в бинарном виде по сформированной строке и кодируем в BASE64

	if($sign !== $_REQUEST['ik_sign'])
		die('bad sign');

	//Parameters
	$out_summ = number_format($_REQUEST["ik_am"], 2, '.', '');
	$inv_id =  $_REQUEST["ik_pm_no"];
	$inv_desc = $_REQUEST["ik_desc"];
	$Shp_file = $_REQUEST["ik_x_shp_file"];
	$Shp_email = $_REQUEST["ik_x_email"];
	$crc = $_REQUEST["ik_x_mysign"];

	// Проверяем контрольную подпись
	$my_crc = md5($ik_shop_id.$inv_id.$out_summ.$ik_secret_key.$Shp_email.$Shp_file);

	//Check sign
	if($my_crc != $crc) {
		echo "bad sign2\n";
		exit();
	}

	if(strpos(file_get_contents(dirname(__FILE__)."/../archive/transaction.log"), $my_crc) === false) {
		file_put_contents(dirname(__FILE__)."/../archive/transaction.log",
			$my_crc."~".
			time()."~".
			$download_times."~".
			intval($inv_id)."~".
			floatval($out_summ)."~".
			htmlspecialchars($Shp_file, ENT_QUOTES, "UTF-8")."~".
			htmlspecialchars($Shp_email, ENT_QUOTES, "UTF-8")."~".
			"interkassa"."\n", FILE_APPEND
		); // log

		//Send link
		$name = explode("/", $Shp_file, 2);

		if(preg_match("/.*?<\s*(.*?)\s*>/i", htmlspecialchars_decode($fxn_website_email, ENT_QUOTES), $match)) {
			$to = $match[1];
		} else {
			$to = $fxn_website_email;
		}

		$mail = new htmlMimeMail5();
		$mail->setHeadCharset("UTF-8");
		$mail->setHTMLCharset("UTF-8");
		$mail->setFrom($to);
		$mail->setSubject($inv_desc." ".$name[1]);
		$mail->setHTML(__("Link to download").": <a href='".$script_url."archive/".$Shp_file."?hash=".$my_crc."'>".$script_url."archive/".$Shp_file."?hash=".$my_crc."</a> <br /><br />".__("Link will be available for")." <i>".($download_minutes > 0 ? $download_minutes." ".__("minutes") : __("unlimited time"))."</i>, ".__("and")." <i>".($download_times > 0 ? $download_times : __("unlimited"))." ".__("downloads")."</i>."."<br /><br />".__("Check link").": <a href='".$script_url."archive/".$Shp_file."?hash=".$my_crc."&check=yes'>".$script_url."archive/".$Shp_file."?hash=".$my_crc."&check=yes</a>");
		$mail->send(array($Shp_email));
	}
?>
