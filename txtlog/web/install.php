<?php
use Txtlog\Includes\InstallHelper;
use Txtlog\Core\Common;
use Txtlog\Core\Constants;

$constants = new Constants();
$installHelper = new InstallHelper();

// Check if the installation has already completed
if(method_exists($constants, 'isInstalled') && is_callable([$constants, 'isInstalled']) && $constants->isInstalled()) {
  header('Location: /');
  exit;
}

// Install button pressed
if(!empty(Common::post('submit'))) {
  $output = $installHelper->install();
  echo '<section class="section">'.implode("<br>\n", $output).'</section>';
  exit;
}

// Required minimum PHP version
$phpMinVersion = '8.3.0';

// The settings file, e.g. /var/www/site/web/../settings.php
$settingsFile = $installHelper->getSettingsLocation();

// Directory for storing temporary data
$tempDir = $installHelper->getTempDir();

?>
<section class="hero is-info">
  <div class="hero-body">
    <p class="title">
      Installation
    </p>
  </div>
</section>

<div class="block">
<table class="table">
  <thead>
    <tr>
      <th>Check</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?=$installHelper->getRow(
      "PHP version >= $phpMinVersion",
      version_compare(phpversion(), $phpMinVersion, '>='),
      "PHP version is too old, please upgrade to at least version $phpMinVersion");
    ?>
    <?=$installHelper->getRow(
      'Redis available',
      extension_loaded('redis'),
      'Enable Redis');
    ?>
    <?=$installHelper->getRow(
      'Settings file writable',
      is_writable($settingsFile),
      'Cannot update the settings file, set correct permissions, i.e. chmod 660 '.Common::getString($settingsFile));
    ?>
    <?=$installHelper->getRow(
      'MySQL/MariaDB available',
      $installHelper->installedMySQL(),
      'Enable the MySQL PDO driver');
    ?>
    <?=$installHelper->getRow(
      'mbstring package',
      function_exists('mb_substr'),
      'Install the mbstring package (apt install php-mbstring)');
    ?>
    <?=$installHelper->getRow(
      'gmp package',
      function_exists('gmp_strval'),
      'Install the gmp package (apt install php-gmp)');
    ?>
    <?=$installHelper->getRow(
      'Curl',
      function_exists('curl_init'),
      'Install the php curl package (apt install php-curl)');
    ?>
    <?=$installHelper->getRow(
      'Browscap',
      ini_get('browscap'),
      'Install "php_browscap.ini" from <a target="_blank" href="https://browscap.org">browscap.org</a>');
    ?>
    <?=$installHelper->getRow(
      'Temporary storage directory writable',
      is_writable($tempDir),
      'Temporary directory is not writable, set correct permissions, i.e. chgrp www-data '.Common::getString($tempDir).' && chmod 770 '.Common::getString($tempDir));
    ?>
    </tbody>
  </table>
</div>

<div class="block">
  <form method="POST" action="/install">
    <table class="table">
      <thead>
        <tr>
          <th colspan="2">Server settings</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Hostname</td>
          <td><input class="input" type="text" name="hostname" value="<?=$_SERVER['SERVER_NAME'] ?? ''?>" autofocus></td>
        </tr>
        <tr>
          <td>Redis host or unix socket filename</td>
          <td><input class="input" type="text" name="redishost" value="/var/run/redis/redis-server.sock"></td>
        </tr>
        <tr>
          <td>Redis port (ignored when using a socket)</td>
          <td><input class="input" type="text" name="redisport" value="6379"></td>
        </tr>

        <tr>
          <th colspan="2">MySQL settings</th>
          <th></th>
        </tr>
        <tr>
          <td>Database IP/hostname</td>
          <td><input class="input" type="text" name="sqldbhost" value="localhost"></td>
        </tr>
        <tr>
          <td>Database name</td>
          <td><input class="input" type="text" name="sqldbname" value="txtlog"></td>
        </tr>
        <tr>
          <td>Database port</td>
          <td><input class="input" type="text" name="sqldbport" value="3306"></td>
        </tr>
        <tr>
          <td>Database username</td>
          <td><input class="input" type="text" name="sqldbuser" value="txtlog"></td>
        </tr>
        <tr>
          <td>Database password</td>
          <td><input class="input" type="text" name="sqldbpassword"></td>
        </tr>
        <tr>
          <td>Use SSL connection</td>
          <td><input type="checkbox" name="sqldbssl" checked></td>
        </tr>

        <tr>
          <th colspan="2">Clickhouse settings</th>
          <th></th>
        </tr>
        <tr>
          <td>Database IP/hostname</td>
          <td><input class="input" type="text" name="chdbhost" value="127.0.0.1"></td>
        </tr>
        <tr>
          <td>Database name</td>
          <td><input class="input" type="text" name="chdbname" value="txtlog"></td>
        </tr>
        <tr>
          <td>Database port</td>
          <td><input class="input" type="text" name="chdbport" value="9004"></td>
        </tr>
        <tr>
          <td>Database username</td>
          <td><input class="input" type="text" name="chdbuser" value="txtlog"></td>
        </tr>
        <tr>
          <td>Database password</td>
          <td><input class="input" type="text" name="chdbpassword"></td>
        </tr>
        <tr>
          <td>Use SSL connection</td>
          <td><input type="checkbox" name="chdbssl" checked></td>
        </tr>

        <tr>
          <th colspan="2">Application settings</th>
          <th></th>
        </tr>

        <tr>
          <td>Public e-mail address shown on homepage</td>
          <td><input class="input" type="text" name="email"></td>
        </tr>
        <tr>
          <td>Admin status page user</td>
          <td><input class="input" type="text" name="adminuser" value="admin"></td>
        </tr>
        <tr>
          <td>Admin status page password</td>
          <td><input class="input" type="password" name="adminpassword"></td>
        </tr>
        <tr>
          <td>Maximum retention in days</td>
          <td><input class="input" type="text" name="maxretention" value="365"></td>
        </tr>
        <tr>
          <td>Max API calls for creating, updating or deleting log metadata</td>
          <td><input class="input" type="text" name="maxapicallslog" value="500"></td>
        </tr>
        <tr>
          <td>Max select (GET) requests per 10 minutes</td>
          <td><input class="input" type="text" name="maxapicallsget" value="1000"></td>
        </tr>
        <tr>
          <td>Max number of failed API calls per 10 minutes</td>
          <td><input class="input" type="text" name="maxapifails" value="20"></td>
        </tr>

        <tr>
          <th colspan="2">Account settings</th>
          <th></th>
        </tr>

        <tr>
          <td>Query timeout for GET requests, in seconds</td>
          <td><input class="input" type="text" name="querytimeout" value="1.9"></td>
        </tr>
        <tr>
          <td>Max number of API requests per 10 minutes</td>
          <td><input class="input" type="text" name="maxipusage" value="100000"></td>
        </tr>
        <tr>
          <td>Max number of rows per log</td>
          <td><input class="input" type="text" name="maxrows" value="10000000"></td>
        </tr>
        <tr>
          <td>Max size per row in bytes</td>
          <td><input class="input" type="text" name="maxrowsize" value="6000"></td>
        </tr>

        <?=$installHelper->getInstallRow()?>
      </tbody>
    </table>
  </form>
</div>
