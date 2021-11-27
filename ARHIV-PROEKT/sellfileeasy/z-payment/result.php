<?php
	//������ �������������� ������� Z-Payment
	require_once(dirname(__FILE__)."/../config.php");

	require_once(dirname(__FILE__)."/../htmlMimeMail5/htmlMimeMail5.php");

	//������������� ����� ������ ������
	if($ResultMethod=='POST') $HTTP = $_POST;
	else $HTTP = $_GET;
	//����������� ������ � ����������
	foreach ($HTTP as $Key=>$Value) { $$Key = $Value; }
	//��������� ����� ��������
	if($LMI_PAYEE_PURSE!=$IdShopZP) {
		die("ERR: Id �������� �� ������������ ���������� �����!");
	}
	//��������� ����� ������
	//if($LMI_PAYMENT_NO!=$NumberOrder) {
		//die("ERR: ����� ����� �� ������������ ������!");
	//}
	//������������ ����������� ������� ����� ������ � ������ ������ ������ �� ��
	//$RealAmountOrder = GetAmountOrder($LMI_PAYMENT_NO);
	//if($RealAmountOrder!=$LMI_PAYMENT_AMOUNT) {
		//die("ERR: ����� ������ �� ������������ ����� ������!");
	//}
	//��������������� ������ �� ���������� �������?
	if($LMI_PREREQUEST==1) {
	// ���� � ���������� �������� https://z-payment.com/shops.php
	// ������ ����� "���������� ��������������� ������ ����� ������� �� Result URL"
	// ����� ������� ���� ������ ����� �������� ������ �� ���������� ������, ���� ������ ������
	// �������� ��������� ������� YES, ����� ������ ��������� ����� ������� �������� ��� ������
	// ���������� ����

	// � ���� ����� �� ������ ��������� ������� ������, ����� ����� � ������ ���������� � ������
	// ��������������� ����� �� ������, ����� ��� ��� ��������� ���������� ��������� ������.
	// ����� �� ����� �������� ������ ������ �� "������� ������"
	// �� ��������� ��������� ������ ������ �� ������� ������ ��� ������

	// $CLIENT_MAIL  -  ����� ����������
	// $LMI_PAYER_WM - ������� ���������� ��� ��� �����
	// $LMI_MODE = 0 - ������� �����
	// $DESC_PAY - �������� ������
	// $FILE_NAME, $USER_VALUE2, ... ��������� ���������� ���������� ���������
	// � ����� ������� �������

		//��������� ������
		echo 'YES';

	} else { //����������� �� ������
	// ���� Result URL ������������ ���������� ���������� SSL � ����������
	// ��������� "���������� ���� ��������, ���� Result URL ������������ ������������"
	// ������� �����, ����� ���������� ��� �������, ��� �� ������
		if(isset($LMI_SECRET_KEY)) {
			// ���� ���� ���������, ������ ��� ��, �������� �����
			if($LMI_SECRET_KEY==$SecretKeyZP) {
				//������������� ������ ������
				$Result = ConfirmOrder($LMI_PAYMENT_NO);
				//��� ������ �������
				if($Result) echo 'YES';
			} else {
				//������ ������
				CancelOrder($LMI_PAYMENT_NO);
			}
		} else {
			// ���� �� ��� �������, ��������� ��������� ����������� ��� �������
			//������ ������������ ���� �� ���������� ���������� � ����� ��������
			$CalcHash = md5($LMI_PAYEE_PURSE.$LMI_PAYMENT_AMOUNT.$LMI_PAYMENT_NO.$LMI_MODE.$LMI_SYS_INVS_NO.$LMI_SYS_TRANS_NO.$LMI_SYS_TRANS_DATE.$SecretKeyZP.$LMI_PAYER_PURSE.$LMI_PAYER_WM);
			//���������� �������� ���������� ���� � ����������
			if($LMI_HASH == strtoupper($CalcHash)) {
				//������������� ������ ������
				$Result = ConfirmOrder($LMI_PAYMENT_NO);
				//��� ������ �������
				if($Result) echo 'YES';
			} else {
				//������ ������
				CancelOrder($LMI_PAYMENT_NO);
			}
		}
	}
	// ������� �������� ����� ������ �� ���� ������ �������� �� ������ �����,
	// � ���� �� ������� ����� ��������� ������� ������ � ������������� ID
	// ����� ������������ ���������� ���� ������ �� �������� config_zp.php
	function GetAmountOrder($IdOrder){
		global $AmountOrder;
		// ������ � �� ����� �������
		return $AmountOrder;
	}
	// ������� ��������� ���������� ������
	// ���������� ���������� ������� ������� �� ������ ������ ������ �����.
	function ConfirmOrder($IdOrder) {
	// ����� ���������� ��������� ��� �������� �� ���������� ������� ������,
	// ����������� �������, �������� ������ � ��. �������� ����� ��������� ������ ������
		global $EXTENDED_SIGN, $IdShopZP, $LMI_PAYMENT_NO, $LMI_PAYMENT_AMOUNT, $SecretKeyZP, $download_times, $FILE_NAME, $CLIENT_MAIL, $script_url, $fxn_website_email, $InitialZP, $FILE_MAIL;
		$my_crc = md5($IdShopZP.$LMI_PAYMENT_NO.$LMI_PAYMENT_AMOUNT.$SecretKeyZP);
		$my_extend_crc = md5($IdShopZP.$LMI_PAYMENT_NO.$LMI_PAYMENT_AMOUNT.$InitialZP.$FILE_MAIL.$FILE_NAME);
		if($my_extend_crc == $EXTENDED_SIGN && strpos(file_get_contents(dirname(__FILE__)."/../archive/transaction.log"), $my_extend_crc) === false) {
			file_put_contents(dirname(__FILE__)."/../archive/transaction.log",
				$my_extend_crc."~".
				time()."~".
				$download_times."~".
				intval($LMI_PAYMENT_NO)."~".
				floatval($LMI_PAYMENT_AMOUNT)."~".
				htmlspecialchars($FILE_NAME, ENT_QUOTES, "UTF-8")."~".
				htmlspecialchars($FILE_MAIL, ENT_QUOTES, "UTF-8")."~".
				"z-payment"."\n", FILE_APPEND
			); // log

			//Send link
			$name = explode("/", $FILE_NAME, 2);

			if(preg_match("/.*?<\s*(.*?)\s*>/i", htmlspecialchars_decode($fxn_website_email, ENT_QUOTES), $match)) {
				$to = $match[1];
			} else {
				$to = $fxn_website_email;
			}

			$mail = new htmlMimeMail5();
			$mail->setHeadCharset("UTF-8");
			$mail->setHTMLCharset("UTF-8");
			$mail->setFrom($to);
			$mail->setSubject($LMI_PAYMENT_NO." ".$name[1]);
			$mail->setHTML(__("Link to download").": <a href='".$script_url."archive/".$FILE_NAME."?hash=".$my_extend_crc."'>".$script_url."archive/".$FILE_NAME."?hash=".$my_extend_crc."</a> <br /><br />".__("Link will be available for")." <i>".($download_minutes > 0 ? $download_minutes." ".__("minutes") : __("unlimited time"))."</i>, ".__("and")." <i>".($download_times > 0 ? $download_times : __("unlimited"))." ".__("downloads")."</i>."."<br /><br />".__("Check link").": <a href='".$script_url."archive/".$FILE_NAME."?hash=".$my_extend_crc."&check=yes'>".$script_url."archive/".$FILE_NAME."?hash=".$my_extend_crc."&check=yes</a>");
			$mail->send(array($FILE_MAIL));
		}
		return true;
	}
	// ������� ������ ������
	// ���������� ���������� ������� ������� �� ������ ������ ������ �����.
	function CancelOrder($IdOrder) {
	// ����� ���������� ��������� ��� �������� �� ������ ������
		return true;
	}
?>
