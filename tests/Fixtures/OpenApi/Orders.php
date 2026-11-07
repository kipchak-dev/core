<?php

declare(strict_types=1);

namespace Kipchak\Core\Tests\Fixtures\OpenApi;

use OpenApi\Attributes as OA;

/**
 * One operation for each case the Envelope processor handles. Not a working controller: only its attributes matter.
 */
#[OA\Info(version: '1.0.0', title: 'Envelope fixture')]
#[OA\Response(response: 'Shared400', description: 'A shared error, documented once', content: new OA\JsonContent(properties: [new OA\Property(property: 'code', type: 'integer'), new OA\Property(property: 'status', type: 'string'), new OA\Property(property: 'data', type: 'string')]))]
final class Orders
{
    #[OA\Get(path: '/v1/orders/{id}', operationId: 'getOrder', summary: 'One order', tags: ['Orders'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The order', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
            new OA\Response(response: 404, description: 'No such order'),
            new OA\Response(ref: '#/components/responses/Shared400', response: 400),
        ])]
    public function get(): void
    {
    }

    #[OA\Get(path: '/v1/orders', operationId: 'listOrders', summary: 'Every order', tags: ['Orders'],
        responses: [
            new OA\Response(response: 200, description: 'The orders', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Order'))),
        ])]
    public function list(): void
    {
    }

    #[OA\Post(path: '/v1/orders', operationId: 'createOrder', summary: 'Create an order', tags: ['Orders'],
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
            new OA\Response(response: 400, description: 'Not a valid order', content: new OA\JsonContent(properties: [new OA\Property(property: 'reason', type: 'string')])),
        ])]
    public function create(): void
    {
    }

    #[OA\Get(path: '/v1/legacy', operationId: 'legacy', summary: 'Already enveloped by hand', tags: ['Orders'],
        responses: [
            new OA\Response(response: 200, description: 'Enveloped inline', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'code', type: 'integer', example: 200),
                new OA\Property(property: 'status', type: 'string', example: 'OK'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
            ])),
        ])]
    public function legacy(): void
    {
    }

    #[OA\Delete(path: '/v1/orders/{id}', operationId: 'deleteOrder', summary: 'Delete an order', tags: ['Orders'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Deleted')])]
    public function delete(): void
    {
    }

    #[OA\Get(path: '/v1/raw', operationId: 'raw', summary: 'A body passed on as it is', tags: ['Orders'], x: ['kipchak-envelope' => false],
        responses: [
            new OA\Response(response: 200, description: 'Raw', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
            new OA\Response(response: 404, description: 'Not there'),
        ])]
    public function raw(): void
    {
    }

    #[OA\Get(path: '/v1/report', operationId: 'report', summary: 'Plain text', tags: ['Orders'],
        responses: [
            new OA\Response(response: 200, description: 'Text', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(type: 'string'))),
            new OA\Response(response: 409, description: 'Conflict'),
        ])]
    public function report(): void
    {
    }
}
