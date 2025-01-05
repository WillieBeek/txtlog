<?php
use Txtlog\Controller\Settings;
use Txtlog\Core\Common;
use Txtlog\Includes\Cron;
use Txtlog\Includes\Testdata;

// Only allow access from localhost
$cron = new Cron();
$cron->checkAccess();

if(!class_exists('Txtlog\Includes\Testdata', true)) {
  echo "Generate fake data first, e.g.\nnode faker.js > /var/www/txtlog/txtlog/includes/testdata.php\n";
  exit;
}

// Insert this many records
$limit = 10000;
$userCount = 5;
$ipCount = 2;

$limitInput = Common::get('limit', 6);
$limit = Common::isInt($limitInput, 1, $limit * 10) ? $limitInput : $limit;
$debug = Common::get('debug') == 'true';
$sitename = (new Settings)->get()->getSitename();
$past = strtotime('-1 day');
$future = strtotime('+1 day');
$data = [];
$baseUrl = "https://$sitename/api/log";
$logUrl = (new Settings)->get()->getLogDomain().'/api/log';
$tokenUrl = "https://$sitename/api/token";

$service = ['sshd', 'cron', 'rdplogin', 'rdplogout', 'login', 'logout', 'client/', 'payment/', 'user/', 'item/', 'product/', 'api/', 'fetch/', 'cgi-bin/', 'srv/', 'private/'];
$methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

// Select some records from the random data
$users = [];
$ips = [];
$browsers = [];
for($i=0; $i<$userCount; $i++) {
  $users[] = Testdata::$users[array_rand(Testdata::$users)];
}
for($i=0; $i<$ipCount; $i++) {
  $ips[] = Testdata::$ips[array_rand(Testdata::$ips)];
}
// faker.js browser are often incompatible with PHP get_browser
$browsers = [
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0',
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0',
  'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0',
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.3',
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Safari/605.1.1'
];

for($i=0; $i<$limit; $i++) {
  $json = [];

  $json['service'] = $service[array_rand($service)];

  if(rand(0,19) < 19) {
    $json['date'] = (new DateTimeImmutable())->setTimestamp(rand($past, $future))->format('Y-m-d\TH:i:s\Z');
  }

  if($json['service'] == 'rdplogin' || $json['service'] == 'rdplogout') {
    $json['computer'] = 'WIN-'.strtoupper(Common::getRandomString(rand(3, 5)));
    $json['ip'] = rand(0,999) == 0 ? Testdata::$ips[array_rand(Testdata::$ips)] : $ips[array_rand($ips)];
    $json['event_id'] = $json['service'] == 'rdplogin' ? (rand(0, 2) == 0 ? 25 : 21) : (rand(0, 2) == 0 ? 24 : 23);
    $json['serverip'] = Testdata::$ips[array_rand(Testdata::$ips)];
    $json['user'] = $json['computer'].'\\'.(rand(0, 3) == 0 ? 'Administrator' : $users[array_rand($users)]);
  } elseif($json['service'] == 'sshd' || $json['service'] == 'sshd') {
    $json['action'] = rand(0, 1) == 0 ? 'login' : 'logout';
    $json['user'] = rand(0, 3) == 0 ? 'root' : $users[array_rand($users)];
    $json['ip'] = rand(0,999) == 0 ? Testdata::$ips[array_rand(Testdata::$ips)] : $ips[array_rand($ips)];
    $json['serverip'] = Testdata::$ips[array_rand(Testdata::$ips)];
    $json['serverhostname'] = $sitename;
  } elseif($json['service'] == 'cron') {
    $json['action'] = rand(0, 1) == 0 ? 'login' : 'logout';
    $json['user'] = rand(0, 3) == 0 ? 'root' : $users[array_rand($users)];
    $json['ip'] = '';
    $json['serverip'] = Testdata::$ips[array_rand(Testdata::$ips)];
    $json['serverhostname'] = $sitename;
  } else {
    if(rand(0,19) < 19) {
      $json['user'] = $users[array_rand($users)];
    }

    if(rand(0,10) < 9) {
      $json['browser'] = rand(0,200) == 0 ? Testdata::$browsers[array_rand(Testdata::$browsers)] : $browsers[array_rand($browsers)];
    }

    if(substr($json['service'], -1) == '/') {
      $json['service'] .= rand(10,9999999);

      if(rand(0,9) < 8) {
        $json['method'] = rand(0,200) == 0 ? $methods[array_rand($methods)] : 'POST';
      }

      if(rand(0,200) == 0) {
        $json['session_id'] = Common::getRandomString(rand(1, 30));
      }
    }

    if(rand(0,2) == 0) {
      $json['url'] = Testdata::$urls[array_rand(Testdata::$urls)];
    }

    if(rand(0,80) == 0) {
      $json['url2'] = Testdata::$urls[array_rand(Testdata::$urls)];
    }

    if(rand(0,100) == 0) {
      $json['uptime'] = rand(1,10000). ' seconds';
    }

    if(rand(0,10) < 9) {
      $json['ip'] = rand(0,999) == 0 ? Testdata::$ips[array_rand(Testdata::$ips)] : $ips[array_rand($ips)];
    }

    if(rand(0,20) < 15) {
      $json['trace_id'] = Testdata::$uuids[array_rand(Testdata::$uuids)];
    }

    if(rand(0,10) == 0) {
      $json['owner'] = Testdata::$companies[array_rand(Testdata::$companies)];
    }
  }

  $data['rows'][] = $json;
}

if($debug) {
  echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

$demoName = 'Demo page';
$demoAdminToken = (new Settings)->get()->getDemoAdminToken();

if(strlen($demoAdminToken) == 0) {
  echo "Creating new demo log\n";

  $ch = curl_init($baseUrl);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $result = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  if($result === false || $status != 201) {
    echo "Curl exception (1), status $status\n";
    exit;
  }
  curl_close($ch);

  $result = json_decode($result);

  // Store the admin and view URL in the settings
  $demoAdminToken = $result->admin;
  $s = (new Settings)->get(useCache: false);
  $s->setDemoAdminToken($demoAdminToken);
  $s->setDemoViewURL($result->view);

  try {
    (new Settings)->update($s);
  } catch(Exception $e) {
    echo "Error updating settings\n";
    print_r($e);
    exit;
  }
}

// Post the data
$ch = curl_init($logUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: $demoAdminToken"]);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$result = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
if($result === false || $status != 202) {
  echo "Curl exception (2), status $status\n";
  print_r($result);
  exit;
}
curl_close($ch);

$demoDashboardURL = (new Settings)->get()->getDemoDashboardURL();

if(strlen($demoDashboardURL) == 0) {
  echo "Creating new public dashboard token\n";

  $ch = curl_init($tokenUrl.'?data=POST+Safari');
  curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: $demoAdminToken"]);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, 'scope=view');
  $tokenResult = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  if($tokenResult === false || $status != 201) {
    echo "Curl exception (3), status $status\n";
    exit;
  }
  curl_close($ch);

  $tokenResult = json_decode($tokenResult);

  // Store the public dashboard URL in the settings
  $demoDashboardURL = $tokenResult->url;
  $s = (new Settings)->get(useCache: false);
  $s->setDemoDashboardURL($demoDashboardURL);

  try {
    (new Settings)->update($s);
  } catch(Exception $e) {
    echo "Error updating settings\n";
    print_r($e);
    exit;
  }
}

echo "Demo page complete, URL: ";
echo (new Settings)->get()->getDemoViewURL()."\n";
echo "Result:\n$result\n";
