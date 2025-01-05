<?php
namespace Txtlog\Controller;

// The backslash is important to catch the exception from the database class, which is in another namespace
use \Exception as Exception;
use Txtlog\Core\Common;
use Txtlog\Includes\Cache;
use Txtlog\Model\SettingsDB;

class Settings extends SettingsDB {
  /**
   * Get the application settings
   *
   * @param useCache optional boolean, set to false to ignore cache
   * @return Settings object
   */
  public function get($useCache=true) {
    $cache = new Cache();
    $cacheName = 'settings';

    if($useCache) {
      // Try to get the settings from the cache
      $appSettings = $cache->get($cacheName);

      if($appSettings) {
        return $appSettings;
      }
    }

    // Fetch from the database
    try {
      $appSettings = parent::get();

      // Store data in the cache
      $cache->set($cacheName, $appSettings);
    } catch(Exception $e) {
      // 503 Service Unavailable
      $httpCode = 503;
      Common::setHttpResponseCode($httpCode);
      // The settings are required. Without these, stop processing the request
      exit;
    }

    return $appSettings;
  }
}
