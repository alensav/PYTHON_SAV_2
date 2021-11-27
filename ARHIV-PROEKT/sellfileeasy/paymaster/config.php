<?php
	//Shop ID
	$paymaster_merchant_id = '123456789';
	//Sekret key
	$paymaster_secret_key = '1234';
	//Description
	$paymaster_desc = __("File for download");

	//Comment // or uncomment payment system
	$paymaster_payment_systems = array(
		//"TEST" => "18",
		"WEBMANY" => "1",
		"WEBMANY MOBILE" => "2",
		"MONEXY" => "6",
		"EASYPAY" => "12",
		"NSMEP" => "15",
		"WEBMANY TERMINAL" => "17",
		"LIQPAY" => "19",
		"PRIVAT24" => "20",
		"VISA MASTER" => "21",
		"KIEVSTAR" => "23"
	);

	//Hash algoritm md5, sha256 or sha1
	$paymaster_hash_alg = "md5";
?>
