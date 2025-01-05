<?php
use Txtlog\Core\Common;
use Txtlog\Includes\Cron;

$cron = new Cron();
$cron->checkAccess();

// Allow custom set runtimes, e.g. /cron?time=11 to run for 11 seconds
$runtime = Common::isInt(Common::get('time'), 1, 600) ? Common::get('time') : 60;
$action = '';
$getAction = Common::get('action') ?? '';
if($getAction == 'filetodatabase') {
  $action = 'fileToDatabase';
} elseif($getAction == 'cachetofile') {
  $action = 'cacheToFile';
} elseif($getAction == 'geoipdownload') {
  $action = 'geoipdownload';
} elseif($getAction == 'toripdownload') {
  $action = 'toripdownload';
} elseif($getAction == 'geoipparse') {
  $action = 'geoipparse';
} elseif($getAction == 'toripparse') {
  $action = 'toripparse';
}
$updateCache = Common::get('updatecache') == 'true';

echo "Start cron job. Action=$action, updateCache=".($updateCache ? 'yes' : 'no').", runtime $runtime seconds: ".(new DateTime())->format('Y-m-d H:i:s.v')."\n";

if($action == 'fileToDatabase' || $action == 'cacheToFile') {
  $end = time() + $runtime;
  $done = false;
  while(!$done) {
    $done = time() >= $end;
    echo "Start new cron loop: ".(new DateTimeImmutable())->format('Y-m-d H:i:s.v')."...";

    if($action == 'cacheToFile') {
      $result = $cron->cacheToFile();
    } elseif ($action == 'fileToDatabase') {
      $result = $cron->fileToDatabase();
    }

    echo $result->debug;
    if($result->inserts < 1000) {
      sleep(1);
    }
  }
} elseif($action == 'geoipdownload') {
  echo $cron->geoIPDownload()."\n";
} elseif($action == 'toripdownload') {
  echo $cron->torIPDownload()."\n";
} elseif($action == 'geoipparse') {
  echo $cron->geoIPParse()."\n";
} elseif($action == 'toripparse') {
  echo $cron->torIPParse()."\n";
}

// Updated locally cached settings and tables: settings, accounts and servers (do this at the end so cacheToFile can continue if the database is unreachable)
if($updateCache) {
  echo $cron->updateLocalCache()->debug;
}

echo "End cron job: ".(new DateTime())->format('Y-m-d H:i:s.v')."\n";
