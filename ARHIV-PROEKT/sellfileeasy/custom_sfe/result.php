<?php
	require_once(dirname(__FILE__)."/../config.php");

	require_once(dirname(__FILE__)."/../htmlMimeMail5/htmlMimeMail5.php");

	//Parameters
	$custom_requisites = $_GET["custom_requisites"];
	$out_summ = $_GET["out_summ"];
	$inv_id =  $_GET["inv_id"];
	$Shp_file = $_GET["shp_file"];
	$Shp_email = $_GET["email"];
	$crc = $_GET["sign"];

	unset($_GET['sign']);
	$data = $_GET;
	ksort($data, SORT_STRING); // сортируем по ключам в алфавитном порядке элементы массива
	$signString = implode('', $data); // конкатенируем значения
	$my_crc = md5($signString.$custom_sfe_pass); // берем MD5 хэш

	//Check sign
	if($my_crc != $crc) {
		echo "bad sign\n";
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
			"custom"."\n", FILE_APPEND
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
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<title><?php echo __("SellFileEasy"); ?></title>
		<link rel="stylesheet" href="<?php echo $script_url;?>css/success.css" type="text/css" media="all" />
	</head>
	<body>
		<div id="email_form">
			<div id="top_title"><?php echo __("Принято!"); ?></div>
			<div id="conteiner">
				<br /><br /><div align='center'><?php echo __("Принято на емаил клиента отправлена ссылка!"); ?></div>
			</div>
		</div>
		<div class="copyright"><?php echo $buttom_copyright; ?></div>
	</body>
</html>
