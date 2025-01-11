<?php
use Txtlog\Controller\Settings;

$hostname = 'https://'.(new Settings)->get()->getSitename();
?>
<div class="container">
  <section class="section">
    <div class="block title">
      Self hosting
    </div>

    <div class="block">
      Setting up a self hosted instance is a great way to keep data under your own control. Installing a new environment requires some Unix/Linux knowledge and one or more servers. There are several types of servers required, when starting small it's possible to combine multiple servers on one machine, e.g. host MySQL and Apache on the same server. The following instructions are tested on the most recent Ubuntu LTS, but installation is possible on most Linux distributions.
    </div>

    <div class="title">
      Requirements
    </div>

    <div class="block">
      <div class="columns">
        <div class="column is-half">
          <div class="column">
            <div class="notification is-info is-light">
              Linux server(s) with SSH access
            </div>
          </div>
          <div class="column">
            <div class="notification is-info is-light">
              Webserver with at least one CPU core, 512 MB memory and 1GB disk
            </div>
          </div>
          <div class="column">
            <div class="notification is-info is-light">
              Database server with at least one CPU core, 512 MB memory and 1GB disk
            </div>
          </div>
          <div class="column">
            <div class="notification is-info is-light">
              ClickHouse server or ClickHouse cloud with at least 8GB RAM
            </div>
          </div>
          <div class="column">
            <div class="notification is-info is-light">
              A domain name and DNS provider
            </div>
          </div>
          <div class="column">
            <div class="notification is-info is-light">
              Moderate Linux knowledge
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="title">
      Installation
    </div>

    <article class="message">
      <div class="message-header">
        <p>Webserver</p>
      </div>
      <div class="message-body">
        <div class="mb-3">The first step is installing one or more webservers. Each webserver works autonomously so you can add and remove servers when needed. These instructions use Apache with FPM (FastCGI Process Manager) but <a target="_blank" href="https://nginx.org/en/">nginx</a> works just as well. Install Apache, PHP, Redis and the required PHP modules. Consult the Redis documentation to enable some form of persistency when possible.</div>
        <code>apt install apache2 libapache2-mod-fcgid curl php-fpm redis php-redis php-mbstring php-curl php-mysql php-gmp</code>
      </div>
    </article>

    <article class="message">
      <div class="message-header">
        <p>MySQL/MariaDB</p>
      </div>
      <div class="message-body">
        <div class="mb-3">MySQL is used to store the settings and metadata for the logs. Both MySQL and MariaDB are tested and should work equally well, mysql-client is optional but useful for testing.</div>
        <code>apt install mysql-server mysql-client</code>
        <div class="mb-3 mt-3"><strong>Tip:</strong> secure the SQL installation.</div>
        <code>mysql_secure_installation</code>
        <div class="mb-3 mt-3">It's recommended to create a user account with limited privileges to connect to MySQL. Login to MySQL as root and create a "txtlog" user, replace p@ssw0rd with a secure password.</div>
        <code>CREATE DATABASE txtlog COLLATE 'utf8mb4_unicode_ci';</code><br>
        <code>CREATE USER IF NOT EXISTS 'txtlog'@'localhost' IDENTIFIED BY 'p@ssw0rd';</code><br>
        <code>GRANT ALL ON txtlog.* TO 'txtlog'@'localhost';</code><br>
      </div>
    </article>

    <article class="message">
      <div class="message-header">
        <p>ClickHouse</p>
      </div>
      <div class="message-body">
        <div class="mb-3">ClickHouse handles the heavy lifting of processing billions of log entries. Instead of self hosting ClickHouse server, using the official ClickHouse cloud should also work. For self hosting, add the repository using the <a target="_blank" href="https://clickhouse.com/docs/en/install#available-installation-options">ClickHouse installation instruction</a> and install ClickHouse server.</div>
        <code>apt install clickhouse-server clickhouse-client</code>
        <div class="mb-3 mt-3">Configuring ClickHouse is similar to MySQL. Again, create a user to connect to ClickHouse. Login to ClickHouse with the default user and create a "txtlog" user, replace p@ssw0rd with a secure password.</div>
        <code>CREATE DATABASE txtlog</code><br>
        <code>CREATE USER IF NOT EXISTS txtlog IDENTIFIED WITH sha256_password BY 'p@ssw0rd'</code><br>
        <code>GRANT ALL ON txtlog.* TO txtlog</code>
        <div class="mb-3 mt-3">For the admin page to work grant the following additional privileges as the ClickHouse administrator.</div>
        <code>GRANT SELECT ON system.query_log TO txtlog</code><br>
        <code>GRANT SELECT ON system.parts TO txtlog</code><br>
        <code>GRANT SELECT ON system.parts_columns TO txtlog</code><br>
        <code>GRANT SELECT ON system.data_skipping_indices TO txtlog</code><br>
        <code>GRANT SELECT ON system.disks TO txtlog</code>
        <div class="mt-3">Scaling to more servers can be done using <a target="_blank" href="https://clickhouse.com/docs/en/guides/sre/keeper/clickhouse-keeper">ClickHouse Keeper</a> or by adding more ClickHouse servers and adding the IP and credentials to the <em>Server</em> table on the MySQL server.</div>
      </div>
    </article>

    <article class="message">
      <div class="message-header">
        <p>Cron jobs</p>
      </div>
      <div class="message-body">
        <div class="mb-3">Log rows are processed in batches using a fast in memory queue (Redis Streams). On each webserver, set at least two cron jobs to handle updating the cache, converting Redis memory streams to disk and to update the ClickHouse database.</div>
        <div class="mb-3 mt-3">Create a cron job to update the cached settings and convert Redis streams (which contain the log lines) to files.</div>
        <code class="doc">curl '<?=$hostname?>/cron?updatecache=true&amp;action=cachetofile'</code><br>
        <div class="mb-3 mt-3">Create another cron job to insert the rows into the ClickHouse database.</div>
        <code class="doc">curl '<?=$hostname?>/cron?action=filetodatabase'</code><br>
        <div class="mb-3 mt-3">To combine these, the following code removes the existing cron job for the current user (!), creates the required jobs and runs them every minute.</div>
        <code class="doc">crontab -r</code><br>
        <code class="doc">(</code><br>
        <code class="doc">  echo "* * * * * curl -s '<?=$hostname?>/cron?updatecache=true&amp;action=cachetofile' &gt;&gt;/tmp/txtlog.cron 2&gt;&amp;1" &amp;&amp;</code><br>
        <code class="doc">  echo "* * * * * curl -s '<?=$hostname?>/cron?action=filetodatabase' &gt;&gt;/tmp/txtlog.cron 2&gt;&amp;1"</code><br>
        <code class="doc">) | crontab -</code><br>
        <div class="mb-3 mt-3">To use the Geo IP and Tor IP functions, run these commands.</div>
        <code class="doc">curl '<?=$hostname?>/cron?action=geoipdownload'</code><br>
        <code class="doc">curl '<?=$hostname?>/cron?action=geoipparse'</code><br>
        <code class="doc">curl '<?=$hostname?>/cron?action=toripdownload'</code><br>
        <code class="doc">curl '<?=$hostname?>/cron?action=toripparse'</code><br>
      </div>
    </article>

    <article class="message">
      <div class="message-header">
        <p>Application installation</p>
      </div>
      <div class="message-body">
        <div class="mb-3">Clone the latest version from the <a href="https://github.com/WillieBeek/txtlog" target="_blank">GitHub repository</a>, move it to the website directory (e.g. /var/www) on the webserver. Start the installation process, which will guide you through the settings. The installer will automatically disable after the installation is complete. When in doubt about a setting, keep the defaults shown on screen.<br>
        The installer will run dependency checks before the installation can proceed.</div>
        <div class="mb-3 mt-3"><a target="_blank" href="/install">Start the installer</a></div>
        <figure>
          <img alt="Installation" src="/images/installation.checks.png">
        </figure>
      </div>
    </article>

    <article class="message">
      <div class="message-header">
        <p>Administration</p>
      </div>
      <div class="message-body">
        <div class="mb-3">After the installation is complete, the installer will be disabled. You can use the <a target="_blank" href="/admin">admin</a> page to get a quick system overview.</div>
        <figure>
          <img alt="Admin page" src="/images/admin.interface.png">
        </figure>
      </div>
    </article>
  </section>
</div>
