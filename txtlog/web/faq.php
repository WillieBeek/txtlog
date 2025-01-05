<?php
use Txtlog\Controller\Account;
use Txtlog\Controller\Settings;

$sitename = (new Settings)->get()->getSitename();

// Account info
$account = (new Account)->get((new Settings)->get()->getAnonymousAccountID());
$maxRows = number_format($account->getMaxRows(), 0, ' ', '.');
$maxRowSize = $account->getMaxRowsize();
$maxIPUsage = number_format($account->getMaxIPUsage(), 0, ' ', '.');
$maxRetention = number_format($account->getMaxRetention(), 0, ' ', '.');
?>
<div class="container">
  <section class="section">
    <div class="title">
      What is this site?
    </div>

    <div class="block">
      <?=ucfirst($sitename)?> is a service for consolidating logs from various systems into one online log. It is used by many users worldwide to keep track of important events, such as server logins.
    </div>
  </section>

  <section class="section">
    <div class="title">
      License
    </div>

    <div class="block">
      This service is licensed under the MIT license, see the <a target="_blank" href="/LICENSE.txt">LICENSE</a> for further info.
    </div>

    <div class="block">
      The Geo IP data is provided by <a target="_blank" href="https://iptoasn.com/">iptoasn.com</a>, licensed under the Public Domain Dedication and License version v1.0.
    </div>
  </section>

  <section class="section">
    <div class="title">
      Scripts
    </div>

    <div class="block">
      The SSH and RDP scripts are simple and should work out of the box on most systems. They are built for resilience, i.e. when this site is slow or even offline, the regular login procedure is not affected.
    </div>
    <div class="block content">
      The RDP script might need some tweaking based on your Windows group policies. The script creates a new scheduled task, which should run automatically after an RDP event. If the script does not seem to work try one of these:
      <ul>
        <li>Recommended way: Open the start menu -&gt; Type <em>cmd</em>, right click on Command Prompt -&gt; Run as administrator. In the command prompt run:<br>
        powershell C:\Temp\txtlog.ps1<br>
        Replace the path C:\Temp\txtlog.ps1 with the correct location of the script. Importing the task this way also prevent a Powershell window from flashing after you login.
        </li>
        <li>Open the created "txtlog" task in the Windows Task Scheduler. Change the Security options to <em>Run whether user is logged in or not</em> and change the <em>User</em> to a local account (such as your own account) instead of the Network Service. Save the task with <em>OK</em>, provide your username and password and try opening/closing an RDP session.</li>
      </ul>
    </div>
    <div class="block">
      The SSH and RDP scripts are provided without any warranty. The scripts cannot access passwords.
    </div>
  </section>

  <section class="section">
    <div class="title">
      Uninstalling scripts
    </div>

    <div class="block">
      The RDP script can be uninstalled by opening the Windows Task Scheduler and removing the task named <em>txtlog</em>. This completely removes the program without leaving any traces.
    </div>

    <div class="block">
      The SSH script can be completely uninstalled by deleting the txtlog script and undoing the PAM modifications:
    </div>
    <div class="block">
      <pre class="doc">sudo rm -f /usr/local/bin/txtlog
sudo sed -i '/\(common auth info using \|\/usr\/local\/bin\/\)txtlog/d' /etc/pam.d/common-auth</pre>
    </div>
  </section>

  <section class="section">
    <div class="title">
      Payments
    </div>

    <div class="block">
      <?=ucfirst($sitename)?> is free to use, modify and host yourself, even for commercial use. See the <a target="_blank" href="/LICENSE.txt">MIT license</a> for details.<br>
      <a href="/pricing">Upgrades</a> are available offering more storage space for a fee. Payment is done securely using <a target="_blank" href="https://stripe.com">Stripe</a>. A subscription can be cancelled at any time from the Stripe dashboard.
    </div>
  </section>

  <section class="section">
    <div class="title">
      Limits
    </div>

    <div class="block">
      The following limits apply to free accounts, these can be changed when <a href="/selfhost">hosting</a> yourself.
    </div>
    <div class="block narrow">
      <div class="columns">
        <div class="column">
          Maximum rows per log
        </div>
        <div class="column">
          <?=$maxRows?>
        </div>
      </div>
      <div class="columns">
        <div class="column">
          Maximum length per row
        </div>
        <div class="column">
          <?=$maxRowSize?>
        </div>
      </div>
      <div class="columns">
        <div class="column">
          Maximum requests per 10 minutes
        </div>
        <div class="column">
          <?=$maxIPUsage?>
        </div>
      </div>
      <div class="columns">
        <div class="column">
          Maximum retention
        </div>
        <div class="column">
          <?=$maxRetention?> days
        </div>
      </div>
    </div>
  </section>
</div>
