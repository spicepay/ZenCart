<?php

class spicepay extends base
{
  public $code;
  public $title;
  public $description;
  public $sort_order;
  public $enabled;

  private $app_id;
  private $api_key;
  // private $api_secret;
  private $test_mode;

  function spicepay()
  {
    $this->code             = 'spicepay';
    $this->title            = MODULE_PAYMENT_SPICEPAY_TEXT_TITLE;
    $this->description      = MODULE_PAYMENT_SPICEPAY_TEXT_DESCRIPTION;
    $this->app_id           = MODULE_PAYMENT_SPICEPAY_APP_ID;
    $this->api_key          = MODULE_PAYMENT_SPICEPAY_API_KEY;
    // $this->api_secret       = MODULE_PAYMENT_SPICEPAY_API_SECRET;
    // $this->receive_currency = MODULE_PAYMENT_SPICEPAY_RECEIVE_CURRENCY;
    $this->sort_order       = MODULE_PAYMENT_SPICEPAY_SORT_ORDER;
    // $this->testMode         = ((MODULE_PAYMENT_SPICEPAY_TEST == 'True') ? true : false);
    $this->enabled          = ((MODULE_PAYMENT_SPICEPAY_STATUS == 'True') ? true : false);
  }

  function javascript_validation()
  {
    return false;
  }

  function selection()
  {
    return array('id' => $this->code, 'module' => $this->title);
  }

  function pre_confirmation_check()
  {
    return false;
  }

  function confirmation()
  {
    return false;
  }

  function process_button()
  {
    return false;
  }

  function before_process()
  {
    return false;
  }

  function after_process()
  {
    global $insert_id, $db, $order;

    $info = $order->info;

    $configuration = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key='STORE_NAME' limit 1");
    $products = $db->Execute("select oc.products_id, oc.products_quantity, pd.products_name from " . TABLE_ORDERS_PRODUCTS . " as oc left join " . TABLE_PRODUCTS_DESCRIPTION . " as pd on pd.products_id=oc.products_id  where orders_id=" . intval($insert_id));

    $description = array();
    while (!$products->EOF) {
      $description[] = $products->fields['products_quantity'] . ' × ' . $products->fields['products_name'];

      $products->MoveNext();
    }

    // $callback = zen_href_link('spicepay_callback.php', $parameters='', $connection='NONSSL', $add_session_id=true, $search_engine_safe=true, $static=true );
    $total_price_vcs=number_format($info['total'], 2, '.', '');

    // $params = array(
    //   'order_id'         => $insert_id,
    //   'price'            => number_format($info['total'], 2, '.', ''),
    //   'currency'         => $info['currency'],
    //   'receive_currency' => MODULE_PAYMENT_SPICEPAY_RECEIVE_CURRENCY,
    //   'callback_url'     => $callback . "?token=" . MODULE_PAYMENT_SPICEPAY_CALLBACK_SECRET,
    //   'cancel_url'       => zen_href_link('index'),
    //   'success_url'      => zen_href_link('checkout_success'),
    //   'title'            => $configuration->fields['configuration_value'] . ' Order #' . $insert_id,
    //   'description'      => join($description, ', ')
    // );

    require_once(dirname(__FILE__) . "/SpicePay/init.php");
    require_once(dirname(__FILE__) . "/SpicePay/version.php");

    $order = \SpicePay\Merchant\Order::createOrFail($params, array(), array(
      'app_id' => MODULE_PAYMENT_SPICEPAY_APP_ID,
      'api_key' => MODULE_PAYMENT_SPICEPAY_API_KEY,
      // 'api_secret' => MODULE_PAYMENT_SPICEPAY_API_SECRET,
      // 'environment' => MODULE_PAYMENT_SPICEPAY_TEST == "True" ? 'sandbox' : 'live',
      'user_agent' => 'SpicePay - ZenCart Extension v' . SPICEPAY_ZENCART_EXTENSION_VERSION));

    $_SESSION['cart']->reset(true);

$form=<<<HTML
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<form action="https://www.spicepay.com/p.php" method="POST" id="form_submit">
<input type="hidden" name="amountUSD" value="{$total_price_vcs}">
<input type="hidden" name="orderId" value="{$insert_id}">
<input type="hidden" name="siteId" value="{$this->app_id}">
<input type="hidden" name="language" value="en">
<input type="submit" value="Pay"></form>

<style>
#form_submit {
  display: none;
}
</style>

<script>
jQuery( document ).ready(function() {
    jQuery( "#form_submit" ).submit();
});
</script>
HTML;

echo $form;

    return false;
  }

  function check()
  {
      global $db;

      if (!isset($this->_check)) {
          $check_query  = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_SPICEPAY_STATUS'");
          $this->_check = $check_query->RecordCount();
      }

      return $this->_check;
  }

  function install()
  {
    global $db, $messageStack;

    if (defined('MODULE_PAYMENT_SPICEPAY_STATUS')) {
      $messageStack->add_session('SpicePay module already installed.', 'error');
      zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=spicepay', 'NONSSL'));

      return 'failed';
    }

    $callbackSecret = md5('zencart_' . mt_rand());

    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable SpicePay Module', 'MODULE_PAYMENT_SPICEPAY_STATUS', 'False', 'Enable the SpicePay bitcoin plugin?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('SpicePay APP ID', 'MODULE_PAYMENT_SPICEPAY_APP_ID', '0', 'Your SpicePay APP ID', '6', '0', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('SpicePay API Key', 'MODULE_PAYMENT_SPICEPAY_API_KEY', '0', 'Your SpicePay API Key', '6', '0', now())");
    // $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('SpicePay APP Secret', 'MODULE_PAYMENT_SPICEPAY_API_SECRET', '0', 'Your SpicePay API Secret', '6', '0', now())");
    // $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Receive Currency', 'MODULE_PAYMENT_SPICEPAY_RECEIVE_CURRENCY', 'BTC', 'Currency you want to receive when making withdrawal at SpicePay. Please take a note what if you choose EUR or USD you will be asked to verify your business before making a withdrawal at SpicePay.', '6', '0', 'zen_cfg_select_option(array(\'EUR\', \'USD\', \'BTC\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_SPICEPAY_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '8', now())");
    // $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable test mode?', 'MODULE_PAYMENT_SPICEPAY_TEST', 'False', 'Enable test mode to test on sandbox.spicepay.com. Please note, that for test mode you must generate separate API credentials on sandbox.spicepay.com. API credentials generated on coingame.com will not work for test mode.', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Pending Order Status', 'MODULE_PAYMENT_SPICEPAY_PENDING_STATUS_ID', '" . intval(DEFAULT_ORDERS_STATUS_ID) .  "', 'Status in your store when SpicePay order status is pending.<br />(\'Pending\' recommended)', '6', '5', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Paid Order Status', 'MODULE_PAYMENT_SPICEPAY_PAID_STATUS_ID', '2', 'Status in your store when SpicePay order status is paid.<br />(\'Processing\' recommended)', '6', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Invalid Order Status', 'MODULE_PAYMENT_SPICEPAY_INVALID_STATUS_ID', '2', 'Status in your store when SpicePay order status is invalid.<br />(\'Failed\' recommended)', '6', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Expired Order Status', 'MODULE_PAYMENT_SPICEPAY_EXPIRED_STATUS_ID', '2', 'Status in your store when SpicePay order status is expired.<br />(\'Expired\' recommended)', '6', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Canceled Order Status', 'MODULE_PAYMENT_SPICEPAY_CANCELED_STATUS_ID', '2', 'Status in your store when SpicePay order status is canceled.<br />(\'Canceled\' recommended)', '6', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Refunded Order Status', 'MODULE_PAYMENT_SPICEPAY_REFUNDED_STATUS_ID', '2', 'Status in your store when SpicePay order status is refunded.<br />(\'Refunded\' recommended)', '6', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    // $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('Callback Secret Key (do not edit)', 'MODULE_PAYMENT_SPICEPAY_CALLBACK_SECRET', '$callbackSecret', '', '6', '6', now(), 'spicepay_censorize')");
  }

  function remove()
  {
    global $db;
    $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key LIKE 'MODULE\_PAYMENT\_SPICEPAY\_%'");
  }

  function keys()
  {
    return array(
      'MODULE_PAYMENT_SPICEPAY_STATUS',
      'MODULE_PAYMENT_SPICEPAY_APP_ID',
      'MODULE_PAYMENT_SPICEPAY_API_KEY',
      // 'MODULE_PAYMENT_SPICEPAY_API_SECRET',
      // 'MODULE_PAYMENT_SPICEPAY_RECEIVE_CURRENCY',
      'MODULE_PAYMENT_SPICEPAY_SORT_ORDER',
      // 'MODULE_PAYMENT_SPICEPAY_TEST',
      'MODULE_PAYMENT_SPICEPAY_PENDING_STATUS_ID',
      'MODULE_PAYMENT_SPICEPAY_PAID_STATUS_ID',
      'MODULE_PAYMENT_SPICEPAY_INVALID_STATUS_ID',
      'MODULE_PAYMENT_SPICEPAY_EXPIRED_STATUS_ID',
      'MODULE_PAYMENT_SPICEPAY_CANCELED_STATUS_ID',
      'MODULE_PAYMENT_SPICEPAY_REFUNDED_STATUS_ID',
      // 'MODULE_PAYMENT_SPICEPAY_CALLBACK_SECRET'
    );
  }
}

function spicepay_censorize($value) {
  return "(hidden for security reasons)";
}
