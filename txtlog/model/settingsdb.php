<?php
namespace Txtlog\Model;

use Txtlog\Entity\Settings as SettingsEntity;
use Txtlog\Database\db;

class SettingsDB extends db {
  /**
   * Get the application settings
   * There should be exactly one record
   *
   * @return settings object
   */
  protected function get() {
    $outputSettings = new SettingsEntity();

    $record = $this->getRow('SELECT '
      .'ID, '
      .'CreationDate, '
      .'ModifyDate, '
      .'AnonymousAccountID, '
      .'Pro1AccountID, '
      .'Pro2AccountID, '
      .'PaymentApiUrl, '
      .'PaymentApiKey, '
      .'Sitename, '
      .'IncomingLogDomain, '
      .'Email, '
      .'CronEnabled, '
      .'CronInsert, '
      .'TempDir, '
      .'AdminUser, '
      .'AdminPassword, '
      .'DemoAdminToken, '
      .'DemoViewURL, '
      .'DemoDashboardURL, '
      .'MaxRetention, '
      .'MaxAPICallsLog, '
      .'MaxAPICallsGet, '
      .'MaxAPIFails '
      .'FROM Settings '
      .'WHERE '
      .'ID=1'
    );

    $outputSettings->setFromDB($record);

    return $outputSettings;
  }


  /**
   * Insert a new settings entry
   *
   * @param settings object
   * @return void
   */
  public function insert($settings) {
    $this->execute('INSERT INTO Settings SET '
      .'AnonymousAccountID=?, '
      .'Pro1AccountID=?, '
      .'Pro2AccountID=?, '
      .'PaymentApiUrl=?, '
      .'PaymentApiKey=?, '
      .'Sitename=?, '
      .'IncomingLogDomain=?, '
      .'Email=?, '
      .'CronEnabled=?, '
      .'CronInsert=?, '
      .'TempDir=?, '
      .'AdminUser=?, '
      .'AdminPassword=?, '
      .'DemoAdminToken=?, '
      .'DemoViewURL=?, '
      .'DemoDashboardURL=?, '
      .'MaxRetention=?, '
      .'MaxAPICallsLog=?, '
      .'MaxAPICallsGet=?, '
      .'MaxAPIFails=?',
      [
        $settings->getAnonymousAccountID(),
        $settings->getPro1AccountID(),
        $settings->getPro2AccountID(),
        $settings->getPaymentApiUrl(),
        $settings->getPaymentApiKey(),
        $settings->getSitename(),
        $settings->getIncomingLogDomain(),
        $settings->getEmail(),
        $settings->getCronEnabled(),
        $settings->getCronInsert(),
        $settings->getTempDir(),
        $settings->getAdminUser(),
        $settings->getAdminPassword(),
        $settings->getDemoAdminToken(),
        $settings->getDemoViewURL(),
        $settings->getDemoDashboardURL(),
        $settings->getMaxRetention(),
        $settings->getMaxAPICallsLog(),
        $settings->getMaxAPICallsGet(),
        $settings->getMaxAPIFails()
      ]
    );
  }


  /**
   * Update the settings
   *
   * @param settings object
   * @return void
   */
  public function update($settings) {
    $this->execute('UPDATE Settings SET '
      .'AnonymousAccountID=?, '
      .'Pro1AccountID=?, '
      .'Pro2AccountID=?, '
      .'PaymentApiUrl=?, '
      .'PaymentApiKey=?, '
      .'Sitename=?, '
      .'IncomingLogDomain=?, '
      .'Email=?, '
      .'CronEnabled=?, '
      .'CronInsert=?, '
      .'TempDir=?, '
      .'AdminUser=?, '
      .'AdminPassword=?, '
      .'DemoAdminToken=?, '
      .'DemoViewURL=?, '
      .'DemoDashboardURL=?, '
      .'MaxRetention=?, '
      .'MaxAPICallsLog=?, '
      .'MaxAPICallsGet=?, '
      .'MaxAPIFails=? '
      .'WHERE ID=?',
      [
        $settings->getAnonymousAccountID(),
        $settings->getPro1AccountID(),
        $settings->getPro2AccountID(),
        $settings->getPaymentApiUrl(),
        $settings->getPaymentApiKey(),
        $settings->getSitename(),
        $settings->getIncomingLogDomain(),
        $settings->getEmail(),
        $settings->getCronEnabled(),
        $settings->getCronInsert(),
        $settings->getTempDir(),
        $settings->getAdminUser(),
        $settings->getAdminPassword(),
        $settings->getDemoAdminToken(),
        $settings->getDemoViewURL(),
        $settings->getDemoDashboardURL(),
        $settings->getMaxRetention(),
        $settings->getMaxAPICallsLog(),
        $settings->getMaxAPICallsGet(),
        $settings->getMaxAPIFails(),
        $settings->getID()
      ]
    );
  }
}
