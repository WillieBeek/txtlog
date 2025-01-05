<?php
namespace Txtlog\Entity;

class Account {
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
   * Account name
   *
   * @var string
   */
  private $Name;


  /**
   * Query timeout in seconds, only used when getting rows
   *
   * @var float
   */
  private $QueryTimeout;


  /**
   * Maximum number of requests per IP per cron cycle
   *
   * @var int
   */
  private $MaxIPUsage;


  /**
   * Maximum allowed retention for this account
   *
   * @var int
   */
  private $MaxRetention;


  /**
   * Maximum number of rows per log
   *
   * @var int
   */
  private $MaxRows;


  /**
   * Maximum bytes of data for a single row
   *
   * @var int
   */
  private $MaxRowSize;


  /**
   * Default number of rows to show on the dashboard
   *
   * @var int
   */
  private $DashboardRows;


  /**
   * Maximum number of rows to show on the dashboard when searching for strings
   * This is a separate setting which can be useful when the tokenbf_v1 filter is slow
   *
   * @var int
   */
  private $DashboardRowsSearch;


  /**
   * Price per month in cents
   *
   * @var int
   */
  private $Price;


  /**
   * (Stripe) Payment Link
   *
   * @var string
   */
  private $PaymentLink;


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

  public function getName() {
    return $this->Name;
  }

  public function getQueryTimeout() {
    return $this->QueryTimeout;
  }

  public function getMaxIPUsage() {
    return $this->MaxIPUsage;
  }

  public function getMaxRetention() {
    return $this->MaxRetention;
  }

  public function getMaxRows() {
    return $this->MaxRows;
  }

  public function getMaxRowSize() {
    return $this->MaxRowSize;
  }

  public function getDashboardRows() {
    return $this->DashboardRows;
  }

  public function getDashboardRowsSearch() {
    return $this->DashboardRowsSearch;
  }

  public function getPrice() {
    return $this->Price;
  }

  public function getPaymentLink() {
    return $this->PaymentLink;
  }


  /**
   * Setters
   */
  public function setName($name) {
    $this->Name = $name;
  }

  public function setQueryTimeout($queryTimeout) {
    $this->QueryTimeout = $queryTimeout;
  }

  public function setMaxIPUsage($maxIPUsage) {
    $this->MaxIPUsage = $maxIPUsage;
  }

  public function setMaxRetention($maxRetention) {
    $this->MaxRetention = $maxRetention;
  }

  public function setMaxRows($maxRows) {
    $this->MaxRows = $maxRows;
  }

  public function setMaxRowSize($maxRowSize) {
    $this->MaxRowSize = $maxRowSize;
  }

  public function setDashboardRows($dashboardRows) {
    $this->DashboardRows = $dashboardRows;
  }

  public function setDashboardRowsSearch($dashboardRowsSearch) {
    $this->DashboardRowsSearch = $dashboardRowsSearch;
  }

  public function setPrice($price) {
    $this->Price = $price;
  }

  public function setPaymentLink($paymentLink) {
    $this->PaymentLink = $paymentLink;
  }


  /**
   * Fill from database
   */
  public function setFromDB($data) {
    $this->ID = $data->ID ?? null;
    $this->CreationDate = $data->CreationDate ?? null;
    $this->ModifyDate = $data->ModifyDate ?? null;
    $this->Name = $data->Name ?? null;
    $this->QueryTimeout = $data->QueryTimeout ?? null;
    $this->MaxIPUsage = $data->MaxIPUsage ?? null;
    $this->MaxRetention = $data->MaxRetention ?? null;
    $this->MaxRows = $data->MaxRows ?? null;
    $this->MaxRowSize = $data->MaxRowSize ?? null;
    $this->DashboardRows = $data->DashboardRows ?? null;
    $this->DashboardRowsSearch = $data->DashboardRowsSearch ?? null;
    $this->Price = $data->Price ?? null;
    $this->PaymentLink = $data->PaymentLink ?? null;
  }
}
