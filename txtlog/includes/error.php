<?php
namespace Txtlog\Includes;
/*
 * All custom error messages and HTTP response codes
 *
 * Reference list:
 * https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
 */
class Error {
  /**
   * Array with HTTP status codes and error message to show the client
   *
   * @var array
   */
  private static $errorList =
    [
      // 403 Forbidden
      'ERROR_FORBIDDEN'=>403,

      // 400 Bad Request
      'ERROR_INVALID_ACTION'=>400,

      // 400 Bad Request
      'ERROR_INVALID_JSON'=>400,

      // 400 Bad Request
      'ERROR_INVALID_USERNAME'=>400,

      // 405 Method Not Allowed
      'ERROR_METHOD_NOT_ALLOWED'=>405,

      // 500 Internal Server Error
      'ERROR_NO_SERVER_AVAILABLE'=>500,

      // 400 Bad Request
      'ERROR_NO_VALID_ROWS'=>400,

      // 404 Not Found
      'ERROR_NOT_FOUND'=>404,

      // 400 Bad Request
      'ERROR_PROVIDE_SCOPE_ADMIN_INSERT_VIEW'=>400,

      // 503 Service Unavailable
      'ERROR_SERVICE_UNAVAILABLE'=>503,

      // 429 Too Many Requests
      'ERROR_TOO_MANY_REQUESTS'=>429,

      // 400 Bad Request
      'ERROR_TOKEN_LIMIT_REACHED'=>400,

      // 500 Internal Server Error
      'ERROR_UNKNOWN'=>500,

      // 400 Bad Request
      'ERROR_USERNAME_ALREADY_EXISTS'=>400,
    ];


  /**
   * Default HTTP status code to return if the error message is not found
   *
   * @var int
   */
  private static $defaultHttpResponse = 200;


  /**
   * Get the accompanying http response code belonging to an error message
   *
   * @param message the error message
   * @return int
   */
  public static function getHttpCode($message) {
    return array_key_exists($message, self::$errorList) ? self::$errorList[$message] : self::$defaultHttpResponse;
  }
}
