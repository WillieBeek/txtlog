<?php
namespace Txtlog\Model;

use Txtlog\Controller\Server;
use Txtlog\Entity\TxtlogRow as TxtlogRowEntity;
use Txtlog\Database\db;

class TxtlogRowDB extends db {
  /**
   * True if a timeout probably happened during a select query
   *
   * @var bool
   */
  private $timeout;


  /**
   * Rows are distributed over multiple servers, use this function to connect to the owner server
   *
   * @param serverID
   * @return void
   */
  public function setServer($serverID) {
    $server = (new Server)->get($serverID);

    // Use specified server settings
    $dbhost = $server->getHostname();
    $dbname = $server->getDBName();
    $dbport = $server->getPort();
    $dbuser = $server->getUsername();
    $dbpass = $server->getPassword();
    $dboptions = $server->getOptions();

    // On failure continue the script, used in the cron job to prevent one server down from stopping the script
    $exitOnError = false;

    $this->open($dbhost, $dbname, $dbport, $dbuser, $dbpass, $dboptions, $exitOnError);
  }


  /**
   * Get the timeout status of the most recent select query
   *
   * @return bool
   */
  public function getTimeout() {
    return $this->timeout;
  }


  /**
   * Count the number of rows for a given Txtlog ID
   *
   * @param txtlogID
   * @return int row count
   */
  public function count($txtlogID) {
    return $this->getRow('SELECT '
      .'COUNT() AS Rows '
      .'FROM TxtlogRow '
      .'WHERE TxtlogID=?', $txtlogID)->Rows;
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
  protected function get($txtlogID, $limit, $search, $timeout) {
    $this->timeout = false;
    $order = 'DESC';
    $params = [$txtlogID];
    $where = '';
    $txtlogRow = new TxtlogRowEntity();

    foreach($search->data as $data) {
      if(strlen($data) > 0) {
        $where .= 'AND hasTokenCaseInsensitiveOrNull(Data, ?) ';
        $params[] = mb_strtolower($data, 'UTF-8');
      }
    }

    foreach($search->searchfields as $searchfield) {
      if(strlen($searchfield) > 0) {
        $where .= 'AND hasTokenCaseInsensitiveOrNull(SearchFields, ?) ';
        $params[] = mb_strtolower($searchfield, 'UTF-8');
      }
    }

    if(strlen($search->date) > 0) {
      $where .= 'AND ID <= UNHEX(?) ';
      $params[] = $search->date;
    }

    if(strlen($search->before) > 0) {
      $where .= 'AND ID < UNHEX(?) ';
      $params[] = $search->before;
    }

    if(strlen($search->after) > 0) {
      $where .= 'AND ID > UNHEX(?) ';
      $params[] = $search->after;
      $order = 'ASC';
    }

    /* This needs to be done with a CTE because after max_execution_time an exception is thrown and no data is returned when using PDO.
     * Using "timeout_overflow_mode='break'" causes PHP/Apache to wait for results never coming, resulting in a Gateway timeout after 5 minutes (default)
     */
    $start = microtime(true);
    $records = $this->execute('WITH cte_result AS (SELECT '
      .'HEX(ID) AS HexID, '
      .'Data '
      .'FROM TxtlogRow '
      .'WHERE TxtlogID=? '
      .$where
      ."ORDER BY ID $order "
      ."LIMIT $limit "
      ."SETTINGS "
      ."max_execution_time=$timeout, "
      ."timeout_before_checking_execution_speed=0, "
      ."timeout_overflow_mode='break') "
      ."SELECT * FROM cte_result",
      $params
    );
    $this->timeout = microtime(true) - $start >= $timeout;

    $outputTxtlogRows = [];
    foreach($records as $record) {
      $outputTxtlogRow = new TxtlogRowEntity();
      $outputTxtlogRow->setFromDB($record);
      $outputTxtlogRows[] = $outputTxtlogRow;
    }

    return $order == 'ASC' ? array_reverse($outputTxtlogRows) : $outputTxtlogRows;
  }


  /**
   * Insert multiple new rows
   *
   * @param txtlogRows array with TxtlogRow objects
   * @return void
   */
  public function insertMultiple($txtlogRows) {
    $data = [];

    if(empty($txtlogRows)) {
      return;
    }

    foreach($txtlogRows as $txtlogRow) {
      $data[] = $txtlogRow->getTxtlogID();
      $data[] = $txtlogRow->getID();
      $data[] = $txtlogRow->getTimestamp();
      $data[] = $txtlogRow->getSearchFields();
      $data[] = $txtlogRow->getData();
    }

    $this->execute('INSERT INTO TxtlogRow '
      .'(TxtlogID, ID, Date, SearchFields, Data) '
      .'VALUES '
      .implode(',', array_fill(0, count($txtlogRows), '(?,UNHEX(?),CAST(? AS UInt64),?,?)')),
      $data,
      "ERROR_INSERTING_ROWS"
    );
  }
}
