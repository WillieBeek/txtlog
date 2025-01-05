<?php
namespace Txtlog\Includes;

use Txtlog\Controller\Account;
use Txtlog\Controller\Clickhouse;
use Txtlog\Controller\Payment;
use Txtlog\Controller\Settings;
use Txtlog\Controller\Txtlog;
use Txtlog\Core\Common;

// Login page helper functions
class Login {
  /**
   * Get an admin login area
   *
   * @return html string
   */
  public function getLoginAdmin() {
    $result =<<<EOD
      <section class="section narrow">
        <form method="post" action="/admin">
          <h1 class="title">Login</h1>
          <div class="block">
            <input name="username" class="input is-primary" type="text" placeholder="user" autofocus>
          </div>
          <div class="block">
            <input name="password" class="input is-primary" placeholder="password" type="password">
          </div>
          <div class="block">
            <input name="submit" class="button" type="submit" value="submit">
          </div>
        </form>
      </section>
    </div>

    EOD;

    return $result;
  }


  /**
   * Get access denied html
   *
   * @param hidden defaults to true to hide the HTML
   * @return html string
   */
  public function getAccessDenied($hidden=true) {
    $hide = $hidden ? ' hide' : '';

    $result =<<<EOD
      <section class="accessdenied section hero is-danger$hide">
        <div class="hero-body">
          <p class="title">
            Access denied
          </p>
          <p class="subtitle">
            Invalid username/password
          </p>
        </div>
      </section>

    EOD;

    return $result;
  }


  /**
   * Get a hero banner start
   *
   * @return html string
   */
  private function startHero($title, $ths) {
    $hero =<<<EOD
    <section class="hero is-small is-info">
      <div class="hero-body">
        <p class="title">
          $title
        </p>
      </div>
    </section>
    <table class="table is-striped">
      <thead>
        <tr>
    EOD;

    foreach($ths as $th) {
      $hero .= "<th>$th</th>";
    }

    $hero .= '</tr></thead><tbody>';

    return $hero;
  }


  /**
   * Close the hero banner
   *
   * @return html string
   */
  private function endHero() {
    return '</tbody></table>';
  }


  /**
   * Get a table row
   *
   * @param  fields the data to show
   * @param  class optional class for the element
   * @return html string
   */
  private function getTr($fields, $class='') {
    $result = '<tr>';
    $htmlClass = empty($class) ? '' : " class=\"$class\"";

    foreach($fields as $value) {
      $result .= "<td$htmlClass>".Common::getString($value).'</td>';
    }

    $result .= '</tr>';

    return $result;
  }


  /**
   * Show the admin interface
   *
   * @param serverID - Rows server ID
   * @return string with admin html
   */
  public function showAdmin($serverID) {
    $clickhouse = new Clickhouse();
    $txtlog = new Txtlog();
    $serverInfo = $clickhouse->setServer($serverID);

    $tableStats = $clickhouse->getTableStats();
    $columnStats = $clickhouse->getColumnStats();
    $indexStats = $clickhouse->getIndexStats();
    $diskStats = $clickhouse->getDiskStats();
    $largestLogs = $clickhouse->getLargestLogs();
    $recentLogs = $txtlog->getAll(50);
    $recentQueries = $clickhouse->getRecentQueries();
    $settings = (new Settings)->get(useCache: false);
    $accounts = (new Account)->getAll(useCache: false);
    $payments = (new Payment)->getAll(50);

    $result = <<<EOD
    <section class="hero">
      <div class="hero-body">
        <p class="title">
          ClickHouse server
        </p>

        <p class="subtitle">
          IP: $serverInfo->host<br>
          Database: $serverInfo->name<br>
          Port: $serverInfo->port<br>
          User: $serverInfo->user<br>
        </p>
      </div>
    </section>
    EOD;

    /*
     * Overall table stats
     */
    $result .= $this->startHero('Rows table size', ['Compressed', 'Uncompressed', 'Comp.rate', 'Rows', 'Parts', 'Primary key']);
    $result .= $this->getTr([
      $tableStats->Compressed,
      $tableStats->Uncompressed,
      $tableStats->Compression_rate,
      number_format($tableStats->Rows, 0, '', '.'),
      $tableStats->Part_count,
      $tableStats->Primary_key_bytes_in_memory
    ]);
    $result .= $this->endHero();

    /*
     * Column stats
     */
    $result .= $this->startHero('Rows Columns size', ['Column', 'Compressed', 'Uncompressed', 'Compr.rate', 'Rows', 'Average Row size']);
    foreach($columnStats as $columnStat) {
      $result .= $this->getTr([
        $columnStat->column,
        $columnStat->Compressed,
        $columnStat->Uncompressed,
        $columnStat->Compression_rate,
        number_format($columnStat->Rows, 0, '', '.'),
        $columnStat->Avg_row_size.' bytes'
      ]);
    }
    $result .= $this->endHero();

    /*
     * Index stats
     */
    $result .= $this->startHero('Index size', ['Index name', 'Type', 'Expression', 'Granularity', 'Compressed', 'Uncompressed', 'Marks']);
    foreach($indexStats as $indexStat) {
      $result .= $this->getTr([
        $indexStat->name,
        $indexStat->type_full,
        $indexStat->expr,
        $indexStat->granularity,
        $indexStat->Compressed,
        $indexStat->Uncompressed,
        $indexStat->marks
      ]);
    }
    $result .= $this->endHero();

    /*
     * Disk stats
     */
    $result .= $this->startHero('Disk status', ['Name', 'Path', 'Free space', 'Total space', 'Reserved space']);
    foreach($diskStats as $diskStat) {
      $result .= $this->getTr([
        $diskStat->name,
        $diskStat->path,
        $diskStat->Free,
        $diskStat->Total,
        $diskStat->Reserved
      ]);
    }
    $result .= $this->endHero();

    /*
     * Largest logs
     */
    $result .= $this->startHero('Largest logs', ['ID', 'Creation date', 'Account', 'Server', 'Retention', 'Name', 'IP', 'Username', 'Tokens', 'Rows']);
    foreach($largestLogs as $largestLog) {
      $logdata = $txtlog->get($largestLog->TxtlogID);

      // Skip rows existing in ClickHouse but removed in MySQL
      if(!empty($logdata->getID())) {
        $result .= $this->getTr([
          $largestLog->TxtlogID,
          $logdata->getCreationDate(),
          $logdata->getAccountID(),
          $logdata->getServerID(),
          $logdata->getRetention(),
          substr($logdata->getName(), 0, 20).(strlen($logdata->getName()) > 20 ? '...' : ''),
          $logdata->getIPAddress(),
          $logdata->getUserHash() ? 'Yes' : 'No',
          count($logdata->getTokens()),
          number_format($largestLog->Rows, 0, '', '.')
        ]);
      }
    }
    $result .= $this->endHero();

    /*
     * Recent logs
     */
    $result .= $this->startHero('Recent logs', ['ID', 'Creation date', 'Modify date', 'Account', 'Server', 'Retention', 'Name', 'IP', 'Username', 'Tokens']);
    foreach($recentLogs as $recentLog) {
      $result .= $this->getTr([
        $recentLog->getID(),
        $recentLog->getCreationDate(),
        $recentLog->getModifyDate(),
        $recentLog->getAccountID(),
        $recentLog->getServerID(),
        $recentLog->getRetention(),
        substr($recentLog->getName(), 0, 20).(strlen($recentLog->getName()) > 20 ? '...' : ''),
        $recentLog->getIPAddress(),
        $recentLog->getUserHash() ? 'Yes' : 'No',
        count($recentLog->getTokens())
      ]);
    }
    $result .= $this->endHero();

    /*
     * Recent queries
     */
    $result .= $this->startHero('Recent queries', ['Start time', 'Duration sec', 'Memory usage (MB)', 'Query']);
    foreach($recentQueries as $recentQuery) {
      $result .= $this->getTr([
        $recentQuery->query_start_time_microseconds,
        $recentQuery->query_duration_sec,
        $recentQuery->Memory,
        $recentQuery->query
      ]);
    }
    $result .= $this->endHero();

    /*
     * Settings
     */
    $result .= $this->startHero('System settings', ['Item', 'Value']);
    $result .= '<tr><td>ID</td><td>'.Common::getString($settings->getID()).'</td></tr>';
    $result .= '<tr><td>Creation date</td><td>'.Common::getString($settings->getCreationDate()).'</td></tr>';
    $result .= '<tr><td>Modify date</td><td>'.Common::getString($settings->getModifyDate()).'</td></tr>';
    $result .= '<tr><td>Anonymous account ID</td><td>'.Common::getString($settings->getAnonymousAccountID()).'</td></tr>';
    $result .= '<tr><td>Pro1 account ID</td><td>'.Common::getString($settings->getPro1AccountID()).'</td></tr>';
    $result .= '<tr><td>Pro2 account ID</td><td>'.Common::getString($settings->getPro2AccountID()).'</td></tr>';
    $result .= '<tr><td>Payment API URL</td><td>'.Common::getString($settings->getPaymentApiUrl()).'</td></tr>';
    $result .= '<tr><td>Payment API key</td><td>'.Common::getString(mb_substr($settings->getPaymentApiKey(), 0, 40, 'UTF-8')).'...</td></tr>';
    $result .= '<tr><td>Sitename</td><td>'.Common::getString($settings->getSitename()).'</td></tr>';
    $result .= '<tr><td>E-mail</td><td>'.Common::getString($settings->getEmail()).'</td></tr>';
    $result .= '<tr><td>Cron enabled</td><td>'.(Common::getString($settings->getCronEnabled() ? 'yes' : 'no')).'</td></tr>';
    $result .= '<tr><td>Number of insert rows per transaction</td><td>'.Common::getString($settings->getCronInsert()).'</td></tr>';
    $result .= '<tr><td>Temporary directory</td><td>'.Common::getString($settings->getTempDir()).'</td></tr>';
    $result .= '<tr><td>Demo admin token</td><td>'.Common::getString($settings->getDemoAdminToken()).'</td></tr>';
    $result .= '<tr><td>Demo view URL</td><td>'.Common::getString($settings->getDemoViewURL()).'</td></tr>';
    $result .= '<tr><td>Demo public dashboard URL</td><td>'.Common::getString($settings->getDemoDashboardURL()).'</td></tr>';
    $result .= '<tr><td>Maximum retention in days</td><td>'.Common::getString($settings->getMaxRetention()).'</td></tr>';
    $result .= '<tr><td>Max API calls for creating, updating or deleting log metadata</td><td>'.Common::getString($settings->getMaxAPICallsLog()).'</td></tr>';
    $result .= '<tr><td>Max select (GET) requests per 10 minutes</td><td>'.Common::getString($settings->getMaxAPICallsGet()).'</td></tr>';
    $result .= '<tr><td>Max number of failed API calls</td><td>'.Common::getString($settings->getMaxAPIFails()).'</td></tr>';
    $result .= $this->endHero();

    /*
     * Accounts
     */
    foreach($accounts as $account) {
      $result .= $this->startHero('Account '.$account->getName(), ['Item', 'Value']);
      $result .= '<tr><td>ID</td><td>'.Common::getString($account->getID()).'</td></tr>';
      $result .= '<tr><td>Creation date</td><td>'.Common::getString($account->getCreationDate()).'</td></tr>';
      $result .= '<tr><td>Modify date</td><td>'.Common::getString($account->getModifyDate()).'</td></tr>';
      $result .= '<tr><td>Name</td><td>'.Common::getString($account->getName()).'</td></tr>';
      $result .= '<tr><td>Query timeout for GET requests, in seconds</td><td>'.Common::getString($account->getQueryTimeout()).'</td></tr>';
      $result .= '<tr><td>Max total number of API requests per IP/10 minutes</td><td>'.number_format(Common::getString($account->getMaxIPUsage()), 0, '', '.').'</td></tr>';
      $result .= '<tr><td>Maximum retention in days</td><td>'.Common::getString($account->getMaxRetention()).'</td></tr>';
      $result .= '<tr><td>Max number of rows per log</td><td>'.number_format(Common::getString($account->getMaxRows()), 0, '', '.').'</td></tr>';
      $result .= '<tr><td>Max size per row in bytes</td><td>'.number_format(Common::getString($account->getMaxRowSize()), 0, '', '.').'</td></tr>';
      $result .= '<tr><td>Default number of rows on the dashboard</td><td>'.Common::getString($account->getDashboardRows()).'</td></tr>';
      $result .= '<tr><td>Max rows returned when searching</td><td>'.Common::getString($account->getDashboardRowsSearch()).'</td></tr>';
      $result .= '<tr><td>Price</td><td>'.Common::getString($account->getPrice()).'</td></tr>';
      $result .= '<tr><td>Payment link</td><td>'.Common::getString($account->getPaymentLink()).'</td></tr>';
      $result .= $this->endHero();
    }

    /*
     * Payments
     */
    $result .= $this->startHero('Payments', ['ID', 'Creation date', 'Session ID', 'Data']);
    foreach($payments as $payment) {
      $result .= $this->getTr([
        $payment->getID(),
        $payment->getCreationDate(),
        $payment->getSessionID(),
        $payment->getData()
      ], 'logline');
    }
    $result .= $this->endHero();

    $result .= '</div>';

    return $result;
  }
}
