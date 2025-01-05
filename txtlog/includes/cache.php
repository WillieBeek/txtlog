<?php
namespace Txtlog\Includes;

use Txtlog\Controller\Account;
use Txtlog\Controller\Settings;
use Exception;
use Txtlog\Core\Common;
use Txtlog\Core\Constants;
use Txtlog\Includes\Country;

/**
 * Txtlog\Includes\Cache
 *
 * Store and retreive information using a (fast and non persistent) memory cache
 */
class Cache {
  /**
   * IP Address of the client doing the request
   *
   * @var string
   */
  private $IPAddress;


  /**
   * Redis object
   *
   * @var string
   */
  private $redis;


  /**
   * First digit of current minutes, e.g. 4 for 09:42
   *
   * @var int
   */
  private $minute;


  /**
   * Constructor
   *
   * @param exitOnError optional set to false to continue the script after a fatal connection error
   */
  public function __construct($exitOnError=true) {
    $this->IPAddress = Common::getIP();
    $error = true;
    $this->minute = substr(date('i'), 0, 1);

    $redisHost = Constants::getRedisHost();
    $redisPort = Constants::getRedisPort();

    if(strlen($redisHost) > 0) {
      try {
        $this->redis = new \Redis();

        if(Common::isInt($redisPort) && Common::isValidDomain($redisHost)) {
          $this->redis->pconnect($redisHost, $redisPort);
        } elseif(substr($redisHost, 0, 1) == '/') {
          // Redis Host starts with a slash and is not an URL, assume it's a unix socket
          $this->redis->pconnect($redisHost);
        } else {
          throw new Exception('Invalid redis configuration');
        }
        $this->setDefaultSerializer();

        $error = false;
      } catch(\RedisException $e) {
      }
    }

    if($error) {
      if($exitOnError) {
        // 503 Service Unavailable
        $httpCode = 503;
        Common::setHttpResponseCode($httpCode);
        exit;
      } else {
        throw new Exception('Cannot connect to Redis');
      }
    }
  }


  /**
   * Set the default serializer, needed for storing arrays in Redis
   *
   * @return void
   */
  private function setDefaultSerializer() {
    // Serialize data when needed, see  https://github.com/phpredis/phpredis
    $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
  }


  /**
   * Get an entry from the cache
   *
   * @param key to get
   * @return cached value
   */
  public function get($key) {
    return $this->redis->get($key);
  }


  /**
   * Set an entry in the cache, overwrite if it already exists
   *
   * @param key to set
   * @param value the data to store in the cache
   * @param ttl optional time-to-live in seconds
   * @return void
   */
  public function set($key, $value, $ttl=null) {
    if(is_null($ttl)) {
      $this->redis->set($key, $value);
    } else {
      $this->redis->set($key, $value, ['ex'=>$ttl]);
    }
  }


  /**
   * Set an entry in the cache and return false if the key already exists
   *
   * @param key to set
   * @param value the data to store in the cache
   * @param ttl time-to-live in seconds
   * @return bool true if successful
   */
  public function setNx($key, $value, $ttl) {
    return $this->redis->set($key, $value, ['nx', 'ex'=>$ttl]);
  }


  /**
   * Delete an entry from the cache
   *
   * @param key to delete
   * @return number of keys deleted
   */
  public function del($key) {
    return $this->redis->unlink($key);
  }


  /**
   * Add an item to a stream
   *
   * @param name of the stream
   * @param value
   * @return void
   */
  public function streamAdd($name, $value) {
    if(!is_array($value)) {
      $value = [$value];
    }

    // * to auto generate an ID
    $this->redis->xAdd($name, "*", $value);
  }


  /**
   * Get items from a stream
   *
   * @param name of the stream
   * @param id of the first message to get
   * @param count, optional defaults to 1000 records
   * @return array with stream data
   */
  public function streamRead($name, $id, $count=1000) {
    return $this->redis->xRead([$name=>$id], $count);
  }


  /**
   * Delete items from a stream
   *
   * @param name of the stream
   * @param ids array with ids to delete
   * @return number of messages removed
   */
  public function streamDel($name, $ids) {
    if(empty($ids)) {
      return 0;
    }

    return $this->redis->xDel($name, $ids);
  }


  /**
   * Add items to an unsorted set
   *
   * @param key string
   * @param value string (multiple vaules are allowed)
   * @return true if the element is added
   */
  public function setAdd($key, ...$arr) {
    $result =  $this->redis->sAdd($key, ...$arr);

    return $result;
  }


  /**
   * Add items to a sorted set
   *
   * @param key string
   * @param score string (multiple scores are allowed)
   * @param value string (multiple vaules are allowed)
   * @return true if the element is added
   */
  public function zSetAdd($key, ...$arr) {
    // Disable serializer to store raw values instead of serialized values so sorting works
    $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);

    $result =  $this->redis->zAdd($key, ...$arr);

    // Restore default serializer
    $this->setDefaultSerializer();

    return $result;
  }


  /**
   * Get Geo IP information from cached IP information
   *
   * @param ip optional
   * @return object with IP and geo IP information
   */
  public function getGeoIP($ip=null) {
    $ip = $ip ?? Common::getIP();

    $result = (object)[
      'ip'=>$ip,
      'cc'=>'',
      'country'=>'',
      'provider'=>'',
      'tor'=>false
    ];
    if(!Common::isIP($ip)) {
      return $result;
    }

    // Loopback exception, not in the ASN list
    if(substr($ip, 0, 4) == '127.' || $ip == '::1') {
      $result->provider = 'localhost';
    } else {
      $ipInt = str_pad((string) gmp_import(inet_pton($ip)), 39, '0', STR_PAD_LEFT);

      $geoIP = explode(':', $this->redis->zRangeByLex('ip', "[$ipInt", '+', 0, 1)[0] ?? '');

      $result->cc = $geoIP[1] ?? '';
      $result->country = Country::getCountry($geoIP[1] ?? '');
      $result->provider = $geoIP[2] ?? '';
      $result->tor = $this->redis->sIsMember('tor', $ip);
    }

    return $result;
  }


  /**
   * Parse the user agent HTTP header in a readable object
   * PHP get_browser is still rather slow, so use a cache if possible
   *
   * @param optional user agent
   * @return object
   */
  public function parseUserAgent($ua='') {
    $ua = $ua ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
    if(empty($ua)) {
      return (object)[];
    }
    $cacheName = 'browser_'.substr(hash('sha256', $ua), 0, 32);
    $cacheValue = $this->get($cacheName);

    if($cacheValue) {
      return $cacheValue;
    }

    $browserInfo = get_browser($ua);
    $browser = $browserInfo->browser;
    $version = $browserInfo->version;
    // Parse user agents like curl/7.88.1
    if(substr($ua, 0, 5) == 'curl/') {
      $version = substr($ua, 5);
    }
    $os = $browserInfo->platform;

    $cacheValue = (object)[
      'ua'=>$ua,
      'name'=>$browser,
      'version'=>$version,
      'os'=>$os,
      'get_browser'=>$browserInfo
    ];

    $this->set($cacheName, $cacheValue, 240);

    return $cacheValue;
  }


  /**
   * Add generic IP usage
   *
   * @param accountID
   * @param incr optional to increase with more than 1
   * @throws Exception error message when the API limit has been reached
   * @return void
   */
  public function addIPUsage($accountID, $increase=1) {
    if($increase < 1) {
      return;
    }

    $name = "apiusage.{$this->minute}.{$this->IPAddress}";

    if($this->get($name) >= (new Account)->get($accountID)->getMaxIPUsage()) {
      throw new Exception('ERROR_TOO_MANY_REQUESTS');
    }

    // Use 'NX' modifier on expire when php-redis is updated on Ubuntu?
    $this->redis->multi()
      ->incr($name, $increase)
      ->expire($name, 600)
      ->exec();
  }

  /**
   * Update API usage for creating, updating or deleting log metadata
   *
   * @throws Exception error message when the API limit has been reached
   * @return void
   */
  public function addIPUsageLog() {
    $name = "apiusage.log.{$this->minute}.{$this->IPAddress}";

    if($this->get($name) >= (new Settings)->get()->getMaxAPICallsLog()) {
      throw new Exception('ERROR_TOO_MANY_REQUESTS');
    }

    $this->redis->multi()
      ->incr($name, 1)
      ->expire($name, 600)
      ->exec();
  }


  /**
   * Update API usage for getting log data
   *
   * @param increase optional to add this number of api usages (default 1)
   * @throws Exception error message when the API limit has been reached
   * @return void
   */
  public function addIPUsageGet($increase=1) {
    $name = "apiusage.get.{$this->minute}.{$this->IPAddress}";

    if($this->get($name) >= (new Settings)->get()->getMaxAPICallsGet()) {
      throw new Exception('ERROR_TOO_MANY_REQUESTS');
    }

    $this->redis->multi()
      ->incr($name, $increase)
      ->expire($name, 600)
      ->exec();
  }


  /**
   * Log one failed API call for the client IP
   *
   * @return void
   */
  public function addIPFail() {
    $name = "apifail.{$this->minute}.{$this->IPAddress}";

    $this->redis->multi()
      ->incr($name, 1)
      ->expire($name, 600)
      ->exec();
  }


  /**
   * Check if the current client IP can do another request
   *
   * @throws Exception error message when the API fail limit has been reached
   * @return void
   */
  public function verifyIPFails() {
    if($this->get("apifail.{$this->minute}.{$this->IPAddress}") >= (new Settings)->get()->getMaxAPIFails()) {
      throw new Exception('ERROR_TOO_MANY_REQUESTS');
    }
  }
}
