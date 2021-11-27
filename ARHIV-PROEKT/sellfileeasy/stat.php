<?php

	if(session_id() == "") session_start();

	require_once(dirname(__FILE__)."/config.php");

	$excluding_files = array(".", "..", ".htaccess" ,"index.html", "transaction.log"); // Excluding files and folders

	function scanDirectories($rootDir, $allFiles=array()) {
		global $excluding_files;
		// run through content of root directory
		$dirContent = scandir($rootDir);
		foreach($dirContent as $key => $content) {
			// filter all files not accessible
			$path = $rootDir.'/'.$content;
			if(!in_array($content, $excluding_files)) {
				// if content is file & readable, add to array
				if(is_file($path) && is_readable($path)) {
					// get file data
					$allFiles[] = $path;
				// if content is a directory and readable, add path and name
				} else if(!is_link($path) && is_dir($path) && is_readable($path)) {
					// recursive callback to open new directory
					$allFiles = scanDirectories($path, $allFiles);
				}
			}
		}
		return $allFiles;
	}

	if(isset($_GET['quit']) && $_GET['quit'] == "yes") {
		unset($_SESSION['fxn_admin']);
	} else if(isset($_SESSION['fxn_admin']) && isset($_GET['download']) && $_GET['download'] == "csv") {
		// Get data
		$transaction = "HASH,TIMESTAMP,DOWNLOAD TIMES,INV_ID,COST,FILE,EMAIL,PAID\n".str_replace("~", ",", file_get_contents(dirname(__FILE__)."/archive/transaction.log"));
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=transaction.csv");
		header("Pragma: no-cache");
		header("Expires: 0");
		die($transaction);
	} else if(isset($_SESSION['fxn_admin']) && isset($_GET['download']) && $_GET['download'] == "txt") {
		// Get data
		$archive_dir = dirname(__FILE__)."/archive";
		$files = scanDirectories($archive_dir);
		sort($files);
		header("Content-type: text/txt");
		header("Content-Disposition: attachment; filename=list_url.txt");
		header("Pragma: no-cache");
		header("Expires: 0");
		$archive_url = $script_url."archive";
		foreach($files as $file) {
			echo str_replace($archive_dir, $archive_url, $file)."\r\n";
		}
		die();
	}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<title><?php echo __("SellFileEasy"); ?></title>
		<link rel="stylesheet" href="<?php echo $script_url;?>css/stat.css" type="text/css" media="all" />
	</head>
	<body>
		<?php if(!isset($_SESSION['fxn_admin']) && (!isset($_POST['login']) || !isset($_POST['pass']) || $_POST['login'] != $stat_login || $_POST['pass'] != $stat_pass)): ?>
			<div id="login_form">
				<div></div>
				<form action="" method="post">
					<span><?php echo __("Login"); ?>:</span> <input type="text" name="login" value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login'], ENT_QUOTES, "UTF-8") : ""?>" />
					<br />
					<span><?php echo __("Password"); ?>:</span> <input type="password" name="pass" value="" />
					<br />
					<input type="submit" name="submit" value="<?php echo __("Enter"); ?>" />
					<br style="clear: both;" />
				</form>
			</div>
		<?php else: ?>
			<div id="stat">
				<div id="top_title"><?php echo __("Statistics"); ?></div>
				<div id="conteiner">
					<div align="right"><a href="?quit=yes"><?php echo __("Quit"); ?></a></div>
					<div id="top_actions"><a href="?download=txt" target="_blank"><?php echo __("Download URL list"); ?></a> | <a href="?download=csv" target="_blank"><?php echo __("Download CSV"); ?></a> | <a href="?clear=yes" onclick="return confirm('<?php echo __("Are you sure?"); ?>');"><font color="ff3333"><?php echo __("Clear ALL!!!"); ?></font></a></div>
					<br />
					<?php
						$_SESSION['fxn_admin'] = true;
						if(isset($_POST['action']) && $_POST['action'] == 'reset') {
							$transaction = file_get_contents(dirname(__FILE__)."/archive/transaction.log");
							if(preg_match("/".$_POST['md5']."~.+~.+~.+~.+~.+~.+~.+/i", $transaction, $match)) {
								$newdata = $data = explode("~", $match[0]);
								$newdata[1] = time();
								$newdata[2] = $download_times;
								file_put_contents(dirname(__FILE__)."/archive/transaction.log", str_replace(implode("~", $data), implode("~", $newdata), $transaction));
							}
						}
						if(isset($_POST['action']) && $_POST['action'] == 'delete') {
							$transaction = file_get_contents(dirname(__FILE__)."/archive/transaction.log");
							if(preg_match("/".$_POST['md5']."~.+~.+~.+~.+~.+~.+~.+\s/i", $transaction, $match)) {
								$data = explode("~", $match[0]);
								file_put_contents(dirname(__FILE__)."/archive/transaction.log", str_replace(implode("~", $data), "", $transaction));
							}
						}
						if(isset($_GET['clear']) && $_GET['clear'] == 'yes') {
							file_put_contents(dirname(__FILE__)."/archive/transaction.log", "");
						}
						$transactions = file(dirname(__FILE__)."/archive/transaction.log");
						$transactions = array_reverse($transactions);
						?>
							<table width="100%">
								<tr class='row_1'><th><?php echo __("Begin time"); ?></th><th><?php echo __("Donload link"); ?></th><th><?php echo __("Downloads left"); ?></th><th><?php echo __("Cost"); ?></th><th><?php echo __("Email"); ?></th><th><?php echo __("Paid by"); ?></th><th><?php echo __("Reset / Delete"); ?></th></tr>
						<?php
						foreach($transactions as $key => $val) {
							if(!isset($_GET['p']) && $key < 15 || $_GET['p']*15 <= $key && ($_GET['p']*15 + 15) > $key) {
								$data = explode("~", $val);
								$url = $script_url."archive/".$data[5]."?hash=".$data[0];
								$md5 = $data[0];
								$data[0] = date("Y-m-d h:i:s", $data[1]);
								$data[1] = "<a href='".$url."'>".__("Download link")."</a>";
								$data[2] = ($data[2] > 0 ? "<i>".$data[2]."</i>" : $data[2]);
								unset($data[3]);
								$data[4] = $data[4]." ".$download_currency_name;
								unset($data[5]);
								$data[6] = "<a href='mailto:".trim($data[6])."'>".trim($data[6])."</a>";
								$data[7] = ucfirst($data[7]);
								$data[8] = "<form action='' method='post'><input onmousedown='document.getElementById(\"action".$key."\").value = \"reset\";' type='image' name='reset' src='".$images_url."/reset.png'/>
												<input type='image' onmousedown='document.getElementById(\"action".$key."\").value = \"delete\";' name='delete' src='".$images_url."/delete.png'/>
												<input type='hidden' name='md5' value='".$md5."'/>
												<input type='hidden' id='action".$key."' name='action' value=''/></form>";
								echo "<tr class='row_".ceil($key % 2)."'><td>".implode("</td><td>", $data)."</td></tr>";
							}
						}
					?>
					</table>
					<br />
					<div align="right"><?php echo __("Powered by"); ?> <a href="http://find-xss.net" target="_black">Find-XSS.net</a> , <?php echo __("Design by"); ?> <a href="http://kasyanov.info" target="_black">Kasyanov.info</a></div>
					<a href="?p=<?php echo isset($_GET['p']) && $_GET['p'] > 1 ? ($_GET['p'] - 1) : 0; ?>"><<</a> Page <?php echo isset($_GET['p']) ? (intval($_GET['p']) + 1) : 1; ?> / <?php echo ceil(count($transactions)/15); ?> <a href="?p=<?php echo (!isset($_GET['p']) || count($transactions)/15 < 1) ? 1 : (isset($_GET['p']) ? ($_GET['p'] + 1) : 1); ?>">>></a>
				</div>
			<?php endif; ?>
		</div>
	</body>
</html>

