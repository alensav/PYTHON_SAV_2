<?php
	require_once(dirname(__FILE__)."/../config.php");

	require_once(dirname(__FILE__)."/../htmlMimeMail5/htmlMimeMail5.php");

	if(isset($_POST['LMI_PREREQUEST'])){
		if($_POST['LMI_MERCHANT_ID'] == $paymaster_merchant_id)
			die('YES');
		else
			die('FAIL');
	}

	if(!isset($_POST['LMI_PREREQUEST']) && isset($_POST['LMI_HASH'])) {

		$inv_id = $_POST['LMI_PAYMENT_NO'];

		$hash = strtoupper(hash($paymaster_hash_alg, $paymaster_merchant_id.$inv_id.$_POST['LMI_SYS_PAYMENT_ID'].$_POST['LMI_SYS_PAYMENT_DATE'].$_POST['LMI_PAYMENT_AMOUNT'].$_POST['LMI_PAID_AMOUNT'].$_POST['LMI_PAYMENT_SYSTEM'].$_POST['LMI_MODE'].$paymaster_secret_key));


		if($hash != $_POST['LMI_HASH']) {
			die("bad sign");
		}

		$lmi_hash = strtoupper(hash($paymaster_hash_alg, $paymaster_merchant_id.$inv_id.number_format($_POST['LMI_PAYMENT_AMOUNT'], 2, '.', '').$paymaster_secret_key));
		$my_crc = md5($lmi_hash);

		$content = file_get_contents(dirname(__FILE__)."/data.txt");

		if(preg_match("/(".preg_quote($my_crc, '/')."):(.+?):(.+?):(.+?):(.+?):(.+?)\n/", $content, $data)) {
			$Shp_email = $data[2];
			$out_summ = $data[3];
			$Shp_file = $data[4];
			$inv_id = $data[5];
			$inv_desc = $data[6];
			$content = str_replace($my_crc.":".$data[2].":".$data[3].":".$data[4].":".$data[5].":".$data[6]."\n", "", $content);
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
				"paymaster"."\n", FILE_APPEND
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
	}
?>
