<?php
use Txtlog\Controller\Settings;
use Txtlog\Includes\Login;

$login = new Login();
$sitename = (new Settings)->get()->getSitename();
$hostname = "https://$sitename";
?>
<div class="container">
  <?=$login->getAccessDenied()?>

  <section class="section narrow hide logininfo">
    <div class="block">
      You are logged in as <strong><span id="loggedinuser"></span></strong> (<a href="#" id="logout">logout</a>). <span class="hide" id="logoutmsg">Logout successful</span><br>
      Account: <span id="loggedinaccount"></span><br>
      Retention: <span id="loggedinretention"></span> days<br>
    </div>
    <div class="block">
      <a target="_blank" class="logid" href=""><input class="button requireslog" type="button" value="View log" disabled></a>
    </div>
  </section>

  <div class="hide logininfo">
<?php
require 'accountinfo.php';
?>
  </div>

  <section class="section narrow hide" id="loginform">
    <form method="post" id="login" action="/api/login">
      <h1 class="title">Login</h1>
      <div class="block">
        <input id="username" class="input is-primary" type="text" placeholder="user" autofocus>
      </div>
      <div class="block">
        <input id="password" class="input is-primary" placeholder="password" type="password">
      </div>
      <div class="block">
        <input name="submit" class="button" type="submit" value="Login">
      </div>
    </form>
    <div class="block mt-6">
      <a href="#" id="generatenew">Click here</a> to generate a new log.
    </div>
    <div class="block hide" id="generatenewinfo">
      New log created, <a href="/">return</a> to the homepage.
    </div>
  </section>
</div>
