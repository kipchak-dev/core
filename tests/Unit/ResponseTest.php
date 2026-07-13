<?php

namespace Kipchak\Core\Tests\Unit;

use Kipchak\Core\Components\Http\Response;
use Kipchak\Core\Components\Psr7\Response as Psr7Response;
use Kipchak\Core\Tests\TestCase;

class ResponseTest extends TestCase
{
    public function testBuildEnvelope()
    {
        $envelope = Response::build(['x' => 1], 200);
        $this->assertSame(['code', 'status', 'data'], array_keys($envelope));
        $this->assertEquals(200, $envelope['code']);
        $this->assertEquals('OK', $envelope['status']);
        $this->assertEquals(['x' => 1], $envelope['data']);
    }

    public function testBuildEnvelopeWithMetaBetweenStatusAndData()
    {
        $envelope = Response::build(['x' => 1], 200, ['page' => 1, 'total' => 9]);
        $this->assertSame(['code', 'status', 'meta', 'data'], array_keys($envelope));
        $this->assertEquals(['page' => 1, 'total' => 9], $envelope['meta']);
    }

    public function testJsonWithMeta()
    {
        $response = Response::json(new Psr7Response(), ['x' => 1], 200, meta: ['page' => 2]);
        $decoded = json_decode((string) $response->getBody(), true);
        $this->assertSame(['code', 'status', 'meta', 'data'], array_keys($decoded));
        $this->assertEquals(['page' => 2], $decoded['meta']);
    }

    public function testJsonDoesNotEscapeUnicodeOrSlashes()
    {
        $response = Response::json(new Psr7Response(), ['name' => 'صلاة', 'url' => 'https://kipchak.dev/x'], 200);
        $body = (string) $response->getBody();
        $this->assertStringContainsString('صلاة', $body);
        $this->assertStringContainsString('https://kipchak.dev/x', $body);
        $this->assertStringNotContainsString('\\u0635', $body);
        $this->assertStringNotContainsString('\/', $body);
    }

    public function testJsonDefaultHeadersUnchanged()
    {
        $response = Response::json(new Psr7Response(), 'NOT FOUND', 404);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('no-cache, no-store, must-revalidate', $response->getHeaderLine('Cache-Control'));
        $this->assertEquals('Kipchak by Mamluk', $response->getHeaderLine('X-Powered-By'));
    }

    public function testJsonCacheHeaders()
    {
        $response = Response::json(new Psr7Response(), ['x' => 1], 200, true, 900, ['public']);
        $this->assertEquals('public,max-age=900', $response->getHeaderLine('Cache-Control'));
        $this->assertNotEmpty($response->getHeaderLine('ETag'));
    }
}
