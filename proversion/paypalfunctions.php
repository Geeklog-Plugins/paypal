<?php

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'paypalfunctions.php') !== false) {
    die('This file can not be used on its own.');
}

global $_PAY_CONF, $API_UserName, $API_Password, $API_Signature, $version;
global $USE_PROXY, $PROXY_HOST, $PROXY_PORT;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$USE_PROXY = false;
$PROXY_HOST = '127.0.0.1';
$PROXY_PORT = '808';

$API_UserName = isset($_PAY_CONF['API_UserName']) ? $_PAY_CONF['API_UserName'] : '';
$API_Password = isset($_PAY_CONF['API_Password']) ? $_PAY_CONF['API_Password'] : '';
$API_Signature = isset($_PAY_CONF['API_Signature']) ? $_PAY_CONF['API_Signature'] : '';
$version = '204';

$sandbox = isset($_PAY_CONF['paypalURL'])
    && trim($_PAY_CONF['paypalURL']) === 'www.sandbox.paypal.com';

if ($sandbox) {
    $_SESSION['API_endpoint'] = 'https://api-3t.sandbox.paypal.com/nvp';
    $_SESSION['PAYPAL_URL'] = 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=';
} else {
    $_SESSION['API_endpoint'] = 'https://api-3t.paypal.com/nvp';
    $_SESSION['PAYPAL_URL'] = 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=';
}

function PAYPAL_NVP_session($key, $default = '')
{
    return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
}

function PAYPAL_NVP_responseValue($response, $key, $default = '')
{
    return is_array($response) && isset($response[$key]) ? $response[$key] : $default;
}

function CallShortcutExpressCheckout($paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL)
{
    $nvp = '&AMT=' . urlencode($paymentAmount)
        . '&PAYMENTACTION=' . urlencode($paymentType)
        . '&BILLINGAGREEMENTDESCRIPTION=' . urlencode(PAYPAL_NVP_session('BILLINGDESCRIPTION'))
        . '&BILLINGTYPE=RecurringPayments'
        . '&RETURNURL=' . urlencode($returnURL)
        . '&CANCELURL=' . urlencode($cancelURL)
        . '&CURRENCYCODE=' . urlencode($currencyCodeType);

    $response = hash_call('SetExpressCheckout', $nvp);
    $ack = strtoupper(PAYPAL_NVP_responseValue($response, 'ACK'));

    if ($ack === 'SUCCESS' || $ack === 'SUCCESSWITHWARNING') {
        $_SESSION['TOKEN'] = urldecode(PAYPAL_NVP_responseValue($response, 'TOKEN'));
    }

    return $response;
}

function CallMarkExpressCheckout(
    $paymentAmount,
    $currencyCodeType,
    $paymentType,
    $returnURL,
    $cancelURL,
    $shipToName,
    $shipToStreet,
    $shipToCity,
    $shipToState,
    $shipToCountryCode,
    $shipToZip,
    $shipToStreet2,
    $phoneNum
) {
    $nvp = '&PAYMENTREQUEST_0_AMT=' . urlencode($paymentAmount)
        . '&PAYMENTREQUEST_0_PAYMENTACTION=' . urlencode($paymentType)
        . '&RETURNURL=' . urlencode($returnURL)
        . '&CANCELURL=' . urlencode($cancelURL)
        . '&PAYMENTREQUEST_0_CURRENCYCODE=' . urlencode($currencyCodeType)
        . '&ADDROVERRIDE=1'
        . '&PAYMENTREQUEST_0_SHIPTONAME=' . urlencode($shipToName)
        . '&PAYMENTREQUEST_0_SHIPTOSTREET=' . urlencode($shipToStreet)
        . '&PAYMENTREQUEST_0_SHIPTOSTREET2=' . urlencode($shipToStreet2)
        . '&PAYMENTREQUEST_0_SHIPTOCITY=' . urlencode($shipToCity)
        . '&PAYMENTREQUEST_0_SHIPTOSTATE=' . urlencode($shipToState)
        . '&PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE=' . urlencode($shipToCountryCode)
        . '&PAYMENTREQUEST_0_SHIPTOZIP=' . urlencode($shipToZip)
        . '&PAYMENTREQUEST_0_SHIPTOPHONENUM=' . urlencode($phoneNum);

    $response = hash_call('SetExpressCheckout', $nvp);
    $ack = strtoupper(PAYPAL_NVP_responseValue($response, 'ACK'));

    if ($ack === 'SUCCESS' || $ack === 'SUCCESSWITHWARNING') {
        $_SESSION['TOKEN'] = urldecode(PAYPAL_NVP_responseValue($response, 'TOKEN'));
    }

    return $response;
}

function GetShippingDetails($token)
{
    $response = hash_call('GetExpressCheckoutDetails', '&TOKEN=' . urlencode($token));
    $ack = strtoupper(PAYPAL_NVP_responseValue($response, 'ACK'));

    if ($ack === 'SUCCESS' || $ack === 'SUCCESSWITHWARNING') {
        $map = array(
            'payer_id' => 'PAYERID',
            'email' => 'EMAIL',
            'firstName' => 'FIRSTNAME',
            'lastName' => 'LASTNAME',
            'shipToName' => 'SHIPTONAME',
            'shipToStreet' => 'SHIPTOSTREET',
            'shipToCity' => 'SHIPTOCITY',
            'shipToState' => 'SHIPTOSTATE',
            'shipToZip' => 'SHIPTOZIP',
            'shipToCountry' => 'SHIPTOCOUNTRYCODE',
        );

        foreach ($map as $sessionKey => $responseKey) {
            $_SESSION[$sessionKey] = PAYPAL_NVP_responseValue($response, $responseKey);
        }
    }

    return $response;
}

function ConfirmPayment($finalPaymentAmount)
{
    $token = PAYPAL_NVP_session('TOKEN');
    $paymentType = PAYPAL_NVP_session('paymentType', 'Sale');
    $currency = PAYPAL_NVP_session('currencyCodeType');
    $payerId = PAYPAL_NVP_session('payer_id');
    $serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';

    $nvp = '&TOKEN=' . urlencode($token)
        . '&PAYERID=' . urlencode($payerId)
        . '&PAYMENTACTION=' . urlencode($paymentType)
        . '&PAYMENTREQUEST_0_AMT=' . urlencode($finalPaymentAmount)
        . '&PAYMENTREQUEST_0_CURRENCYCODE=' . urlencode($currency)
        . '&IPADDRESS=' . urlencode($serverName)
        . '&PAYMENTREQUEST_0_CUSTOM=' . urlencode(PAYPAL_NVP_session('user_id', 0));

    $response = hash_call('DoExpressCheckoutPayment', $nvp);
    $_SESSION['billing_agreement_id'] = PAYPAL_NVP_responseValue($response, 'BILLINGAGREEMENTID');

    return $response;
}

function CreateRecurringPaymentsProfile()
{
    $remoteAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

    $nvp = '&TOKEN=' . urlencode(PAYPAL_NVP_session('TOKEN'))
        . '&SHIPTONAME=' . urlencode(PAYPAL_NVP_session('shipToName'))
        . '&SHIPTOSTREET=' . urlencode(PAYPAL_NVP_session('shipToStreet'))
        . '&SHIPTOCITY=' . urlencode(PAYPAL_NVP_session('shipToCity'))
        . '&SHIPTOSTATE=' . urlencode(PAYPAL_NVP_session('shipToState'))
        . '&SHIPTOZIP=' . urlencode(PAYPAL_NVP_session('shipToZip'))
        . '&SHIPTOCOUNTRY=' . urlencode(PAYPAL_NVP_session('shipToCountry'))
        . '&PROFILESTARTDATE=' . urlencode(gmdate('Y-m-d\TH:i:s\Z', strtotime('+1 hour')))
        . '&DESC=' . urlencode(PAYPAL_NVP_session('BILLINGDESCRIPTION'))
        . '&BILLINGPERIOD=' . urlencode(PAYPAL_NVP_session('BILLINGPERIOD'))
        . '&BILLINGFREQUENCY=' . urlencode(PAYPAL_NVP_session('BILLINGFREQUENCY'))
        . '&AMT=' . urlencode(PAYPAL_NVP_session('BILLINGAMT', 0))
        . '&CURRENCYCODE=' . urlencode(PAYPAL_NVP_session('currencyCodeType'))
        . '&IPADDRESS=' . urlencode($remoteAddress);

    return hash_call('CreateRecurringPaymentsProfile', $nvp);
}

function GetRecurringPaymentsProfileDetails($profileId)
{
    return hash_call('GetRecurringPaymentsProfileDetails', '&PROFILEID=' . urlencode($profileId));
}

function DirectPayment(
    $paymentType,
    $paymentAmount,
    $creditCardType,
    $creditCardNumber,
    $expDate,
    $cvv2,
    $firstName,
    $lastName,
    $street,
    $city,
    $state,
    $zip,
    $countryCode,
    $currencyCode
) {
    $remoteAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

    $nvp = '&AMT=' . urlencode($paymentAmount)
        . '&CURRENCYCODE=' . urlencode($currencyCode)
        . '&PAYMENTACTION=' . urlencode($paymentType)
        . '&CREDITCARDTYPE=' . urlencode($creditCardType)
        . '&ACCT=' . urlencode($creditCardNumber)
        . '&EXPDATE=' . urlencode($expDate)
        . '&CVV2=' . urlencode($cvv2)
        . '&FIRSTNAME=' . urlencode($firstName)
        . '&LASTNAME=' . urlencode($lastName)
        . '&STREET=' . urlencode($street)
        . '&CITY=' . urlencode($city)
        . '&STATE=' . urlencode($state)
        . '&ZIP=' . urlencode($zip)
        . '&COUNTRYCODE=' . urlencode($countryCode)
        . '&IPADDRESS=' . urlencode($remoteAddress);

    return hash_call('DoDirectPayment', $nvp);
}

function hash_call($methodName, $nvpStr)
{
    global $version, $API_UserName, $API_Password, $API_Signature;
    global $USE_PROXY, $PROXY_HOST, $PROXY_PORT;

    if (!function_exists('curl_init')) {
        COM_errorLog('PayPal: cURL extension is required for NVP API calls.');
        return array('ACK' => 'Failure', 'L_LONGMESSAGE0' => 'cURL extension is not available.');
    }

    $endpoint = PAYPAL_NVP_session('API_endpoint');
    if ($endpoint === '') {
        COM_errorLog('PayPal: NVP API endpoint is not configured.');
        return array('ACK' => 'Failure', 'L_LONGMESSAGE0' => 'PayPal API endpoint is not configured.');
    }

    if ($API_UserName === '' || $API_Password === '' || $API_Signature === '') {
        COM_errorLog('PayPal: NVP API credentials are incomplete.');
        return array('ACK' => 'Failure', 'L_LONGMESSAGE0' => 'PayPal API credentials are incomplete.');
    }

    $request = 'METHOD=' . urlencode($methodName)
        . '&VERSION=' . urlencode($version)
        . '&PWD=' . urlencode($API_Password)
        . '&USER=' . urlencode($API_UserName)
        . '&SIGNATURE=' . urlencode($API_Signature)
        . $nvpStr;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);

    if ($USE_PROXY) {
        curl_setopt($ch, CURLOPT_PROXY, $PROXY_HOST . ':' . $PROXY_PORT);
    }

    $response = curl_exec($ch);

    if ($response === false) {
        $_SESSION['curl_error_no'] = curl_errno($ch);
        $_SESSION['curl_error_msg'] = curl_error($ch);
        COM_errorLog(
            'PayPal NVP cURL error ' . $_SESSION['curl_error_no']
            . ': ' . $_SESSION['curl_error_msg']
        );
        curl_close($ch);
        return array('ACK' => 'Failure', 'L_LONGMESSAGE0' => $_SESSION['curl_error_msg']);
    }

    curl_close($ch);

    $_SESSION['nvpReqArray'] = deformatNVP($request);
    return deformatNVP($response);
}

function RedirectToPayPal($token)
{
    $base = PAYPAL_NVP_session('PAYPAL_URL');
    if ($base === '') {
        return false;
    }

    header('Location: ' . $base . rawurlencode($token));
    exit;
}

function deformatNVP($nvpString)
{
    $result = array();

    foreach (explode('&', ltrim((string) $nvpString, '&')) as $pair) {
        if ($pair === '') {
            continue;
        }

        $parts = explode('=', $pair, 2);
        $key = urldecode($parts[0]);
        $value = isset($parts[1]) ? urldecode($parts[1]) : '';
        $result[$key] = $value;
    }

    return $result;
}
