<?php

require_once 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Firebase\JWT\JWT;

/**
 * AuthRouteTest Class
 * => /auth/login
 * => /auth/refresh
 */
class AuthRouteTest extends TestCase {

  private $http;

  public function setUp() {
    $this->http = new GuzzleHttp\Client(['base_uri' => getenv("BASE_URI")]);
  }

  public function testAuth()
  {
    $login_response = $this->http->request('POST', 'auth/login', [
      'form_params' => [
          'username' => getenv("TEST_USER"),
          'password' => getenv("TEST_PASS")
      ]
    ]);

    # Check if the status code was 200
    $this->assertEquals(200, $login_response->getStatusCode());

    # Check if the response was valid json
    $contents = $login_response->getBody()->getContents();
    $this->assertEquals(true, $this::validJSON($contents));

    # Check if there's a JWT
    $contents = json_decode($contents, true);
    $this->assertEquals(true, isset($contents['jwt']));

    # Check if the JWT is valid
    $jwt = $contents['jwt'];
    $validJWT = null;
    try {
      $token = JWT::decode($jwt, base64_decode(getenv("JWT_SECRET")), array('HS512'));
      $validJWT = true;
    } catch (\Exception $e) {
      $validJWT = false;
    }
    $this->assertEquals(true, $validJWT);

    # Check if the JWT is refreshable
    $auth_response = $this->http->request('GET', 'auth/refresh', [
        'headers'        => ['Authentication' => 'Bearer ' . $jwt]
    ]);

    # Check if the status code was 200
    $this->assertEquals(200, $auth_response->getStatusCode());

    # Check if the response was valid json
    $contents = $auth_response->getBody()->getContents();
    $this->assertEquals(true, $this::validJSON($contents));

    # Check if there's a JWT
    $contents = json_decode($contents, true);
    $this->assertEquals(true, isset($contents['jwt']));

    # Check if the refreshed JWT is valid
    $jwt = $contents['jwt'];
    $validJWT = null;
    try {
      $token = JWT::decode($jwt, base64_decode(getenv("JWT_SECRET")), array('HS512'));
      $validJWT = true;
    } catch (\Exception $e) {
      $validJWT = false;
    }
    $this->assertEquals(true, $validJWT);
  }

  private function validJSON($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
  }


  public function tearDown() {
    $this->http = null;
  }

}

?>
