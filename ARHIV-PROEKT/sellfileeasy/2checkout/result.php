<?php
	require_once(dirname(__FILE__)."/../config.php");

	require_once(dirname(__FILE__)."/../htmlMimeMail5/htmlMimeMail5.php");

	$out_summ = $_REQUEST['total'];
	$hashOrder = $_REQUEST['order_number'];  //2Checkout Order Number
	$inv_id = $_REQUEST['li_0_product_id'];
	$price = $_REQUEST['li_0_price'];
	$Shp_file = $_REQUEST['li_0_file'];
	$Shp_email = $_REQUEST['li_0_email'];
	$checkout_sign = strtoupper($_REQUEST['li_0_checkout_sign']);
	$StringToHash = strtoupper(md5($co_password . $sid . $hashOrder . $out_summ));

	$my_crc = strtoupper(md5($sid.$inv_id.number_format($price, 2, '.', '').$co_password.$Shp_email.$Shp_file));

	if($StringToHash == $_REQUEST['key']) {
		if(strpos(file_get_contents(dirname(__FILE__)."/../archive/transaction.log"), $my_crc) === false && $my_crc == $checkout_sign) {
			file_put_contents(dirname(__FILE__)."/../archive/transaction.log",
				$my_crc."~".
				time()."~".
				$download_times."~".
				intval($inv_id)."~".
				floatval($price)."~".
				htmlspecialchars($Shp_file, ENT_QUOTES, "UTF-8")."~".
				htmlspecialchars($Shp_email, ENT_QUOTES, "UTF-8")."~".
				"2checkout"."\n", FILE_APPEND
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

			header("Location: ".$script_url."2checkout/success.php");
		} else {
			header("Location: ".$script_url."2checkout/fail.php");
		}
	} else {
		die("Hash error");
	}
?>
