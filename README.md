# Chip Technical Task
This is a repository to house a technical task for Chip. The above code achieves a dockerised RESTful API service, with basic JWT authentication.

## Getting Started
The above code is a dockerised solution composed out of 3 main services: Nginx, MongoDB & PHP-FPM. The Nginx and MongoDB image used are official images, whereas the PHP-FPM image used is a **custom-made image** that I've uploaded to my Docker Hub - it compiles PHP with Phalcon, MongoDB and PCNTL extensions. Which are all vital to making the above API work. To run the container, simple execute the following:

    $ cd dist/
    $ docker-compose up -d

An administrator account has also been made with the following credentials:

**Username**: admin

**Password**: b4nnKK6UqsCRJbDf

It is also recommended to add the following to your `/etc/hosts` file, especially if you'll be using the test classes.

    127.0.0.1 phalcon.local
## Endpoints
**Authenticate a user**

*Description*: Authenticates a user by accepting a username and password.

*Response*: JWT Token

*Authentication Header Required*: None.

*Request*: `POST /auth/login`

|Field|Type|
|--|--|
| username | String |
| password | String |

---
**Refreshing an expired token**

*Description*: Refreshes an expired token within the refresh range.

*Response*: JWT Token

*Authentication Header Required*: `Authentication: Bearer <token>`

*Request*: `GET /auth/refresh`

---
**Listing the user's message history**

*Description*: Lists all the message history for the current user.

*Response*:  `messages` (array of messages), `requests` (number of requests made)

*Authentication Header Required*: `Authentication: Bearer <token>`

*Request*: `GET /api/messages/history`

---
**Adding a new message**

*Description*: Asynchronously adds a message to the database.

*Response*:  `success` on success, `error` on error.

*Authentication Header Required*: `Authentication: Bearer <token>`

*Request*: `POST /api/messages/new`

|Field|Type|
|--|--|
| message | String |
---
## PCNTL Usage
The PHP-FPM docker image also encapsulates PCNTL which is used to achieve asynchronicity for the new message endpoint. Upon receiving a request, the function spawns a child process in parallel.

Although the above seemed to be the best option, there are other ways of achieving asynchronicity:

 - Using a message queue like Beanstalkd or RabbitMQ
 - Asynchronously calling requests through the function with a fast connection time out
 - Using an external library like ReactPHP that has built-in asynchronicity.
 - Using HHVM / Hack

## Unit Testing
To run PHPUnit Tests, simply enter the `dist/` directory, and execute the following:

    composer test

 This will run REST tests using **Guzzle** and call each endpoint, using an individual test user.

## Side Notes

 - The above code utilises a framework called PHP-JWT, from Firebase's
   repository. This ensures that all JWTs are encoded and decoded in the
   same fashion.
 - The ports on MongoDB are left open for testing purposes - they can be closed for production.
 - Nginx uses the recommended configuration specified by Phalcon
