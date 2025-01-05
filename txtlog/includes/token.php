<?php
namespace Txtlog\Includes;

use Txtlog\Controller\Txtlog as TxtlogController;
use Txtlog\Core\Common;
use Txtlog\Includes\Priv;
use Exception;

// Token functions
class Token {
  /**
   * Number of random bits per token
   *
   * @var int
   */
  private static $randomBits = 96;


  /**
   * Maximum number of tokens per log
   *
   * @var int
   */
  private static $maxTokens = 100;


  /**
   * Maximum length of a GET search parameter
   *
   * @var int
   */
  private static $maxSearchLength = 1000;


  /**
   * Return the maximum number of allowed tokens
   *
   * @return int
   */
  public static function getMaxTokens() {
    return self::$maxTokens;
  }


  /**
   * Return the maximum length of a GET search query
   *
   * @return int
   */
  public static function getMaxSearchLength() {
    return self::$maxSearchLength;
  }


  /**
   * Generate a new token
   *
   * @param txtlog object of the existing log
   * @param scope optional scope, if omitted defaults to the POST parameter "scope"
   * @throws Exception error message
   * @return object containing the privilege, token, and get parameters
   */
  public static function generateToken($txtlog, $scope=null) {
    $scope = ucfirst($scope ?? Common::post('scope', 10));
    if(!in_array($scope, array_column(Priv::cases(), 'name'))) {
      throw new Exception('ERROR_PROVIDE_SCOPE_ADMIN_INSERT_VIEW');
    }
    $priv = Priv::{$scope};
    $payload = (object)[
      'priv'=>$priv,
      'token'=>Common::base58Encode($priv->value.Common::getRandomHex(self::$randomBits / 4).$txtlog->getIDHex())
    ];

    // Public dashboard, store GET parameters as well
    if($priv == Priv::View) {
      $get = Common::get('data', self::$maxSearchLength);
      if(strlen($get) > 1) {
        $payload->get = json_encode($get);
        $payload->getUrl = http_build_query(['data'=>$get]);
      }
    }

    return $payload;
  }


  /**
   * Parse a token
   *
   * @param token string
   * @throws Exception error message
   * @return token object
   */
  public static function parseToken($token) {
    if(strlen($token) > 100 || !Common::isBase58($token)) {
      throw new Exception('ERROR_FORBIDDEN');
    }

    $hexID = Common::base58Decode($token);
    $txtlogController = new TxtlogController();
    $txtlogID = substr($hexID, -16);

    $txtlog = $txtlogController->getByHex($txtlogID);
    foreach($txtlog->getTokens() ?: [] as $validToken) {
      if($validToken->token == $token) {
        $validToken->txtlog = $txtlog;
        return $validToken;
      }
    }

    throw new Exception('ERROR_FORBIDDEN');
  }


  /**
   * Add a new token and store it in the database
   *
   * @param txtlog object of the existing log
   * @param scope optional scope, if omitted defaults to the POST parameter "scope"
   * @throws Exception error message
   * @return token object
   */
  public static function addToken($txtlog, $scope=null) {
    $txtlogController = new TxtlogController();

    if(count($txtlog->getTokens()) >= self::$maxTokens) {
      throw new Exception('ERROR_TOKEN_LIMIT_REACHED');
    }

    // Generate a new token
    $token = self::generateToken($txtlog);

    // Refresh because tokens are stored denormalized
    $txtlog = $txtlogController->getByID($txtlog->getID(), useCache: false);

    // Store the new token in the database
    $txtlog->setTokens(array_merge($txtlog->getTokens(), [$token]));
    $txtlogController->update($txtlog);

    return $token;
  }


  /**
   * Delete a given token
   *
   * @param txtlog object of the existing log
   * @param token string
   * @throws Exception error message
   * @return array
   */
  public static function deleteToken($txtlog, $token) {
    $tokenInfo = self::parseToken($token);
    $txtlogController = new TxtlogController();
    $found = false;

    foreach($txtlog->getTokens() ?: [] as $key=>$validToken) {
      if($validToken->token == $tokenInfo->token) {
        // Refresh because tokens are stored denormalized
        $txtlog = $txtlogController->getByID($txtlog->getID(), useCache: false);
        $tokens = $txtlog->getTokens();
        unset($tokens[$key]);
        $txtlog->setTokens($tokens);

        // Require at least one admin token
        $hasAdminToken = false;
        foreach($txtlog->getTokens() as $newToken) {
          if($newToken->priv == Priv::Admin) {
            $hasAdminToken = true;
          }
        }
        if(!$hasAdminToken) {
          throw new Exception('ERROR_INVALID_ACTION');
        }

        $txtlogController->update($txtlog);
        $found = true;
      }
    }

    if(!$found) {
      throw new Exception('ERROR_NOT_FOUND');
    }

    return [
      'status'=>'success',
      'detail'=>"Removed token $token"
    ];
  }


  /**
   * Get a list of all existing tokens
   *
   * @param txtlog object of the existing log
   * @throws Exception error message
   * @return array
   */
  public static function getExistingTokens($txtlog) {
    $result = [];

    foreach($txtlog->getTokens() ?: [] as $validToken) {
      $tokenInfo = [
        'privilege'=>strtolower($validToken->priv->name),
        'token'=>$validToken->token
      ];

      if(isset($validToken->get)) {
        $tokenInfo['search'] = str_replace('[]', '', $validToken->get);
      }

      $result[] = $tokenInfo;
    }

    return $result;
  }
}
