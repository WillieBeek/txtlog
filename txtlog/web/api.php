<?php
use Txtlog\Database\ConnectionException as ConnectionException;
use Txtlog\Includes\Api;

$api = new Api();
$output = '';

try {
  $api->verifyMethod();
  $api->verifyLimit();
  $output = $api->parseRequest();
} catch(ConnectionException $eConn) {
  $errorMsg = 'ERROR_SERVICE_UNAVAILABLE';
  $output = (object)['error'=>$errorMsg];
  $api->setHttpCode($errorMsg);
} catch(Exception $e) {
  $errorMsg = $e->getMessage();
  $output = (object)['error'=>$errorMsg];
  // Set the HTTP status code based on the error message, defined in includes/error.php
  $api->setHttpCode($errorMsg);
}

// Send the correct HTTP status code, header and output
$api->sendHttpCode();
$api->setContentHeader();
$api->setContent($output);
