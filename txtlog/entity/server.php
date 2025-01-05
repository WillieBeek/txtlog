<?php
namespace Txtlog\Entity;

use Txtlog\Core\Common;

class Server {
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
   * Boolean indicating whether this server is active/enabled
   *
   * @var bool
   */
  private $Active;


  /**
   * Hostname/IP of the database server
   *
   * @var string
   */
  private $Hostname;


  /**
   * Database name
   *
   * @var string
   */
  private $DBName;


  /**
   * Database server port
   *
   * @var int
   */
  private $Port;


  /**
   * Database username
   *
   * @var string
   */
  private $Username;


  /**
   * Database password
   *
   * @var string
   */
  private $Password;


  /**
   * Database connection options (e.g. for SSL)
   *
   * @var string
   */
  private $Options;


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

  public function getActive() {
    return $this->Active == true;
  }

  public function getHostname() {
    return $this->Hostname;
  }

  public function getDBName() {
    return $this->DBName;
  }

  public function getPort() {
    return $this->Port;
  }

  public function getUsername() {
    return $this->Username;
  }

  public function getPassword() {
    return $this->Password;
  }

  public function getOptions() {
    return $this->Options;
  }

  // Convert options array to string for string in the database
  public function getOptionsString() {
    return serialize($this->Options);
  }


  /**
   * Setters
   */
  public function setID($ID) {
    $this->ID = $ID;
  }

  public function setActive($active) {
    $this->Active = $active;
  }

  public function setHostname($hostname) {
    $this->Hostname = $hostname;
  }

  public function setDBName($dbname) {
    $this->DBName = $dbname;
  }

  public function setPort($port) {
    $this->Port = $port;
  }

  public function setUsername($username) {
    $this->Username = $username;
  }

  public function setPassword($password) {
    $this->Password = $password;
  }

  public function setOptions($options) {
    $this->Options = $options;
  }


  /**
   * Fill from database
   */
  public function setFromDB($data) {
    $this->ID = $data->ID ?? null;
    $this->CreationDate = $data->CreationDate ?? null;
    $this->ModifyDate = $data->ModifyDate ?? null;
    $this->Active = $data->Active ?? null;
    $this->Hostname = $data->Hostname ?? null;
    $this->DBName = $data->DBName ?? null;
    $this->Port = $data->Port ?? null;
    $this->Username = $data->Username ?? null;
    $this->Password = $data->Password ?? null;
    $this->Options = Common::isSerialized($data->Options) ? unserialize($data->Options) : null;
  }
}
