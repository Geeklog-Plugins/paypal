<?php
// +--------------------------------------------------------------------------+
// | PayPal Plugin - geeklog CMS                                              |
// +--------------------------------------------------------------------------+
// | recurring_payment.php                                                    |
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
require_once '../lib-common.php';

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

session_start();

require_once ("recurring/paypalfunctions.php");

// ==================================
// PayPal Express Checkout Module
// ==================================

//'------------------------------------
//' The paymentAmount is the total value of 
//' the shopping cart, that was set 
//' earlier in a session variable 
//' by the shopping cart page
//'------------------------------------

//$paymentAmount = $_POST["Payment_Amount"];
//$paymentAmount = $_SESSION["Payment_Amount"];
$_SESSION["Payment_Amount"] = 100;

$_SESSION["BILLINGDESCRIPTION"] = 'Premium member';
$_SESSION["BILLINGPERIOD"] = 'Month'; // Day, Week, SemiMonth, Month, Year. For SemiMonth, billing is done on the 1st and 15th of each month.
$_SESSION["BILLINGFREQUENCY"] = 12; //The combination of billing frequency and billing period must be less than or equal to one year. For example, if the billing cycle is Month, the maximum value for billing frequency is 12. Similarly, if the billing cycle is Week, the maximum value for billing frequency is 52. Note If the billing period is SemiMonth, the billing frequency must be 1.
$_SESSION["BILLINGAMT"] = 20; //Billing amount for each billing cycle during this payment period. This amount does not include shipping and tax amounts.

//'------------------------------------
//' The currencyCodeType and paymentType 
//' are set to the selections made on the Integration Assistant 
//'------------------------------------
$_SESSION["currencyCodeType"] = $currencyCodeType = "EUR";
$paymentType = "Sale";
#$paymentType = "Authorization";
#$paymentType = "Order";

//'------------------------------------
//' The returnURL is the location where buyers return to when a
//' payment has been succesfully authorized.
//'
//' This is set to the value entered on the Integration Assistant 
//'------------------------------------
$returnURL = $_PAY_CONF['site_url'] . '/recurring/review.php';

//'------------------------------------
//' The cancelURL is the location buyers are sent to when they hit the
//' cancel button during authorization of payment during the PayPal flow
//'
//' This is set to the value entered on the Integration Assistant 
//'------------------------------------
$cancelURL = $_PAY_CONF['site_url'] . '/index.php?mode=cancel';

//'------------------------------------
//' Calls the SetExpressCheckout API call
//'
//' The CallShortcutExpressCheckout function is defined in the file PayPalFunctions.php,
//' it is included at the top of this file.
//'-------------------------------------------------
$resArray = CallShortcutExpressCheckout ($paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL);

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

	$display .= "SetExpressCheckout API call failed. ";
	$display .= "Detailed Error Message: " . $ErrorLongMsg;
	$display .= "Short Error Message: " . $ErrorShortMsg;
	$display .= "Error Code: " . $ErrorCode;
	$display .= "Error Severity Code: " . $ErrorSeverityCode;
}

$display .= PAYPAL_siteFooter();

COM_output($display);

?>