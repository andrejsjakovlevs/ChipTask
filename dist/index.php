<?php

###
### Setup
###

require_once('vendor/autoload.php');

use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\DI\FactoryDefault;
use Phalcon\Db\Adapter\MongoDB\Client;
use Phalcon\Http\Request;
use Firebase\JWT\JWT;

/**
 * Helper Functions
 */
include 'util/Helpers.php';

/**
 * Definitions
 */
define("JWT_SECRET", "gZEZASr/+lYZWAmuuxYkhteCEfySMTqB+b6KTWdDUsilGrqLXNZNajU9/ylMFriGDwWqnyqqSPgZ/3yzMZAfUQ=="); # Base-64 Encoded JWT Key ||  Value also found in PHPUnit.xml
define("JWT_EXPIRY", 120); # Amount of time (120 seconds) it takes for a token to expire
define("JWT_REFRESH_EXPIRY", 604800); # Amount of time (7 days) allowed for a token to be refreshed

/**
 * Load Phalcon Incubator & Models
 */
$loader = new Loader();

$loader->registerNamespaces([
    'Phalcon' => 'incubator/Library/Phalcon/'
]);

$loader->registerDirs(
    [
        'models'
    ]
);

$loader->register();

/**
 * MongoDB Connection
 */
$di = new FactoryDefault();

$di->setShared('mongo', function() {
    $mongo = new Client('mongodb://mongodb:27017');
    return $mongo->selectDatabase('phalcon');
});

$di->set('collectionManager', function(){
    return new Phalcon\Mvc\Collection\Manager();
}, true);

$app = new Micro($di);

/**
 * Micro App will only use JSON-encoded data.
 */
header('Content-Type: application/json');

/**
 * Clear pre-defined routes.
 */
$di->get('router')->clear();

/**
 * Global request variable for utility
 */
$request = new Request();


###
### Routes
###

/**
 * Simple catch for 404'd routes.
 */
$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, "Not Found")->sendHeaders();
    oError("not_found");
});


/**
 * [POST] Username & Password Login Route
 * Description: Accepts a username and password in POST, and returns a JWT valid till the expiry (120 seconds).
 * Note: If the username or password is incorrect, an error is sent
 */
$app->post(
    '/auth/login',
    function () {
      global $request;
      if($request->getPost('username') != NULL && $request->getPost('password') != NULL){
        $username = trim($request->getPost('username'));
        $password = hash('sha256', trim($request->getPost('password')));

        $user = Users::findFirst(
            [
                [
                    'username' => $username,
                    'password' => $password
                ]
            ]
        );

        if(is_object($user)){
          $id = ((array)$user->getId())['oid'];
          $token = generateToken($id, $username);

          oJSON($token);
        } else {
          oError("invalid_credentials", 401);
        }
      } else {
        oError("invalid_parameters", 400);
      }
    }
);

/**
 * [GET] Token Refresh Route
 * Description: Accepts a JWT in the Authentication header, and resends a new JWT with a fresh expiry.
 * Note: If the token was issued before the refrsh expiry date (7 days), the user must re-login.
 */
 $app->get(
     '/auth/refresh',
     function () {
       global $request;
       if($request->getHeader("Authentication")){
         list($token) = sscanf( $request->getHeader("Authentication"), 'Bearer %s');
         if($token){
           try {
             $jwt = JWT::decode($token, base64_decode(constant("JWT_SECRET")), array('HS512'));
             list($headb64, $bodyb64, $cryptob64) = explode('.', $token);
             $payload = json_decode(JWT::urlsafeB64Decode($bodyb64), true);
             $issued_at = $payload['iat'];
             $new_token = generateToken($payload['data']['userID'], $payload['data']['userName']);
             oJSON($new_token);
           } catch (Exception $e) {
             if($e->getMessage() == "Expired token"){
               list($headb64, $bodyb64, $cryptob64) = explode('.', $token);
               $payload = json_decode(JWT::urlsafeB64Decode($bodyb64), true);
               $issued_at = $payload['iat'];
               if(time() <= ($issued_at + constant("JWT_REFRESH_EXPIRY"))){
                  $new_token = generateToken($payload['data']['userID'], $payload['data']['userName']);
                  oJSON($new_token);
               } else {
                 oError("expired_refresh", 401);
               }
             } else {
               oError("invalid_token", 400);
             }
           }
         } else {
           oError("invalid_parameters", 400);
         }
       } else {
         oError("invalid_parameters", 400);
       }
     }
 );

 /**
  * [GET] Message History Retrieval Route
  * Description: Gets the message history for the User based on JWT's payload.
  * Note: Also includes a field to retrieve amount of times the request has been loaded.
  */
  $app->get(
      '/api/messages/history',
      function () {
        global $request;
        if($request->getHeader("Authentication")){
          list($token) = sscanf( $request->getHeader("Authentication"), 'Bearer %s');
          if($token){
            try {
              $jwt = JWT::decode($token, base64_decode(constant("JWT_SECRET")), array('HS512'));
              $payload = (array)((array)$jwt)['data'];
              if(isset($payload['userID'])){
                $user = $payload['userID'];

                $api_request = new Requests();
                $api_request->action = 'message_list';
                $api_request->subject = $user;
                $api_request->time = time();
                $api_request->save();

                $load = array("messages" => [], "requests" => 0);

                # First, get the messages

                $messages = Messages::find(
                    [
                        [
                            'user' => $user
                        ]
                    ]
                );

                foreach ($messages as $message) {
                  $instance = array(
                    "id" => ((array)$message->getId())['oid'],
                    "user" => $message->user,
                    "message" => $message->message,
                    "time" => $message->time
                  );

                  array_push($load['messages'], $instance);
                }

                # Second, get the requests

                $requests = Requests::find(
                    [
                        [
                            'action' => 'message_list',
                            'subject' => $user
                        ]
                    ]
                );

                $load['requests'] = count($requests);

                # Save the request, and send the load.
                oJSON($load);
              } else {
                 oError("invalid_token", 401);
              }
            } catch (Exception $e) {
              if($e->getMessage() == "Expired token"){
                oError("expired_token", 401);
              } else {
                oError("invalid_token", 401);
              }
            }
          } else {
            oError("invalid_parameters", 400);
          }
        } else {
          oError("invalid_parameters", 400);
        }
      }
  );

  /**
   * [POST] New Message Route
   * Description: Creates a new message for a User based on the JWT payload.
   * Note: Saving the message is asynchronous, by spawning a child process.
   */
   $app->post(
       '/api/messages/new',
       function () {
         global $request;
         if($request->getPost('message') != NULL){
           if($request->getHeader("Authentication")){
             list($token) = sscanf( $request->getHeader("Authentication"), 'Bearer %s');
             if($token){
               try {
                 $jwt = JWT::decode($token, base64_decode(constant("JWT_SECRET")), array('HS512'));
                 $payload = (array)((array)$jwt)['data'];
                 if(isset($payload['userID'])){
                   $message = new Messages();
                   $message->user = $payload['userID'];
                   $message->message = $request->getPost('message');
                   $message->time = time();
                   spawnAsyncProcess(function() use ($message){
                     $message->save();
                   });
                   oJSON(array("success" => "request_sent"));
                 } else {
                    oError("invalid_token", 401);
                 }
               } catch (Exception $e) {
                 if($e->getMessage() == "Expired token"){
                   oError("expired_token", 401);
                 } else {
                   oError("invalid_token", 401);
                 }
               }
             } else {
               oError("invalid_parameters", 400);
             }
           } else {
             oError("invalid_parameters", 400);
           }
         } else {
           oError("invalid_parameters", 400);
         }
       }
   );


$app->handle();
