<?php
namespace Txtlog\Core;

/**
 * Txtlog\Core\Common
 *
 * Common framwork functions
 */
class Common {
  /**
   * Get user input, whether it is POST, PUT, etc. and without parsing values into get/post parameters
   *
   * @return raw HTTP input
   */
  public static function getUserInputRaw() {
    return file_get_contents('php://input');
  }


  /**
   * Return PATCH data in an array
   *
   * @return array containing PATCH variables
   */
  public static function getPatch() {
    parse_str(file_get_contents('php://input'), $patch);

    return $patch;
  }


  /**
   * Get the request method in lower case, e.g. post
   *
   * @return string containing the lowercase HTTP request method
   */
  public static function getRequestMethod() {
    $method = $_SERVER['REQUEST_METHOD'] ?? '';

    $method = strtolower($method);

    return $method;
  }


  /**
   * Get a client HTTP header value
   *
   * @param headerName the name of the header to search
   * @return header value or an empty string if not set
   */
  public static function getHttpHeader($headerName) {
    $result = '';
    $headerName = strtolower($headerName);

    $headers = apache_request_headers();

    foreach($headers as $header=>$value) {
      // Header names are case insensitive
      $lheader = strtolower($header);

      if($lheader == $headerName) {
        $result = trim($value);
      }
    }

    return $result;
  }


  /**
   * Poor man's HTTP Accept header parsing
   *
   * @return prefered mime type, either "json" or "html"
   */
  public static function getHttpAccept() {
    $result =  self::getHttpHeader('accept');

    // Curl or jquery
    if($result == '*/*' || $result == '') {
      return 'json';
    } else {
      return 'html';
    }
  }


  /**
   * Redirect to a provided url
   *
   * @param url where to redirect
   * @param exit optional, defaults to true to exit immediately after the redirect
   * @return void
   */
  public static function redirect($url, $exit=true) {
    header("Location: $url");

    if($exit) {
      exit;
    }
  }


  /**
   * Determine if the input value is a valid domain name
   *
   * @param domain the domain name to check
   * @return bool, true if the input is a valid domain name
   */
  public static function isValidDomain($domain) {
    return filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) == $domain;
  }


  /**
   * Determine if the provided string is a valid IP address
   *
   * @param IP address
   * @return bool, true if the input is a valid IP address
   */
  public static function isIP($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP);
  }


  /**
   * Determine if the provided string is a valid IPv6 address
   *
   * @param IP address
   * @return bool, true if the input is a valid IPv6 address
   */
  public static function isIPv6($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
  }


  /**
   * Determine if the provided string is valid JSON
   * https://stackoverflow.com/questions/6041741/fastest-way-to-check-if-a-string-is-json-in-php#6041773
   *
   * @param string the json string to test
   * @return bool, true if the input is valid JSON
   */
  public static function isJson($string) {
    if(!is_string($string)) {
      return false;
    }

    json_decode($string);
    return (json_last_error() === JSON_ERROR_NONE);
  }


  /**
   * Base58 encode a string with the Bitcoin alphabet
   * gmp base58 uses 0-9A-Za-v, translate zero (0) with w, I with x, O with y and lower l with z
   * gmp_strval(gmp_init('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuv', 58), 10);
   *
   * @param string in hex
   * @return base58 string
   */
  public static function base58Encode($string) {
    // (Bitcoin) base58 alphabet. No zero (0) upper I, upper O or lower l
    $base58 = gmp_strval(gmp_init($string, 16), 58);

    return strtr($base58, '0IOl', 'wxyz');
  }


  /**
   * Base58 decode a string
   *
   * @param base58 string
   * @return hex string
   */
  public static function base58Decode($string) {
    $gmpBase58 = strtr($string, 'wxyz', '0IOl');

    return gmp_strval(gmp_init($gmpBase58, 58), 16);
  }


  /**
   * Determine if the provided string is in base58 Bitcoin encoding
   *
   * @param input string
   * @return bool, true if the input is valid base58
   */
  public static function isBase58($input) {
    return preg_match('/^[1-9A-HJ-NP-Za-km-z]+$/', $input);
  }


  /**
   * Determine if the provided string is a UUID
   *
   * @param input string
   * @return bool, true if the input is a valid UUID
   */
  public static function isUUID($input) {
    return preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-5][0-9a-f]{3}-[089ab][0-9a-f]{3}-[0-9a-f]{12}/i', $input);
  }


  /**
   * Extract all the valid IP addresses from a given string
   *
   * @param input free form text
   * @return array with IP addresses (stripped from . and :)
   */
  public static function extractIPs($input) {
    // Split on non alphanumeric and keep IPv4 char . and IPv6 char :
    $parts = preg_split('/[^a-zA-Z0-9.:]+/', str_replace(["\\r\\n", "\\r", "\\n"], '', $input));
    $ips = [];

    foreach($parts as $part) {
      // Check if this part is an IP
      if((str_contains($part, '.') || str_contains($part, ':')) && self::isIP($part)) {
        $ips[] = str_replace(['.',':'], '', $part);
      }
    }

    return $ips;
  }


  /**
   * Extract all the UUIDs from a string
   *
   * @param input string
   * @return array with UUID's (without dashes)
   */
  public static function extractUUIDs($input) {
    $input = strtolower($input);

    if(preg_match_all('/[0-9a-f]{8}-[0-9a-f]{4}-[0-5][0-9a-f]{3}-[089ab][0-9a-f]{3}-[0-9a-f]{12}/', $input, $matches)) {
      return str_replace('-', '', $matches[0]);
    }

    return [];
  }


  /**
   * Check if the provided string is in one of these exact formats:
   * 2yyy-mm-dd hh:mm:ss
   * 2yyy-mm-dd hh:mm:ssZ
   * 2yyy-mm-dd hh:mm:ss.sss
   * 2yyy-mm-dd hh:mm:ss.sssZ
   * 2yyy-mm-ddThh:mm:ss
   * 2yyy-mm-ddThh:mm:ssZ
   * 2yyy-mm-ddThh:mm:ss.sss
   * 2yyy-mm-ddThh:mm:ss.sssZ
   * 123456789012345678901234
   *
   * @param input string with date to check
   * @return bool, true if the input is a valid ISO8601 date in the above format
   */
  public static function isDateISO8601($input) {
    if(!is_string($input) || strlen($input) > 24) {
      return false;
    }

    // Add "T" parameter if necessary
    $input = str_replace(' ', 'T', $input);

    // Add trailing Z if necessary
    if(strlen($input) == 19 || strlen($input) == 23) {
      $input .= 'Z';
    }

    if(strlen($input) == 20 || strlen($input) == 24) {
      // Note: this check is rather lax and accepts hours such as 33, minute 99, etc... but so does DateTime::ISO8601_EXPANDED
      //return \DateTimeImmutable::createFromFormat(\DateTime::ISO8601_EXPANDED, $input) === false;
      if(preg_match('/^2\d{3}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d{3})?Z$/', $input)) {
        return true;
      }
    }

    return false;
  }


  /**
   * Get the timestamp with milliseconds for an ISO8601 date in this format:
   * yyyy-mm-dd hh:mm:ss
   * yyyy-mm-dd hh:mm:ss.sss
   * yyyy-mm-dd hh:mm:ssZ
   * yyyy-mm-dd hh:mm:ss.sssZ
   * yyyy-mm-ddThh:mm:ss
   * yyyy-mm-ddThh:mm:ss.sss
   * yyyy-mm-ddThh:mm:ssZ
   * yyyy-mm-ddThh:mm:ss.sssZ
   * 123456789012345678901234
   *
   * @param input string with validated date
   * @return timestamp with milliseconds
   */
  public static function getTimestampFromISO8601Date($input) {
    // Add "T" parameter if necessary
    $input = str_replace(' ', 'T', $input);

    // Add trailing Z if necessary
    if(strlen($input) == 19 || strlen($input) == 23) {
      $input .= 'Z';
    }

    // Strip milliseconds and add back Z
    $ts = \DateTimeImmutable::createFromFormat(\DateTime::ISO8601, substr($input, 0, 19).'Z')->format('U');

    return strlen($input) == 20 ? $ts.'000' : "$ts".substr($input, 20, 3);
  }


  /**
   * Determine if the provided string can be a Stripe session identifier
   *
   * @param input string
   * @return bool, true if the input can be a valid Strip session identifier
   */
  public static function canBeStripeSession($session) {
    if(substr($session, 0, 3) != 'cs_') {
      return false;
    }

    if(strlen($session) > 100) {
      return false;
    }

    if(!ctype_alnum(str_replace(['_', '-'], '', $session))) {
      return false;
    }

    return true;
  }


  /**
   * Return a GET parameter
   *
   * @param var the GET variable to get
   * @param maxlength optional trim to this length
   * @param xssSafe optional return a variable suitable for outputting in HTML
   * @return the GET value
   */
  public static function get($var, $maxlength=0, $xssSafe=false) {
    $result = '';

    if(isset($_GET[$var])) {
      $result = $_GET[$var];
    }

    // Set the max length before stripping html tags
    if(strlen($result) > 0 && $maxlength) {
      $result = mb_substr($result, 0, $maxlength, 'UTF-8');
    }

    // Get an XSS safe string if required
    if($xssSafe) {
      $result = self::getString($result);
    }

    return trim($result);
  }


  /**
   * Return a POST parameter
   *
   * @param var the POST variable to get
   * @param maxlength optional trim to this length
   * @return the POST value
   */
  public static function post($var, $maxlength=0) {
    $result = '';

    if(isset($_POST[$var])) {
      $result = $_POST[$var];
    }

    // Set the max length before stripping html tags
    if(strlen($result) > 0 && $maxlength) {
      $result = mb_substr($result, 0, $maxlength, 'UTF-8');
    }

    return $result;
  }


  /**
   * Get the SERVER REQUEST_URI, without leading slash
   * Optionally stripping a part at the beginning
   *
   * @param strip if set then remove all occurences of "strip" from the result
   * @param getFirstPart. Defaults to false, if true: "/list/abc/def?gg=jj" => "abc"
   * @return string with the request URI
   */
  public static function getRequest($strip='', $getFirstPart=false) {
    $result = '';

    if(isset($_SERVER['REQUEST_URI'])) {
      $result = $_SERVER['REQUEST_URI'];

      // Strip an optional URL part from the beginning
      if($strip && substr($result, 0, strlen($strip)) == $strip) {
        $result = substr($result, strlen($strip));
      }

      // Strip leading forward slash if there is one
      if(substr($result, 0, 1) == '/') {
        $result = substr($result, 1);
      }
    }

    if($getFirstPart) {
      // Strip everything after an optional slash
      if(strpos($result, '/') !== false) {
        $result = substr($result, 0, strpos($result, '/'));
      }

      // Strip querystring parameters
      if(strpos($result, '?') !== false) {
        $result = substr($result, 0, strpos($result, '?'));
      }
    }

    return $result;
  }


  /**
   * Get the HTTP Authorization header
   *
   * @param includeHeader include the header name in the return value, i.e. prepend "authorization: "
   * @return string
   */
  public static function getAuthorizationHeader($includeHeader=false) {
    $result = null;

    $headers = apache_request_headers();

    foreach($headers as $header=>$value) {
      // Header names are case insensitive
      $lheader = strtolower($header);
      if($lheader == 'authorization' && !empty($value)) {
        $result = $includeHeader ? "$lheader: " : '';
        $result .= trim($value);
      }
    }

    return $result;
  }


  /**
   * Return an HTML XSS safe string to display inside HTML
   * Not safe for display inside a.href!
   *
   * @param orig the string to encode
   * @param maxLength optional trim to this length
   * @return string
   */
  public static function getString($orig, $maxLength=null) {
    $result = '';

    if(empty($orig)) {
      if(is_array($orig)) {
        return '';
      }
      // Return $orig instead of '' so (int) 0 values are returned properly
      return $orig;
    }

    // Check for arrays or objects
    if(is_array($orig) || is_object($orig)) {
      $orig = json_encode($orig);
    }

    if(!is_null($maxLength)) {
      $orig = substr($orig, 0, $maxLength);
    }

    $result = htmlentities($orig, ENT_QUOTES, 'UTF-8');

    return $result;
  }


  /**
   * Return a random base58 string
   *
   * @param length of the string
   * @return string
   */
  public static function getRandomString($length) {
    // (Bitcoin) base58 alphabet. No zero (0) upper I, upper O or lower l
    $chars = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    $max = strlen($chars) - 1;
    $rndstring = '';

    for($i = 0; $i < $length; $i++) {
      $rnd = random_int(0, $max);
      $rndstring .= $chars[$rnd];
    }

    return $rndstring;
  }


  /**
   * Return a random hex string
   *
   * @param length of the string
   * @return string
   */
  public static function getRandomHex($length) {
    $chars = '1234567890abcdef';

    $max = strlen($chars) - 1;
    $rndstring = '';

    for($i = 0; $i < $length; $i++) {
      $rnd = random_int(0, $max);
      $rndstring .= $chars[$rnd];
    }

    return $rndstring;
  }


  /**
   * Returns the IPv4/IPv6 address of the client
   *
   * @return string containing the IP
   */
  public static function getIP() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    return $ip;
  }


  /**
   * Returns true if the provided value is an integer or a string containing an int
   *
   * @param val the value to check
   * @param min the minimum valid int value (inclusive)
   * @param max the maximum valid int value (inclusive)
   * @return bool
   */
  public static function isInt($val, $min=null, $max=null) {
    $isInt = ctype_digit(strval($val));

    if(!is_null($min) && $val < $min) {
      $isInt = false;
    }

    if(!is_null($max) && $val > $max) {
      $isInt = false;
    }

    return $isInt;
  }


  /**
   * Returns true if the provided value is a hex string
   *
   * @param val the value to check, optional with 0x prefix
   * @return bool
   */
  public static function isHex($val) {
    return ctype_xdigit(ltrim($val, '0x'));
  }


  /**
   * Convert a (large) number from hex to int
   *
   * @param val
   * @return int value or null when val is not valid hex
   */
  public static function hexToInt($val) {
    return self::isHex($val) ? gmp_strval(gmp_init('0x'.$val)) : null;
  }


  /**
   * Convert a (large) int to hex
   *
   * @param val
   * @return int value
   */
  public static function intToHex($val) {
    return gmp_strval(gmp_init($val, 10), 16);
  }


  /**
   * Returns true if the provided string is serialized
   * Credit: https://www.php.net/manual/en/function.unserialize.php#85097
   *
   * @param string the value to check
   * @return bool
   */
  public static function isSerialized($string) {
    return ($string == serialize(false) || @unserialize($string) !== false);
  }


  /**
   * Set an HTTP response code
   *
   * @return void
   */
  public static function setHttpResponseCode($code) {
    if(self::isInt($code)) {
      http_response_code($code);
    }
    // Ignore invalid codes
  }


  /**
   * Format numbers in a human readable way
   *
   * @param number integer
   * @return string with a textual representation of the number
   */
  public static function formatReadable($number) {
    if(!self::isInt($number)) {
      return '';
    }

    if($number >= 1000000000) {
      return round($number/1000000000, 2).' billion';
    } elseif($number >= 1000000) {
      return round($number/1000000, 2).' million';
    } elseif($number >= 1000) {
      return round($number/1000, 2).' thousand';
    } else {
      return $number;
    }
  }


  /**
   * Get the first 64 bits of a IPv6 address
   *
   * @param ipv6 string
   * @return First 64 bits of the provided IPv6 address
   */
  public static function get64FromIPv6($ip) {
    $result = (object)['ipFull'=>'', 'ipFrom'=>''];

    if(!self::isIPv6($ip)) {
      return $result;
    }

    $addr = inet_pton($ip);
    $unpack = unpack('H*hex', $addr);
    $hex = $unpack['hex'];
    $hexArr = str_split($hex, 4);

    // e.g. ip = 2620:7:6001::ffff:c759:e648, ipFull = 2620:0007:6001:0000:0000:ffff:c759:e648
    $result->ipFull = implode(':', $hexArr);

    // e.g. ipFrom = 2620:0007:6001:0000::
    $result->ipFrom = implode(':', array_slice($hexArr, 0, 4)).'::';

    return $result;
  }


  /**
   * Get an incrementing unique ID
   * Valid timestamps until 2527-06-23 06:20:44.000
   *
   * @param length number of random hex characters
   * @param timestamp optional to generate an ID with the given timestamp (with optional milliseconds: seconds.msecs)
   * @return string
   */
  public static function getUniqueID($length, $timestamp=null) {
    if(is_null($timestamp)) {
      $timestamp = intval(microtime(true) * 1000);
    } elseif(strpos($timestamp, '.') !== false) {
      $timestamp = str_replace('.', '', $timestamp);
    } else {
      $timestamp = "{$timestamp}000";
    }

    return dechex($timestamp) // 11 hex chars
      .self::getRandomHex($length);
  }


  /**
   * Parse a uniqueID into the date, hex value, random number and timestamp
   *
   * @param uniqueID generated by this class
   * @return object containing date, hex, id, random and timestamp
   */
  public static function parseUniqueID($uniqueID) {
    $time = hexdec(substr($uniqueID, 0, 11));
    $ts = substr($time, 0, -3);

    return (object)[
      'date'=>date('Y-m-d H:i:s', $ts).'.'.substr($time, -3),
      'id'=>$uniqueID,
      'int'=>self::hexToInt($uniqueID),
      'random'=>substr($uniqueID, 11),
      'timestamp'=>$ts
    ];
  }
}
