# Lucinda Framework Engine API

This API stores internal components of [Lucinda Framework 3.0](http://www.lucinda-framework.com) open for update but not open for change by developers. Most of classes inside are dedicated at APIs binding, so only the following are of any interest to developers:

- [Lucinda\Framework\AbstractCacheable](#abstractcacheable)
- [Lucinda\Framework\AbstractLoginThrottler](#abstractloginthrottler)
- [Lucinda\Framework\AbstractReporter](#abstractreporter)
- [Lucinda\Framework\Json](#json)
- [Lucinda\Framework\RestController](#restcontroller)
- [Lucinda\Framework\SingletonRepository](#singletonrepository)

API is fully PSR-4 compliant, only requiring PHP7.1+ interpreter and SimpleXML extension. It has 100% Unit Test coverage, using [UnitTest API](https://github.com/aherne/unit-testing) instead of PHPUnit for greater flexibility.

## AbstractCacheable

This abstract class is a [\Lucinda\Headers\Cacheable](https://github.com/aherne/headers-api/blob/master/src/Cacheable.php) that binds to [STDOUT MVC API](https://github.com/aherne/php-servlets-api/tree/v3.0.0) in order to implement recipe for a class able to generate an ETag/LastModified header value for response to any cacheable requested resource.

<sub>That value will be used in cache-driven communication between your project and client's browser and thus make it possible for your site to answer only with 304 Not Modified header instead of full response in case latter hasn't changed.</sub>

Class defines following prototype methods developers must implement when extending:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| setTime | void | void | Sets ETag representation of response to requested resource into **$etag** field |
| setEtag | void | void | Sets LastModified representation of response to requested resource into **$last_modified_time** field |

By virtue of binding to STDOUT MVC API, classes implementing above will have access to following protected fields:

| Field | Type | Description |
| --- | --- | --- |
| $request | [\Lucinda\STDOUT\Request](https://github.com/aherne/php-servlets-api/tree/v3.0.0#class-request) | Request received from client. |
| $response | [\Lucinda\STDOUT\Response](https://github.com/aherne/php-servlets-api/tree/v3.0.0#class-response) | Response to send back to caller. |

## AbstractLoginThrottler

This abstract class is a [\Lucinda\WebSecurity\Authentication\Form\LoginThrottler](https://github.com/aherne/php-security-api/blob/v3.0.0/src/Authentication/Form/LoginThrottler.php) that penalizes each failed login by ``` pow(2, failedAttempts-1) ``` seconds in order to fight against dictionary attacks.

<sub>Throughout the period of penalty, login will fail automatically without checking database. The more consecutive failed attempts, the greater the penalty. Once login passes, all penalties are unset.</sub>

Class defines following prototype methods developers must implement when extending:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| persist | void | void | Tracks login attempts to a storage medium (sql or nosql database) |

Classes implementing above will have access to following protected fields:

| Field | Type | Description |
| --- | --- | --- |
| $attempts | integer | How many attempts were made from current IP for same username. |
| $penaltyExpiration | integer\|NULL | UNIX time at which penalty will expire, if any |

## AbstractReporter

This abstract class is a [\Lucinda\STDERR\Reporter](https://github.com/aherne/errors-api/tree/v2.0.0#abstract-class-reporter) that binds to [Logging API](https://github.com/aherne/php-logging-api/tree/v3.0.0) in order to report errors to logs based on their severity level, identified by value of attribute *error_type* of [**exception**](https://github.com/aherne/errors-api/tree/v2.0.0#exceptions) tag @ *stderr.xml* that matches \Exception handled:

| Value | Effect |
| --- | --- |
| none | Exception is not an error so it will not be reported. |
| client | Exception is an error caused by client (eg: a 404), so it will not be logged except if developers decide otherwise. |
| server | Exception is an error caused by database server, so it will be logged with *emergency* priority. |
| syntax | Exception is a code bug made by developers, so it will be logged with *alert* priority. |
| logical | Exception is a conceptual bug discovered at runtime, so it will be logged with *critical* priority. |
|   | Exception is a generic error, so it will be logged with *error* priority. |

Class defines following prototype method developers must implement when extending:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| getLogger | void | [\Lucinda\Logging\Logger](https://github.com/aherne/php-logging-api/tree/v3.0.0#logging) | Generates and returns a logger instance from matching **reporter** tag @ *stderr.xml* |

## Json

This class is an OOP wrapper over PHP's native functions, encapsulating json generation and execution, throwing \Lucinda\Framework\Json\Exception in case process fails. Class defines following methods, all relevant to developers:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| encode | mixed | string | Encodes a primitive/array/object to json format. |
| decode | string | array | Decodes a json into array. |

## RestController

This abstract class is a [\Lucinda\MVC\Controller](https://github.com/aherne/php-servlets-api/tree/v3.0.0#abstract-class-controller) to be extended if your project is a RESTful web service API. Classes extending it must implement a method for each HTTP method respective route *supports*:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| GET | void | void | Logic to execute when respective route is accessed via GET method. |
| POST | void | void | Logic to execute when respective route is accessed via POST method. |
| PUT | void | void | Logic to execute when respective route is accessed via PUT method. |
| DELETE | void | void | Logic to execute when respective route is accessed via DELETE method. |
| HEAD | void | void | Logic to execute when respective route is accessed via HEAD method. |
| CONNECT | void | void | Logic to execute when respective route is accessed via CONNECT method. |
| OPTIONS | void | void | Logic to execute when respective route is accessed via OPTIONS method. |
| TRACE | void | void | Logic to execute when respective route is accessed via TRACE method. |

If a route is accessed using a method not covered in its matching controller, a [\Lucinda\STDOUT\MethodNotAllowedException](https://github.com/aherne/php-servlets-api/blob/v3.0.0/src/MethodNotAllowedException.php) is thrown!

<sub>Unlike other frameworks, Lucinda considers controller "actions" to be an anti-pattern, a recipe for bloated illogical controllers. Only in this particular case are "actions" making a logical sense: a controller that behaves differently depending on HTTP method it's called with.</sub>

## SingletonRepository

This class is static repository of singletons, to be *set* by event listeners and *get* by procedural functions provided by skeleton. Class defines following public static methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| set | string, object | void | Sets a singleton by unique identifier. |
| get | string | object | Gets a singleton by unique identifier. |
