<?php
namespace Txtlog\Includes;

use Txtlog\Controller\Account;
use Txtlog\Controller\Server;
use Txtlog\Controller\Settings;
use Txtlog\Database\CreateDB;
use Txtlog\Entity\Account as AccountEntity;
use Txtlog\Entity\Server as ServerEntity;
use Txtlog\Entity\Settings as SettingsEntity;
use Exception;
use Txtlog\Core\Common;
use PDO;

// Helper class for the installation wizard
class InstallHelper {
  /**
   * Boolean indicating success
   *
   * @var bool
   */
  private $allGood;


  /**
   * Constructor
   *
   */
  public function __construct() {
    $this->allGood = true;
  }


  /**
   * Get one or two rows with information for the installer
   *
   * @param check string description of the performed check
   * @param sucess bool indicating whether the check was a success
   * @param extraInfo string with extra information, placed on a row below the check
   * @return html html with one or two table rows
   */
  public function getRow($check, $success, $extraInfo) {
    $html = '<tr>'
      .'<td>'
      .Common::getString($check)
      .'</td>'
      .'<td '
      .($success ? ' class="notification is-success is-light">OK' : ' class="notification is-danger is-light">Not ok')
      .'</td>'
      .'</tr>';

    if(!$success && !empty($extraInfo)) {
      $html .= '<tr>'
        .'<td colspan="2">'
        .$extraInfo
        .'</td>'
        .'</tr>';
    }

    if(!$success) {
      $this->allGood = false;
    }

    return $html;
  }


  /**
   * Get a row for proceeding to the actual installation step of the installer
   * Checks the allGood variable to determine if all checks have passed
   *
   * @return html with one table row
   */
  public function getInstallRow() {
    $html = '<tr>'
      .'<td>'
      .'&nbsp;'
      .'</td>'
      .'<td>'
      .($this->allGood ? '<input class="button is-success" value="Install" name="submit" type="submit">' : '&nbsp;')
      .'</td>'
      .'</tr>';

    return $html;
  }


  /**
   * Check if MySQL/MariaDB is installed and available
   *
   * @return bool true if the MySQL/MariaDB database driver is available
   */
  public function installedMySQL() {
    foreach(PDO::getAvailableDrivers() as $driver) {
      if($driver == 'mysql') {
        return true;
      }
    }

    return false;
  }


  /**
   * Parse POST-ed values and setup the initial environment
   *
   * @param test if true only runs the tests but do not alter/insert any data
   * @return array containing installation information
   */
  public function install($test=false) {
    $account = new Account();
    $createdb = new CreateDB();
    $server = new Server();
    $settings = new Settings();

    // Hardcoded defaults
    $anonymousAccountID = 1;
    $serverID = 1;

    // Server settings
    $hostname = Common::post('hostname');
    $redishost = Common::post('redishost');
    $redisport = Common::post('redisport');

    // Database settings
    $sqldbhost = Common::post('sqldbhost');
    $sqldbname = Common::post('sqldbname');
    $sqldbport = Common::post('sqldbport');
    $sqldbuser = Common::post('sqldbuser');
    $sqldbpassword = Common::post('sqldbpassword');
    $sqldboptions = null;
    $sqldboptionsSettings = null;
    if(Common::post('sqldbssl') == 'on') {
      // Remove PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT whenever possible!
      $sqldboptions = [\PDO::MYSQL_ATTR_SSL_CIPHER=>'DHE-RSA-AES256-SHA', \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=>false];
      $sqldboptionsSettings = '[\PDO::MYSQL_ATTR_SSL_CIPHER=>\'DHE-RSA-AES256-SHA\', \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=>false]';
      // MySQL SSL only works when using TCP/IP connections
      if($sqldbhost == 'localhost') {
        $sqldbhost = '127.0.0.1';
      }
    }

    $chdbhost = Common::post('chdbhost');
    $chdbname = Common::post('chdbname');
    $chdbport = Common::post('chdbport');
    $chdbuser = Common::post('chdbuser');
    $chdbpassword = Common::post('chdbpassword');
    $chdboptions = Common::post('chdbssl') == 'on' ? [\PDO::MYSQL_ATTR_SSL_CIPHER=>'DHE-RSA-AES256-SHA', \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=>false] : null;

    // Settings
    $email = Common::post('email');
    $adminUser = Common::post('adminuser');
    $adminPassword = Common::post('adminpassword');
    $maxRetention = Common::post('maxretention');
    $maxAPICallsLog = Common::post('maxapicallslog');
    $maxAPICallsGet = Common::post('maxapicallsget');
    $maxAPIFails = Common::post('maxapifails');

    // Account
    $queryTimeout = Common::post('querytimeout');
    $maxIPUsage = Common::post('maxipusage');
    $maxRows = Common::post('maxrows');
    $maxRowSize = Common::post('maxrowsize');

    $accountEntityAnon = new AccountEntity();
    $accountEntityAnon->setName('Anonymous');
    $accountEntityAnon->setQueryTimeout($queryTimeout);
    $accountEntityAnon->setMaxIPUsage($maxIPUsage);
    $accountEntityAnon->setMaxRetention($maxRetention);
    $accountEntityAnon->setMaxRows($maxRows);
    $accountEntityAnon->setMaxRowSize($maxRowSize);
    $accountEntityAnon->setDashboardRows(250);
    $accountEntityAnon->setDashboardRowsSearch(100);

    $serverEntity = new serverEntity();
    $serverEntity->setID($serverID);
    $serverEntity->setActive(true);
    $serverEntity->setHostname($chdbhost);
    $serverEntity->setDBName($chdbname);
    $serverEntity->setPort($chdbport);
    $serverEntity->setUsername($chdbuser);
    $serverEntity->setPassword($chdbpassword);
    $serverEntity->setOptions($chdboptions);

    $settingsEntity = new SettingsEntity();
    $settingsEntity->setAnonymousAccountID($anonymousAccountID);
    $settingsEntity->setSitename($hostname);
    $settingsEntity->setEmail($email);
    $settingsEntity->setCronEnabled(true);
    $settingsEntity->setCronInsert(10000);
    $settingsEntity->setTempDir($this->getTempDir());
    $settingsEntity->setAdminUser($adminUser);
    $settingsEntity->setAdminPassword($adminPassword);
    $settingsEntity->setMaxRetention($maxRetention);
    $settingsEntity->setMaxAPICallsLog($maxAPICallsLog);
    $settingsEntity->setMaxAPICallsGet($maxAPICallsGet);
    $settingsEntity->setMaxAPIFails($maxAPIFails);

    try {
      // Some sanity checks
      if(empty($hostname) || !Common::isValidDomain($hostname)) {
        throw new Exception('Invalid hostname');
      }
      if(!Common::isInt($maxRetention, 1, 999999)) {
        throw new Exception('Invalid retention');
      }

      $out[] = 'Database setup';
      $out[] = 'Testing MySQL connection...';
      $dbhsql = $createdb->testConnection($sqldbhost, $sqldbname, $sqldbport, $sqldbuser, $sqldbpassword, $sqldboptions);
      $out[] = 'Success: connection established';
      $out[] = '';

      $out[] = 'Testing Clickhouse connection...';
      $dbhch = $createdb->testConnection($chdbhost, $chdbname, $chdbport, $chdbuser, $chdbpassword, $chdboptions);
      $out[] = 'Success: connection established';
      $out[] = '';

      if($test) {
        return $out;
      }

      $out[] = 'Creating MySQL tables';
      $results = $createdb->createSQLTables($dbhsql);
      foreach($results as $result) {
        $out[] = $result;
      }
      $out[] = '';

      $out[] = 'Creating Clickhouse tables';
      $results = $createdb->createCHTables($dbhch, $maxRetention);
      foreach($results as $result) {
        $out[] = $result;
      }
      $out[] = '';

      $out[] = 'Creating settings file';
      $settingsFile = $this->getSettingsLocation();
      // Use the supplied database settings instead of the empty included settings file
      $settingsContents = $this->getSettings($hostname, $redishost, $redisport, $sqldbhost, $sqldbname, $sqldbport, $sqldbuser, $sqldbpassword, $sqldboptionsSettings);
      $writtenBytes = $this->writeSettings($settingsFile, $settingsContents);
      if(!$writtenBytes) {
        throw new Exception("Cannot create settings file \"$settingsFile\"");
      }
      $out[] = "Succesfully wrote $writtenBytes bytes to $settingsFile";
      $out[] = '';

      // Add initial data to the database
      $out[] = 'Initializing database';

      $account->open($sqldbhost, $sqldbname, $sqldbport, $sqldbuser, $sqldbpassword, $sqldboptions);
      $account->insert($accountEntityAnon);
      $out[] = "Added anonymous user account";

      $server->open($sqldbhost, $sqldbname, $sqldbport, $sqldbuser, $sqldbpassword, $sqldboptions);
      $server->insert($serverEntity);
      $out[] = "Added server database record";

      $settings->open($sqldbhost, $sqldbname, $sqldbport, $sqldbuser, $sqldbpassword, $sqldboptions);
      $settings->insert($settingsEntity);
      $out[] = "Added settings database record";
      $out[] = 'Installation finished!';
    } catch(Exception $e) {
      $out[] = Common::getString($e->getMessage());
    }

    return $out;
  }


  /**
   * Get the settings file path
   *
   * @return string with the settings file location
   */
  public function getSettingsLocation() {
    // The path where this file resides, e.g. /var/www/site/web
    $thisPath = $_SERVER['DOCUMENT_ROOT'] ?? '';

    // The settings file, e.g. /var/www/site/web/../settings.php
    $settingsFile = "$thisPath/../txtlog/settings.php";

    return $settingsFile;
  }


  /**
   * Get directory for storing temporary files
   *
   * @return string
   */
  public function getTempDir() {
    // The path where this file resides, e.g. /var/www/site/web
    $thisPath = $_SERVER['DOCUMENT_ROOT'] ?? '';

    // The settings file, e.g. /var/www/site/web/../settings.php
    $tempDir = "$thisPath/../txtlog/tmp";
    $tempDir = realpath($tempDir);

    return $tempDir;
  }


  /**
   * Get a new application settings file
   *
   * @param hostname
   * @param redishost hostname of the Redis instance
   * @param redisport
   * @param sqldbhost database server hostname
   * @param sqldbname database name
   * @param sqldbport database server port
   * @param sqldbuser database username
   * @param sqldbpassword database password
   * @param sqldboptions PDO connection options as string
   * @return string with a settings file
   */
  public function getSettings($hostname, $redishost, $redisport, $sqldbhost, $sqldbname, $sqldbport, $sqldbuser, $sqldbpassword, $sqldboptions) {
    $pageTitle = ucfirst($hostname);

    $settings = <<<"EOD"
<?php
namespace Txtlog;

// This class contains all the application settings which are not definied in the database
class Settings {
  // installed=true prevents anyone from going through the installation procedure again
  protected static \$installed    = true;

  // Redis settings
  protected static \$redishost    = '$redishost';
  protected static \$redisport    = $redisport;

  // Default page name
  protected static \$pageTitle    = '$pageTitle';

  // MySQL settings
  protected static \$sqldbmaxrows = 5000;
  // Set host to 127.0.0.1 instead of localhost when using SSL on localhost to prevent this error: "PDO::__construct(): this stream does not support SSL/crypto"
  protected static \$sqldbhost    = '$sqldbhost';
  protected static \$sqldbname    = '$sqldbname';
  // Use 127.0.0.1 as sqldbhost (instead of localhost) when changing the default port number
  protected static \$sqldbport    = '$sqldbport';
  protected static \$sqldbopts    = $sqldboptions;
  protected static \$sqldbuser    = '$sqldbuser';
  protected static \$sqldbpass    = '$sqldbpassword';
}

EOD;

    return $settings;
  }


  /**
   * Write a settings file to disk
   *
   * @param settingsFile location of the settings file
   * @param settingsContents string with the (new) contents of the settings file
   * @return number of bytes written or false when writing the settings file failed
   */
  public function writeSettings($settingsFile, $settingsContents) {
    // Check again if the file is writable
    if(is_writable($settingsFile)) {
      // To avoid mistakes, check if the target filename ends with PHP
      if(substr($settingsFile, -12) == 'settings.php') {
        $bytes = file_put_contents($settingsFile, $settingsContents);
        return $bytes;
      }
    }

    return false;
  }
}
