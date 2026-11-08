<?php

namespace Kipchak\Core\Components\Http;

/**
 * The status an error response carries in its envelope, {"code": 401, "status": "UNAUTHENTICATED", "data": ...}: one
 * source for kipchak/middleware-error, every Kipchak middleware that refuses a request itself, and the OpenAPI
 * Envelope processor, so that one API never names the same refusal two ways.
 *
 * Seven codes have Kipchak's own names. Any other 5xx is SERVER_ERROR; any other 4xx keeps its HTTP name from Codes,
 * so a 409 is "CONFLICT". Codes itself is unchanged: a success, or an error a route writes with Response::json(),
 * still carries the HTTP name.
 */
final class ErrorStatus
{
    public const BAD_REQUEST = 'BAD_REQUEST';
    public const UNAUTHENTICATED = 'UNAUTHENTICATED';
    public const INSUFFICIENT_PRIVILEGES = 'INSUFFICIENT_PRIVILEGES';
    public const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    public const NOT_ALLOWED = 'NOT_ALLOWED';
    public const TOO_MANY_REQUESTS = 'TOO_MANY_REQUESTS';
    public const NOT_IMPLEMENTED = 'NOT_IMPLEMENTED';
    public const SERVER_ERROR = 'SERVER_ERROR';

    /** The codes with Kipchak's own names. */
    public const NAMES = [
        400 => self::BAD_REQUEST,
        401 => self::UNAUTHENTICATED,
        403 => self::INSUFFICIENT_PRIVILEGES,
        404 => self::RESOURCE_NOT_FOUND,
        405 => self::NOT_ALLOWED,
        429 => self::TOO_MANY_REQUESTS,
        501 => self::NOT_IMPLEMENTED,
    ];

    public static function for(int $code): string
    {
        if (isset(self::NAMES[$code])) {
            return self::NAMES[$code];
        }
        if ($code >= 500) {
            return self::SERVER_ERROR;
        }

        return Codes::getCodes()[$code] ?? self::SERVER_ERROR;
    }
}
