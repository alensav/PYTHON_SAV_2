<?php
	require_once(dirname(__FILE__)."/../config.php");

	require_once(dirname(__FILE__)."/../htmlMimeMail5/htmlMimeMail5.php");

	function curl_get_contents($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

		$query = "";
		foreach($_POST as $key => $value) {
			$query .= "&".urlencode($key)."=".urlencode($value);
		}

		$result = curl_get_contents("https://www.paypal.com/cgi-bin/webscr?cmd=_notify-validate".$query);
		if($result != "VERIFIED" || $_POST['payment_status'] != "Completed" || !is_numeric(trim($_POST['item_number']))) {
			die("Error ".$result);
		}

		//Parameters
		$out_summ = $_REQUEST["amount"];
		$inv_id =  $_REQUEST["item_number"];
		$Shp_file = $_REQUEST["item_name"];
		$Shp_email = $_REQUEST["invoice"];
		$crc = $_REQUEST["custom"];

		$crc = strtolower($crc);

		$paypal_sign = md5($business.$inv_id.$out_summ.$stat_pass.$email.$shp_file);

	if(is_numeric($_POST['item_number'])) {
		//Parameters
		$out_summ = $_POST["payment_gross"];
		$inv_id =  $_POST["item_number"];
		$Shp_file = $_POST["item_name"];
		$Shp_email = $_POST["invoice"];
		$crc = $_POST["custom"];

		$crc = strtolower($crc);

		//$paypal_sign = md5($business.$inv_id.$out_summ.$stat_pass.$email.$shp_file);

		$my_crc = strtolower(md5($business.$inv_id.$out_summ.$stat_pass.$Shp_email.$Shp_file));
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
				"paypal"."\n", FILE_APPEND
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
			echo "OK";
		} else {
			echo "Already";
		}
	} else {
		echo "bad request\n";
	}

?>
