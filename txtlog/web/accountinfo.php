<?php
use Txtlog\Controller\Settings;
use Txtlog\Core\Common;

$randomID = Common::getRandomString(16);
$ip = Common::getIP();
$logDomain = (new Settings)->get()->getLogDomain();
?>
<input type="hidden" id="logdomain" value="<?=$logDomain?>">
  <section class="section">
    <div class="title">
      Application logs
    </div>

    <div class="block">
      Log anything from a web or mobile application by sending a POST request with the log data.<br>
      The <a target="_blank" href="/doc">documentation</a> has examples for popular programming languages.
    </div>

    <div class="block narrow">
      <textarea class="textarea is-small is-family-code" id="logdata" rows="7">
{
"user": "458",
"url": "/login",
"ip": "<?=$ip?>",
"trace_id": "<?=$randomID?>"
}
      </textarea>
    </div>
    <div class="block">
      <input class="button requireslog mr-6" id="addlog" type="button" value="Add log" disabled>
      <a target="_blank" class="logid" href=""><input class="button requireslog" type="button" value="View log" disabled></a>
    </div>
    <div class="block narrow high">
      <pre class="future-result" id="addlog-result"></pre>
      <code class="future-result pl-0" id="addlog-curl"></code>
    </div>
  </section>

  <section class="section">
    <div class="title">
      Monitor server logins
    </div>

    <div class="block">
      For Linux servers, <a id="linuxapp" href="/txtlog">download</a> the open source txtlog script to log all local and SSH logins.<br>
      Install the script on each server you want to monitor:
      <div class="box mt-5 is-family-code cmd">
        sudo /bin/bash ./txtlog
      </div>
      On Windows, <a id="rdpapp" href="/txtlog">download</a> the open source txtlog script to monitor RDP logins. Right click and choose "Run with PowerShell".
    </div>
    <div class="block">
      <img src="/images/rdp.txtlog.png">
    </div>
    <div class="block">
      The txtlog scripts contains comments to explain what happens and should be relatively easy to comprehend, the <a target="_blank" href="/faq">faq</a> explains in more detail how it works.<br>
    </div>
  </section>

