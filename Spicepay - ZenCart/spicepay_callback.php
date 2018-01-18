<?php

ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

if (isset($_POST['paymentId']) && isset($_POST['orderId']) && isset($_POST['hash']) 
&& isset($_POST['paymentCryptoAmount']) && isset($_POST['paymentAmountUSD']) 
&& isset($_POST['receivedCryptoAmount']) && isset($_POST['receivedAmountUSD'])) {
        
		$paymentId = addslashes(filter_input(INPUT_POST, 'paymentId', FILTER_SANITIZE_STRING));
        $orderId = addslashes(filter_input(INPUT_POST, 'orderId', FILTER_SANITIZE_STRING));
        $hash = addslashes(filter_input(INPUT_POST, 'hash', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH));    
        $clientId = addslashes(filter_input(INPUT_POST, 'clientId', FILTER_SANITIZE_STRING));
        $paymentAmountBTC = addslashes(filter_input(INPUT_POST, 'paymentAmountBTC', FILTER_SANITIZE_NUMBER_INT));
        $paymentAmountUSD = addslashes(filter_input(INPUT_POST, 'paymentAmountUSD', FILTER_SANITIZE_STRING));
        $receivedAmountBTC = addslashes(filter_input(INPUT_POST, 'receivedAmountBTC', FILTER_SANITIZE_NUMBER_INT));
        $receivedAmountUSD = addslashes(filter_input(INPUT_POST, 'receivedAmountUSD', FILTER_SANITIZE_STRING));
        $status = addslashes(filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING));
        
        if(isset($_POST['paymentCryptoAmount']) && isset($_POST['receivedCryptoAmount'])) {
            $paymentCryptoAmount = addslashes(filter_input(INPUT_POST, 'paymentCryptoAmount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
            $receivedCryptoAmount = addslashes(filter_input(INPUT_POST, 'receivedCryptoAmount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
        }
        else {
            $paymentCryptoAmount = addslashes(filter_input(INPUT_POST, 'paymentAmountBTC', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
            $receivedCryptoAmount = addslashes(filter_input(INPUT_POST, 'receivedAmountBTC', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
        }

	unset($_POST);
	unset($_GET);

	require_once('includes/application_top.php');
	require_once("includes/modules/payment/SpicePay/init.php");
	require_once("includes/modules/payment/SpicePay/version.php");

	$secretCode = MODULE_PAYMENT_SPICEPAY_API_KEY;

	global $db;
	$order = $db->Execute("select orders_id from " . TABLE_ORDERS . " where orders_id = '" . intval($orderId) . "' limit 1");

	if (!$order || !$order->fields['orders_id']){
		die('Order #' . $orderId . ' does not exists');
	}	

	$spicepay_order = \SpicePay\Merchant\Order::findOrFail($orderId, array(), array(
		'app_id' => MODULE_PAYMENT_SPICEPAY_APP_ID,
		'api_key' => MODULE_PAYMENT_SPICEPAY_API_KEY,

		'user_agent' => 'SpicePay - ZenCart Extension v' . SPICEPAY_ZENCART_EXTENSION_VERSION
		));

	switch ($status) {
		case 'paid':
		$cg_order_status = MODULE_PAYMENT_SPICEPAY_PAID_STATUS_ID;
		break;
		case 'expired':
		$cg_order_status = MODULE_PAYMENT_SPICEPAY_EXPIRED_STATUS_ID;
		break;
		case 'invalid':
		$cg_order_status = MODULE_PAYMENT_SPICEPAY_INVALID_STATUS_ID;
		break;
		case 'canceled':
		$cg_order_status = MODULE_PAYMENT_SPICEPAY_CANCELED_STATUS_ID;
		break;
		case 'refunded':
		$cg_order_status = MODULE_PAYMENT_SPICEPAY_REFUNDED_STATUS_ID;
		break;
	}


	$hashString = $secretCode . $paymentId . $orderId . $clientId . $paymentCryptoAmount . $paymentAmountUSD . $receivedCryptoAmount . $receivedAmountUSD . $status;
	
	//echo md5($hashString).' - '. $hash; die();
			
	if (0 == strcmp(md5($hashString), $hash)) {
		//echo $cg_order_status."<br>".$orderId."<br>";
		echo 'OK';

		if ($cg_order_status)
		$db->Execute("update ". TABLE_ORDERS. " set orders_status = " . $cg_order_status . " where orders_id = ". $orderId);
	}

} else {
	echo 'fail';
}