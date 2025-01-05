<?php
namespace Txtlog\Controller;

use Txtlog\Entity\Account as AccountEntity;
use Txtlog\Includes\Cache;
use Txtlog\Model\AccountDB;

class Account extends AccountDB {
  /**
   * Cache name for the accounts
   *
   * @var int
   */
  private $cacheName = 'account';


  /**
   * Get all accounts
   *
   * @param useCache optional boolean, set to false to ignore cache
   * @return array with account objects
   */
  public function getAll($useCache=true) {
    $cache = new Cache();

    if($useCache) {
      // Try to get the data from the cache
      $accountData = $cache->get($this->cacheName);

      if($accountData) {
        return $accountData;
      }
    }

    // Fetch all accounts from the database
    $accounts = parent::getAll();

    $accountData = [];
    foreach($accounts as $a) {
      $accountData[$a->getID()] = $a;
    }

    $cache->set($this->cacheName, $accountData);

    return $accountData;
  }


  /**
   * Get an account based on the provided ID, preferably from the cache
   *
   * @param id
   * @return Account object
   */
  public function get($id) {
    $cache = new Cache();

    $accountData = $cache->get($this->cacheName);

    if($accountData) {
      return $accountData[$id] ?? new AccountEntity;
    }

    $accountData = $this->getAll();

    return $accountData[$id];
  }
}
