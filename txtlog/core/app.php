<?php
namespace Txtlog\Core;

use Txtlog\Core\Common;
use Txtlog\Core\Constants;

/**
 * Txtlog\Core\App
 *
 * Main framework class
 */
final class App {
  /**
   * Disk path and filename of the requested page
   *
   * @var string
   */
  private $fullPath;


  /**
   * HTML title of the page, defaults to the title defined in the settings
   * Title can be overruled in txtlog/router.php
   *
   * @var bool
   */
  private $pagetitle;


  /**
   * The framework base directory
   * e.g. /var/www/site if this script is /var/www/site/txtlog/core/app.php
   *
   * @var bool
   */
  private static $webdir;


  /**
   * Include txtlog/base_pages/header.php on the page
   *
   * @var bool
   */
  private $header = true;


  /**
   * Include txtlog/base_pages/footer.php on the page
   *
   * @var bool
   */
  private $footer = true;


  /**
   * Allow the page to be loaded in a frame
   *
   * @var bool
   */
  private $allowFrame = false;


  /**
   * Instance to prevent multiple creations of an App
   *
   * @var class
   */
  private static $instance = null;


  /**
   * Protected constructor, so $app = new App(); does not work
   *
   */
  protected function __construct() { }


  /**
   * Get an instance of Txtlog\App
   *
   * @return void
   */
  public static function getInstance() {
    if(!isset(self::$instance)) {
      self::$instance = new static;

      // Set the base web directory
      self::$webdir = dirname(dirname(__DIR__));
    }

    return self::$instance;
  }


  /**
   * Register the class autoLoad function
   *
   * @return void
   */
  private function register() {
    spl_autoload_register([$this, 'autoLoad']);
  }


  /**
   * Class autoloader
   *
   * @return bool false if the file cannot be found or determined
   */
  public function autoLoad($class) {
    $path = strtolower($class);
    $appdir = 'txtlog';
    $webdir = self::$webdir;

    $basedir = "$webdir/$appdir";

    // Strip the app name out of the class name
    $len = strlen($appdir);

    // Get the class name without the vendor prefix
    $path = substr($path, $len + 1);

    // Translate namespace to path
    $path = str_replace('\\', '/', $path);

    // Ignore this request if the requested class is not completely alphanumeric
    if(!ctype_alnum(str_replace('/', '', $path))) {
      return false;
    }

    $filename = "$basedir/$path.php";

    if(file_exists($filename)) {
      require $filename;
    }
  }


  /**
   * Start the application flow, register the autoloader, set parameters and HTTP headers
   *
   * @return void
   */
  public function start() {
    // Setup class autoloader
    $this->register();

    // Check access, an IP whitelist can be provided in app/settings.php
    $this->checkAccess();

    // Setup requested page and parameters
    if(!$this->init()) {
      $this->showHeader();
      $this->error404();
      $this->showFooter();
    }

    // Harden security with extra headers
    $this->setSecurityHeaders();
  }


  /**
   * Set the page to load and determine all the relevant parameters
   *
   * @return bool false if the file cannot be found or determined
   */
  private function init() {
    // Check if a router file is provided
    if(!is_file($this->getWebdir().'/txtlog/router.php')) {
      return false;
    }
    require $this->getWebdir().'/txtlog/router.php';

    if(!is_array($routers)) {
      return false;
    }

    $request = $_SERVER['REQUEST_URI'];
    $path = explode('/', trim(parse_url($request)['path'] ?? '', '/'))[0] ?: 'index';

    // Syntax: 'api'=>['title'=>'API', 'header'=>false, 'footer'=>false],
    $u = $routers[$path] ?? null;

    $path = $this->getWebdir().'/txtlog/web/'.($u['filename'] ?? $path).'.php';
    if(is_null($u) || !file_exists($path)) {
      return false;
    }

    // Page options
    $this->pagetitle = $u['title'] ?? '';
    $this->header = $u['header'] ?? $this->header;
    $this->footer = $u['footer'] ?? $this->footer;
    $this->allowFrame = $u['frame'] ?? $this->allowFrame;
    $this->fullPath = $path;

    return true;
  }


  /**
   * Return disk path and filename of the requested page
   *
   * @return string
   */
  public function getPage() {
    return $this->fullPath;
  }


  /**
   * Get the page title, defined by a router entry or the default if none is specified in the settings
   *
   * @return string containing the page title
   */
  public function getPageTitle() {
    if($this->pagetitle) {
      return $this->pagetitle;
    }

    // No page title found in the routers, get the default title
    $pagetitle = Constants::getPageTitle();

    return $pagetitle;
  }


  /**
   * Check if access to the page is granted based on a possible IP whitelist
   *
   * @return string
   */
  private function checkAccess() {
    $trustedIPs = Constants::getTrustedIPs();

    // Check if a whitelist is provided
    if(empty($trustedIPs)) {
      return true;
    }

    $remoteIp = Common::getIP();

    // Check if the IP address of the remote client exactly matches one in the trusted IP array
    if(in_array($remoteIp, $trustedIPs)) {
      return true;
    }

    // Check if the remote client IPv6 starts with a valid part
    $part = Common::get64FromIPv6($remoteIp);
    if(in_array($part->ipFrom, $trustedIPs)) {
      return true;
    }

    // Forbidden
    Common::setHttpResponseCode(403);
    exit;
  }


  /**
   * Set extra security headers
   *
   * @return void
   */
  private function setSecurityHeaders() {
    if(!$this->allowFrame) {
      header('X-Frame-Options: DENY');
    }
  }


  /**
   * Get the webdir, i.e. ../../ from this file
   *
   * @return string
   */
  public function getWebdir() {
    return self::$webdir;
  }


  /**
   * Echoes the (HTML) header to the client, if the header is required for this page in the router
   *
   * @param force set to true to force showing the header, even if it is disabled in the router
   * @return void
   */
  public function showHeader($force=false) {
    if($this->header || $force) {
      $headerPage = $this->getWebdir().'/txtlog/base_pages/header.php';

      if(is_file($headerPage)) {
        require $headerPage;
      }
    }
  }


  /**
   * Echoes the (html) footer to the client
   *
   * @param force set to true to force showing the footer, even if it is disabled in the router
   * @return void
   */
  public function showFooter($force=false) {
    if($this->footer || $force) {
      $footerPage = $this->getWebdir().'/txtlog/base_pages/footer.php';

      if(is_file($footerPage)) {
        require $footerPage;
      }
    }
  }


  /**
   * Set a 404-Not Found error and show an error page if it exists
   *
   * @return void
   */
  public function error404() {
    Common::setHttpResponseCode(404);

    if(is_file($this->getWebdir().'/txtlog/web/error.php')) {
      require $this->getWebdir().'/txtlog/web/error.php';
    }

    exit;
  }
}
