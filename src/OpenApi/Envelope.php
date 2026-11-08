<?php

namespace Kipchak\Core\OpenApi;

use Kipchak\Core\Components\Http\Codes;
use Kipchak\Core\Components\Http\ErrorStatus;
use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use OpenApi\Generator;

/**
 * A swagger-php processor that documents responses as a Kipchak API sends them: in the envelope that
 * Http\Response::json() writes, {"code": 200, "status": "OK", "meta"?: ..., "data": ...}.
 *
 * A JSON success declared as the data alone, such as `new OA\JsonContent(ref: '#/components/schemas/Order')`,
 * becomes `allOf: [{$ref: Success}, {properties: {code, status, data: <as declared>}}]`, with the status name for
 * its code. With $errors, a 4xx or 5xx response that declares no content gets the error envelope that
 * kipchak/middleware-error writes. Register it with the generator:
 *
 *   (new Generator($logger))->withProcessorPipeline(fn ($pipeline) => $pipeline->add(new Envelope(errors: true)))
 *
 * Left as they are: responses already in the envelope (an object with code, status and data, or an allOf that
 * refers to Success), 204, responses without JSON content, and any response or operation marked
 * `x: ['kipchak-envelope' => false]`, for a route that answers with Http\Response::raw() or passes another body on.
 */
class Envelope
{
    public const SUCCESS = 'Success';
    public const ERROR = 'Error';

    /** The codes with Kipchak's own status names; ErrorStatus::for() names every other code. */
    public const ERROR_STATUSES = ErrorStatus::NAMES;

    private const OPT_OUT = 'kipchak-envelope';

    public function __construct(private readonly bool $errors = false)
    {
    }

    public function __invoke(Analysis $analysis): void
    {
        $openapi = $analysis->openapi;
        if ($openapi === null || Generator::isDefault($openapi->paths)) {
            return;
        }
        $usedSuccess = false;
        $usedError = false;

        foreach ($openapi->paths as $pathItem) {
            foreach (['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'] as $method) {
                $operation = $pathItem->{$method};
                if (Generator::isDefault($operation) || Generator::isDefault($operation->responses)) {
                    continue;
                }
                $skipOperation = self::optedOut($operation);
                foreach ($operation->responses as $response) {
                    // A response that refers to components/responses is documented there; nothing may sit beside a $ref.
                    $skip = $skipOperation || self::optedOut($response) || !Generator::isDefault($response->ref);
                    self::removeOptOut($response);
                    $code = is_numeric($response->response) ? (int) $response->response : null;
                    if ($skip || $code === null) {
                        continue;
                    }
                    if ($code >= 200 && $code < 300 && $code !== 204) {
                        $usedSuccess = self::wrapSuccess($response, $code) || $usedSuccess;
                    } elseif ($code >= 400 && $this->errors) {
                        $usedError = self::documentError($response, $code) || $usedError;
                    }
                }
                self::removeOptOut($operation);
            }
        }

        if ($usedSuccess) {
            self::addComponent($openapi, self::successSchema($openapi));
        }
        if ($usedError) {
            self::addComponent($openapi, self::errorSchema($openapi));
        }
    }

    private static function wrapSuccess(OA\Response $response, int $code): bool
    {
        $media = self::json($response);
        if ($media === null || Generator::isDefault($media->schema) || self::isEnveloped($media->schema)) {
            return false;
        }
        $context = new Context(['generated' => true], $media->_context);
        $data = new OA\Property(['property' => 'data', '_context' => $context]);
        foreach (get_object_vars($media->schema) as $name => $value) {
            if ($name[0] !== '_' && $name !== 'schema' && !Generator::isDefault($value)) {
                $data->{$name} = $value;
            }
        }
        $media->schema = new OA\Schema([
            'allOf' => [
                new OA\Schema(['ref' => '#/components/schemas/' . self::SUCCESS, '_context' => $context]),
                new OA\Schema([
                    'properties' => [
                        new OA\Property(['property' => 'code', 'type' => 'integer', 'enum' => [$code], '_context' => $context]),
                        new OA\Property(['property' => 'status', 'type' => 'string', 'enum' => [Codes::getCode($code)], '_context' => $context]),
                        $data,
                    ],
                    '_context' => $context,
                ]),
            ],
            '_context' => $context,
        ]);

        return true;
    }

    private static function documentError(OA\Response $response, int $code): bool
    {
        if (!Generator::isDefault($response->content) && $response->content !== []) {
            return false; // the route documents its own error body
        }
        $context = new Context(['generated' => true], $response->_context);
        $response->content = ['application/json' => new OA\MediaType([
            'mediaType' => 'application/json',
            'schema' => new OA\Schema([
                'allOf' => [
                    new OA\Schema(['ref' => '#/components/schemas/' . self::ERROR, '_context' => $context]),
                    new OA\Schema([
                        'properties' => [
                            new OA\Property(['property' => 'code', 'type' => 'integer', 'enum' => [$code], '_context' => $context]),
                            new OA\Property(['property' => 'status', 'type' => 'string', 'enum' => [ErrorStatus::for($code)], '_context' => $context]),
                        ],
                        '_context' => $context,
                    ]),
                ],
                '_context' => $context,
            ]),
            '_context' => $context,
        ])];

        return true;
    }

    private static function json(OA\Response $response): ?OA\MediaType
    {
        if (Generator::isDefault($response->content) || !is_array($response->content)) {
            return null;
        }
        foreach ($response->content as $type => $media) {
            $name = is_string($type) ? $type : $media->mediaType;
            if ($media instanceof OA\MediaType && is_string($name) && preg_match('#^application/(.+\+)?json$#', $name)) {
                return $media;
            }
        }

        return null;
    }

    /**
     * Already in the envelope: an object declaring code, status and data, or an allOf that refers to Success.
     */
    private static function isEnveloped(OA\Schema $schema): bool
    {
        if (!Generator::isDefault($schema->allOf)) {
            foreach ($schema->allOf as $part) {
                if (!Generator::isDefault($part->ref) && $part->ref === '#/components/schemas/' . self::SUCCESS) {
                    return true;
                }
            }
        }
        if (!Generator::isDefault($schema->properties)) {
            $names = array_map(static fn (OA\Property $p) => $p->property, $schema->properties);

            return in_array('data', $names, true) && in_array('code', $names, true);
        }

        return false;
    }

    private static function optedOut(OA\AbstractAnnotation $annotation): bool
    {
        return !Generator::isDefault($annotation->x) && is_array($annotation->x) && ($annotation->x[self::OPT_OUT] ?? null) === false;
    }

    private static function removeOptOut(OA\AbstractAnnotation $annotation): void
    {
        if (!Generator::isDefault($annotation->x) && is_array($annotation->x) && array_key_exists(self::OPT_OUT, $annotation->x)) {
            unset($annotation->x[self::OPT_OUT]);
            if ($annotation->x === []) {
                $annotation->x = Generator::UNDEFINED;
            }
        }
    }

    private static function addComponent(OA\OpenApi $openapi, OA\Schema $schema): void
    {
        if (Generator::isDefault($openapi->components)) {
            $openapi->components = new OA\Components(['_context' => new Context(['generated' => true], $openapi->_context)]);
        }
        if (Generator::isDefault($openapi->components->schemas)) {
            $openapi->components->schemas = [];
        }
        foreach ($openapi->components->schemas as $existing) {
            if ($existing->schema === $schema->schema) {
                return; // the API defines its own
            }
        }
        $openapi->components->schemas[] = $schema;
    }

    private static function successSchema(OA\OpenApi $openapi): OA\Schema
    {
        $context = new Context(['generated' => true], $openapi->_context);

        return new OA\Schema([
            'schema' => self::SUCCESS,
            'type' => 'object',
            'description' => "Kipchak's response envelope, as Http\\Response::json() writes it. Each operation states what data holds.",
            'required' => ['code', 'status', 'data'],
            'properties' => [
                new OA\Property(['property' => 'code', 'type' => 'integer', 'description' => 'The HTTP status.', 'example' => 200, '_context' => $context]),
                new OA\Property(['property' => 'status', 'type' => 'string', 'description' => 'The name of the HTTP status.', 'example' => 'OK', '_context' => $context]),
                new OA\Property(['property' => 'meta', 'type' => 'object', 'description' => 'Endpoint metadata, such as paging, when the operation states it.', 'example' => new \stdClass(), '_context' => $context]),
                new OA\Property(['property' => 'data', 'description' => 'The result, as each operation states it.', 'example' => new \stdClass(), '_context' => $context]),
            ],
            '_context' => $context,
        ]);
    }

    private static function errorSchema(OA\OpenApi $openapi): OA\Schema
    {
        $context = new Context(['generated' => true], $openapi->_context);

        return new OA\Schema([
            'schema' => self::ERROR,
            'type' => 'object',
            'description' => "Kipchak's response envelope for an error, as kipchak/middleware-error writes it.",
            'required' => ['code', 'status', 'data'],
            'properties' => [
                new OA\Property(['property' => 'code', 'type' => 'integer', 'description' => 'The HTTP status.', 'example' => 404, '_context' => $context]),
                new OA\Property(['property' => 'status', 'type' => 'string', 'example' => 'RESOURCE_NOT_FOUND', '_context' => $context,
                    'description' => 'The type of error: BAD_REQUEST, UNAUTHENTICATED, INSUFFICIENT_PRIVILEGES, RESOURCE_NOT_FOUND, NOT_ALLOWED, '
                        . 'TOO_MANY_REQUESTS or NOT_IMPLEMENTED; SERVER_ERROR for any other 5xx; the HTTP name, such as CONFLICT, for any other 4xx. '
                        . 'Each operation states its own.']),
                new OA\Property(['property' => 'meta', 'type' => 'object', 'description' => 'For a request body that is not valid: what is wrong with each field, under errors, by the same field names and paths as the message in data.', '_context' => $context,
                    'example' => ['errors' => ['title' => ['Must be at least 1 character.']]],
                    'properties' => [new OA\Property(['property' => 'errors', 'type' => 'object', '_context' => $context,
                        'additionalProperties' => new OA\AdditionalProperties(['type' => 'array', 'items' => new OA\Items(['type' => 'string', '_context' => $context]), '_context' => $context])])]]),
                new OA\Property(['property' => 'data', 'type' => 'string', 'description' => 'A message for the client.', 'example' => 'Not found.', '_context' => $context]),
            ],
            '_context' => $context,
        ]);
    }
}
