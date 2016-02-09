<?php
// +--------------------------------------------------------------------------+
// | PayPal Plugin 1.6 - geeklog CMS                                              |
// +--------------------------------------------------------------------------+
// | buy_now.php                                                              |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2010-2014 by the following authors:                        |
// |                                                                          |
// | Authors: ::Ben - cordiste AT free DOT fr                                 |
// +--------------------------------------------------------------------------+
// |                                                                          |
// | This program is free software; you can redistribute it and/or            |
// | modify it under the terms of the GNU General Public License              |
// | as published by the Free Software Foundation; either version 2           |
// | of the License, or (at your option) any later version.                   |
// |                                                                          |
// | This program is distributed in the hope that it will be useful,          |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with this program; if not, write to the Free Software Foundation,  |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.          |
// |                                                                          |
// +--------------------------------------------------------------------------+

/**
 * require core geeklog code
 */
require_once '../lib-common.php';

// Incoming variable filter
$vars = array('item_number' => 'number',
              'amount' => 'text',
			  'shipping' => 'number'
			 );
paypal_filterVars($vars, $_POST);

/* Ensure sufficient privs to read this page */
paypal_access_check('paypal.user');


$valid_process = true;
$item_id = $_POST['item_number'];
$item_price = $_POST['amount'];
$paypalURL = 'https://' . $_PAY_CONF['paypalURL'] . '/cgi-bin/webscr?cmd=_xclick';


/* MAIN */

$display .= PAYPAL_siteHeader();
$display .= paypal_user_menu();

session_start();
$_SESSION["user_id"] = $_USER['uid'];
$_SESSION["item_id"] = $_POST['item_number'];

$A = DB_fetchArray(DB_query("SELECT * FROM {$_TABLES['paypal_products']} WHERE id = '{$item_id}' LIMIT 1"));

if ($A['type'] == 'recurrent') {

	require_once ($_CONF['path'] . 'plugins/paypal/proversion/paypalfunctions.php');

	$_SESSION["group_id"] = $A['add_to_group'];
	$_SESSION["Payment_Amount"] = PAYPAL_productPrice ($A);
	$_SESSION["BILLINGDESCRIPTION"] = $A['name'];
	$_SESSION["BILLINGPERIOD"] = $A['duration_type']; // Day, Week, SemiMonth, Month, Year. For SemiMonth, billing is done on the 1st and 15th of each month.
	$_SESSION["BILLINGFREQUENCY"] = $A['duration']; //The combination of billing frequency and billing period must be less than or equal to one year. For example, if the billing cycle is Month, the maximum value for billing frequency is 12. Similarly, if the billing cycle is Week, the maximum value for billing frequency is 52. Note If the billing period is SemiMonth, the billing frequency must be 1.
	$_SESSION["BILLINGAMT"] = $A['billingamt']; //Billing amount for each billing cycle during this payment period. This amount does not include shipping and tax amounts.
	//$_SESSION["INITAMT"] = PAYPAL_productPrice($A);
	$_SESSION["currencyCodeType"] = $_PAY_CONF['currency'];
	$_SESSION["paymentType"] = "Sale"; //Sale, Authorization, Order;

	//'------------------------------------
	//' The returnURL is the location where buyers return to when a
	//' payment has been succesfully authorized.
	//'------------------------------------
	$returnURL = $_PAY_CONF['site_url'] . '/recurring-payment/review.php';

	//'------------------------------------
	//' The cancelURL is the location buyers are sent to when they hit the
	//' cancel button during authorization of payment during the PayPal flow
	//'------------------------------------
	$cancelURL = $_PAY_CONF['site_url'] . '/index.php?mode=cancel';

	//'------------------------------------
	//' Calls the SetExpressCheckout API call
	//'-------------------------------------------------
	$resArray = CallShortcutExpressCheckout ($_SESSION["Payment_Amount"], $_SESSION["currencyCodeType"], $_SESSION["paymentType"], $returnURL, $cancelURL);

	$ack = strtoupper($resArray["ACK"]);
	if($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING")
	{
		RedirectToPayPal ( $resArray["TOKEN"] );
	} 
	else  
	{
		//Display a user friendly Error on the page using any of the following error information returned by PayPal
		$ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
		$ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
		$ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
		$ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);

		$display .= "<p>SetExpressCheckout API call failed.</p>";
		if( $_SESSION['curl_error_no'] != '' ) $ErrorCode = $_SESSION['curl_error_no']; 
		if( $_SESSION['curl_error_msg'] != '' ) $ErrorLongMsg = $_SESSION['curl_error_msg']; 
		$display .= "<p>Detailed Error Message: " . $ErrorLongMsg;
		$display .= "</p><p>Short Error Message: " . $ErrorShortMsg;
		$display .= "</p><p>Error Code: " . $ErrorCode;
		$display .= "</p><p>Error Severity Code: " . $ErrorSeverityCode . '</p>';
	}
    
} else {

	if ($item_price <> $A['price'] || !SEC_hasAccess2($A) || $A['active'] != '1') $valid_process = false;

	$PAYPAL_POST['business'] = $_PAY_CONF['receiverEmailAddr'];
	$PAYPAL_POST['item_name'] = $A['name'];
	$PAYPAL_POST['custom'] = $_USER['uid'];
	$PAYPAL_POST['item_number'] = $A['id'];
	$PAYPAL_POST['amount'] = $A['price'];
	$PAYPAL_POST['no_note'] = '1';
	$PAYPAL_POST['currency_code'] = $_PAY_CONF['currency'];
	$PAYPAL_POST['return'] = $_PAY_CONF['site_url'] . '/index.php?mode=endTransaction';
	$PAYPAL_POST['notify_url'] = $_PAY_CONF['site_url'] . '/ipn.php';
	//TODO how to choose shipping cost? Do not use Buy now button...
	$PAYPAL_POST['handling_cart'] = $_POST['shipping'];
	$PAYPAL_POST['rm'] = '2';
	$PAYPAL_POST['cbt'] = $LANG_PAYPAL_1['cbt'] . ' ' . $_CONF['site_name'];
	$PAYPAL_POST['cancel_return'] = $_PAY_CONF['site_url'] . '/index.php?mode=cancel';
	$PAYPAL_POST['image_url'] = $_PAY_CONF['image_url'];
	$PAYPAL_POST['cpp_header_image'] = $_PAY_CONF['cpp_header_image'];
	$PAYPAL_POST['cpp_headerback_color'] = $_PAY_CONF['cpp_headerback_color'];
	$PAYPAL_POST['cpp_headerborder_color'] = $_PAY_CONF['cpp_headerborder_color'];
	$PAYPAL_POST['cpp_payflow_color'] = $_PAY_CONF['cpp_payflow_color'];
	$PAYPAL_POST['cs'] = $_PAY_CONF['cs'];
	$PAYPAL_POST['charset'] = $_CONF['default_charset'];


	foreach ($PAYPAL_POST as $key => $value) {
		$value = urlencode(stripslashes($value));
		$req .= "&$key=$value";
	}

	if ($valid_process) {
		header('Location:'. $paypalURL .$req);
		exit;
	} else {
		$display .= $jcart['text']['checkout_error'];
	}
}

$display .= PAYPAL_siteFooter();

COM_output($display);

?>