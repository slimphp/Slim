<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Http;

class StatusCode
{
    /**
     * Status code HTTP_CONTINUE
     *
     * @var int
     */
    const HTTP_CONTINUE = 100;

    /**
     * Status code HTTP_SWITCHING_PROTOCOLS
     *
     * @var int
     */
    const SWITCHING_PROTOCOLS = 101;

    /**
     * Status code HTTP_PROCESSING
     *
     * @var int
     */
    const HTTP_PROCESSING = 102;

    /**
     * Status code HTTP_OK
     *
     * @var int
     */
    const HTTP_OK = 200;

    /**
     * Status code HTTP_CREATED
     *
     * @var int
     */
    const HTTP_CREATED = 201;

    /**
     * Status code HTTP_ACCEPTED
     *
     * @var int
     */
    const HTTP_ACCEPTED = 202;

    /**
     * Status code HTTP_NONAUTHORITATIVE_INFORMATION
     *
     * @var int
     */
    const NONAUTHORITATIVE_INFORMATION = 203;

    /**
     * Status code HTTP_NO_CONTENT
     *
     * @var int
     */
    const NO_CONTENT = 204;

    /**
     * Status code HTTP_RESET_CONTENT
     *
     * @var int
     */
    const RESET_CONTENT = 205;

    /**
     * Status code HTTP_PARTIAL_CONTENT
     *
     * @var int
     */
    const PARTIAL_CONTENT = 206;

    /**
     * Status code HTTP_MULTI_STATUS
     *
     * @var int
     */
    const MULTI_STATUS = 207;

    /**
     * Status code HTTP_ALREADY_REPORTED
     *
     * @var int
     */
    const ALREADY_REPORTED = 208;

    /**
     * Status code HTTP_IM_USED
     *
     * @var int
     */
    const IM_USED = 226;

    /**
     * Status code HTTP_MULTIPLE_CHOICES
     *
     * @var int
     */
    const MULTIPLE_CHOICES = 300;

    /**
     * Status code HTTP_MOVED_PERMANENTLY
     *
     * @var int
     */
    const MOVED_PERMANENTLY = 301;

    /**
     * Status code HTTP_FOUND
     *
     * @var int
     */
    const HTTP_FOUND = 302;

    /**
     * Status code HTTP_SEE_OTHER
     *
     * @var int
     */
    const SEE_OTHER = 303;

    /**
     * Status code HTTP_NOT_MODIFIED
     *
     * @var int
     */
    const NOT_MODIFIED = 304;

    /**
     * Status code HTTP_USE_PROXY
     *
     * @var int
     */
    const USE_PROXY = 305;

    /**
     * Status code HTTP_UNUSED
     *
     * @var int
     */
    const HTTP_UNUSED = 306;

    /**
     * Status code HTTP_TEMPORARY_REDIRECT
     *
     * @var int
     */
    const TEMPORARY_REDIRECT = 307;

    /**
     * Status code HTTP_PERMANENT_REDIRECT
     *
     * @var int
     */
    const PERMANENT_REDIRECT = 308;

    /**
     * Status code HTTP_BAD_REQUEST
     *
     * @var int
     */
    const BAD_REQUEST = 400;

    /**
     * Status code HTTP_UNAUTHORIZED
     *
     * @var int
     */
    const HTTP_UNAUTHORIZED  = 401;

    /**
     * Status code HTTP_PAYMENT_REQUIRED
     *
     * @var int
     */
    const PAYMENT_REQUIRED = 402;

    /**
     * Status code HTTP_FORBIDDEN
     *
     * @var int
     */
    const HTTP_FORBIDDEN = 403;

    /**
     * Status code HTTP_NOT_FOUND
     *
     * @var int
     */
    const NOT_FOUND = 404;

    /**
     * Status code HTTP_METHOD_NOT_ALLOWED
     *
     * @var int
     */
    const METHOD_NOT_ALLOWED = 405;

    /**
     * Status code HTTP_NOT_ACCEPTABLE
     *
     * @var int
     */
    const NOT_ACCEPTABLE = 406;

    /**
     * Status code HTTP_PROXY_AUTHENTICATION_REQUIRED
     *
     * @var int
     */
    const PROXY_AUTHENTICATION_REQUIRED = 407;

    /**
     * Status code HTTP_REQUEST_TIMEOUT
     *
     * @var int
     */
    const REQUEST_TIMEOUT = 408;

    /**
     * Status code HTTP_CONFLICT
     *
     * @var int
     */
    const HTTP_CONFLICT = 409;

    /**
     * Status code HTTP_GONE
     *
     * @var int
     */
    const HTTP_GONE = 410;

    /**
     * Status code HTTP_LENGTH_REQUIRED
     *
     * @var int
     */
    const LENGTH_REQUIRED = 411;

    /**
     * Status code HTTP_PRECONDITION_FAILED
     *
     * @var int
     */
    const PRECONDITION_FAILED = 412;

    /**
     * Status code HTTP_REQUEST_ENTITY_TOO_LARGE
     *
     * @var int
     */
    const REQUEST_ENTITY_TOO_LARGE = 413;

    /**
     * Status code HTTP_REQUEST_URI_TOO_LONG
     *
     * @var int
     */
    const REQUEST_URI_TOO_LONG = 414;

    /**
     * Status code HTTP_UNSUPPORTED_MEDIA_TYPE
     *
     * @var int
     */
    const UNSUPPORTED_MEDIA_TYPE = 415;

    /**
     * Status code HTTP_REQUESTED_RANGE_NOT_SATISFIABLE
     *
     * @var int
     */
    const REQUESTED_RANGE_NOT_SATISFIABLE = 416;

    /**
     * Status code HTTP_EXPECTATION_FAILED
     *
     * @var int
     */
    const EXPECTATION_FAILED = 417;

    /**
     * Status code HTTP_IM_A_TEAPOT
     *
     * @var int
     */
    const IM_A_TEAPOT = 418;

    /**
     * Status code HTTP_MISDIRECTED_REQUEST
     *
     * @var int
     */
    const MISDIRECTED_REQUEST = 421;

    /**
     * Status code HTTP_UNPROCESSABLE_ENTITY
     *
     * @var int
     */
    const UNPROCESSABLE_ENTITY = 422;

    /**
     * Status code HTTP_LOCKED
     *
     * @var int
     */
    const HTTP_LOCKED = 423;

    /**
     * Status code HTTP_FAILED_DEPENDENCY
     *
     * @var int
     */
    const FAILED_DEPENDENCY = 424;

    /**
     * Status code HTTP_UPGRADE_REQUIRED
     *
     * @var int
     */
    const UPGRADE_REQUIRED = 426;

    /**
     * Status code HTTP_PRECONDITION_REQUIRED
     *
     * @var int
     */
    const PRECONDITION_REQUIRED = 428;

    /**
     * Status code HTTP_TOO_MANY_REQUESTS
     *
     * @var int
     */
    const TOO_MANY_REQUESTS = 429;

    /**
     * Status code HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE
     *
     * @var int
     */
    const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;

    /**
     * Status code HTTP_CONNECTION_CLOSED_WITHOUT_RESPONSE
     *
     * @var int
     */
    const CONNECTION_CLOSED_WITHOUT_RESPONSE = 444;

    /**
     * Status code HTTP_UNAVAILABLE_FOR_LEGAL_REASONS
     *
     * @var int
     */
    const UNAVAILABLE_FOR_LEGAL_REASONS = 451;

    /**
     * Status code HTTP_CLIENT_CLOSED_REQUEST
     *
     * @var int
     */
    const CLIENT_CLOSED_REQUEST = 499;

    /**
     * Status code HTTP_INTERNAL_SERVER_ERROR
     *
     * @var int
     */
    const INTERNAL_SERVER_ERROR = 500;

    /**
     * Status code HTTP_NOT_IMPLEMENTED
     *
     * @var int
     */
    const NOT_IMPLEMENTED = 501;

    /**
     * Status code HTTP_BAD_GATEWAY
     *
     * @var int
     */
    const BAD_GATEWAY = 502;

    /**
     * Status code HTTP_SERVICE_UNAVAILABLE
     *
     * @var int
     */
    const SERVICE_UNAVAILABLE = 503;

    /**
     * Status code HTTP_GATEWAY_TIMEOUT
     *
     * @var int
     */
    const GATEWAY_TIMEOUT = 504;

    /**
     * Status code HTTP_VERSION_NOT_SUPPORTED
     *
     * @var int
     */
    const VERSION_NOT_SUPPORTED = 505;

    /**
     * Status code HTTP_VARIANT_ALSO_NEGOTIATES
     *
     * @var int
     */
    const VARIANT_ALSO_NEGOTIATES = 506;

    /**
     * Status code HTTP_INSUFFICIENT_STORAGE
     *
     * @var int
     */
    const INSUFFICIENT_STORAGE = 507;

    /**
     * Status code HTTP_LOOP_DETECTED
     *
     * @var int
     */
    const LOOP_DETECTED = 508;

    /**
     * Status code HTTP_NOT_EXTENDED
     *
     * @var int
     */
    const NOT_EXTENDED = 510;

    /**
     * Status code HTTP_NETWORK_AUTHENTICATION_REQUIRED
     *
     * @var int
     */
    const NETWORK_AUTHENTICATION_REQUIRED = 511;

    /**
     * Status code HTTP_NETWORK_CONNECTION_TIMEOUT_ERROR
     *
     * @var int
     */
    const NETWORK_CONNECTION_TIMEOUT_ERROR = 599;
}
