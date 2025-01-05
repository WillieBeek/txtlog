<?php
namespace Txtlog\Database;
use Exception;
use Txtlog\Core\Common;
use PDO;
use PDOException;

// Helper class to setup the databases
class CreateDB {
  /**
   * Test if the provided database is reachable
   *
   * @param dbhost database server hostname
   * @param dbname database name
   * @param dbport database server port
   * @param dbuser database username
   * @param dbpass database password
   * @param dboptions optional PDO connection array
   * @return dbh PDO database handler
   */
  public function testConnection($dbhost, $dbname, $dbport, $dbuser, $dbpass, $dboptions=null) {
    // IPv6 requires brackets around the IP address
    $dbhost = Common::isIPv6($dbhost) ? "[$dbhost]" : $dbhost;

    try {
      $dbh = new PDO("mysql:"
        ."host=$dbhost;"
        ."port=$dbport;"
        ."dbname=$dbname",
        $dbuser,
        $dbpass,
        $dboptions
      );

      return $dbh;
    } catch(PDOException $e) {
      $msg = $e->getMessage();
    } catch(Exception $e) {
      $msg = $e->getMessage();
    }

    if(!empty($msg)) {
      throw new Exception("fail: $msg");
    }
  }


  /**
   * Create MySQL database tables
   * Run testConnection first!
   *
   * @param dbh PDO database handler
   * @return array with created database table names
   */
  public function createSQLTables($dbh) {
    $sql = $this->getTableSQL();

    if(!$dbh instanceof PDO) {
      throw new Exception('Error connecting to the database');
    }

    $results = [];

    foreach($sql as $name=>$sql) {
      if($dbh->exec($sql) === false) {
        throw new Exception("Error creating table $name with the following sql: ".Common::getString($sql));
      }
      $results[] = $name;
    }

    return $results;
  }


  /**
   * Create Clickhouse database tables
   * Run testConnection first!
   *
   * @param  dbh PDO database handler
   * @param  default (maximum) retention
   * @return array with created database table names
   */
  public function createCHTables($dbh, $retention) {
    $sql = $this->getCHSQL($retention);

    if(!$dbh instanceof PDO) {
      throw new Exception('Error connecting to the database');
    }

    $results = [];

    foreach($sql as $name=>$sql) {
      if($dbh->exec($sql) === false) {
        throw new Exception("Error creating table $name with the following sql: ".Common::getString($sql));
      }
      $results[] = $name;
    }

    return $results;
  }


  /**
   * Generate SQL with all tables to create
   *
   * @return array with queries to create SQL tables
   */
  private function getTableSQL() {
    $sql['Account'] =
    'CREATE TABLE IF NOT EXISTS Account (
      ID tinyint unsigned NOT NULL AUTO_INCREMENT,
      CreationDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      ModifyDate timestamp NULL ON UPDATE CURRENT_TIMESTAMP,
      Name varchar(64) NOT NULL,
      QueryTimeout DECIMAL(5,3) DEFAULT NULL,
      MaxIPUsage int unsigned DEFAULT 0,
      MaxRetention int unsigned DEFAULT 0,
      MaxRows int unsigned DEFAULT 0,
      MaxRowSize int unsigned DEFAULT 0,
      DashboardRows int unsigned DEFAULT 250,
      DashboardRowsSearch int unsigned DEFAULT 100,
      Price int unsigned DEFAULT NULL,
      PaymentLink varchar(1024) DEFAULT NULL,
      PRIMARY KEY (ID),
      UNIQUE INDEX Account_Name (Name)
    ) ENGINE=InnoDB;';

    $sql['Server'] =
    'CREATE TABLE IF NOT EXISTS Server (
      ID tinyint unsigned NOT NULL,
      CreationDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      ModifyDate timestamp NULL ON UPDATE CURRENT_TIMESTAMP,
      Active bool DEFAULT TRUE,
      Hostname varbinary(512) NOT NULL,
      DBName varchar(255) NOT NULL,
      Port int unsigned DEFAULT 9004,
      Username varchar(255) NOT NULL,
      Password varchar(255) NOT NULL,
      Options varchar(1024) DEFAULT NULL,
      PRIMARY KEY (ID)
    ) ENGINE=InnoDB;';

    $sql['Settings'] =
    'CREATE TABLE IF NOT EXISTS Settings (
      ID tinyint unsigned NOT NULL AUTO_INCREMENT,
      CreationDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      ModifyDate timestamp NULL ON UPDATE CURRENT_TIMESTAMP,
      AnonymousAccountID tinyint unsigned NOT NULL,
      Pro1AccountID tinyint unsigned DEFAULT NULL,
      Pro2AccountID tinyint unsigned DEFAULT NULL,
      PaymentApiUrl varchar(1024) DEFAULT NULL,
      PaymentApiKey varchar(1024) DEFAULT NULL,
      Sitename varchar(255) DEFAULT NULL,
      IncomingLogDomain varchar(255) DEFAULT NULL,
      Email varchar(255) DEFAULT NULL,
      CronEnabled bool DEFAULT TRUE,
      CronInsert int DEFAULT 0,
      TempDir varchar(1024) DEFAULT NULL,
      AdminUser varchar(255) DEFAULT NULL,
      AdminPassword varchar(255) DEFAULT NULL,
      DemoAdminToken varchar(255) DEFAULT NULL,
      DemoViewURL varchar(255) DEFAULT NULL,
      DemoDashboardURL varchar(255) DEFAULT NULL,
      MaxRetention int unsigned DEFAULT 0,
      MaxAPICallsLog int unsigned DEFAULT 0,
      MaxAPICallsGet int unsigned DEFAULT 0,
      MaxAPIFails int unsigned DEFAULT 0,
      PRIMARY KEY (ID),
      CONSTRAINT FK_Settings_AnonymousAccount FOREIGN KEY (AnonymousAccountID) REFERENCES Account (ID) ON DELETE NO ACTION ON UPDATE NO ACTION,
      CONSTRAINT FK_Settings_Pro1Account FOREIGN KEY (Pro1AccountID) REFERENCES Account (ID) ON DELETE NO ACTION ON UPDATE NO ACTION,
      CONSTRAINT FK_Settings_Pro2Account FOREIGN KEY (Pro2AccountID) REFERENCES Account (ID) ON DELETE NO ACTION ON UPDATE NO ACTION
    ) ENGINE=InnoDB;';

    $sql['Txtlog'] =
    'CREATE TABLE IF NOT EXISTS Txtlog (
      ID bigint unsigned NOT NULL,
      ModifyDate timestamp NULL ON UPDATE CURRENT_TIMESTAMP,
      AccountID tinyint unsigned NOT NULL,
      ServerID tinyint unsigned NOT NULL,
      Retention int unsigned NOT NULL,
      Name varchar(255) DEFAULT NULL,
      IPAddress varbinary(16) DEFAULT NULL,
      Userhash binary(8) DEFAULT NULL,
      Password varchar(255) DEFAULT NULL,
      Tokens text DEFAULT NULL,
      PRIMARY KEY (ID),
      UNIQUE INDEX Txtlog_Userhash (Userhash),
      CONSTRAINT FK_Txtlog_Account FOREIGN KEY (AccountID) REFERENCES Account (ID) ON DELETE CASCADE ON UPDATE NO ACTION,
      CONSTRAINT FK_Txtlog_Server FOREIGN KEY (ServerID) REFERENCES Server (ID) ON DELETE CASCADE ON UPDATE NO ACTION
    ) ENGINE=InnoDB COMPRESSION="zlib";';

    $sql['Payment'] =
    'CREATE TABLE IF NOT EXISTS Payment (
      ID int unsigned NOT NULL AUTO_INCREMENT,
      CreationDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      SessionID varchar(255) NOT NULL,
      Data mediumblob DEFAULT NULL,
      PRIMARY KEY (ID),
      UNIQUE INDEX Payment_SessionID (SessionID)
    ) ENGINE=InnoDB COMPRESSION="zlib";';

    return $sql;
  }


  /**
   * Generate SQL for clickhouse tables
   *
   * @param  default (maximum) retention
   * @return array with queries to create SQL tables
   */
  private function getCHSQL($retention) {
    $sql['TxtlogRow'] =
      "CREATE TABLE IF NOT EXISTS TxtlogRow (
        TxtlogID UInt64 CODEC(ZSTD),
        ID FixedString(12) CODEC(ZSTD),
        Date Date CODEC(Delta, ZSTD),
        SearchFields String CODEC(ZSTD),
        Data String CODEC(ZSTD),
        INDEX TxtlogRow_SearchFields lower(SearchFields) TYPE tokenbf_v1(32768, 3, 0) GRANULARITY 2,
        INDEX TxtlogRow_Data lower(Data) TYPE tokenbf_v1(32768, 4, 0) GRANULARITY 1
      )
      ENGINE = MergeTree
      PRIMARY KEY (TxtlogID, ID)
      TTL Date + INTERVAL $retention DAY
    ";

    return $sql;
  }
}
