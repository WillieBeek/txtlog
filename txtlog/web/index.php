<?php
use Txtlog\Controller\Settings;
use Txtlog\Includes\Cache;

$sitename = (new Settings)->get()->getSitename();
$demoUrl = (new Settings)->get()->getDemoViewURL();
$demoDashboardUrl = (new Settings)->get()->getDemoDashboardURL();
$email = '';

try {
  $cache = new Cache(exitOnError: false);
  $email = (new Settings)->get()->getEmail() ?? '';
} catch(Exception $e) {
  // Cache not reachable
}
?>
<div class="container">
  <section class="section">
    <div class="title">
      Open source log
    </div>

    <div class="block">
      <?=ucfirst($sitename)?> is an easy to use text log
    </div>
    <div class="content">
      Why another log service?
    </div>
    <div class="content medium">
      <blockquote>
        Fault tolerant, store valid JSON, invalid JSON or raw text
      </blockquote>
      <blockquote>
        No required fields and a functional dashboard without distractions
      </blockquote>
      <blockquote>
        Automatic Geo IP checks for incoming logs and public dashboards
      </blockquote>
      <blockquote>
        Secure and high performance, with a queue based on Redis Streams
      </blockquote>
      <blockquote>
        All code is open source, released under the permissive MIT license
      </blockquote>
    </div>
  </section>

  <section class="section">
    <div class="title">
      How it looks
    </div>
    <div class="block">
      <img src="/images/txtlog.jpg">
    </div>
  </section>

  <section class="section">
    <div class="title">
      Easy to use
    </div>
    <div class="columns">
      <div class="column">
        A unique link is automatically generated when you open this page.
        <div class="box mt-5 mb-5 logurl"></div>
        <div class="block">
          <a target="_blank" class="logid" href=""><input class="button requireslog" type="button" value="View" disabled></a>
        </div>
      </div>
      <div class="column">
      </div>
    </div>
  </section>
<?php
require 'accountinfo.php';
?>
  <section class="section notop">
    <div class="title">
      Account
    </div>

    <div class="block"><br>
      All functionality is available without an account but you can set an optional username and password instead of remembering the long URL.<br>
      <div class="columns is-mobile">
        <div class="column is-narrow">
          <input id="username" class="input is-primary mb-4 mt-4" type="text" placeholder="Username"><br>
          <input id="password" class="input is-primary mb-4" placeholder="password" type="Password"><br>
          <button class="button" id="protect">Set login details</button>
        </div>
      </div>
      <div class="block" id="protectresult"></div>
      <div class="hide" id="removeview">
        To further protect the log, you can remove the public view link.<br><br>
        <button class="button" id="removeviewbutton">Remove public link</button>
      </div>
      <div class="block" id="removeviewresult"></div>
    </div>
  </section>

<?php if(strlen($demoUrl) > 0) { ?>
  <section class="section notop">
    <div class="title">
      Search
    </div>

    <div class="block"><br>
      The demo log contains fake data to test.
    </div>
    <div class="block">
      <a target="_blank" href="<?=$demoUrl?>" class="button">Demo</a>
    </div>
    <?php if(strlen($demoDashboardUrl) > 0) { ?>
    <div class="block"><br>
      Public dashboards have required search terms. For example, the next link only shows logs with at least the text POST and Safari in the log.
    </div>
    <div class="block">
      <a target="_blank" href="<?=$demoDashboardUrl?>" class="button">Public dashboard</a>
    </div>
    <?php } ?>
  </section>
<?php } ?>
  <section class="section">
    <div class="title">
      Open source
    </div>

    <div class="block">
      <?=ucfirst($sitename)?> is licensed under the flexible MIT open source license.<br>
      Built on trusted open source software by <a href="mailto:<?=$email?>">Willie Beek</a>
    </div>
    <div class="columns is-flex is-vcentered">
      <div class="column">
        <a target="_blank" href="https://clickhouse.com/">
          <img src="/images/clickhouse.png" alt="clickhouse">
        </a>
      </div>
      <div class="column">
        <a target="_blank" href="https://www.mysql.com/">
          <img src="/images/mysql.png" alt="mysql">
        </a>
      </div>
      <div class="column">
        <a target="_blank" href="https://apache.org/">
          <img src="/images/apache.png" alt="apache">
        </a>
      </div>
      <div class="column">
        <a target="_blank" href="https://php.net/">
          <img src="/images/php.png" alt="php">
        </a>
      </div>
      <div class="column">
        <a target="_blank" href="https://jquery.com/">
          <img src="/images/jquery.png" alt="jquery">
        </a>
      </div>
      <div class="column">
        <a target="_blank" href="https://bulma.io/">
          <img src="/images/bulma.png" alt="bulma">
        </a>
      </div>
      <div class="column">
        <a target="_blank" href="https://redis.io/">
          <img src="/images/redis.png" alt="redis">
        </a>
      </div>
    </div>
  </section>
</div>
