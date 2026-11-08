<?php

declare(strict_types=1);

namespace Kipchak\Core\Components\Dto;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;

/**
 * A request body that is not valid: a 400. kipchak/middleware-error writes the message as the envelope's data and the
 * errors, by field, as meta.errors.
 */
final class InvalidInput extends HttpBadRequestException
{
    /**
     * @param array<string, list<string>> $errors what is wrong with each field, by its path in the body
     */
    public function __construct(ServerRequestInterface $request, string $message, public readonly array $errors = [])
    {
        parent::__construct($request, $message);
    }
}
