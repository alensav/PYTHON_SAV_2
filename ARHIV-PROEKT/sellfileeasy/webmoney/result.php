<?php
	require_once(dirname(__FILE__)."/../config.php");

	require_once(dirname(__FILE__)."/../htmlMimeMail5/htmlMimeMail5.php");

	$out_summ = $_REQUEST["LMI_PAYMENT_AMOUNT"];
	$inv_id = $_REQUEST["LMI_PAYMENT_NO"];
	$inv_desc = urldecode($_REQUEST["Shp_desc"]);
	$Shp_email = $_REQUEST["Shp_email"];
	$Shp_file = $_REQUEST["Shp_file"];
	$crc_zp = strtolower($_REQUEST["Shp_sign"]);
	$crc = strtolower($_REQUEST["LMI_HASH"]);

	$hash = strtolower(hash("sha256", $_REQUEST['LMI_PAYEE_PURSE'].$_REQUEST['LMI_PAYMENT_AMOUNT'].$_REQUEST['LMI_PAYMENT_NO'].$_REQUEST['LMI_MODE'].$_REQUEST['LMI_SYS_INVS_NO'].$_REQUEST['LMI_SYS_TRANS_NO'].$_REQUEST['LMI_SYS_TRANS_DATE'].$wm_pass.$_REQUEST['LMI_PAYER_PURSE'].$_REQUEST['LMI_PAYER_WM']));

	$my_crc = md5($wm_login.$inv_id.$out_summ.$wm_pass.$Shp_email.$Shp_file);

	//Check sign
	if($my_crc != $crc_zp) {
		die("bad sign\n");
	} else if($hash != $crc) {
		die("bad sign 2\n");
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
			"robokassa"."\n", FILE_APPEND
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
