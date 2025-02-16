<?php
namespace Txtlog\Includes;

use Txtlog\Controller\Account;
use Txtlog\Controller\Server;
use Txtlog\Controller\Settings;
use Txtlog\Controller\Txtlog as TxtlogController;
use Txtlog\Controller\TxtlogRow as TxtlogRowController;
use Txtlog\Core\Common;
use Txtlog\Core\Constants;
use Txtlog\Entity\Txtlog as TxtlogEntity;
use Txtlog\Entity\TxtlogRow as TxtlogRowEntity;
use Txtlog\Includes\Cache;
use Txtlog\Includes\Dashboard;
use Txtlog\Includes\Error;
use Txtlog\Includes\Token;
use Exception;

// The main API class
class Api {
  /**
   * Requested action, e.g. action=token for endpoint /api/token
   *
   * @var string
   */
  private $action;


  /**
   * Settings object from the database
   *
   * @var object
   */
  private $settings;


  /**
   * Sitename for this page including https prefix
   *
   * @var string
   */
  private $baseUrl;


  /**
   * HTTP request method, e.g. GET or POST
   *
   * @var string
   */
  private $method;


  /**
   * Account object for the current request
   *
   * @var object
   */
  private $currentAccount;


  /**
   * IP address of the client
   *
   * @var string
   */
  private $IP;


  /**
   * The (default) HTTP code to send to the client
   *
   * @var int
   */
  private $httpCode = 200;


  /**
   * Posted variables when client sends a PATCH request
   *
   * @var string
   */
  private $patch;


  /**
   * Client provided username
   *
   * @var string
   */
  private $username;


  /**
   * Client provided password
   *
   * @var string
   */
  private $password;


  /**
   * Cache object
   *
   * @var object
   */
  private $cache;


  /**
   * Txtlog controller object
   *
   * @var object
   */
  private $txtlog;


  /**
   * TxtlogRow controller object
   *
   * @var object
   */
  private $txtlogRow;


  /**
   * Parsed token object
   *
   * @var object
   */
  private $parsedToken;


  /**
   * Optional token provided in the URL
   *
   * @var string
   */
  private $getToken;


  /**
   * Automatically add metadata to the log
   *
   * @var array
   */
  private $addMetadata = true;


  /**
   * Default mime type for output
   *
   * @var array
   */
  private $outputMime = 'application/json';


  /**
   * Constructor
   */
  public function __construct() {
    $this->method = Common::getRequestMethod();
    $this->username = Common::post('username', 200);
    $this->password = Common::post('password', 200);

    if($this->method == 'patch' || $this->method == 'delete') {
      $this->patch = Common::getPatch();
      $this->username = substr($this->patch['username'] ?? '', 0, 200);
      $this->password = substr($this->patch['password'] ?? '', 0, 200);
    }

    $this->settings = (new Settings)->get();
    $this->baseUrl = 'https://';
    $this->baseUrl .= $this->settings->getSitename();
    $this->baseUrl .= '/api/log/';
    $this->cache = new Cache();
    $this->txtlog = new TxtlogController();
    $this->txtlogRow = new TxtlogRowController();

    $this->currentAccount = (new Account)->get($this->settings->getAnonymousAccountID());
    $this->IP = Common::getIP();
    $url = Common::getRequest();
    $this->action = explode('/', $url.'/')[1];
    // Strip querystring
    if(strpos($this->action, '?') !== false) {
      $this->action = substr($this->action, 0, strpos($this->action, '?'));
    }
    $this->getToken = Common::getRequest('/api/log', true);
    $this->addMetadata = Common::getHttpHeader('X-Metadata') != 'false';

    // Support HTML output for viewing a log
    if(Common::get('type') == 'html') {
      $this->outputMime = 'text/html';
    } elseif(Common::get('type') == 'json') {
      // Use defaults
    } elseif(Common::getHttpAccept() == 'html' && $this->method == 'get') {
      $this->outputMime = 'text/html';
    }
  }


  /**
   * Verify the HTTP method set by the client
   *
   * @throws Exception possible error message
   * @return void
   */
  public function verifyMethod() {
    $validMethods = ['delete', 'get', 'patch', 'post', 'options'];

    if(!in_array($this->method, $validMethods)) {
      $this->handleFailRequest('ERROR_METHOD_NOT_ALLOWED');
    }
  }


  /**
   * Check if the client API usage is within set limits
   *
   * @throws Exception when limits are reached, containing a user readable error
   * @return void
   */
  public function verifyLimit() {
    // Check if this IP can do another request, throws an exception when there are too many failed attempts
    $this->cache->verifyIPFails();
  }


  /**
   * Parse a request
   *
   * @throws Exception possible error message
   * @return object with information for the client
   */
  public function parseRequest() {
    // The token can be provided in 1) Authorization header 2) URL for GET log 3) Cookie
    $request = Common::getRequest();
    $token = Common::getAuthorizationHeader();
    $token = empty($token) && $this->action == 'log' && $this->method == 'get' ? $this->getToken : $token;
    $token = empty($token) ? json_decode($_COOKIE['logurls'] ?? '')?->admin : $token;
    $inputData = Common::getUserInputRaw();

    // CORS preflight, necessary when hosting incoming log servers at a custom domain
    if($this->method == 'options') {
      exit;
    }

    $validActions = ['log', 'login', 'token'];
    if(!in_array($this->action, $validActions)) {
      $this->handleFailRequest('ERROR_INVALID_ACTION');
    }

    if($this->action == 'log' && $this->method == 'post' && strlen($token) == 0) {
      $this->cache->addIPUsageLog();
      return $this->insertTxtlog();
    }

    if($this->action == 'login' && $this->method == 'post' && strlen($this->username) > 0 && strlen($this->password) > 0) {
      return $this->login();
    }

    // All other functions require a token
    if(strlen($token) < 1) {
      $this->handleFailRequest('ERROR_NOT_FOUND');
    }
    // Fetch the requested Txtlog from the database
    try {
      $this->parsedToken = Token::parseToken($token);
    } catch(Exception $e) {
      $this->handleFailRequest($e->getMessage());
    }
    $existingTxtlog = $this->parsedToken->txtlog;
    if(empty($existingTxtlog->getID())) {
      $this->handleFailRequest('ERROR_NOT_FOUND');
    }

    if($this->action == 'log') {
      if($this->method == 'post') {
        return $this->insertTxtlogRow($existingTxtlog, $inputData);
      }

      if($this->method == 'get') {
        return $this->getTxtlogData($existingTxtlog);
      }

      if($this->method == 'patch') {
        $this->cache->addIPUsageLog();
        return $this->updateTxtlog($existingTxtlog);
      }

      if($this->method == 'delete') {
        $this->cache->addIPUsageLog();
        return $this->deleteTxtlog();
      }
    }

    if($this->action == 'token') {
      try {
        if($this->parsedToken->priv != Priv::Admin) {
          throw new Exception('ERROR_FORBIDDEN');
        }

        if($this->method == 'post') {
          $this->cache->addIPUsageLog();
          $token = Token::addToken($existingTxtlog);
          $this->httpCode = 201; // Created
          return strlen($token->get ?? '') > 0 ? ['url'=>"{$this->baseUrl}{$token->token}"] : ['token'=>$token->token];
        } elseif($this->method == 'delete') {
          $this->cache->addIPUsageLog();
          $token = substr($this->patch['token'] ?? '', 0, 100);
          return Token::deleteToken($existingTxtlog, $token);
        } elseif($this->method == 'get') {
          $this->cache->addIPUsage($existingTxtlog->getAccountID());
          return Token::getExistingTokens($existingTxtlog);
        } else {
          throw new Exception('ERROR_METHOD_NOT_ALLOWED');
        }
      } catch(Exception $e) {
        $this->handleFailRequest($e->getMessage());
      }
    }

    // A valid request should not reach this point
    $this->handleFailRequest('ERROR_UNKNOWN');
  }


  /**
   * Get a set of tokens with privileges admin, insert and view
   *
   * @param txtlog object of the existing log
   * @return array with tokens
   */
  private function getTokens($txtlog) {
    $adminToken = Token::generateToken($txtlog, 'admin');
    $insertToken = Token::generateToken($txtlog, 'insert');
    $viewToken = Token::generateToken($txtlog, 'view');

    return [
      $adminToken,
      $insertToken,
      $viewToken
    ];
  }


  /**
   * Insert a new Txtlog
   *
   * @throws Exception possible error message
   * @return object information for the client
   */
  public function insertTxtlog() {
    $name = Common::post('name', 100);

    // Get the preferred server for this webserver or a random server if none is specified
    $serverID = Constants::getPreferredDB() ?: (new Server)->getRandom()->getID();

    // Calculate retention
    $retentionInput = Common::post('retention', 4);
    $maxRetention = $this->currentAccount->getMaxRetention();
    $retention = Common::isInt($retentionInput, 1, $maxRetention) ? $retentionInput : $maxRetention;

    $txtlog = new TxtlogEntity();
    $txtlog->setID(Common::parseUniqueID(Common::getUniqueID(5))->int);
    $txtlog->setAccountID($this->currentAccount->getID());
    $txtlog->setServerID($serverID);
    $txtlog->setRetention($retention);
    $txtlog->setName($name);
    $txtlog->setIPAddress($this->IP);
    if(strlen($this->username) > 0 && strlen($this->password) > 0) {
      $txtlog->setUsername($this->username);
      $txtlog->setPassword($this->password);
    }

    // Generate and store a set of tokens
    $tokens = $this->getTokens($txtlog);
    $txtlog->setTokens($tokens);

    // Insert the new txtlog in the database
    $this->txtlog->insert($txtlog);

    $this->httpCode = 201; // Created
    $result = ['status'=>'created'];
    foreach($txtlog->getTokens() as $token) {
      if($token->priv == Priv::Admin) {
        $result['admin'] = $token->token;
      } elseif($token->priv == Priv::Insert) {
        $result['insert'] = $token->token;
      } elseif($token->priv == Priv::View) {
        $result['view'] = $this->baseUrl.$token->token;
      }
    }

    return $result;
  }


  /**
   * Update an existing Txtlog
   *
   * @param txtlog object of the existing log
   * @throws Exception Possible error message
   * @return object information for the client
   */
  public function updateTxtlog($txtlog) {
    if($this->parsedToken->priv != Priv::Admin) {
      $this->handleFailRequest('ERROR_FORBIDDEN');
    }

    $name = substr($this->patch['name'] ?? '', 0, 100);
    $usermsg = '';

    // Get the newest version from the database
    $txtlog = $this->txtlog->getByID($this->parsedToken->txtlog->getID(), useCache: false);

    // Update only fields which are supplied
    if(strlen($name) > 0) {
      if($name == '""') {
        $name = null;
      }
      $txtlog->setName($name);
    }

    if($this->username == $this->settings->getAdminUser()) {
      $this->handleFailRequest('ERROR_USERNAME_ALREADY_EXISTS');
    }

    // Update retention
    $retentionInput = substr($this->patch['retention'] ?? '', 0, 4);
    $maxRetention = (new Account)->get($txtlog->getAccountID())->getMaxRetention();
    $retention = Common::isInt($retentionInput, 1, $maxRetention) ? $retentionInput : $txtlog->getRetention();
    if($retention != $txtlog->getRetention()) {
      $txtlog->setRetention($retention);
    }

    // Set username and password/update password
    if(!$txtlog->getUserHash() && strlen($this->username) > 0 && strlen($this->password) > 0) {
      $usermsg = 'Username and password successfully set';
      $txtlog->setUsername($this->username);
      $txtlog->setPassword($this->password);
    } elseif(strlen($txtlog->getUserHash()) > 0 && strlen($this->password) > 0) {
      $usermsg = 'Password successfully updated';
      $txtlog->setPassword($this->password);
    }

    // Update the existing txtlog in the database
    $this->txtlog->update($txtlog);

    $return = [
      'status'=>'updated',
      'usermsg'=>$usermsg
    ];

    return $return;
  }


  /**
   * Login with a username and password, returning a set of tokens
   *
   * @throws Exception Possible error message
   * @return object information for the client
   */
  public function login() {
    $txtlog = $this->txtlog->getByUsername($this->username);
    if(!$txtlog->getID() || !password_verify($this->password, $txtlog->getPassword())) {
      $this->handleFailRequest('ERROR_FORBIDDEN');
    }

    $result = (object)[
      'account'=>(new Account)->get($txtlog->getAccountID())->getName(),
      'username'=>$this->username,
      'userhash'=>$txtlog->getUserHash(),
      'retention'=>$txtlog->getRetention(),
      'admin'=>'',
      'insert'=>'',
      'view'=>'',
    ];

    foreach($txtlog->getTokens() as $token) {
      if($token->priv  == Priv::Admin && empty($result->admin)) {
        $result->admin = $token->token;
      } elseif($token->priv == Priv::Insert && empty($result->insert)) {
        $result->insert = $token->token;
      } elseif($token->priv == Priv::View && empty($result->view) && strlen($token->get ?? '') < 1) {
        $result->view = $this->baseUrl.$token->token;
      }
    }

    return $result;
  }


  /**
   * Insert a new row into the cache, cron job inserts it in the database later
   *
   * @param txtlog object of the existing log
   * @param inputData raw data from the client
   * @throws Exception Possible error message
   * @return object information for the client
   */
  public function insertTxtlogRow($txtlog, $inputData) {
    $rowCount = 0;

    if(!$this->cache->get('ch_down_'.$txtlog->getServerID())) {
      try {
        $this->txtlogRow->setServer($txtlog->getServerID());
        $rowCount = $this->txtlogRow->rowCount($txtlog->getID());
      } catch(Exception $e) {
        // Allow inserting rows when ClickHouse is down
        $this->cache->set('ch_down_'.$txtlog->getServerID(), true, 20);
      }
    }

    // Check API usage
    if($rowCount > (new Account)->get($txtlog->getAccountID())->getMaxRows()) {
      $this->handleFailRequest('ERROR_TOO_MANY_REQUESTS');
    }
    $this->cache->addIPUsage($txtlog->getAccountID());

    // Check if the client can add rows to this log
    if(!in_array($this->parsedToken->priv, [Priv::Admin, Priv::Insert])) {
      $this->handleFailRequest('ERROR_FORBIDDEN');
    }

    $txtlogRow = new TxtlogRowEntity();
    $rowCount = 0;
    $now = time();
    $inputIsJson = true;

    if(is_array($inputData) || strlen($inputData) < 1) {
      $this->handleFailRequest('ERROR_INVALID_JSON');
    }

    // Try to to convert non-json to json
    if(!Common::isJson($inputData)) {
      $inputIsJson = false;
      $inputData = mb_substr($inputData, 0, $this->currentAccount->getMaxRowSize(), 'UTF-8');
      $inputData = json_encode(['data'=>$inputData]);
      if($inputData === false) {
        $this->handleFailRequest('ERROR_INVALID_JSON');
      }
    }

    // Parse the input and convert it to an array if necessary
    $input = json_decode($inputData);
    if(!is_array($input->rows ?? null)) {
      $input = (object)['rows'=>[$input]];
    }

    // Allow max. 20000 items per call
    $rows = array_slice($input->rows, 0, 20000);

    // Calculate retention offset
    $retentionOffset = $now - 60 * 60 * 24 * ($this->settings->getMaxRetention() - $txtlog->getRetention());

    foreach($rows as $row) {
      // Truncate long JSON rows, non JSON is already truncated
      if($inputIsJson && strlen(json_encode($row)) > $this->currentAccount->getMaxRowSize()) {
        $row = mb_substr(json_encode($row), 0, $this->currentAccount->getMaxRowSize(), 'UTF-8');
      }

      // Skip empty rows
      if(empty($row)) {
        continue;
      }

      if(Common::isDateISO8601($row->date ?? null)) {
        $ts = Common::getTimestampFromISO8601Date($row->date);

        // Check if timestamp is within valid range, -1 day, + 1 day
        if($ts < strtotime('-1 day') * 1000 || $ts > strtotime('+1 day') * 1000) {
          continue;
        }
        // 1709958144000 -> 1709958144.000
        $id = Common::getUniqueID(13, substr_replace($ts, '.', -3, 0));
      } else {
        $id = Common::getUniqueID(13);
      }

      // Store metadata in a JSON element txtlog_metadata
      $metaname = 'txtlog_metadata';
      if($this->addMetadata && !is_string($row) && !isset($row->$metaname)) {
        // If metadata is found, add it to the JSON
        $metadata = (object)[
          'ip'=>$this->cache->getGeoIP($row->ip ?? ''),
          'browser'=>$this->cache->parseUserAgent($row->browser ?? ''),
        ];

        $row->$metaname = (object)[];

        // Store the sender IP
        $row->$metaname->origin_ip = $this->IP;

        if(!empty($metadata->ip->country)) {
          $row->$metaname->ip_country = $metadata->ip->country.' ('.($metadata->ip->cc ?? '').')';
        }
        if(!empty($metadata->ip->provider)) {
          $row->$metaname->ip_provider = $metadata->ip->provider;
        }
        if(!empty($metadata->ip->tor)) {
          $row->$metaname->ip_tor = $metadata->ip->tor;
        }
        if(!empty($metadata->browser->name)) {
          $row->$metaname->browser_name = $metadata->browser->name;
        }
        if(!empty($metadata->browser->version)) {
          $row->$metaname->browser_version = $metadata->browser->version;
        }
        if(!empty($metadata->browser->os)) {
          $row->$metaname->browser_os = $metadata->browser->os;
        }
        // Remove metadata if nothing is available, {} != empty so cast to an array
        if(empty((array)$row->$metaname)) {
          unset($row->$metaname);
        }
      }

      $data = json_encode($row);
      if($data === false) {
        $this->handleFailRequest('ERROR_INVALID_JSON');
      }

      $txtlogRow->setTxtlogID($txtlog->getID());
      $txtlogRow->setID($id);
      $txtlogRow->setTimestamp($retentionOffset);
      $txtlogRow->setData($data);
      $txtlogRow->setSearchFields(implode(',', array_merge(Common::extractIPs($data), Common::extractUUIDs($data))));

      // Store the row in the cache (Redis stream) to insert it later in batches
      $this->cache->streamAdd('txtlogrows', ['server'=>$txtlog->getServerID(), 'row'=>gzcompress(serialize($txtlogRow))]);
      $rowCount++;
    }

    if($rowCount < 1) {
      $this->handleFailRequest('ERROR_NO_VALID_ROWS');
    }
    $this->cache->addIPUsage($txtlog->getAccountID(), $rowCount - 1);

    $this->httpCode = 202; // Accepted
    $return = [
      'status'=>'accepted',
      'inserts'=>$rowCount
    ];

    return $return;
  }


  /**
   * Get data from a Txtlog
   *
   * @param txtlog object of the existing log
   * @return Txtlog data object
   */
  protected function getTxtlogData($txtlog) {
    $this->cache->addIPUsage($txtlog->getAccountID());

    if(!in_array($this->parsedToken->priv, [Priv::Admin, Priv::View])) {
      $this->handleFailRequest('ERROR_FORBIDDEN');
    }

    $this->cache->addIPUsageGet();
    $this->txtlogRow->setServer($txtlog->getServerID());

    // Cancel query after this many seconds
    $timeout = (new Account)->get($txtlog->getAccountID())->getQueryTimeout() ?? 10;
    $search = (object)[
      'after'=>null,
      'before'=>null,
      'date'=>null,
      'searchfields'=>[],
      'data'=>[],
    ];

    // Data search
    $data = Common::get('data', Token::getMaxSearchLength()) ?? null;

    // Public dashboard
    $data = strlen($this->parsedToken->get ?? '') > 0 ? "{$this->parsedToken->get} $data" : $data;

    // Clickhouse tokens split on everything except a-zA-z0-9
    foreach(preg_split('/[^a-zA-Z0-9.:-]+/', $data, -1, PREG_SPLIT_NO_EMPTY) as $token) {
      // Add special search options for an IP address or UUID
      if(Common::isIP($token)) {
        $search->searchfields[] = str_replace(['.',':'], '', $token);
        if(count($search->searchfields) > 20) {
          break;
        }
      } elseif(Common::isUUID($token)) {
        $search->searchfields[] = str_replace('-', '', $token);
        if(count($search->searchfields) > 20) {
          break;
        }
      } else {
        foreach(preg_split('/[.:-]+/', $token, -1, PREG_SPLIT_NO_EMPTY) as $subtoken) {
          $search->data[] = $subtoken;
          if(count($search->data) > 20) {
            break 2;
          }
        }
      }
    }

    // Paging
    $before = Common::get('before');
    $search->before = strlen($before) == 24 && Common::isHex($before) ? $before : null;

    $after = Common::get('after');
    $search->after = strlen($after) == 24 && Common::isHex($after) ? $after : null;

    $dateInput = Common::get('date');
    if($dateInput) {
      $strlen = strlen($dateInput);
      if($strlen == 23 || $strlen == 24) {
        // "2023-12-30 14:42:36.745","2023-12-30 14:42:36.745Z","2023-12-30T14:42:36.745","2023-12-30T14:42:36.745Z" -> "2023-12-30T14:42:36.745Z"
        $dateInput = rtrim(str_replace(' ', 'T', $dateInput), 'Z').'Z';
      } elseif($strlen == 10 && preg_match('/^2\d{3}-\d{2}-\d{2}$/', $dateInput)) {
        // "2023-12-30" -> "2023-12-30T29:59:59.999Z"
        $dateInput = "{$dateInput}T23:59:59.999Z";
      } elseif($strlen == 19 && preg_match('/^2\d{3}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/', $dateInput)) {
        // "2023-12-30 15:10:09","2023-12-30T15:10:09" -> "2023-12-30T15:10:09.999Z"
        $dateInput = str_replace(' ', 'T', $dateInput).'.999Z';
      }

      // Check if the provided date(time) is within a valid range
      if(Common::isDateISO8601($dateInput) && common::isInt(substr(Common::getTimestampFromISO8601Date($dateInput), 0, -3), strtotime('-365 day'), strtotime('+1 day'))) {
        $search->date = Common::getUniqueID(13, substr_replace(Common::getTimestampFromISO8601Date($dateInput), '.', -3, 0));
      }
    }

    // Get the total number of rows
    $rowCount = $this->txtlogRow->rowCount($txtlog->getID());

    // Limit number of returned rows
    $limitInput = Common::get('limit', 6);
    $limitMax = Constants::getMaxRows() ?? 1000;
    $limit = Common::isInt($limitInput, 1, $limitMax) ? $limitInput : (new Account)->get($txtlog->getAccountID())->getDashboardRows();

    // When searching for strings limit the results to the Account search limit
    if(count($search->searchfields) > 0 || count($search->data) > 0) {
      $limit = min((new Account)->get($txtlog->getAccountID())->getDashboardRowsSearch(), $limit);
    }

    // Get data from the database
    $rows = $this->txtlogRow->get($txtlog->getID(), $limit, $search, $timeout);

    $result = $this->getLogMetadata($txtlog, $rowCount);

    if($dateInput && !$search->date) {
      $result['log']->date_error = 'Invalid date format';
    }

    if($this->txtlogRow->getTimeout()) {
      $result['log']->warning = 'Search timeout, showing partial results.';
    }

    foreach($rows as $r) {
      $result['rows'][] = (object)[
        'date'=>$r->getCreationDate(),
        'log'=>json_decode($r->getData())
      ];
    }

    if(!empty($result['log']->url)) {
      // Construct URL for redirecting
      $params = [
        'date'=>Common::get('date') ?: null,
        'limit'=>Common::get('limit') ?: null,
        'data'=>Common::get('data') ?: null,
      ];
      $hasParams = strlen(implode('', $params)) > 0;
      $result['log']->base_url = $result['log']->url;
      $result['log']->url .= $hasParams ? '?'.http_build_query($params) : '';

      // Construct previous/next URLs
      $firstID = empty($rows) ? '' : $rows[0]->getID();
      $lastID = empty($rows) ? '' : end($rows)->getID();
      $result['log']->prev = $result['log']->base_url.(empty($firstID) ? '' : '?'.http_build_query($params + ['after'=>"$firstID"]));
      $result['log']->next = $result['log']->base_url.(empty($lastID) ? '' : '?'.http_build_query($params + ['before'=>"$lastID"]));

      // When navigating to the previous/next page (with a browser) and there is no data, redirect to the default page
      if($this->outputMime == 'text/html' && ($search->before || $search->after) && count($rows) == 0) {
        Common::redirect($result['log']->url);
      }
    }

    return (object)$result;
  }


  /**
   * Get an array with log metadata suitable for output
   *
   * @param txtlog object of the existing log
   * @param rowCount total number of rows
   * @return array
   */
  private function getLogMetadata($txtlog, $rowCount) {
    $priv = $this->parsedToken->priv;
    // If the client provided the token in the URL, use if in future requests as well
    $url = $this->baseUrl.$this->getToken;

    return [
      'log'=>(object)[
        'base_url'=>$url,
        'url'=>$url,
        'prev'=>'',
        'next'=>'',
        'fixed_search'=>$this->parsedToken->getUrl ?? '',
        'create'=>$txtlog->getCreationDate(),
        'modify'=>$txtlog->getModifyDate() ?? '',
        'name'=>$txtlog->getName(),
        'authorization'=>strlen($txtlog->getPassword()) > 0 ? 'yes' : 'no',
        'retention'=>$txtlog->getRetention().' days',
        'total_rows'=>$rowCount
      ]
    ];
  }


  /**
   * Delete an existing Txtlog
   *
   * @return void
   */
  private function deleteTxtlog() {
    if($this->parsedToken->priv != Priv::Admin) {
      $this->handleFailRequest('ERROR_FORBIDDEN');
    }

    $this->txtlog->delete($this->parsedToken->txtlog);

    $this->httpCode = 200; // OK

    $return = ['status'=>'deleted'];

    return $return;
  }


  /**
   * Store a failed attempt in the cache and throw an exception with the error message
   *
   * @param message the error message to show to the client
   * @throws Exception error message (always)
   * @return void
   */
  private function handleFailRequest($message) {
    $this->cache->addIPFail();

    throw new Exception($message);
  }


  /**
   * Sets the appropriate HTTP code based on an error message
   * This method does not yet send the HTTP status code to the client
   *
   * @param message the message which can be translated to a numeric http status code
   * @return void
   */
  public function setHttpCode($message) {
    $this->httpCode = Error::getHttpCode($message);
  }


  /**
   * Send the HTTP status code to the client
   *
   * @return void
   */
  public function sendHttpCode() {
    Common::setHttpResponseCode($this->httpCode);
  }


  /**
   * Set the correct return type header
   *
   * @return void
   */
  public function setContentHeader() {
    header("Content-Type: {$this->outputMime}");
  }


  /**
   * Output the content in the requested format
   *
   * @param JSON output to send
   * @return void
   */
  public function setContent($output) {
    if($this->outputMime == 'application/json') {
      echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } else {
      $GLOBALS['app']->showHeader(force: true);
      echo Dashboard::getHTML($output);
      $GLOBALS['app']->showFooter(force: true);
    }
  }
}
