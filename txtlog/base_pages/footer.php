<?php
use Txtlog\Controller\Settings;
use Txtlog\Includes\Cache;

$email = '';
$hasPricing = false;

try {
  $cache = new Cache(exitOnError: false);

  $email = (new Settings)->get()->getEmail() ?? '';
  $hasPricing = (new Settings)->get()->getPro1AccountID() > 1;
} catch(Exception $e) {
  // Cache not reachable
}
?>
    <footer class="footer">
      <div class="content has-text-centered container is-max-desktop">
        <div class="columns">
          <div class="column">
            <a href="/faq">FAQ</a>
          </div>
          <div class="column">
            <a href="https://github.com/WillieBeek/txtlog" target="_blank">Code on GitHub</a>
          </div>
          <div class="column">
            <a href="/privacy">Privacy policy</a>
          </div>
          <div class="column">
            <?php if(strlen($email) > 0) {?>
            <a href="mailto:<?=$email?>">E-mail</a>
            <?php } ?>
          </div>
        </div>
        <div class="columns">
          <div class="column">
            <a href="/selfhost">Self hosting</a>
          </div>
          <div class="column">
            <a href="/doc">Doc</a>
          </div>
          <div class="column">
            <a href="/admin">Admin</a>
          </div>
          <div class="column">
            <?php if($hasPricing) {?>
            <a href="/pricing">Pricing</a>
            <?php } ?>
          </div>
        </div>
      </div>
    </footer>
  </body>
</html>
