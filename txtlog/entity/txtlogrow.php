<?php
namespace Txtlog\Entity;

use Txtlog\Core\Common;

class TxtlogRow {
  /**
   * TxtlogID reference to Txtlog.ID
   *
   * @var int
   */
  private $TxtlogID;


  /**
   * ID
   *
   * @var string (hex)
   */
  private $ID;


  /**
   * Date
   *
   * @var date
   */
  private $Date;


  /**
   * SearchFields - extracted values from the JSON, such as IP addresses
   *
   * @var string
   */
  private $SearchFields;


  /**
   * Data - custom data
   *
   * @var string
   */
  private $Data;


  /**
   * Timestamp, virtual caculated
   *
   * @var int
   */
  private $Timestamp;


  /**
   * Getters
   */
  public function getTxtlogID() {
    return $this->TxtlogID;
  }

  public function getID() {
    return $this->ID;
  }

  public function getCreationDate() {
    return Common::parseUniqueID($this->ID)->date;
  }

  public function getTimestamp() {
    return $this->Timestamp ?? Common::parseUniqueID($this->ID)->timestamp;
  }

  public function getDate() {
    return $this->Date;
  }

  public function getSearchFields() {
    return $this->SearchFields;
  }

  public function getData() {
    return $this->Data;
  }


  /**
   * Setters
   */
  public function setTxtlogID($txtlogID) {
    $this->TxtlogID = $txtlogID;
  }

  public function setID($id) {
    $this->ID = $id;
  }

  // To manipulate retention, use this function to overrule the timestamp which changes TxtlogRow.Date
  public function setTimestamp($timestamp) {
    $this->Timestamp = $timestamp;
  }

  public function setSearchFields($searchFields) {
    $this->SearchFields = $searchFields;
  }

  public function setData($data) {
    $this->Data = $data;
  }


  /**
   * Fill from database
   */
  public function setFromDB($data) {
    $this->TxtlogID = $data->TxtlogID ?? null;
    $this->ID = $data->HexID ? $data->HexID : null;
    $this->Date = $data->Date ?? null;
    $this->SearchFields = $data->SearchFields ?? null;
    $this->Data = $data->Data ?? null;
  }
}
