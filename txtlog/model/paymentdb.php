<?php
namespace Txtlog\Model;

use Txtlog\Database\db;
use Txtlog\Entity\Payment as PaymentEntity;

class PaymentDB extends db {
  /**
   * Get a list of Payment objects
   *
   * @param limit max number of rows to return
   * @return Payment objects
   */
  public function getAll($limit) {
    $records = $this->execute('SELECT '
      .'ID, '
      .'CreationDate, '
      .'SessionID, '
      .'Data '
      .'FROM Payment '
      .'ORDER BY ID DESC '
      ."LIMIT $limit"
    );

    $outputPayments = [];
    foreach($records as $record) {
      $outputPayment = new PaymentEntity();
      $outputPayment->setFromDB($record);
      $outputPayments[] = $outputPayment;
    }

    return $outputPayments;
  }


  /**
   * Get payment object by session ID
   *
   * @return Payment object
   */
  public function get($sessionID) {
    return $this->getRow('SELECT '
      .'ID, '
      .'CreationDate, '
      .'SessionID, '
      .'Data '
      .'FROM Payment '
      .'WHERE SessionID=?',
      $sessionID
    );
  }


  /**
   * Insert a new Payment
   *
   * @param payment object with the data to insert
   * @return void
   */
  public function insert($payment) {
    $result = $this->execute('INSERT INTO Payment '
      .'(SessionID, Data) '
      .'SELECT '
      .'?, ? '
      .'WHERE NOT EXISTS (SELECT * FROM Payment WHERE SessionID=?)',
      [
        $payment->getSessionID(),
        $payment->getData(),
        $payment->getSessionID()
      ]
    );
  }
}
