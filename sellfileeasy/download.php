<?php

	require_once(dirname(__FILE__)."/config.php");

	class FXNAntileech {
		var $fileName;
		var $fileTime;
		var $storedFileName;
		var $contentSize;
		var $storageDir;
		var $storedFileSize;
		var $httpContentDisposition;
		var $httpContentType;
		var $bufSize;

		function send($file_url) {
			$UserBrowser = '';
			if(preg_match('/Opera/i', $_SERVER['HTTP_USER_AGENT'])) {
				$UserBrowser = "Opera";
			} else if(preg_match('/MSIE/i', $_SERVER['HTTP_USER_AGENT'])) {
				$UserBrowser = "IE";
			}
			$this->httpContentType = ($UserBrowser == 'IE' || $UserBrowser == 'Opera') ? 'application/octetstream' : 'application/octet-stream';
			$this->httpContentDisposition = "attachment";

			$this->fileName = trim(dirname(__FILE__)."/archive/".$file_url);
			$this->fileTime = filemtime($this->fileName);
			$this->storedFileSize = filesize($this->fileName);
			$sf = explode('/', $this->fileName);
			$sf = $sf[count($sf)-1];
			$this->storedFileName = $sf;
			$fh = fopen($this->fileName, "rb");
			if(isset($_SERVER["HTTP_RANGE"])) {
				preg_match ("/bytes=(\d+)-/", $_SERVER["HTTP_RANGE"], $os);
				$this->offset = intval($os[1]);
				$this->contentSize = $this->storedFileSize - $this->offset;
				fseek ($fh, $this->offset);
				$this->http206();
			} else {
				$this->contentSize = $this->storedFileSize;
				$this->http200();
			}
			if($nginx){
				header("X-Accel-Redirect: ".$this->fileName);
			} else {
				$contents='';
				$this->bufSize = 8192;
				while(!feof($fh) && !connection_status()) {
					$contents = fread ($fh, $this->bufSize);
					echo $contents;
					if($this->contentSize < $this->bufSize) $this->contentSize=0;
					else $this->contentSize -= $this->bufSize;
				}
			}
			fclose ($fh);
		}

		function http200() {
			@ob_end_clean();
			header ("HTTP/1.1 200 OK");
			header ("Date: " . $this->getGMTDateTime ());
			header ("X-Powered-By: PHP/" . phpversion());
			header ("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
			header ("Last-Modified: " . $this->getGMTDateTime ($this->fileTime) );
			header ("Cache-Control: None");
			header ("Pragma: no-cache");
			header ("Accept-Ranges: bytes");
			header ("Content-Disposition: " . $this->httpContentDisposition . "; filename=\"" . $this->storedFileName  . "\"");
			header ("Content-Type: " . $this->httpContentType);
			if($this->httpContentDescription) header ("Content-Description: " . $this->httpContentDescription );
			header ("Content-Length: " . $this->contentSize);
			header ("Proxy-Connection: close");
			header ("");
		}

		function http206() {
			$p1 = $this->storedFileSize - $this->contentSize;
			$p2 = $this->storedFileSize - 1;
			$p3 = $this->storedFileSize;

			header ("HTTP/1.1 206 Partial Content");
			header ("Date: " . $this->getGMTDateTime ());
			header ("X-Powered-By: PHP/" . phpversion());
			header ("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
			header ("Last-Modified: " . $this->getGMTDateTime ($this->fileTime) );
			header ("Cache-Control: None");
			header ("Pragma: no-cache");
			header ("Accept-Ranges: bytes");
			header ("Content-Disposition: " . $this->httpContentDisposition . "; filename=\"" . $this->storedFileName  . "\"");
			header ("Content-Type: " . $this->httpContentType);
			if($this->httpContentDescription) header ("Content-Description: " . $this->httpContentDescription );
			header ("Content-Range: bytes " . $p1 . "-" . $p2 . "/" . $p3);
			header ("Content-Length: " . $this->contentSize);
			header ("Proxy-Connection: close");
			header ("");
		}

		function getGMTDateTime ($time=NULL) {
			$offset = date("O");
			$roffset = "";
			if($offset[0] == "+") {
				$roffset = "-";
			} else {
				$roffset = "+";
			}
			$roffset .= $offset[1].$offset[2];
			if (!$time)	{
				$time = Time();
			}
			return (date ("D, d M Y H:i:s", $time+$roffset*3600 ) . " GMT");
		}

		function check_file($file) {
			if(isset($file) && !empty($file)) {
				return file_exists(dirname(__FILE__)."/archive/".$file);
			}
			return false;
		}

		function file_cost($file) {
			if($this->check_file($file)) {
				$cost = explode("/", $file, 1);
				return floatval($cost[0]);
			}
			return "Error";
		}

		function file_name($file) {
			if($this->check_file($file)) {
				$name = explode("/", $file, 2);
				return htmlspecialchars($name[1], ENT_QUOTES, "UTF-8");
			}
			return "Error";
		}
	}

	//Create object
	$FXNAntileech = new FXNAntileech();

	if(isset($_GET['hash']) && preg_match("/[a-z0-9]+/i", $_GET['hash'])) {
		if(isset($_GET['check'])) {
			$transaction = file_get_contents(dirname(__FILE__)."/archive/transaction.log");
			if(preg_match("/".$_GET['hash']."~.+~.+~.+~.+~.+~.+~.+/i", $transaction, $match)) {
				$data = explode("~", $match[0]);
				$download_minutes_left = $data[1] + $download_minutes * 60 - time();
				$download_minutes_left = $download_minutes_left < 0 ? 0 : floor($download_minutes_left / 60);
				$download_times_left = intval($data[2]);
			}
		} else {
			$transaction = file_get_contents(dirname(__FILE__)."/archive/transaction.log");
			if(preg_match("/".$_GET['hash']."~.+~.+~.+~.+~.+~.+~.+/i", $transaction, $match)) {
				$newdata = $data = explode("~", $match[0]);
				$head = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body>';
				$end = "</body></html>";
				if($download_minutes > 0 && time() < ($newdata[1] + ($download_minutes * 60))) {
					if($newdata[2] > 0 && $download_times > 0) {
						$newdata[2]--;
					} else if($download_times > 0) {
						die($head."<br /><br /><div align='center'>".__("Error, download limit")."</div>".$end);
					}
					if($download_times > 0) {
						$transaction = file_get_contents(dirname(__FILE__)."/archive/transaction.log");
						file_put_contents(dirname(__FILE__)."/archive/transaction.log", str_replace(implode("~", $data), implode("~", $newdata), $transaction));
					}
					if(isset($_GET['file']) && $newdata[5] == $_GET['file']) {
						$FXNAntileech->send($newdata[5]);
						die();
					} else {
						die($head."<br /><br /><div align='center'>".__("Error, file name")."</div>".$end);
					}
				} else {
					die($head."<br /><br /><div align='center'>".__("Error, time limit")."</div>".$end);
				}
				die();
			} else {
				die($head."<br /><br /><div align='center'>".__("Error token")."</div>".$end);
			}
		}
	}
?><!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<title><?php echo __("SellFileEasy"); ?></title>
		<link rel="stylesheet" href="<?php echo $script_url;?>css/download.css" type="text/css" media="all" />
	</head>
	<body>
		<?php if(isset($_GET['check'])): ?>
			<div id="email_form">
				<div id="top_title"><?php echo __("Statistics"); ?></div>
				<div id="conteiner">
					<br />
					<div><?php echo __("File name is"); ?>: <i><?php echo $FXNAntileech->file_name($_GET['file']); ?></i></div>
					<br />
					<div><?php echo __("Available time in minutes"); ?>: <?php echo ($download_minutes_left > 0 ? "<i>".$download_minutes_left."</i>" : ($download_minutes == 0 ? "<i>".__("unlimited")."</i>" : "<b>".__("expired")."</b>")); ?></div>
					<br />
					<div><?php echo __("Available downloads"); ?>: <?php echo ($download_times_left > 0 ? "<i>".$download_times_left."</i>" : ($download_times == 0 ? "<i>".__("unlimited")."</i>" : "<b>".__("expired")."</b>")); ?></div>
				</div>
			</div>
		<?php else: ?>
			<?php if(!isset($_POST['email']) || !isset($_POST['confirm']) || $_POST['email'] != $_POST['confirm'] || !preg_match("/.+@.+\..+/i", $_POST['email'])): ?>
				<div id="email_form">
					<div id="top_title"><?php echo __("Order page"); ?></div>
					<div id="conteiner">
						<form action="" method="post">
							<div class="alert"><?php if(isset($_POST['email'])):?><?php echo __("Wrong email, or email not match"); ?><?php endif;?></div>
							<br />
							<div><?php echo __("File name is"); ?>: <label><?php echo $FXNAntileech->file_name($_GET['file']); ?></label></div>
							<br />
							<div><?php echo __("File cost is"); ?>: <i><?php echo $FXNAntileech->file_cost($_GET['file']); ?></i> <label><?php echo $download_currency_name;?></label></div>
							<!--<br />
							<div><label><?php echo __("Link will be available for"); ?> <i><?php echo ($download_minutes > 0 ? $download_minutes." ".__("minutes") : __("unlimited time"));?></i>, <?php echo __("and"); ?> <i><?php echo ($download_times > 0 ? $download_times : __("unlimited"));?></i> <?php echo __("downloads"); ?>.</label></div>-->
							<br />
							<div><?php echo __("Link for downloading will be sent to the e-mail after payment"); ?></div>
							<br />
							<span><?php echo __("Email"); ?>:</span> <input type="text" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, "UTF-8") : ""?>" />
							<br />
							<span><?php echo __("Confirm"); ?>:</span> <input type="text" name="confirm" value="<?php echo isset($_POST['confirm']) ? htmlspecialchars($_POST['confirm'], ENT_QUOTES, "UTF-8") : ""?>" />
							<br />
							<input type="submit" name="submit" value="<?php echo __("Submit"); ?>" />
							<br style="clear: both;" />
						</form>
					</div>
				</div>
			<?php else: ?>
				<?php
					//Email to send link
					$email = htmlspecialchars($_POST['email'], ENT_QUOTES, "UTF-8");
					//ROBOKASSA cost
					$out_summ = number_format($FXNAntileech->file_cost($_GET['file']), 2, '.', '');
					//ROBOKASSA order number
					$inv_id = time();
					//ROBOKASSA item specification
					$shp_file = htmlspecialchars($_GET['file'], ENT_QUOTES, "UTF-8");
				?>
				<div id="robokassa" align="center">
					<div id="top_title"><?php echo __("Choice payment gateway"); ?></div>
					<div id="conteiner">
						<?php if(isset($mrh_login)): ?>
							<?php
								//ROBOKASSA sign
								$crc  = md5($mrh_login.":".$out_summ.":".$inv_id.":".$mrh_pass1.":Shp_email=".$email.":Shp_file=".$shp_file);
							?>
							<br />
							<br />
							<img src="<?php echo $images_url;?>/robokassa.png" border="0" alt="robokassa" />
							<br />
							<br />
							<script language=JavaScript src='https://merchant.roboxchange.com/Handler/MrchSumPreview.ashx?MrchLogin=<?php echo $mrh_login; ?>&OutSum=<?php echo $out_summ; ?>&InvId=<?php echo $inv_id; ?>&Shp_file=<?php echo $shp_file; ?>&Shp_email=<?php echo $email; ?>&IncCurrLabel=<?php echo $in_curr; ?>&Desc=<?php echo $inv_desc; ?>&SignatureValue=<?php echo $crc; ?>&Culture=<?php echo $culture; ?>&Encoding=<?php echo $encoding; ?>'></script>
						<?php endif; ?>
						<?php if(isset($IdShopZP)): ?>
							<?php
								//Z-Payment signs
								$crc_zp = md5($IdShopZP.$inv_id.$out_summ.$InitialZP);
								$crc_extend_zp = md5($IdShopZP.$inv_id.$out_summ.$InitialZP.$email.$shp_file);
							?>
							<br />
							<br />
							<img src="<?php echo $images_url;?>/z-payment.jpg" border="0" alt="z-payment" />
							<br />
							<br />
							<form id="pay" name="pay" method="post" action="https://z-payment.com/merchant.php">
								<input name="LMI_PAYMENT_NO" type="hidden" value="<?php echo $inv_id; ?>" />
								<input name="LMI_PAYMENT_AMOUNT" type="hidden" value="<?php echo $out_summ; ?>" />
								<input name="CLIENT_MAIL" type="hidden" value="<?php echo $email; ?>" />
								<input name="LMI_PAYMENT_DESC" type="hidden" value="<?php echo $zp_desc; ?>" />
								<input name="FILE_NAME" type="hidden" value="<?php echo $shp_file; ?>" />
								<input name="FILE_MAIL" type="hidden" value="<?php echo $email; ?>" />
								<input name="LMI_PAYEE_PURSE" type="hidden" value="<?php echo $IdShopZP; ?>" />
								<input name="ZP_SIGN" type="hidden" value="<?php echo $crc_zp; ?>">
								<input name="EXTENDED_SIGN" type="hidden" value="<?php echo $crc_extend_zp; ?>">
								<input type="submit" class="patmentbutton" value="Z-Payment" />
							</form>
						<?php endif; ?>
						<?php if(isset($sid)): ?>
							<?php
								//2checkout signs
								$checkout_sign = md5($sid.$inv_id.$out_summ.$co_password.$email.$shp_file);
							?>
							<br />
							<br />
							<img src="<?php echo $images_url;?>/2co_logo.png" border="0" alt="2checkout" />
							<br />
							<br />
							<!--<form id="pay" name="pay" method="post" action="https://sandbox.2checkout.com/checkout/purchase">-->
							<form id="pay" name="pay" method="post" action="https://www.2checkout.com/checkout/purchase">
								<input name="sid" type="hidden" value="<?php echo $sid; ?>" />
								<input name="li_0_price" type="hidden" value="<?php echo $out_summ; ?>" />
								<input name="li_0_email" type="hidden" value="<?php echo $email; ?>" />
								<input name="li_0_name" type="hidden" value="<?php echo $li_0_name; ?>" />
								<input name="li_0_file" type="hidden" value="<?php echo $shp_file; ?>" />
								<input name="li_0_product_id" type="hidden" value="<?php echo $inv_id; ?>" />
								<input name="li_0_type" type="hidden" value="product" />
								<input name="mode" type="hidden" value="2CO">
								<input name="li_0_checkout_sign" type="hidden" value="<?php echo $checkout_sign; ?>">
								<input type="submit" class="patmentbutton" value="2CheckOut" />
							</form>
						<?php endif; ?>
						<?php if(isset($ik_shop_id)): ?>
							<?php
								$checkout_sign = md5($ik_shop_id.$inv_id.$out_summ.$ik_secret_key.$email.$shp_file);
							?>
							<br />
							<br />
							<img src="<?php echo $images_url;?>/interkassa.png" border="0" alt="interkassa" />
							<br />
							<br />
							<form name='payment' method='post' action='https://sci.interkassa.com/' accept-charset='UTF-8'>
								<input type='hidden' name='ik_co_id' value='<?php echo $ik_shop_id;?>'>
								<input type='hidden' name='ik_pm_no' value='<?php echo $inv_id;?>'>
								<input type='hidden' name='ik_cur'   value='<?php echo $ik_currency;?>'>
								<input type='hidden' name='ik_am'    value='<?php echo $out_summ;?>'>
								<input type='hidden' name='ik_desc'  value='<?php echo $ik_desc;?>'>
								<input type='hidden' name='ik_x_email'  value='<?php echo $email;?>'>
								<input type='hidden' name='ik_x_shp_file'  value='<?php echo $shp_file;?>'>
								<input type="hidden" name="ik_x_mysign" value="<?php echo $checkout_sign; ?>">
								<input type="submit" name='process' class="patmentbutton" value="Interkassa" />
							</form>
						<?php endif; ?>
						<?php if(isset($business)): ?>
							<?php
								//paypal signs
								$paypal_sign = md5($business.$inv_id.$out_summ.$stat_pass.$email.$shp_file);
							?>
							<br />
							<br />
							<img src="<?php echo $images_url;?>/paypal.png" border="0" alt="paypal" />
							<br />
							<br />
							<!--<form action="https://sandbox.paypal.com/cgi-bin/webscr" method="post">-->
							<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
								<input type="hidden" name="cmd" value="_ext-enter">
								<input type="hidden" name="redirect_cmd" value="_xclick">
								<input type="hidden" name="business" value="<?php echo $business; ?>">
								<input type="hidden" name="item_name" value="<?php echo $shp_file; ?>">
								<input type="hidden" name="order_id" value="<?php echo $inv_id; ?>">
								<input type="hidden" name="currency_code" value="<?php echo $currency_code; ?>">
								<input type="hidden" name="amount" value="<?php echo $out_summ; ?>">
								<input type="hidden" name="invoice" value="<?php echo $email; ?>">
								<input type="hidden" name="custom" value="<?php echo $paypal_sign; ?>">
								<input type="hidden" name="notify_url" value="<?php echo $script_url."paypal/result.php";?>">
								<input type="submit" class="patmentbutton" value="PayPal" />
							</form>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		<div class="copyright"><?php echo $buttom_copyright; ?></div>
	</body>
</html>

