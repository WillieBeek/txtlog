<?php
namespace Txtlog\Controller;

use Txtlog\Core\Common;
use Txtlog\Core\Constants;
use Txtlog\Entity\Txtlog as TxtlogEntity;
use Txtlog\Includes\Cache;
use Txtlog\Model\TxtlogDB;

class Txtlog extends TxtlogDB {
  /**
   * Get a list of Txtlog objects
   *
   * @param limit max number of rows to return
   * @return Txtlog objects
   */
  public function getAll($limit) {
    $max = Constants::getMaxRows() ?? 1000;
    $realLimit = Common::isInt($limit, 1, $max) ? $limit : $max;

    return parent::getAll($realLimit);
  }


  /**
   * Get a single Txtlog by hex code
   *
   * @param Txtlog ID hex value
   * @param useCache optional boolean, set to false to ignore cache
   * @return Txtlog object
   */
  public function getByHex($hex, $useCache=true) {
    if(strlen($hex) != 16) {
      return new TxtlogEntity();
    }

    $id = Common::hexToInt($hex);

    return $this->getByID($id, $useCache);
  }


  /**
   * Get a single Txtlog by ID
   *
   * @param Txtlog ID
   * @param useCache optional boolean, set to false to ignore cache
   * @return Txtlog object
   */
  public function getByID($id, $useCache=true) {
    $cache = new Cache();

    if($useCache) {
      $cacheTxtlog = $cache->get("txtlog.$id");

      if($cacheTxtlog) {
        return $cacheTxtlog;
      }
    }

    $cacheTxtlog = $this->get($id);
    if(!empty($cacheTxtlog->getID())) {
      $cache->set("txtlog.$id", $cacheTxtlog, 30);
    }

    return $cacheTxtlog;
  }


  /**
   * Get a single Txtlog by username
   *
   * @param username
   * @param useCache optional boolean, set to false to ignore cache
   * @return Txtlog object
   */
  public function getByUsername($username, $useCache=true) {
    $txtlog = new TxtlogEntity();
    if(strlen($username) < 1) {
      return $txtlog;
    }

    $txtlog->setUsername($username);
    $userHash = $txtlog->getUserHash();

    return $this->getByUserHash($userHash, $useCache);
  }


  /**
   * Get a single Txtlog by userHash
   *
   * @param userHash
   * @param useCache optional boolean, set to false to ignore cache
   * @return Txtlog object
   */
  public function getByUserHash($userHash, $useCache=true) {
    $cache = new Cache();
    $txtlog = new TxtlogEntity();

    if(strlen($userHash) != 16) {
      return $txtlog;
    }

    if($useCache) {
      $cacheTxtlog = $cache->get("txtlog.$userHash");

      if($cacheTxtlog) {
        return $cacheTxtlog;
      }
    }

    $cacheTxtlog = $this->get(null, $userHash);
    if(!empty($cacheTxtlog->getID())) {
      $cache->set("txtlog.$userHash", $cacheTxtlog, 30);
    }

    return $cacheTxtlog;
  }


  /**
   * Update an existing Txtlog
   *
   * @param txtlog object with the new data
   * @return void
   */
  public function update($txtlog) {
    parent::update($txtlog);

    // Update cached info (note: use a shared cache or custom cache invalidation when using multiple webservers)
    $cache = new Cache();
    $cache->del("txtlog.{$txtlog->getUserHash()}");
    $cache->del("txtlog.{$txtlog->getID()}");

    $this->getByID($txtlog->getID(), useCache: false);
  }


  /**
   * Delete a Txtlog
   *
   * @param txtlog object
   * @return void
   */
  public function delete($txtlog) {
    parent::delete($txtlog);

    // Remove from the cache
    (new Cache)->del('txtlog.'.$txtlog->getID());
  }
}
