<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'OAuth.php';

define('CONTEXT_KEY', 'your-contextio-consumer-key');
define('CONTEXT_SECRET', 'your-contextio-consumer-secret');
define('CONTEXT_USER_ID', 'your-contextio-user-id');
define('CONTEXT_TRASH_NAME', '[Gmail]/Trash'); // change if not using Gmail

define('MAILROUTE_USER', 'your-mailroute-username'); // should be your email address
define('MAILROUTE_API_KEY', 'your-mailroute-api-key');
define('MAILROUTE_EMAIL_ACCOUNT_ID', 'your-mailroute-email-id'); // numeric - can be found in admin.mailroute.net network traffic

$whitelist = ['sending@example.com']; // add the email address you are forwarding from or you'll blacklist yourself...

$inbox = performOAuthRequest('GET', 'https://api.context.io/lite/users/'.CONTEXT_USER_ID.'/email_accounts/0/folders/inbox/messages');
foreach ($inbox as $message) {
	$id = $message->{'message_id'};
	$body = performOAuthRequest('GET', 'https://api.context.io/lite/users/'.CONTEXT_USER_ID.'/email_accounts/0/folders/inbox/messages/'.$id.'/body');
	$content = str_replace('<', '', str_replace('>', '', $body->bodies[0]->content));
	$emails = extractEmailAddresses($content);
	foreach ($emails as $email) {
		if (!in_array($email, $whitelist)) {
			performRequest('POST', 'https://admin.mailroute.net/api/v1/wblist/', ['email_account' => '/api/v1/email_account/'.MAILROUTE_EMAIL_ACCOUNT_ID.'/', 'email' => $email, 'wb' => 'b'], ['Authorization: ApiKey '.MAILROUTE_USER.':'.MAILROUTE_API_KEY]);
		}
	}
	performOAuthRequest('PUT', 'https://api.context.io/lite/users/'.CONTEXT_USER_ID.'/email_accounts/0/folders/inbox/messages/'.$id, ['new_folder_id' => CONTEXT_TRASH_NAME]);
}

echo 'DONE!';



// CURL

function performOAuthRequest($httpMethod, $url, $params=[], $headers=[]) {
	$signatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
	$oauthConsumer = new OAuthConsumer(CONTEXT_KEY, CONTEXT_SECRET, NULL);
	$oauthRequest = OAuthRequest::from_consumer_and_token($oauthConsumer, NULL, $httpMethod, $url, $params);
	$oauthRequest->sign_request($signatureMethod, $oauthConsumer, NULL);
	return performRequest($httpMethod, $oauthRequest, $params);
}

function performRequest($httpMethod, $url, $params=[], $headers=[]) {
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if (!in_array($httpMethod, ['GET', 'POST'])) {
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
    }
    if ($httpMethod != 'GET') {
    	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    }
    $output = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return json_decode($output);
}



// Email parsing

function extractEmailAddresses($string) {
   $emails = array();
   $string = str_replace("\r\n",' ',$string);
   $string = str_replace("\n",' ',$string);

   foreach(preg_split('/ /', $string) as $token) {
        $email = filter_var($token, FILTER_VALIDATE_EMAIL);
        if ($email !== false) { 
            $emails[] = $email;
        }
    }
    return array_unique($emails);
}