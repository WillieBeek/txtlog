<?php
namespace Txtlog\Model;

use Txtlog\Controller\Server;
use Txtlog\Database\db;

class ClickhouseDB extends db {
  /**
   * Rows can be stored on multiple servers (per TxtlogID), use this function to connect to the correct server
   *
   * @param serverID
   * @return object with server info
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

    $this->open($dbhost, $dbname, $dbport, $dbuser, $dbpass, $dboptions);

    return (object)[
      'host'=>$dbhost,
      'name'=>$dbname,
      'port'=>$dbport,
      'user'=>$dbuser
    ];
  }


  /**
   * Get recently executes ClickHouse queries
   *
   * @return object
   */
  public function getRecentQueries() {
    return $this->execute("SELECT query_start_time_microseconds, "
      ."round(memory_usage/1024/1024, 2) AS Memory, "
      ."query_duration_ms/1000 as query_duration_sec, "
      ."LEFT(query, 1000) AS query "
      ."FROM system.query_log "
      ."WHERE type='QueryFinish' "
      ."ORDER BY query_start_time DESC "
      ."LIMIT 50");
  }


  /**
   * Get TxtlogRow table statistics
   *
   * @return object
   */
  public function getTableStats() {
    return $this->getRow("SELECT "
      ."formatReadableSize(sum(data_compressed_bytes) AS size) AS Compressed, "
      ."formatReadableSize(sum(data_uncompressed_bytes) AS usize) AS Uncompressed, "
      ."round(usize / size, 2) AS Compression_rate, "
      ."SUM(rows) AS Rows, "
      ."COUNT() AS Part_count, "
      ."formatReadableSize(SUM(primary_key_bytes_in_memory)) AS Primary_key_bytes_in_memory "
      ."FROM system.parts "
      ."WHERE database='txtlog' "
      ."AND active=1 "
      ."AND table='TxtlogRow'");
  }


  /**
   * Get TxtlogRow column statistics
   *
   * @return object
   */
  public function getColumnStats() {
    return $this->execute("SELECT "
      ."column, "
      ."formatReadableSize(SUM(column_data_compressed_bytes) AS Size) AS Compressed, "
      ."formatReadableSize(SUM(column_data_uncompressed_bytes) AS Usize) AS Uncompressed, "
      ."round(Usize / Size, 2) AS Compression_rate, "
      ."sum(rows) AS Rows, "
      ."round(Usize / Rows, 2) AS Avg_row_size "
      ."FROM system.parts_columns "
      ."WHERE table='TxtlogRow' "
      ."AND active=1 "
      ."GROUP BY column "
      ."ORDER BY Size DESC");
  }


  /**
   * Get disk information
   *
   * @return object
   */
  public function getDiskStats() {
    return $this->execute('SELECT '
      .'name, '
      .'path, '
      .'formatReadableSize(free_space) AS Free, '
      .'formatReadableSize(total_space) AS Total, '
      .'formatReadableSize(keep_free_space) AS Reserved '
      .'FROM system.disks');
  }


  /**
   * Get TxtlogRow index statistics
   *
   * @return object
   */
  public function getIndexStats() {
    return $this->execute("SELECT "
      ."name, "
      ."type_full, "
      ."expr, "
      ."granularity, "
      ."formatReadableSize(data_compressed_bytes) AS Compressed, "
      ."formatReadableSize(data_uncompressed_bytes) AS Uncompressed, "
      ."marks "
      ."FROM system.data_skipping_indices "
      ."WHERE table='TxtlogRow'");
  }


  /**
   * Get TxtlogRows with most rows per Txtlog
   *
   * @return object
   */
  public function getLargestLogs() {
    return $this->execute('SELECT '
      .'TxtlogID, '
      .'COUNT() AS Rows '
      .'FROM TxtlogRow '
      .'GROUP BY TxtlogID '
      .'ORDER BY COUNT() DESC '
      .'LIMIT 50');
  }
}
