<?php
use Txtlog\Core\Common;
use Txtlog\Controller\Settings;

$type = Common::get('type');
$auth = Common::get('auth');
$logdomain = (new Settings)->get()->getLogDomain();

if(strlen($auth) > 100 || !in_array($type, ['ssh', 'rdp'])) {
  exit;
}

if($type == 'ssh') {
  $file = $_SERVER['DOCUMENT_ROOT'].'/../txtlog/scripts/txtlog';
  $contents = file_get_contents($file);
  $contents = str_replace('$REPLACE_AUTH_CODE', $auth, $contents);
  $contents = str_replace('$REPLACE_DOMAIN', $logdomain, $contents);
} elseif($type == 'rdp') {
  $file = $_SERVER['DOCUMENT_ROOT'].'/../txtlog/scripts/txtlog.ps1';
  $contents = file_get_contents($file);

  $charset = 'UTF-16LE';
  mb_regex_encoding($charset);
  // Use multibyte replace for non ASCII scripts
  $contents = mb_ereg_replace(mb_convert_encoding('\$REPLACE_AUTH_CODE', $charset, 'UTF-8'), mb_convert_encoding($auth, $charset, 'UTF-8') , $contents);
  $contents = mb_ereg_replace(mb_convert_encoding('\$REPLACE_DOMAIN', $charset, 'UTF-8'), mb_convert_encoding($logdomain, $charset, 'UTF-8') , $contents);
} else {
  exit;
}

$filename = basename($file);

header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: '.strlen($contents));

echo $contents;
