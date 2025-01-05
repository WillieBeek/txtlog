<?php
namespace Txtlog\Controller;

use Txtlog\Core\Common;
use Txtlog\Core\Constants;
use Txtlog\Includes\Cache;
use Txtlog\Model\TxtlogRowDB;

class TxtlogRow extends TxtlogRowDB {
  /**
   * Count the number of rows for a given Txtlog ID
   *
   * @param txtlogID
   * @return int row count
   */
  public function rowCount($txtlogID) {
    $cache = new Cache();
    $cacheName = "$txtlogID.count";

    $rowCount = $cache->get($cacheName);

    if($rowCount) {
      return $rowCount;
    }

    $rowCount = parent::count($txtlogID);

    $cache->set($cacheName, $rowCount, 60);

    return $rowCount;
  }


  /**
   * Get all TxtlogRow rows for a given Txtlog ID
   *
   * @param txtlogID
   * @param limit, retrieve max this number of rows
   * @param search object with search keys and values
   * @param timeout cancel the query after this many seconds (fractions are possible) and return the partial results
   * @return array of TxtlogRow objects
   */
  public function get($txtlogID, $limit, $search, $timeout) {
    // Make sure limit is an integer and within a valid range
    $max = Constants::getMaxRows() ?? 1000;
    $realLimit = Common::isInt($limit, 1, $max) ? $limit : $max;

    return parent::get($txtlogID, $realLimit, $search, $timeout);
  }
}
