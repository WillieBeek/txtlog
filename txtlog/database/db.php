<?php
namespace Txtlog\Database;

use Txtlog\Core\Common;
use Txtlog\Core\Constants;
use Txtlog\Database\ConnectionException;
use PDO;
use Exception;

class db {
  /**
   * PDO database connection object to the (local) database
   *
   * @var PDO resource
   */
  private $dbh=null;


  /**
   * Open a new database connection
   *
   * @param dbhost overrule database server hostname from settings
   * @param dbname overrule database name from settings
   * @param dbport overrule database port from settings
   * @param dbuser overrule database user from settings
   * @param dbpass overrule database pass from settings
   * @param options overrule PDO options array
   * @param exitOnError optional set to false to continue after a connection error
   * @return database handler
   */
  public function open($dbhost=null, $dbname=null, $dbport=null, $dbuser=null, $dbpass=null, $options=null, $exitOnError=true) {
    $dbhost = $dbhost ?? Constants::getDBHost();
    // IPv6 requires brackets around the IP address
    $dbhost = Common::isIPv6($dbhost) ? "[$dbhost]" : $dbhost;
    $dbname = $dbname ?? Constants::getDBName();
    $dbport = $dbport ?? Constants::getDBPort();
    $dbuser = $dbuser ?? Constants::getDBUser();
    $dbpass = $dbpass ?? Constants::getDBPass();
    $options = $options ?? Constants::getDBOpts();

    $failed = false;
    try {
      $this->dbh = new PDO("mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass, $options);
    } catch(PDOException $e) {
      $failed = true;
    } catch(Exception $e) {
      $failed = true;
    }

    if($failed) {
      file_put_contents('/tmp/db.pdoexception', date('d-m-y H:i:s')."\nFunction arguments=\n".print_r(func_get_args(), true)."\n".print_r($e, true), FILE_APPEND);
      if($exitOnError) {
        // 500 Internal Server Error
        $httpCode = 500;
        Common::setHttpResponseCode($httpCode);
        exit;
      } else {
        // Handle in the caller function, don't show the exception to the client because it may contain confidential information
        throw new ConnectionException("Database connection failed: {$e->getMessage()}");
      }
    }

    return $this->dbh;
  }


  /**
   * Close the database connection
   *
   * @return void
   */
  public function __destruct() {
    $this->dbh = null;
  }


  /**
   * Execute any kind of SQL query
   *
   * @param query to execute
   * @param params optional parameters to use in the query
   * @param error throw this error message when an error occurs, instead of logging it and throwing the generic message
   * @return array
   */
  public function execute($query, $params=null, $error=null) {
    $result = null;

    if(empty($query)) {
      return;
    }

    // Parameters need to be passed in an array
    if(!empty($params) && !is_array($params)) {
      $params = [$params];
    }

    // Initialize the connection
    if(!$this->dbh) {
      $this->open();
    }

    try {
      if(!($stmt = $this->dbh->prepare($query))) {
        throw new Exception('Cannot prepare sql query');
      }

      // Check for parameters to bind
      if(empty($params)) {
        // No arguments, just execute the query
        $exec_result = $stmt->execute();
      } else {
        // Columns can be nullable ENUMs where an empty string is not valid, so always convert empty strings to NULL
        foreach($params as $key=>$value) {
          if(is_string($value) && $value == '') {
            $params[$key] = null;
          }
        }
        $exec_result = $stmt->execute($params);
      }

      $result = $stmt->fetchAll(PDO::FETCH_OBJ);

      if(!$exec_result) {
        throw new Exception('Cannot execute sql statement');
      }
    } catch(Exception $e) {
      if(!is_null($error)) {
        throw new Exception($error);
      }
      // Log a detailed error but don't show it to the client
      $this->logError($stmt, $e, $query, $params);
      throw new Exception('ERROR_UNKNOWN');
    } catch(PDOException $e) {
      // Log a detailed error but don't show it to the client
      $this->logError($stmt, $e, $query, $params);
      throw new Exception('ERROR_UNKNOWN');
    }

    return $result;
  }


  /**
   * Get a single table row
   *
   * @param query to execute
   * @param params optional parameters to use in the query
   * @return std object
   */
  public function getRow($query, $params=null) {
    $result = $this->execute("$query LIMIT 1", $params);

    return $result[0] ?? null;
  }


  /**
   * Log a database error
   *
   * @param stmt PDO statement object
   * @param trace containing an exception
   * @param query which caused the error
   * @param params used in the query
   * @return void
   */
  private function logError($stmt, $trace, $query, $params) {
    $info = '';
    $info .= (new \DateTimeImmutable())->format('Y-m-d H:i:s.v')." - Database error\n";

    $info .= "PDO::errorInfo():\n";
    $info .= var_export($this->dbh->errorInfo(), true);

    $info .= "PDOStatement::errorInfo():\n";
    $info .= var_export($stmt->errorInfo(), true)."\n";

    $info .= "Query start:\n";
    $info .= substr($query, 0, 1024)."\n";

    $info .= "Params start:\n";
    $info .= substr(var_export($params, true), 0, 1024)."\n";

    file_put_contents('/tmp/db.pdoexception', $info, FILE_APPEND);
  }
}
