<?php
	require_once(dirname(__FILE__)."/../config.php");

	require_once(dirname(__FILE__)."/../htmlMimeMail5/htmlMimeMail5.php");

	$notification_type = $_REQUEST['notification_type'];
	$operation_id = $_REQUEST['operation_id'];
	$amount = $_REQUEST['amount'];
	$currency = $_REQUEST['currency'];
	$datetime = $_REQUEST['datetime'];
	$sender = $_REQUEST['sender'];
	$codepro = $_REQUEST['codepro'];
	$notification_secret = $ya_secret_key;
	$label = $_REQUEST['label'];

	$sha1 = sha1($notification_type."&".$operation_id."&".$amount."&".$currency."&".$datetime."&".$sender."&".$codepro."&".$notification_secret."&".$label);

	if($sha1 !== $_REQUEST['sha1_hash'] || strlen($label) != 32 || !preg_match("/^[0-9a-zA-Z]+$/", $label)) {
		die("bad sign");
	}

	$content = file_get_contents(dirname(__FILE__)."/data.txt");

	if(preg_match("/(".preg_quote($label, '/')."):(.+?):(.+?):(.+?):(.+?):(.+?)\n/", $content, $data)) {
		$my_crc = $label;
		$Shp_email = $data[2];
		$out_summ = $data[3];
		$Shp_file = $data[4];
		$inv_id = $data[5];
		$inv_desc = $data[6];
		$content = str_replace($label.":".$data[2].":".$data[3].":".$data[4].":".$data[5].":".$data[6]."\n", "", $content);
		file_put_contents(dirname(__FILE__)."/data.txt", $content);
	} else {
		die("bad request");
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
			"yandex.money"."\n", FILE_APPEND
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
