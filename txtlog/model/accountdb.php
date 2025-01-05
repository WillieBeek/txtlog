<?php
namespace Txtlog\Model;

use Txtlog\Entity\Account as AccountEntity;
use Txtlog\Database\db;

class AccountDB extends db {
  /**
   * Get all accounts
   *
   * @return array of account objects
   */
  protected function getAll() {
    $records = $this->execute('SELECT '
      .'ID, '
      .'CreationDate, '
      .'ModifyDate, '
      .'Name, '
      .'QueryTimeout, '
      .'MaxIPUsage, '
      .'MaxRetention, '
      .'MaxRows, '
      .'MaxRowSize, '
      .'DashboardRows, '
      .'DashboardRowsSearch, '
      .'Price, '
      .'PaymentLink '
      .'FROM Account '
      .'ORDER BY ID'
    );

    $outputAccounts = [];
    foreach($records as $record) {
      $outputAccount = new AccountEntity();
      $outputAccount->setFromDB($record);
      $outputAccounts[] = $outputAccount;
    }

    return $outputAccounts;
  }


  /**
   * Insert a new account
   *
   * @param account object
   * @return void
   */
  public function insert($account) {
    $this->execute('INSERT INTO Account SET '
      .'Name=?, '
      .'QueryTimeout=?, '
      .'MaxIPUsage=?, '
      .'MaxRetention=?, '
      .'MaxRows=?, '
      .'MaxRowsize=?, '
      .'DashboardRows=?, '
      .'DashboardRowsSearch=?, '
      .'Price=?, '
      .'PaymentLink=?',
      [
        $account->getName(),
        $account->getQueryTimeout(),
        $account->getMaxIPUsage(),
        $account->getMaxRetention(),
        $account->getMaxRows(),
        $account->getMaxRowSize(),
        $account->getDashboardRows(),
        $account->getDashboardRowsSearch(),
        $account->getPrice(),
        $account->getPaymentLink()
      ]
    );
  }
}
