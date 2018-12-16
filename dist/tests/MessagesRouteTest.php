<?php

require_once 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Firebase\JWT\JWT;

/**
 * MessagesRouteTest Class
 * => /api/messages/history
 * => /api/messages/new
 */
class MessagesRouteTest extends TestCase {

  private $http;

  public function setUp() {
    $this->http = new GuzzleHttp\Client(['base_uri' => getenv("BASE_URI")]);
  }

  public function testMessageHistory() {
    # Get a JWT Token
    $jwt = $this::authJWT();

    $response = $this->http->request('GET', 'api/messages/history', [
        'headers'        => ['Authentication' => 'Bearer ' . $jwt]
    ]);

    # Check if the status code was 200
    $this->assertEquals(200, $response->getStatusCode());

    # Check if is valid JSON
    $contents = $response->getBody()->getContents();
    $this->assertEquals(true, $this::validJSON($contents));

    # Check if there's an error
    $contents = json_decode($contents, true);
    $this->assertEquals(false, isset($contents['error']));

    # Check if messages & requests keys apc_exists
    $this->assertEquals(true, (isset($contents['messages']) && isset($contents['requests'])));

    # Check types of messages and requests
    $this->assertEquals(true, (is_array($contents['messages']) && is_numeric($contents['requests'])));
  }

  public function testNewMessage() {
    # Get a JWT Token
    $jwt = $this::authJWT();

    $response = $this->http->request('POST', 'api/messages/new', [
        'headers'        => ['Authentication' => 'Bearer ' . $jwt],
        'form_params' => [
            'message' => "Test Message",
        ]
    ]);

    # Check if the status code was 200
    $this->assertEquals(200, $response->getStatusCode());

    # Check if is valid JSON
    $contents = $response->getBody()->getContents();
    $this->assertEquals(true, $this::validJSON($contents));

    # Check if there's a success
    $contents = json_decode($contents, true);
    $this->assertEquals(true, isset($contents['success']));
  }


  private function authJWT() {
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
    return $jwt;
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
