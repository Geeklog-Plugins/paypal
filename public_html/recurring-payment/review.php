<?php
// +--------------------------------------------------------------------------+
// | PayPal Plugin 1.6 - geeklog CMS                                          |
// +--------------------------------------------------------------------------+
// | review.php                                                               |
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

/*==================================================================
 PayPal Express Checkout Call
 ===================================================================
*/
// Check to see if the Request object contains a variable named 'token'	
$token = "";
if (isset($_REQUEST['token']))
{
	$token = $_REQUEST['token'];
}

// If the Request object contains the variable 'token' then it means that the user is coming from PayPal site.	
if ( $token != "" )
{

	require_once ($_CONF['path'] . 'plugins/paypal/proversion/paypalfunctions.php');

	/*
	'------------------------------------
	' Calls the GetExpressCheckoutDetails API call
	'-------------------------------------------------
	*/

	$resArray = GetShippingDetails( $token );
	$ack = strtoupper($resArray["ACK"]);
	if( $ack == "SUCCESS" || $ack == "SUCESSWITHWARNING") 
	{
		$email 			    = $resArray["EMAIL"]; // ' Email address of payer.
		$payerId 			= $resArray["PAYERID"]; // ' Unique PayPal customer account identification number.
		$payerStatus		= $resArray["PAYERSTATUS"]; // ' Status of payer. Character length and limitations: 10 single-byte alphabetic characters.
		$salutation			= $resArray["SALUTATION"]; // ' Payer's salutation.
		$firstName			= $resArray["FIRSTNAME"]; // ' Payer's first name.
		$middleName			= $resArray["MIDDLENAME"]; // ' Payer's middle name.
		$lastName			= $resArray["LASTNAME"]; // ' Payer's last name.
		$suffix				= $resArray["SUFFIX"]; // ' Payer's suffix.
		$cntryCode			= $resArray["COUNTRYCODE"]; // ' Payer's country of residence in the form of ISO standard 3166 two-character country codes.
		$business			= $resArray["BUSINESS"]; // ' Payer's business name.
		$shipToName			= $resArray["SHIPTONAME"]; // ' Person's name associated with this address.
		$shipToStreet		= $resArray["SHIPTOSTREET"]; // ' First street address.
		$shipToStreet2		= $resArray["SHIPTOSTREET2"]; // ' Second street address.
		$shipToCity			= $resArray["SHIPTOCITY"]; // ' Name of city.
		$shipToState		= $resArray["SHIPTOSTATE"]; // ' State or province
		$shipToCntryName	= $resArray["SHIPTOCOUNTRYNAME"]; // ' Country code. 
		$shipToZip			= $resArray["SHIPTOZIP"]; // ' U.S. Zip code or other country-specific postal code.
		$addressStatus 		= $resArray["ADDRESSSTATUS"]; // ' Status of street address on file with PayPal   
		//$invoiceNumber		= $resArray["INVNUM"]; // ' Your own invoice or tracking number, as set by you in the element of the same name in SetExpressCheckout request .
		$phonNumber			= $resArray["PHONENUM"]; // ' Payer's contact telephone number. Note:  PayPal returns a contact telephone number only if your Merchant account profile settings require that the buyer enter one. 
	} 
	else  
	{
		//Display a user friendly Error on the page using any of the following error information returned by PayPal
		$ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
		$ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
		$ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
		$ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
		
		$display .= "GetExpressCheckoutDetails API call failed. ";
		$display .= "Detailed Error Message: " . $ErrorLongMsg;
		$display .= "Short Error Message: " . $ErrorShortMsg;
		$display .= "Error Code: " . $ErrorCode;
		$display .= "Error Severity Code: " . $ErrorSeverityCode;
	}
}

if ($_USER['uid'] > 1) {
    // Update user details
	$details['user_name'] = $shipToName;
	$details['user_street1'] = $shipToStreet;
	$details['user_street2'] = $shipToStreet2;
	$details['user_postal'] = $shipToZip;
	$details['user_city'] = $shipToCity;
	$details['user_country'] = $shipToCntryName;
	$details['user_contact'] = $firstName . ' ' . $lastName	;
	
	PAYPAL_updateUserDetails ($_USER['uid'], $details, true);
}

$onetime = $LANG_PAYPAL_1['will_pay'];

if ($_SESSION["Payment_Amount"] > 0 ) $onetime = $LANG_PAYPAL_1['will_pay_once'] . " <span style=\"border: 1px solid #DDD; background:#EEE; padding:5px;\">{$_SESSION["currencyCodeType"]} {$_SESSION["Payment_Amount"]}</span> " . $LANG_PAYPAL_1['and'];

$display .= "<h2>{$LANG_PAYPAL_1['confirm_informations']}</h2>
<p>{$LANG_PAYPAL_1['info_name']} <span style=\"border: 1px solid #DDD; background:#EEE; padding:5px;\">$lastName $firstName</span></p>
<p>$onetime <span style=\"border: 1px solid #DDD; background:#EEE; padding:5px;\">{$_SESSION["currencyCodeType"]} {$_SESSION["BILLINGAMT"]}</span> {$LANG_PAYPAL_1['every']} <span style=\"border: 1px solid #DDD; background:#EEE; padding:5px;\">{$_SESSION["BILLINGFREQUENCY"]} {$_SESSION["BILLINGPERIOD"]}</span> {$LANG_PAYPAL_1['for']} <span style=\"border: 1px solid #DDD; background:#EEE; padding:5px;\">\"{$_SESSION["BILLINGDESCRIPTION"]}\"</span> </p>
<form action='{$_PAY_CONF['site_url']}/recurring-payment/order_confirm.php' METHOD='POST'>
<input type=\"submit\" value=\"{$LANG_PAYPAL_1['review']}\"/>
</form>";

$display .= PAYPAL_siteFooter();

COM_output($display);
