<?php
use Txtlog\Controller\Settings;
use Txtlog\Core\Common;
use Txtlog\Includes\Login;
use Txtlog\Includes\Cache;

echo '<div class="container">';

$cache = new Cache();
$login = new Login();
$inputUser = Common::post('username', 200);
$inputPass = Common::post('password', 200);
$serverInput = Common::get('server');
$serverID = Common::isInt($serverInput, 1, 255) ? $serverInput : 1;
$settings = (new Settings)->get();

try {
  $cache->verifyIPFails();
} catch(Exception $e) {
  echo $login->getAccessDenied(false);
  exit;
}

if(strlen($inputPass) < 1) {
  echo $login->getLoginAdmin();
} elseif($inputUser == $settings->getAdminUser() && password_verify($inputPass, $settings->getAdminPassword())) {
  echo $login->showAdmin($serverID);
} else {
  $cache->addIPFail();
  echo $login->getLoginAdmin();
  echo $login->getAccessDenied(false);
}
