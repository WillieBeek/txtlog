<?php
namespace Txtlog\Model;

use Txtlog\Entity\Server as ServerEntity;
use Txtlog\Database\db;

class ServerDB extends db {
  /**
   * Get all servers
   *
   * @return array of server objects
   */
  protected function getAll() {
    $records = $this->execute('SELECT '
      .'ID, '
      .'CreationDate, '
      .'ModifyDate, '
      .'Active, '
      .'Hostname, '
      .'DBName, '
      .'Port, '
      .'Username, '
      .'Password, '
      .'Options '
      .'FROM Server '
      .'ORDER BY ID'
    );

    $outputServers = [];
    foreach($records as $record) {
      $outputServer = new ServerEntity();
      $outputServer->setFromDB($record);
      $outputServers[] = $outputServer;
    }

    return $outputServers;
  }


  /**
   * Insert a new server
   *
   * @param server object
   * @return void
   */
  public function insert($server) {
    $this->execute('INSERT INTO Server SET '
      .'ID=?, '
      .'Active=?, '
      .'Hostname=?, '
      .'DBName=?, '
      .'Port=?, '
      .'Username=?, '
      .'Password=?, '
      .'Options=?',
      [
        $server->getID(),
        $server->getActive() ?: null,
        $server->getHostname(),
        $server->getDBName(),
        $server->getPort(),
        $server->getUsername(),
        $server->getPassword(),
        $server->getOptionsString()
      ]
    );
  }
}
