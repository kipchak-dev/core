<?php

namespace Kipchak\Core\Components\Http;
use Exception;

/**
 * Class Codes
 * @package Mamluk\KipchakHelper\HttpCodes
 */

class Codes
{
    /**
     *
     */
    public static function getCode($code)
    {
        $codes = self::getCodes();

        if (isset($codes[$code])) {
            return $codes[$code];
        }

        return $codes[$code];
    }

    /**
     *
     */
    public static function getCodes()
    {
        return [
            // 1xx Informational
            100 => 'CONTINUE',
            101 => 'SWITCHING PROTOCOLS',
            102 => 'PROCESSING',
            103 => 'EARLY HINTS',
            // 2xx Success
            200 => 'OK',
            201 => 'CREATED',
            202 => 'ACCEPTED',
            203 => 'NON-AUTHORITATIVE INFORMATION',
            204 => 'NO_CONTENT',
            205 => 'RESET_CONTENT',
            206 => 'PARTIAL_CONTENT',
            207 => 'MULTI-STATUS',
            208 => 'ALREADY REPORTED',
            226 => 'IM USED',
            // 3xx Redirection
            300 => 'MULTIPLE_CHOICES',
            301 => 'MOVED_PERMANENTLY',
            302 => 'FOUND',
            303 => 'SEE OTHER',
            304 => 'NOT MODIFIED',
            305 => 'USE PROXY',
            306 => 'UNUSED',
            307 => 'TEMPORARY REDIRECT',
            308 => 'PERMANENT REDIRECT',
            // 4xx Client Errors
            400 => 'BAD REQUEST',
            401 => 'UNAUTHORIZED',
            402 => 'PAYMENT REQUIRED',
            403 => 'FORBIDDEN',
            404 => 'NOT FOUND',
            405 => 'METHOD NOT ALLOWED',
            406 => 'NOT ACCEPTABLE',
            407 => 'PROXY AUTHENTICATION REQUIRED',
            408 => 'REQUEST TIMEOUT',
            409 => 'CONFLICT',
            410 => 'GONE',
            411 => 'LENGTH REQUIRED',
            412 => 'PRECONDITION FAILED',
            413 => 'PAYLOAD TOO LARGE',
            414 => 'URI TOO LONG',
            415 => 'UNSUPPORTED MEDIA TYPE',
            416 => 'RANGE NOT SATISFIABLE',
            417 => 'EXPECTATION FAILED',
            418 => 'I\'M A TEAPOT',
            421 => 'MISDIRECTED REQUEST',
            422 => 'UNPROCESSABLE ENTITY',
            423 => 'LOCKED',
            424 => 'FAILED DEPENDENCY',
            425 => 'TOO EARLY',
            426 => 'UPGRADE REQUIRED',
            428 => 'PRECONDITION REQUIRED',
            429 => 'TOO MANY REQUESTS',
            431 => 'REQUEST HEADER FIELDS TOO LARGE',
            451 => 'UNAVAILABLE FOR LEGAL REASONS',
            // 5xx Server Errors
            500 => 'INTERNAL SERVER ERROR',
            501 => 'NOT IMPLEMENTED',
            502 => 'BAD GATEWAY',
            503 => 'SERVICE UNAVAILABLE',
            504 => 'GATEWAY TIMEOUT',
            505 => 'HTTP VERSION NOT SUPPORTED',
            506 => 'VARIANT ALSO NEGOTIATES',
            507 => 'INSUFFICIENT STORAGE',
            508 => 'LOOP DETECTED',
            510 => 'NOT EXTENDED',
            511 => 'NETWORK AUTHENTICATION REQUIRED',
            599 => 'UNKNOWN ERROR CODE'
        ];
    }

}