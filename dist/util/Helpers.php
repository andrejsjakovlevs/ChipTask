<?php

  /**
   * Paranoid autoload insertion
   */
  require_once('vendor/autoload.php');

  /**
   * Making use of Firebase's JWT implementation
   */
  use Firebase\JWT\JWT;

  /**
   * Simple JSON-based error outputing with code headers
   */
  function oError($error, $code = 200) {
    switch ($code) {
      case 200:
          header('HTTP/1.0 200 Ok');
        break;
      case 401:
          header('HTTP/1.0 401 Unauthorized');
        break;
      case 401:
          header('HTTP/1.0 400 Bad Request');
        break;
    }
    echo json_encode(array("error"=>$error));
  }

  /**
   * Simple JSON-based outputing
   */
  function oJSON($array) {
    echo json_encode($array);
  }

  /**
   * Generate a custom JWT token based on the user's credentials.
   */
  function generateToken($id, $username) {
    $tokenId    = base64_encode(random_bytes(32));
    $issuedAt   = time();
    $notBefore  = $issuedAt;
    $expire     = $notBefore + constant("JWT_EXPIRY");
    $serverName = $_SERVER['SERVER_NAME'];

    $data = [
      'iat'  => $issuedAt,
      'jti'  => $tokenId,
      'iss'  => $serverName,
      'nbf'  => $notBefore,
      'exp'  => $expire,
      'data' => [
        'userID'   => $id,
        'userName' => $username,
      ]
    ];

    $secretKey = base64_decode(constant("JWT_SECRET"));

    $jwt = JWT::encode(
      $data,
      $secretKey,
      'HS512'
    );

    return array('jwt' => $jwt);
  }

  /**
   * Spawn a child process using PCNTL.
   */
  function spawnAsyncProcess($func, $args = []) {
    if (pcntl_fork() == 0) {
      call_user_func_array($func, $args);
    }
  }

?>
