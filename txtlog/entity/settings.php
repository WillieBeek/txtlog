<?php
namespace Txtlog\Entity;

class Settings {
  /**
   * ID primary key
   *
   * @var int
   */
  private $ID;


  /**
   * Auto generated creation date
   *
   * @var timestamp
   */
  private $CreationDate;


  /**
   * Auto generated modify date
   *
   * @var timestamp
   */
  private $ModifyDate;


  /**
   * Anonymous Account ID
   *
   * @var int
   */
  private $AnonymousAccountID;


  /**
   * Pro1 Account ID
   *
   * @var int
   */
  private $Pro1AccountID;


  /**
   * Pro2 Account ID
   *
   * @var int
   */
  private $Pro2AccountID;


  /**
   * (Stripe) Payment API link
   *
   * @var string
   */
  private $PaymentApiUrl;


  /**
   * (Stripe) Payment secret API key
   *
   * @var string
   */
  private $PaymentApiKey;


  /**
   * Sitename
   *
   * @var string
   */
  private $Sitename;


  /**
   * Incoming log domain - optional specify another domain for receiving logs in table Settings.IncomingLogDomain
   * If empty, the Sitename is used for receiving logs
   *
   * @var string
   */
  private $IncomingLogDomain;


  /**
   * Public e-mail address
   *
   * @var string
   */
  private $Email;


  /**
   * CronEnabled - true if the cron job is enabled
   *
   * @var bool
   */
  private $CronEnabled;


  /**
   * CronInsert - insert this many rows in one batch in the cron job
   *
   * @var int
   */
  private $CronInsert;


  /**
   * TempDir - temporary storage for rows
   *
   * @var string
   */
  private $TempDir;


  /**
   * Admin user
   *
   * @var string
   */
  private $AdminUser;


  /**
   * Admin password hash
   *
   * @var string
   */
  private $AdminPassword;


  /**
   * Demo admin token - the (auto generated) admin token for the demo page
   *
   * @var string
   */
  private $DemoAdminToken;


  /**
   * Demo view URL - public view URL for the demo page
   *
   * @var string
   */
  private $DemoViewURL;


  /**
   * Demo URL for a public dashboard
   *
   * @var string
   */
  private $DemoDashboardURL;


  /**
   * MaxRetention - the global maximum allowed retention
   *
   * @var int
   */
  private $MaxRetention;


  /**
   * MaxAPICallsLog - maximum number of API calls for inserting, updating or deleting log metadata
   *
   * @var int
   */
  private $MaxAPICallsLog;


  /**
   * Maximum number of API calls for getting log data
   *
   * @var int
   */
  private $MaxAPICallsGet;


  /**
   * MaxAPIFails - maximum number of API calls resulting in an error
   *
   * @var int
   */
  private $MaxAPIFails;


  /**
   * Getters
   */
  public function getID() {
    return $this->ID;
  }

  public function getCreationDate() {
    return $this->CreationDate;
  }

  public function getModifyDate() {
    return $this->ModifyDate;
  }

  public function getAnonymousAccountID() {
    return $this->AnonymousAccountID;
  }

  public function getPro1AccountID() {
    return $this->Pro1AccountID;
  }

  public function getPro2AccountID() {
    return $this->Pro2AccountID;
  }

  public function getPaymentApiUrl() {
    return $this->PaymentApiUrl;
  }

  public function getPaymentApiKey() {
    return $this->PaymentApiKey;
  }

  public function getSitename() {
    return $this->Sitename;
  }

  public function getIncomingLogDomain() {
    return $this->IncomingLogDomain;
  }

  // Helper function to get the correct incoming log domain
  public function getLogDomain() {
    return empty($this->getIncomingLogDomain()) ? "https://{$this->getSitename()}" : "https://{$this->getIncomingLogDomain()}";
  }

  public function getEmail() {
    return $this->Email;
  }

  public function getCronEnabled() {
    return $this->CronEnabled;
  }

  public function getCronInsert() {
    return $this->CronInsert;
  }

  public function getTempDir() {
    return $this->TempDir;
  }

  public function getAdminUser() {
    return $this->AdminUser;
  }

  public function getAdminPassword() {
    return $this->AdminPassword;
  }

  public function getDemoAdminToken() {
    return $this->DemoAdminToken;
  }

  public function getDemoViewURL() {
    return $this->DemoViewURL;
  }

  public function getDemoDashboardURL() {
    return $this->DemoDashboardURL;
  }

  public function getMaxRetention() {
    return $this->MaxRetention;
  }

  public function getMaxAPICallsLog() {
    return $this->MaxAPICallsLog;
  }

  public function getMaxAPICallsGet() {
    return $this->MaxAPICallsGet;
  }

  public function getMaxAPIFails() {
    return $this->MaxAPIFails;
  }


  /**
   * Setters
   */
  public function setAnonymousAccountID($accountID) {
    $this->AnonymousAccountID = $accountID;
  }

  public function setPro1AccountID($accountID) {
    $this->Pro1AccountID = $accountID;
  }

  public function setPro2AccountID($accountID) {
    $this->Pro2AccountID = $accountID;
  }

  public function setPaymentApiUrl($paymentApiUrl) {
    $this->PaymentApiUrl = $paymentApiUrl;
  }

  public function setPaymentApiKey($paymentApiKey) {
    $this->PaymentApiKey = $paymentApiKey;
  }

  public function setSitename($sitename) {
    $this->Sitename = $sitename;
  }

  public function setIncomingLogDomain($incomingLogDomain) {
    $this->IncomingLogDomain = $incomingLogDomain;
  }

  public function setEmail($email) {
    $this->Email = $email;
  }

  public function setCronEnabled($cronEnabled) {
    $this->CronEnabled = $cronEnabled;
  }

  public function setCronInsert($cronInsert) {
    $this->CronInsert = $cronInsert;
  }

  public function setTempDir($tempDir) {
    $this->TempDir = $tempDir;
  }

  public function setAdminUser($adminUser) {
    $this->AdminUser = $adminUser;
  }

  public function setAdminPassword($adminPassword, $hash=true) {
    $this->AdminPassword = $hash ? password_hash($adminPassword, PASSWORD_DEFAULT) : $adminPassword;
  }

  public function setDemoAdminToken($demoAdminToken) {
    $this->DemoAdminToken = $demoAdminToken;
  }

  public function setDemoViewURL($demoViewURL) {
    $this->DemoViewURL = $demoViewURL;
  }

  public function setDemoDashboardURL($demoDashboardURL) {
    $this->DemoDashboardURL = $demoDashboardURL;
  }

  public function setMaxRetention($maxRetention) {
    $this->MaxRetention = $maxRetention;
  }

  public function setMaxAPICallsLog($maxAPICallsLog) {
    $this->MaxAPICallsLog = $maxAPICallsLog;
  }

  public function setMaxAPICallsGet($maxAPICallsGet) {
    $this->MaxAPICallsGet = $maxAPICallsGet;
  }

  public function setMaxAPIFails($maxAPIFails) {
    $this->MaxAPIFails = $maxAPIFails;
  }


  /**
   * Fill from database
   */
  public function setFromDB($data) {
    $this->ID = $data->ID ?? null;
    $this->CreationDate = $data->CreationDate ?? null;
    $this->ModifyDate = $data->ModifyDate ?? null;
    $this->AnonymousAccountID = $data->AnonymousAccountID ?? null;
    $this->Pro1AccountID = $data->Pro1AccountID ?? null;
    $this->Pro2AccountID = $data->Pro2AccountID ?? null;
    $this->PaymentApiUrl = $data->PaymentApiUrl ?? null;
    $this->PaymentApiKey = $data->PaymentApiKey ?? null;
    $this->Sitename = $data->Sitename ?? null;
    $this->IncomingLogDomain = $data->IncomingLogDomain ?? null;
    $this->Email = $data->Email ?? null;
    $this->CronEnabled = $data->CronEnabled ?? null;
    $this->CronInsert = $data->CronInsert ?? null;
    $this->TempDir = $data->TempDir ?? null;
    $this->AdminUser = $data->AdminUser ?? null;
    $this->AdminPassword = $data->AdminPassword ?? null;
    $this->DemoAdminToken = $data->DemoAdminToken ?? null;
    $this->DemoViewURL = $data->DemoViewURL ?? null;
    $this->DemoDashboardURL = $data->DemoDashboardURL ?? null;
    $this->MaxRetention = $data->MaxRetention ?? null;
    $this->MaxAPICallsLog = $data->MaxAPICallsLog ?? null;
    $this->MaxAPICallsGet = $data->MaxAPICallsGet ?? null;
    $this->MaxAPIFails = $data->MaxAPIFails ?? null;
  }
}
