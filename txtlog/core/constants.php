<?php
namespace Txtlog\Core;

use Txtlog\Settings;

/**
 * Txtlog\Core\Constants
 *
 * Getter functions for the application settings defined in settings.php
 * These functions usually return an empty string/array if a value is missing from the settings
 */
class Constants extends Settings {
  /**
   * True when the property installed is set
   *
   * @var bool
   */
  public static function isInstalled() {
    return isset(parent::$installed) && parent::$installed;
  }


  /**
   * Fallback page title if none is set in the router
   *
   * @var string
   */
  public static function getPageTitle() {
    return parent::$pageTitle ?? '';
  }


  /**
   * Return a list (array) of IP addresses allowed to access a page
   *
   * @var array
   */
  public static function getTrustedIPs() {
    return parent::$trustedIPs ?? [];
  }


  /**
   * Return the Redis hostname
   *
   * @var string
   */
  public static function getRedisHost() {
    return parent::$redishost ?? '';
  }


  /**
   * Return the Redis port
   *
   * @var string
   */
  public static function getRedisPort() {
    return parent::$redisport ?? '';
  }


  /**
   * Maximum number of rows to return from queries, can be overridden
   *
   * @var int
   */
  public static function getMaxRows() {
    return parent::$sqldbmaxrows ?? '';
  }


  /**
   * Return the database hostname, e.g. localhost
   *
   * @var string
   */
  public static function getDBHost() {
    return parent::$sqldbhost ?? '';
  }


  /**
   * Return the database name
   *
   * @var string
   */
  public static function getDBName() {
    return parent::$sqldbname ?? '';
  }


  /**
   * Return the database port
   *
   * @var string
   */
  public static function getDBPort() {
    return parent::$sqldbport ?? '';
  }


  /**
   * Return the database user
   *
   * @var string
   */
  public static function getDBUser() {
    return parent::$sqldbuser ?? '';
  }


  /**
   * Return the database password
   *
   * @var string
   */
  public static function getDBPass() {
    return parent::$sqldbpass ?? '';
  }


  /**
   * Return optional database options, like SSL connectivity
   *
   * @var array
   */
  public static function getDBOpts() {
    return isset(parent::$sqldbopts) && is_array(parent::$sqldbopts) ? parent::$sqldbopts : null;
  }


  /**
   * Return the preferred database server
   *
   * @var string
   */
  public static function getPreferredDB() {
    return parent::$preferredDB ?? '';
  }
}
