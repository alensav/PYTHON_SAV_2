<?php
	require_once(dirname(__FILE__)."/../config.php");

	require_once(dirname(__FILE__)."/../htmlMimeMail5/htmlMimeMail5.php");

/*
		if(preg_match("/.*?<\s*(.*?)\s*>/i", htmlspecialchars_decode($fxn_website_email, ENT_QUOTES), $match)) {
			$to = $match[1];
		} else {
			$to = $fxn_website_email;
		}

		$mail = new htmlMimeMail5();
		$mail->setHeadCharset("UTF-8");
		$mail->setHTMLCharset("UTF-8");
		$mail->setFrom($to);
		$mail->setSMTPParams('mail.example.com', 25, 'my.host.com', true, 'user', 'pass');
		$mail->setSubject("test");
		$mail->setHTML(print_r($_GET, true).print_r($_POST, true).print_r($_REQUEST, true));
		$mail->send(array("vitalyreznik@gmail.com"));
*/

	//Parameters
	$out_summ = $_REQUEST["OutSum"];
	$inv_id =  $_REQUEST["InvId"];
	$Shp_file = $_REQUEST["Shp_file"];
	$Shp_email = $_REQUEST["Shp_email"];
	$crc = $_REQUEST["SignatureValue"];

	$crc = strtolower($crc);

	$my_crc = strtolower(md5("$out_summ:$inv_id:$mrh_pass2:Shp_email=$Shp_email:Shp_file=$Shp_file"));

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
