<?php
// +--------------------------------------------------------------------------+
// | PayPal Plugin 1.6 - geeklog CMS                                          |
// +--------------------------------------------------------------------------+
// | order_confirm.php                                                        |
// +--------------------------------------------------------------------------+
// |                                                                          |
// | Copyright (C) 2014 by the following authors:                             |
// |                                                                          |
// | Authors: Ben     -    ben AT geeklog DOT fr                              |
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
require_once '../../lib-common.php';

// take user back to the homepage if the plugin is not active
if (!in_array('paypal', $_PLUGINS) ) {
    $display .= COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

/* Ensure sufficient privs to read this page */
paypal_access_check('paypal.user');

$vars = array('msg' => 'text',
              'pid' => 'number',
              );
paypal_filterVars($vars, $_REQUEST);



//Main

$display .= PAYPAL_siteHeader();
$display .= paypal_user_menu();

require_once ($_CONF['path'] . 'plugins/paypal/proversion/paypalfunctions.php');
	
$finalPaymentAmount =  $_SESSION["Payment_Amount"];

/*
'------------------------------------
' Calls the DoExpressCheckoutPayment API call
'-------------------------------------------------
*/
if ( $finalPaymentAmount > 0 ) {

	$resArray1 = ConfirmPayment ( $finalPaymentAmount );

	$ack = strtoupper($resArray1["ACK"]); 

	if( $ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING" ) {
		$items[1] = $_SESSION["item_id"];
		$quantities[1] = 1;
		$item_price[1] = $_SESSION["Payment_Amount"];
		$name[1] = $_SESSION["BILLINGDESCRIPTION"];
		$display .= PAYPAL_handlePurchase($items, $quantities, $data, $name, $item_price,1,'complete',0,'','',$resArray1["PAYMENTINFO_0_TRANSACTIONTYPE"],$resArray1["PAYMENTINFO_0_PAYMENTTYPE"]);
		
		// Add user to group
		PAYPAL_addToGroup ($_SESSION["group_id"], $_USER['uid']);
	}
}

$resArray = CreateRecurringPaymentsProfile();
$ack = strtoupper($resArray["ACK"]);

if( $ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING" )
{
	//Record profileid : ActiveProfile, PendingProfile, ExpiredProfile, SuspendedProfile, CancelledProfile
	$recdate = date("Y-m-d H:i:s");
	DB_query("INSERT INTO {$_TABLES['paypal_recurrent']} SET profileid='{$resArray['PROFILEID']}', recdate='{$recdate}', status ='{$resArray['PROFILESTATUS']}', user_id = '{$_USER['uid']}', product_id = '{$_SESSION['item_id']}', group_id = '{$_SESSION["group_id"]}' ");
	
	$display .= "<p>{$LANG_PAYPAL_1['recurrent_has_been_set']} {$LANG_PAYPAL_1['will_pay']} <span style=\"border: 1px solid #DDD; background:#EEE; padding:5px;\">{$_SESSION["currencyCodeType"]} {$_SESSION["BILLINGAMT"]}</span> {$LANG_PAYPAL_1['every']} <span style=\"border: 1px solid #DDD; background:#EEE; padding:5px;\">{$_SESSION["BILLINGFREQUENCY"]} {$_SESSION["BILLINGPERIOD"]}</span></p>";
	
	if ( $finalPaymentAmount = 0 )PAYPAL_addToGroup ($_SESSION["group_id"], $_USER['uid']);
}
else  
{
	//Display a user friendly Error on the page using any of the following error information returned by PayPal
	$ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
	$ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
	$ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
	$ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
	
	$display .= "<p>GetExpressCheckoutDetails API call failed.";
	$display .= "</p><p>Detailed Error Message: " . $ErrorLongMsg;
	$display .= "</p><p>Short Error Message: " . $ErrorShortMsg;
	$display .= "</p><p>Error Code: " . $ErrorCode;
	$display .= "</p><p>Error Severity Code: " . $ErrorSeverityCode . '</p>';
}



$display .= PAYPAL_siteFooter();

COM_output($display);
		
?>
