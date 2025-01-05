<?php
namespace Txtlog\Model;

use Txtlog\Database\db;
use Txtlog\Entity\Txtlog as TxtlogEntity;

class TxtlogDB extends db {
  /**
   * Get a list of Txtlog objects
   *
   * @param limit max number of rows to return
   * @return Txtlog objects
   */
  protected function getAll($limit) {
    $records = $this->execute('SELECT '
      .'ID, '
      .'ModifyDate, '
      .'AccountID, '
      .'ServerID, '
      .'Retention, '
      .'Name, '
      .'INET6_NTOA(IPAddress) AS IPAddress, '
      .'HEX(UserHash) AS UserHash, '
      .'Password, '
      .'Tokens '
      .'FROM Txtlog '
      .'ORDER BY ID DESC '
      ."LIMIT $limit"
    );

    $outputTxtlogs = [];
    foreach($records as $record) {
      $outputTxtlog = new TxtlogEntity();
      $outputTxtlog->setFromDB($record);
      $outputTxtlogs[] = $outputTxtlog;
    }

    return $outputTxtlogs;
  }


  /**
   * Get a single Txtlog by ID or code
   *
   * @param id
   * @param userHash optional to select on userHash instead of ID
   * @return Txtlog object
   */
  public function get($id, $userHash=null) {
    $outputTxtlog = new TxtlogEntity();

    $query = 'SELECT '
      .'ID, '
      .'ModifyDate, '
      .'AccountID, '
      .'ServerID, '
      .'Retention, '
      .'Name, '
      .'INET6_NTOA(IPAddress) AS IPAddress, '
      .'HEX(UserHash) AS UserHash, '
      .'Password, '
      .'Tokens '
      .'FROM Txtlog '
      .'WHERE ';

    if(!is_null($userHash)) {
      $query .= 'UserHash=UNHEX(?)';
      $param = $userHash;
    } else {
      $query .= 'ID=?';
      $param = $id;
    }

    $record = $this->getRow($query, $param);

    $outputTxtlog->setFromDB($record);

    return $outputTxtlog;
  }


  /**
   * Insert a new Txtlog
   *
   * @param txtlog object with the data to insert
   * @return void
   */
  public function insert($txtlog) {
    $result = $this->execute('INSERT INTO Txtlog SET '
      .'ID=?, '
      .'AccountID=?, '
      .'ServerID=?, '
      .'Retention=?, '
      .'Name=?, '
      .'IPAddress=INET6_ATON(?), '
      .'UserHash=UNHEX(?), '
      .'Password=?, '
      .'Tokens=?',
      [
        $txtlog->getID(),
        $txtlog->getAccountID(),
        $txtlog->getServerID(),
        $txtlog->getRetention(),
        $txtlog->getName(),
        $txtlog->getIPAddress(),
        $txtlog->getUserHash(),
        $txtlog->getPassword(),
        $txtlog->getTokensString()
      ],
      'ERROR_USERNAME_ALREADY_EXISTS'
    );
  }


  /**
   * Update an existing Txtlog
   *
   * @param txtlog object with the new data
   * @return void
   */
  protected function update($txtlog) {
    $result = $this->execute('UPDATE Txtlog SET '
      .'AccountID=?, '
      .'ServerID=?, '
      .'Retention=?, '
      .'Name=?, '
      .'IPAddress=INET6_ATON(?), '
      .'UserHash=UNHEX(?), '
      .'Password=?, '
      .'Tokens=? '
      .'WHERE ID=?',
      [
        $txtlog->getAccountID(),
        $txtlog->getServerID(),
        $txtlog->getRetention(),
        $txtlog->getName(),
        $txtlog->getIPAddress(),
        $txtlog->getUserHash(),
        $txtlog->getPassword(),
        $txtlog->getTokensString(),
        $txtlog->getID()
      ],
      'ERROR_USERNAME_ALREADY_EXISTS'
    );
  }


  /**
   * Delete a Txtlog
   *
   * @param txtlog object
   * @return void
   */
  protected function delete($txtlog) {
    $this->execute('DELETE FROM Txtlog '
      .'WHERE ID=?',
      $txtlog->getID()
    );
  }
}
