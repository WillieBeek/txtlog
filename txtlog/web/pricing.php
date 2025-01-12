<?php
use Txtlog\Controller\Account;
use Txtlog\Controller\Payment;
use Txtlog\Controller\Settings;
use Txtlog\Controller\Txtlog;
use Txtlog\Core\Common;
use Txtlog\Entity\Payment as PaymentEntity;
use Txtlog\Includes\Cache;

$sitename = (new Settings)->get()->getSitename();
// Account info
$free = (new Account)->get((new Settings)->get()->getAnonymousAccountID());
$settings = (new Settings)->get();
$pro1ID = $settings->getPro1AccountID();
$pro2ID = $settings->getPro2AccountID();
$paymentApiUrl = $settings->getPaymentApiUrl();
$paymentApiKey = $settings->getPaymentApiKey();

if($pro1ID < 1) {
  $GLOBALS['app']->error404();
}

$cache = new Cache();
$msg = '';
$payment = new Payment();
$pro1 = (new Account)->get($pro1ID);
$pro2 = (new Account)->get($pro2ID);
$session = Common::get('session');
$txtlog = new Txtlog();

try {
  $cache->verifyIPFails();
} catch(Exception $e) {
  $msg = str_replace('_', ' ', $e->getMessage());
  $session = null;
}

// After payment a session ID is set
if(Common::canBeStripeSession($session)) {
  try {
    $cache->addIPUsageLog();
    // Check if the payment already exists
    if($payment->get($session)) {
      throw new Exception('This payment has already been processed.');
    }
    $paymentApiUrl .= $session;

    // Contact Stripe
    $ch = curl_init($paymentApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, "$paymentApiKey:");

    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if($result === false || $status != 200) {
      throw new Exception("Invalid payment");
    }

    curl_close($ch);
    $resultObj = json_decode($result);

    /* Use the userHash: https://docs.stripe.com/payment-links/url-parameters
     * client_reference_id can be composed of alphanumeric characters, dashes, or underscores, and be any value up to 200 characters.
     * Invalid values are silently dropped, but your payment page continues to work as expected.
     */
    $userHash = $resultObj->client_reference_id ?? '';
    $amount_subtotal = $resultObj->amount_subtotal ?? 0;

    if(strlen($userHash) < 1 || $amount_subtotal < 1) {
      throw new Exception('Invalid payment');
    }

    // amount_subtotal: Total of all items before discounts or taxes are applied.
    foreach((new Account)->getAll() as $account) {
      if($account->getPrice() == $amount_subtotal) {
        $existingTxtlog = $txtlog->getByuserHash($userHash, useCache: false);
        if(empty($existingTxtlog->getID())) {
          throw new Exception('Log not found');
        }

        $existingTxtlog->setAccountID($account->getID());
        $existingTxtlog->setRetention($account->getMaxRetention());
        $txtlog->update($existingTxtlog);
        $msg = 'Upgrade complete, thanks for your support!';

        // Log the payment info
        $paymentInfo = new PaymentEntity();
        $paymentInfo->setSessionID($session);
        $paymentInfo->setData($result);
        $payment->insert($paymentInfo);
        break;
      }
    }

    if(!$msg) {
      throw new Exception('Invalid payment');
    }
  } catch(Exception $e) {
    $msg = $e->getMessage();
    $cache->addIPFail();
  }
}
?>
<input type="hidden" id="upgradebase1" value="<?=$pro1->getPaymentLink()?>">
<input type="hidden" id="upgradebase2" value="<?=$pro2->getPaymentLink()?>">

<div class="container">
  <section class="section">
    <div class="has-text-danger is-size-4">
      <?=$msg?>
    </div>

    <div class="block">
      <?=ucfirst($sitename)?> is free to use, modify and host yourself, even for commercial use! See the <a target="_blank" href="/LICENSE.txt">MIT license</a> for details.
    </div>

    <div class="block">
      A subscription helps to fund continued development and sustainability of this service. All payment processing is done with <a target="_blank" href="https://stripe.com">Stripe</a>.
    </div>
  </section>

  <section class="section">
    <div class="columns">
      <div class="column">
        <div class="card">
          <header class="card-header">
            <p class="card-header-title is-centered">
              Free<br>
              &nbsp;
            </p>
          </header>
          <div class="card-content">
            <div class="content">
              <div class="block">
                Maximum logs: <?=Common::formatReadable($free->getMaxRows())?>
              </div>
              <div class="block">
                Requests/10 min.: <?=Common::formatReadable($free->getMaxIPUsage())?>
              </div>
              <div class="block">
                Retention: <?=$free->getMaxRetention()?> days
              </div>
            </div>
          </div>

          <div class="card-content medium">
          </div>

          <footer class="card-footer">
            <a class="card-footer-item">
              <button class="button is-fullwidth is-light" disabled>Free</button>
            </a>
          </footer>
        </div>
      </div>

      <div class="column">
        <div class="card">
          <header class="card-header">
            <p class="card-header-title is-centered">
            &nbsp;<?=$pro1->getName()?><br>
              $<?=number_format($pro1->getPrice()/100, 2, '.', '')?> / month
            </p>
          </header>

          <div class="card-content">
            <div class="content">
              <div class="block">
                Maximum logs: <strong><?=Common::formatReadable($pro1->getMaxRows())?></strong>
              </div>
              <div class="block">
                Requests/10 min.: <strong><?=($pro1->getMaxIPUsage() == $pro1->getMaxRows() ? 'unlimited' : Common::formatReadable($pro1->getMaxIPUsage()))?></strong>
              </div>
              <div class="block">
                Retention: <strong><?=$pro1->getMaxRetention()?></strong> days
              </div>
            </div>
          </div>

          <div class="card-content medium">
            <div class="content">
              <div class="field is-horizontal">
                <div class="field-label is-normal">
                  <label class="label">Username</label>
                </div>
                <div class="field-body">
                  <div class="field">
                    <p class="control">
                      <input class="input is-static username pt-1" type="text" placeholder="Username" readonly>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <footer class="card-footer">
            <a href="#" class="upgradeurl1 card-footer-item">
              <button class="button is-fullwidth is-light upgradebutton" disabled>Upgrade</button>
            </a>
          </footer>
        </div>
      </div>

      <div class="column">
        <div class="card">
          <header class="card-header">
            <p class="card-header-title is-centered">
            &nbsp;<?=$pro2->getName()?><br>
              $<?=number_format($pro2->getPrice()/100, 2, '.', '')?> / month
            </p>
          </header>

          <div class="card-content">
            <div class="content">
              <div class="block">
                Maximum logs: <strong><?=Common::formatReadable($pro2->getMaxRows())?></strong>
              </div>
              <div class="block">
                Requests/10 min.: <strong><?=($pro2->getMaxIPUsage() == $pro2->getMaxRows() ? 'unlimited' : Common::formatReadable($pro2->getMaxIPUsage()))?></strong>
              </div>
              <div class="block">
                Retention: <strong><?=$pro2->getMaxRetention()?></strong> days
              </div>
            </div>
          </div>

          <div class="card-content medium">
            <div class="content">
              <div class="field is-horizontal">
                <div class="field-label is-normal">
                  <label class="label">Username</label>
                </div>
                <div class="field-body">
                  <div class="field">
                    <p class="control">
                      <input class="input is-static username pt-1" type="text" placeholder="Username" readonly>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <footer class="card-footer">
            <a href="#" class="upgradeurl2 card-footer-item">
              <button class="button is-fullwidth is-light upgradebutton" disabled>Upgrade</button>
            </a>
          </footer>
        </div>
      </div>
    </div>
  </section>
</div>
