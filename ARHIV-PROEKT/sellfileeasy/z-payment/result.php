<?php
	//Скрипт обрабатывающий запросы Z-Payment
	require_once(dirname(__FILE__)."/../config.php");

	require_once(dirname(__FILE__)."/../htmlMimeMail5/htmlMimeMail5.php");

	//Устанавливаем метод приема данных
	if($ResultMethod=='POST') $HTTP = $_POST;
	else $HTTP = $_GET;
	//Преобразуем массив в переменные
	foreach ($HTTP as $Key=>$Value) { $$Key = $Value; }
	//Проверяем номер магазина
	if($LMI_PAYEE_PURSE!=$IdShopZP) {
		die("ERR: Id магазина не соответсвует настройкам сайта!");
	}
	//Проверяем номер заказа
	//if($LMI_PAYMENT_NO!=$NumberOrder) {
		//die("ERR: Номер счета не соответсвует заказу!");
	//}
	//Настоятельно рекомендуем сверять сумму оплаты с суммой вашего заказа из БД
	//$RealAmountOrder = GetAmountOrder($LMI_PAYMENT_NO);
	//if($RealAmountOrder!=$LMI_PAYMENT_AMOUNT) {
		//die("ERR: Сумма оплаты не соответсвует сумме заказа!");
	//}
	//Предварительный запрос на проведение платежа?
	if($LMI_PREREQUEST==1) {
	// Если в настройках магазина https://z-payment.com/shops.php
	// Задана опция "Отправлять предварительный запрос перед оплатой на Result URL"
	// Перед оплатой этот скрипт будет получать запрос на разрешение оплаты, если платеж прошел
	// проверку требуется вернуть YES, любое другое сообщение будет принято системой как запрет
	// оплачивать счет

	// В этом месте вы можете проверить наличие товара, курсы валют и другую информацию о заказе
	// зарезервировать товар на складе, перед тем как разрешите покупателю совершить оплату.
	// Здесь же можно изменить статус заказа на "Ожидает оплаты"
	// Не забывайте проверить статус заказа на предмет ОТМЕНЫ или ОПЛАТЫ

	// $CLIENT_MAIL  -  емаил покупателя
	// $LMI_PAYER_WM - кошелек покупателя или его емаил
	// $LMI_MODE = 0 - рабочий режим
	// $DESC_PAY - Описание товара
	// $FILE_NAME, $USER_VALUE2, ... Остальные переменные переданные продавцом
	// в форме запроса платежа

		//Разрешаем оплату
		echo 'YES';

	} else { //Уведомление об оплате
	// Если Result URL обеспечивает безопасное соединение SSL и выставлена
	// настройка "Отправлять ключ магазина, если Result URL обеспечивает безопасность"
	// сверяем ключи, этого достаточно при условии, что вы задали
		if(isset($LMI_SECRET_KEY)) {
			// Если ключ совпадает, занчит все ОК, проводим заказ
			if($LMI_SECRET_KEY==$SecretKeyZP) {
				//Подтверждение оплаты заказа
				$Result = ConfirmOrder($LMI_PAYMENT_NO);
				//Все прошло успешно
				if($Result) echo 'YES';
			} else {
				//Отмена заказа
				CancelOrder($LMI_PAYMENT_NO);
			}
		} else {
			// Ключ не был передан, требуется проверить контрольный хеш запроса
			//Расчет контрольного хеша из полученных переменных и Ключа мерчанта
			$CalcHash = md5($LMI_PAYEE_PURSE.$LMI_PAYMENT_AMOUNT.$LMI_PAYMENT_NO.$LMI_MODE.$LMI_SYS_INVS_NO.$LMI_SYS_TRANS_NO.$LMI_SYS_TRANS_DATE.$SecretKeyZP.$LMI_PAYER_PURSE.$LMI_PAYER_WM);
			//Сравниваем значение расчетного хеша с полученным
			if($LMI_HASH == strtoupper($CalcHash)) {
				//Подтверждение оплаты заказа
				$Result = ConfirmOrder($LMI_PAYMENT_NO);
				//Все прошло успешно
				if($Result) echo 'YES';
			} else {
				//Отмена заказа
				CancelOrder($LMI_PAYMENT_NO);
			}
		}
	}
	// Функция получает сумму заказа из базы данных магазина по номеру счета,
	// В этой же функции можно проверить наличие заказа с запрашиваемым ID
	// здесь символически возвращает цену заказа из настроек config_zp.php
	function GetAmountOrder($IdOrder){
		global $AmountOrder;
		// Запрос к БД даных заказов
		return $AmountOrder;
	}
	// Функция успешного проведения оплаты
	// Конкретное наполнении функции зависит от бизнес логики вашего сайта.
	function ConfirmOrder($IdOrder) {
	// Здесь необходимо выполнить все действия по обновлению статуса заказа,
	// уведомлению клиента, отгрузке товара и пр. действия после получения оплаты заказа
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
	// Функция отмены заказа
	// Конкретное наполнении функции зависит от бизнес логики вашего сайта.
	function CancelOrder($IdOrder) {
	// Здесь необходимо выполнить все действия по отмене заказа
		return true;
	}
?>
