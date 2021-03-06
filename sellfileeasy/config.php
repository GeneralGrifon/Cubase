<?php
	//Localization
	require_once(dirname(__FILE__)."/lang/russian.php");

	//Download available in minutes
	$download_minutes = 120;
	//Download times available
	$download_times = 1;
	//Download currency
	$download_currency_name = __("RUB");
	//Script URL
	$script_url = "http://alexxgriffon.tk/sellfileeasy/";
	//Send email to user from(website email)
	$fxn_website_email = "Support <sashk.only@gmail.com>";
	//Buttom copyright row
	$buttom_copyright = "БЛА БЛА БЛА";
	//Is Nginx
	$nginx = false;
	//Statistics Login
	$stat_login = "login"; //Change
	//Statistics Password
	$stat_pass = "vg345v35434vtv"; //Change

	//ROBOKASSA uncomment to use, or comment // to disable
	require_once(dirname(__FILE__)."/robokassa/config.php");

	//Z-Payment uncomment to use, or comment // to disable
	require_once(dirname(__FILE__)."/z-payment/config.php");

	//2CheckOut uncomment to use, or comment // to disable
	require_once(dirname(__FILE__)."/2checkout/config.php");

	//Interkassa uncomment to use, or comment // to disable
	require_once(dirname(__FILE__)."/interkassa/config.php");

	require_once(dirname(__FILE__)."/qiwi/config.php");

	//Paypal uncomment to use, or comment // to disable
	//require_once(dirname(__FILE__)."/paypal/config.php");

	//Localization
	function __($text) {
		global $lang;
		return isset($lang[md5($text)]) ? $lang[md5($text)] : $text;
	}

	//Fix URL
	$script_url = substr($script_url, -1, 1) == "/" ? $script_url : $script_url."/";

	//Images url
	$images_url = $script_url."images";

?>
