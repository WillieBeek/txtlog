<?php
namespace Txtlog\Entity;

class Payment {
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
   * (Stripe) Session ID
   *
   * @var string
   */
  private $SessionID;


  /**
   * Invoice JSON data
   *
   * @var string
   */
  private $Data;


  /**
   * Getters
   */
  public function getID() {
    return $this->ID;
  }

  public function getCreationDate() {
    return $this->CreationDate;
  }

  public function getSessionID() {
    return $this->SessionID;
  }

  public function getData() {
    return $this->Data;
  }


  /**
   * Setters
   */
  public function setSessionID($sessionID) {
    $this->SessionID = $sessionID;
  }

  public function setData($data) {
    $this->Data = $data;
  }


  /**
   * Fill from database
   */
  public function setFromDB($data) {
    $this->ID = $data->ID ?? null;
    $this->CreationDate = $data->CreationDate ?? null;
    $this->SessionID = $data->SessionID ?? '';
    $this->Data = $data->Data ?? '';
  }
}
