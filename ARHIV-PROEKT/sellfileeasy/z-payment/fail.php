<?php

	require_once(dirname(__FILE__)."/../config.php");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<title><?php echo __("SellFileEasy"); ?></title>
		<link rel="stylesheet" href="<?php echo $script_url;?>css/fail.css" type="text/css" media="all" />
	</head>
	<body>
		<div id="email_form">
			<div id="top_title"><?php echo __("Payment rejected!"); ?></div>
			<div id="conteiner">
				<br /><br /><div align='center'><?php echo __("Payment rejected!"); ?></div>
			</div>
		</div>
		<div class="copyright"><?php echo $buttom_copyright; ?></div>
	</body>
</html>
