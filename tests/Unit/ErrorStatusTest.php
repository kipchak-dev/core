<?php

declare(strict_types=1);

namespace Kipchak\Core\Tests\Unit;

use Kipchak\Core\Components\Http\ErrorStatus;
use Kipchak\Core\Components\Http\Response;
use Kipchak\Core\Components\Psr7\Response as Psr7Response;
use Kipchak\Core\OpenApi\Envelope;
use Kipchak\Core\Tests\TestCase;

/**
 * One name per refusal: Response::error() names a code as kipchak/middleware-error does, while Response::json() keeps
 * the HTTP name from Codes for everything a route writes itself.
 */
final class ErrorStatusTest extends TestCase
{
    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function names(): array
    {
        return [
            '400' => [400, 'BAD_REQUEST'],
            '401' => [401, 'UNAUTHENTICATED'],
            '403' => [403, 'INSUFFICIENT_PRIVILEGES'],
            '404' => [404, 'RESOURCE_NOT_FOUND'],
            '405' => [405, 'NOT_ALLOWED'],
            '429' => [429, 'TOO_MANY_REQUESTS'],
            '501' => [501, 'NOT_IMPLEMENTED'],
            'another 4xx keeps its HTTP name' => [409, 'CONFLICT'],
            'another 4xx, 413' => [413, 'PAYLOAD TOO LARGE'],
            'another 5xx' => [503, 'SERVER_ERROR'],
            'a code Codes does not know' => [499, 'SERVER_ERROR'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('names')]
    public function testEachCodeHasOneName(int $code, string $status): void
    {
        $this->assertSame($status, ErrorStatus::for($code));
    }

    public function testErrorWritesTheEnvelopeWithTheErrorStatus(): void
    {
        $response = Response::error(new Psr7Response(), 'Invalid token', 401);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(['code' => 401, 'status' => 'UNAUTHENTICATED', 'data' => 'Invalid token'], json_decode((string) $response->getBody(), true));
    }

    public function testControlJsonKeepsTheHttpName(): void
    {
        // What every Kipchak middleware wrote before core 3.18, and what a route's own errors still carry.
        $body = json_decode((string) Response::json(new Psr7Response(), 'Invalid token', 401)->getBody(), true);

        $this->assertSame('UNAUTHORIZED', $body['status']);
    }

    public function testTheOpenApiEnvelopeUsesTheSameNames(): void
    {
        $this->assertSame(ErrorStatus::NAMES, Envelope::ERROR_STATUSES);
    }
}
