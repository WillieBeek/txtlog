<?php
namespace Txtlog\Includes;

use Exception;
use Txtlog\Controller\Account;
use Txtlog\Controller\TxtlogRow;
use Txtlog\Controller\Server;
use Txtlog\Controller\Settings;
use Txtlog\Core\Common;
use Txtlog\Database\ConnectionException as ConnectionException;
use Txtlog\Includes\Cache;

/**
 * Txtlog\Includes\Cron
 *
 * Helper class for the cron jobs
 */
class Cron {
  /**
   * Check if running the cron job is allowed
   *
   * @return void, script exit on failure
   */
  public function checkAccess() {
    $remoteIP = Common::getIP();

    // Allow access from localhost only
    if(!in_array($remoteIP, ['127.0.0.1', '::1', $_SERVER['SERVER_ADDR']])) {
      Common::setHttpResponseCode(403);
      exit;
    }

    if(!(new Settings)->get(useCache: false)->getCronEnabled()) {
      echo "Cron job disabled in settings, exiting\n";
      exit;
    }
  }


  /**
   * Update the locally cached settings, accounts, etc.
   *
   * @return void
   */
  public function updateLocalCache() {
    $result = (object)['debug'=>''];
    $time = microtime(true);

    $result->debug .= "Refreshing settings, accounts and servers in the cache...";

    try {
      // Open connection to the database first so a connection error can be caught instead of the Settings controller exiting the script
      (new Settings)->open(exitOnError: false);
      (new Settings)->get(useCache: false);
      (new Account)->getAll(useCache: false);
      (new Server)->getAll(useCache: false);
    } catch(ConnectionException $eConn) {
      $result->debug .= "\n* ERROR: Database connection fail ({$eConn->getMessage()}), aborting\n";
      return $result;
    }

    $result->debug .= "complete in ".round(microtime(true) - $time, 4)." seconds\n";

    return $result;
  }


  /**
   * Get data from the cache and store it on the local filesystem
   *
   * @return array with debug information
   */
  public function cacheToFile() {
    $time = microtime(true);
    $cache = new Cache();
    $settings = (new Settings)->get();

    $filename = $settings->getTempDir().'/txtlog.rows.'.((new \DateTimeImmutable())->format('Y-m-d_Hisv_')).Common::getRandomString(10);
    $id = 0;
    $insertRows = [[]];
    // Insert this many rows on the database in one statement
    $limit = $settings->getCronInsert();
    $rowTemps = [];
    $result = (object)[
      'debug'=>'',
      'inserts'=>0
    ];
    $stream = 'txtlogrows';
    $streamDel = [];

    $cacheRows = $cache->streamRead($stream, $id, $limit);
    if(!isset($cacheRows['txtlogrows'])) {
      $result->debug .= "nothing\n";
      return $result;
    }
    $result->inserts = count($cacheRows['txtlogrows']);

    foreach($cacheRows[$stream] as $key=>$rowTemp) {
      $rowTemps[] = (object)$rowTemp;
      $streamDel[] = $key;

      $serverID = $rowTemp['server'];
      $txtlogRowData = unserialize(gzuncompress($rowTemp['row']));
      $insertRows[$serverID][] = $txtlogRowData;
    }

    // Remove first empty array item
    unset($insertRows[0]);

    // Store the rows in a local file
    if(count($streamDel) > 0) {
      // Set a lock to prevent fileToDatabase from processing this file until write is complete
      $rnd = Common::getRandomString(16);
      $ttl = 120;
      $cache->setNx($filename, $rnd, $ttl);

      file_put_contents($filename, serialize($insertRows));

      // Free the lock
      $cache->del($filename);
    }

    // Remove entries from cache
    $cache->streamDel($stream, $streamDel);

    $result->debug .= "stored {$result->inserts} rows in $filename (".round(microtime(true) - $time, 4)." seconds).\n";

    return $result;
  }


  /**
   * Get rows from the filesystem and store in the database
   * Processes max one file
   *
   * @return array with debug information and the amount of rows inserted
   */
  public function fileToDatabase() {
    $cache = new Cache();
    $txtlogRow = new TxtlogRow();
    $ttl = 120;

    $filename = (new Settings)->get()->getTempDir().'/txtlog.rows.*';
    $files = glob($filename);
    $result = (object)[
      'debug'=>'',
      'inserts'=>0
    ];

    if(empty($files)) {
      $result->debug .= "nothing\n";
      return $result;
    }

    foreach($files as $file) {
      // Set an exclusive lock so multiples jobs can process different files
      $id = Common::getRandomString(16);
      if(!$cache->setNx($file, $id, $ttl)) {
        $result->debug .= "(skipping locked $file) ";
        continue;
      }

      if(!is_file($file)) {
        // In chronological order: job 1 processed this file, job 2 found this file with "glob", job 1 removed this file and its lock, job 2 sets a lock and tries to open this file
        $result->debug .= "\n* ERROR: file $file does not exist anymore\n";
        return $result;
      }

      $data = file_get_contents($file);
      if(Common::isSerialized($data)) {
        $data = unserialize($data);
      } else {
        $newfile = str_replace('/txtlog.rows.', '/corrupt.txtlog.rows.', $file);
        $result->debug .= "\n* ERROR: corrupt data, ";
        $result->debug .= rename($file, $newfile) ? "renamed $file to $newfile\n" : "ERROR: cannot rename $file to $newfile\n";
        return $result;
      }
      $result->debug .= "parsing file $file\n";

      try {
        foreach($data as $serverID=>$insertRowServer) {
          $rowCount = count($insertRowServer);
          $result->debug .= "* Inserting $rowCount row(s) on database server $serverID...";

          try {
            $startInsert = microtime(true);

            $txtlogRow->setServer($serverID);
            // Insert in one batch, this is a lot faster compared to single row inserts over a remote connection (a transaction does not help here)
            $txtlogRow->insertMultiple($insertRowServer);

            $size = round(strlen(serialize($insertRowServer)) / 1024, 2);
            $result->debug .= round(microtime(true) - $startInsert, 4)." seconds (approx. $size KB).\n";
            $result->inserts += $rowCount;
          } catch(ConnectionException $eConn) {
            if($cache->get($file) == $id) {
              $cache->del($file);
            }
            $result->debug .= "\n* ERROR: Database connection fail ({$eConn->getMessage()}), aborting\n";
            return $result;
          } catch(Exception $eInsert) {
            $result->debug .= "\n* ERROR: Insert rows exception: {$eInsert->getMessage()}\n";
            $newfile = str_replace('/txtlog.rows.', '/exception.txtlog.rows.', $file);
            $result->debug .= rename($file, $newfile) ? "renamed $file to $newfile\n" : "ERROR: cannot rename $file to $newfile\n";
            return $result;
          }
        }

        $result->debug .= unlink($file) ? "* Removed $file\n" : "ERROR: cannot delete $file\n";
      } catch(Exception $e) {
        $result->debug .= "ERROR : {$e->getMessage()}\n";
      }

      // Free the lock
      if($cache->get($file) == $id) {
        $cache->del($file);
      }

      // Process max one file per function call
      break;
    }

    return $result;
  }


  /**
   * Download a file and store it in the tmp directory
   *
   * @param url the (https) link to download
   * @param filename (basename) of the file, e.g. 'ip.gz'
   * @param minSize optional make sure the file is at least this many bytes
   * @return debug text
   */
  private function downloadToTmp($url, $filename, $minSize=2000) {
    $result = '';

    // Store in the tmp directory
    $filename = (new Settings)->get()->getTempDir()."/$filename";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    $size = strlen($data);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if($data === false || $status != 200 || $size < $minSize) {
      return "Curl exception (1), status=$status, size=$size\nCannot download database from $url";
    }
    file_put_contents($filename, $data);
    $result .= "Download $size bytes from $url and store in $filename";

    return $result;
  }


  /**
   * Download IP address country files released in the public domain: https://iptoasn.com/
   *
   * @return debug text
   */
  public function geoIPDownload() {
    $url = 'https://iptoasn.com/data/ip2asn-combined.tsv.gz';
    $filename = 'ip.gz';

    return $this->downloadToTmp($url, $filename);
  }


  /**
   * Download IP addresses of Tor exit nodes
   *
   * @return debug text
   */
  public function torIPDownload() {
    // https://blog.torproject.org/changes-tor-exit-list-service/
    $url = 'https://check.torproject.org/torbulkexitlist';
    $filename = 'tor';

    return $this->downloadToTmp($url, $filename);
  }


  /**
   * Parse a geo IP file and store it in memory
   * These files are small enough to store in memory instead of a database
   *
   * @return debug text
   */
  public function geoIPParse() {
    $cache = new Cache();
    $result = '';
    $set = 'ip';
    $setData = [];
    $ipFile = (new Settings)->get()->getTempDir().'/ip.gz';

    if(!is_file($ipFile)) {
      return "File not found: $ipFile";
    }

    $handle = gzopen($ipFile, 'r');
    while(!gzeof($handle)) {
      $line = gzgets($handle, 4096);
      $arr = explode("\t", $line);
      $start = $arr[0] ?? '';
      $startNum = str_pad((string) gmp_import(inet_pton($start)), 39, '0', STR_PAD_LEFT);
      $end = $arr[1] ?? '';
      $endNum = str_pad((string) gmp_import(inet_pton($end)), 39, '0', STR_PAD_LEFT);
      $country = $arr[3] ?? '';
      $provider = trim($arr[4] ?? '');

      // Skip unknown countries
      if($country == 'None' || $country == '' || $start == '' || $end == '') {
        continue;
      }

      $setData[] = 0; // score
      $setData[] = "$endNum:$country:$provider";
    }
    gzclose($handle);

    // Clear cached IP set and add new one
    $cache->del($set);
    $stored = $cache->zSetAdd($set, ...$setData);
    $result .= "Stored $stored items in cacheset \"$set\"";

    return $result;
  }


  /**
   * Parse a tor file with exit IP addresses and store it in memory
   *
   * @return debug text
   */
  public function torIPParse() {
    $cache = new Cache();
    $result = '';
    $set = 'tor';
    $setData = [];
    $torFile = (new Settings)->get()->getTempDir().'/tor';

    if(!is_file($torFile)) {
      return "File not found: $torFile";
    }

    $handle = gzopen($torFile, 'r');
    while(!gzeof($handle)) {
      $ip = trim(gzgets($handle, 4096));
      if(Common::isIP($ip)) {
        $setData[] = $ip;
      }
    }
    gzclose($handle);

    // Clear cached IP set and add new one
    $cache->del($set);
    $stored = $cache->setAdd($set, ...$setData);
    $result .= "Stored $stored items in cacheset \"$set\"";

    return $result;
  }
}
