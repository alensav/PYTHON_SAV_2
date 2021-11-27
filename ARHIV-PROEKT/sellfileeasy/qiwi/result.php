<?php
	require_once(dirname(__FILE__)."/../config.php");

	require_once(dirname(__FILE__)."/../htmlMimeMail5/htmlMimeMail5.php");

	function notify() {
		global $qiwi_Pull_PWD;
		if (!empty($_SERVER['HTTP_X_API_SIGNATURE'])) {
			ksort($_POST);
			$Invoice_parameters_byte = implode('|', $_POST);
			$Notification_password_byte = $qiwi_Pull_PWD; // секретная подпись получения от киви
			$sign = hash_hmac('sha1', $Invoice_parameters_byte, $Notification_password_byte, true);
			if (strcmp($_SERVER['HTTP_X_API_SIGNATURE'], base64_encode($sign))===0) {
				return true;
			}
		}
		return false;
	}

/*

		if(preg_match("/.*?<\s*(.*?)\s*>/i", htmlspecialchars_decode($fxn_website_email, ENT_QUOTES), $match)) {
			$to = $match[1];
		}

		$head   = apache_request_headers();

		$mail = new htmlMimeMail5();
		$mail->setHeadCharset("UTF-8");
		$mail->setHTMLCharset("UTF-8");
		$mail->setFrom("Олег Касьянов <report@kasyanov.info>");
		$mail->setSubject("test sfe ".notify());
		$mail->setHTML(print_r($_GET, true)."<br /><br />".print_r($_POST, true)."<br /><br />".print_r($_REQUEST, true)."<br /><br />".print_r($head, true));
		$mail->send(array("vitalyreznik@gmail.com"));

*/

	if(!isset($_REQUEST["amount"])) die("Empty request");

	header("HTTP/1.1 200 OK");
	header("Content-Type: text/xml");

	$out_summ = number_format($_REQUEST["amount"], 2, '.', '');
	$inv_id =  $_REQUEST["bill_id"];

	if($_REQUEST["status"] != "paid" || !notify()) {
		echo '<?xml version="1.0"?>
				<result>
					<result_code>78</result_code>
				</result>';
		exit();
	}

	$content = file_get_contents(dirname(__FILE__)."/data.txt");

	if(preg_match("/(".preg_quote($inv_id, '/')."):(.+?):(.+?):(.+?):(.+?)\n/", $content, $data)) {
		$Shp_email = $data[2];
		$out_summ = $data[3];
		$Shp_file = $data[4];
		$inv_desc = $data[5];
		$content = str_replace($inv_id.":".$data[2].":".$data[3].":".$data[4].":".$data[5]."\n", "", $content);
		file_put_contents(dirname(__FILE__)."/data.txt", $content);
	} else {
		die("bad request");
	}

	$my_crc = md5($qiwi_SHOP_ID.$qiwi_REST_ID.$inv_id.$out_summ.$Shp_email.$Shp_file.$qiwi_Pull_PWD);

	if(strpos(file_get_contents(dirname(__FILE__)."/../archive/transaction.log"), $my_crc) === false) {
		file_put_contents(dirname(__FILE__)."/../archive/transaction.log",
			$my_crc."~".
			time()."~".
			$download_times."~".
			intval($inv_id)."~".
			floatval($out_summ)."~".
			htmlspecialchars($Shp_file, ENT_QUOTES, "UTF-8")."~".
			htmlspecialchars($Shp_email, ENT_QUOTES, "UTF-8")."~".
			"qiwi"."\n", FILE_APPEND
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
		echo '<?xml version="1.0"?>
				<result>
					<result_code>0</result_code>
				</result>';
		exit();
	}

?>
