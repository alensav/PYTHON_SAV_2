<?php
	require_once(dirname(__FILE__)."/../config.php");

	require_once(dirname(__FILE__)."/../htmlMimeMail5/htmlMimeMail5.php");

	$sign = md5($qiwi_SHOP_ID.$qiwi_REST_ID.$_REQUEST['inv_id'].$_REQUEST['out_summ'].$_REQUEST['email'].$_REQUEST['shp_file'].$qiwi_Pull_PWD);

	if($sign !== $_REQUEST['qiwi_sign'])
		die('bad sign');

	//ID счета
	$BILL_ID = $_REQUEST['inv_id'];
	$PHONE = $_REQUEST['qiwi_phone'];

	$data = array(
		"user" => "tel:".$PHONE,
		"amount" => $_REQUEST['out_summ'],
		"ccy" => $qiwi_currency,
		"comment" => $qiwi_desc,
		"lifetime" => substr(date("c", time() + 86400), 0, -6),
		"pay_source" => "qw"
	);

	file_put_contents(dirname(__FILE__)."/data.txt", $BILL_ID.":".substr($_REQUEST['email'], 0, 200).":".$_REQUEST['out_summ'].":".$_REQUEST['shp_file'].":".$qiwi_desc."\n", FILE_APPEND);


	$ch = curl_init('https://api.qiwi.com/api/v2/prv/'.$qiwi_SHOP_ID.'/bills/'.$BILL_ID);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, $qiwi_REST_ID.":".$qiwi_REST_KEY);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array (
					"Accept: application/json"
				));
	$results = curl_exec ($ch) or die(curl_error($ch));
	//echo $results;
	//echo curl_error($ch);
	curl_close ($ch);

	//Необязательный редирект пользователя
	$url = 'https://qiwi.com/order/external/main.action?shop='.$qiwi_SHOP_ID.'&transaction='.$BILL_ID.'&successUrl='.urlencode($script_url."qiwi/success.php").'&failUrl='.urlencode($script_url."qiwi/fail.php").'&qiwi_phone='.$PHONE;

	header("Location: ".$url);
	//echo "<pre>".print_r($data, true)."</pre>";
	//echo "<pre>".print_r($results, true)."</pre>";
	//echo "<a href='".$url."'>Pay</a>";

?>
