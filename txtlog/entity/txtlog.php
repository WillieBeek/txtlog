<?php
namespace Txtlog\Entity;

use Exception;
use Txtlog\Core\Common;

class Txtlog {
  /**
   * ID primary key
   *
   * @var int
   */
  private $ID;


  /**
   * Auto generated modify date
   *
   * @var timestamp
   */
  private $ModifyDate;


  /**
   * Account ID
   *
   * @var int
   */
  private $AccountID;


  /**
   * Server ID
   *
   * @var int
   */
  private $ServerID;


  /**
   * Retention
   *
   * @var int
   */
  private $Retention;


  /**
   * Name
   *
   * @var string
   */
  private $Name;


  /**
   * IP Address of the creator
   *
   * @var string
   */
  private $IPAddress;


  /**
   * Hash of the username
   *
   * @var string
   */
  private $UserHash;


  /**
   * Password
   *
   * @var string
   */
  private $Password;


  /**
   * Valid tokens
   *
   * @var string
   */
  private $Tokens;


  /**
   * Getters
   */
  public function getID() {
    return $this->ID;
  }

  public function getIDHex() {
    return strtolower(Common::intToHex($this->ID));
  }

  public function getCreationDate() {
    return Common::parseUniqueID(Common::intToHex($this->ID))->date;
  }

  public function getModifyDate() {
    return $this->ModifyDate;
  }

  public function getAccountID() {
    return $this->AccountID;
  }

  public function getServerID() {
    return $this->ServerID;
  }

  public function getRetention() {
    return $this->Retention;
  }

  public function getName() {
    return $this->Name;
  }

  public function getIPAddress() {
    return $this->IPAddress;
  }

  public function getUserHash() {
    return strtolower($this->UserHash);
  }

  public function getPassword() {
    return $this->Password;
  }

  public function getTokens() {
    return unserialize($this->Tokens);
  }

  public function getTokensString() {
    return $this->Tokens;
  }


  /**
   * Setters
   */
  public function setID($id) {
    $this->ID = $id;
  }

  public function setAccountID($accountID) {
    $this->AccountID = $accountID;
  }

  public function setServerID($serverID) {
    $this->ServerID = $serverID;
  }

  public function setRetention($retention) {
    $this->Retention = $retention;
  }

  public function setName($name) {
    $this->Name = $name;
  }

  public function setIPAddress($ipAddress) {
    $this->IPAddress = $ipAddress;
  }

  public function setUsername($username) {
    if(strlen($username) < 1) {
      $this->UserHash = '';
      return;
    }

    // Check username validity
    if(!preg_match("/^[A-Za-z0-9 \.@_-]+$/", $username)) {
      throw new Exception('ERROR_INVALID_USERNAME');
    }

    // The username itself is not stored, only a truncated hash of the username
    $this->UserHash = substr(hash('sha256', $username), 0, 16);
  }

  public function setPassword($password, $hash=true) {
    if(strlen($password) > 0) {
      $this->Password = $hash ? password_hash($password, PASSWORD_DEFAULT) : $password;
    }
  }

  public function setTokens($tokens) {
    // note: json_encode an enum results in the value (e.g. 1) instead of an enum object
    $this->Tokens = serialize($tokens);
  }


  /**
   * Fill from database
   */
  public function setFromDB($data) {
    $this->ID = $data->ID ?? null;
    $this->ModifyDate = $data->ModifyDate ?? '';
    $this->AccountID = $data->AccountID ?? '';
    $this->ServerID = $data->ServerID ?? '';
    $this->Retention = $data->Retention ?? '';
    $this->Name = $data->Name ?? '';
    $this->IPAddress = $data->IPAddress ?? '';
    $this->UserHash = $data->UserHash ?? '';
    $this->Password = $data->Password ?? '';
    $this->Tokens = $data->Tokens ?? '';
  }
}
