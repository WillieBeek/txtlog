<?php
namespace Txtlog\Controller;

use Exception;
use Txtlog\Includes\Cache;
use Txtlog\Model\ServerDB;

class Server extends ServerDB {
  /**
   * Cache name for the servers
   *
   * @var int
   */
  private $cacheName = 'server';


  /**
   * Get all servers
   *
   * @param useCache optional boolean, set to false to ignore cache
   * @return array of server objects
   */
  public function getAll($useCache=true) {
    $cache = new Cache();

    if($useCache) {
      // Try to get the data from the cache
      $serverData = $cache->get($this->cacheName);

      if($serverData) {
        return $serverData;
      }
    }

    // Fetch all servers from the database
    $servers = parent::getAll();

    $serverData = [];
    foreach($servers as $a) {
      $serverData[$a->getID()] = $a;
    }

    $cache->set($this->cacheName, $serverData);

    return $serverData;
  }


  /**
   * Get a server based on the provided ID, preferably from the cache
   * If the ID does not exist it returns the first server (which should always exist)
   *
   * @param id of the Server
   * @return Server object
   */
  public function get($id) {
    $cache = new Cache();

    $serverData = $cache->get($this->cacheName);

    if($serverData) {
      return $serverData[$id] ?? $serverData[1];
    }

    $serverData = $this->getAll();

    return $serverData[$id] ?? $serverData[1];
  }


  /**
   * Get a random sever
   * Note: this has rather poor performance when dealing with lots of servers
   *
   * @return Server object
   */
  public function getRandom() {
    $serverData = $this->getAll();

    $server = $serverData[array_rand($serverData)];

    if($server->getActive()) {
      return $server;
    } else {
      $active = false;
      foreach($serverData as $server) {
        if($server->getActive()) {
          $active = true;
        }
      }

      if($active) {
        // There is at least one active server
        return $this->getRandom();
      } else {
        // There are no active servers
        throw new exception('ERROR_NO_SERVER_AVAILABLE');
      }
    }
  }
}
