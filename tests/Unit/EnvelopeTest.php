<?php

declare(strict_types=1);

namespace Kipchak\Core\Tests\Unit;

use Kipchak\Core\OpenApi\Envelope;
use OpenApi\Generator;
use OpenApi\Utils\Pipeline;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

/**
 * The OpenAPI document of tests/Fixtures/OpenApi, generated as an API generates its own.
 */
final class EnvelopeTest extends TestCase
{
    /** @var list<string> */
    private array $warnings = [];

    private function document(?Envelope $envelope): array
    {
        $test = $this;
        $logger = new class ($test) extends AbstractLogger {
            public function __construct(private EnvelopeTest $test)
            {
            }

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                if (in_array($level, ['warning', 'error', 'critical', 'alert', 'emergency'], true)) {
                    $this->test->warn((string) $message);
                }
            }
        };
        $generator = new Generator($logger);
        if ($envelope !== null) {
            $generator = $generator->withProcessorPipeline(fn (Pipeline $pipeline) => $pipeline->add($envelope));
        }

        return json_decode($generator->generate([dirname(__DIR__) . '/Fixtures/OpenApi'])->toJson(), true);
    }

    public function warn(string $message): void
    {
        $this->warnings[] = $message;
    }

    private static function schema(array $document, string $path, string $method, string $code): ?array
    {
        return $document['paths'][$path][$method]['responses'][$code]['content']['application/json']['schema'] ?? null;
    }

    private static function enveloped(int $code, string $status, array $data): array
    {
        return ['allOf' => [
            ['$ref' => '#/components/schemas/Success'],
            ['properties' => [
                'code' => ['type' => 'integer', 'enum' => [$code]],
                'status' => ['type' => 'string', 'enum' => [$status]],
                'data' => $data,
            ]],
        ]];
    }

    public function testJsonSuccessesAreDocumentedInTheEnvelope(): void
    {
        $document = $this->document(new Envelope());

        $this->assertSame(self::enveloped(200, 'OK', ['$ref' => '#/components/schemas/Order']), self::schema($document, '/v1/orders/{id}', 'get', '200'));
        $this->assertSame(self::enveloped(200, 'OK', ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Order']]), self::schema($document, '/v1/orders', 'get', '200'));
        $this->assertSame(self::enveloped(201, 'CREATED', ['$ref' => '#/components/schemas/Order']), self::schema($document, '/v1/orders', 'post', '201'));

        $success = $document['components']['schemas']['Success'];
        $this->assertSame(['code', 'status', 'data'], $success['required']);
        $this->assertSame(['code', 'status', 'meta', 'data'], array_keys($success['properties']));
        $this->assertSame([], $this->warnings);
    }

    public function testWhatIsNotADataOnlySuccessIsLeftAsItIs(): void
    {
        $with = $this->document(new Envelope());
        $without = $this->document(null);

        foreach ([['/v1/legacy', 'get', '200'], ['/v1/raw', 'get', '200'], ['/v1/orders', 'post', '400']] as [$path, $method, $code]) {
            $this->assertSame(self::schema($without, $path, $method, $code), self::schema($with, $path, $method, $code), "{$method} {$path} {$code}");
        }
        $this->assertSame($without['paths']['/v1/orders/{id}']['delete'], $with['paths']['/v1/orders/{id}']['delete'], '204 has no body');
        $this->assertSame($without['paths']['/v1/report']['get']['responses']['200'], $with['paths']['/v1/report']['get']['responses']['200'], 'Not JSON');
        $this->assertArrayNotHasKey('x-kipchak-envelope', $with['paths']['/v1/raw']['get'], 'The opt-out marker is not left in the document');
    }

    public function testErrorsAreDocumentedOnlyWhenAsked(): void
    {
        $this->assertNull(self::schema($this->document(new Envelope()), '/v1/orders/{id}', 'get', '404'));

        $document = $this->document(new Envelope(errors: true));
        $error = fn (int $code, string $status) => ['allOf' => [
            ['$ref' => '#/components/schemas/Error'],
            ['properties' => ['code' => ['type' => 'integer', 'enum' => [$code]], 'status' => ['type' => 'string', 'enum' => [$status]]]],
        ]];
        $this->assertSame($error(404, 'RESOURCE_NOT_FOUND'), self::schema($document, '/v1/orders/{id}', 'get', '404'));
        $this->assertSame($error(409, 'SERVER_ERROR'), self::schema($document, '/v1/report', 'get', '409'), 'middleware-error names any other code SERVER_ERROR');
        $this->assertSame(['reason' => ['type' => 'string']], self::schema($document, '/v1/orders', 'post', '400')['properties'], 'A documented error body is kept');
        $this->assertNull(self::schema($document, '/v1/raw', 'get', '404'), 'The opt-out covers errors too');
        $this->assertSame(['$ref' => '#/components/responses/Shared400'], $document['paths']['/v1/orders/{id}']['get']['responses']['400'], 'A response that is a $ref stays a bare $ref');
        $this->assertContains('RESOURCE_NOT_FOUND', $document['components']['schemas']['Error']['properties']['status']['enum']);
    }

    public function testWithoutTheProcessorTheDocumentStatesTheBareData(): void
    {
        // Control: the misstatement the processor corrects.
        $document = $this->document(null);

        $this->assertSame(['$ref' => '#/components/schemas/Order'], self::schema($document, '/v1/orders/{id}', 'get', '200'));
        $this->assertArrayNotHasKey('Success', $document['components']['schemas']);
    }

    public function testAnApisOwnSuccessSchemaIsKept(): void
    {
        $document = $this->document(new Envelope());

        $this->assertCount(1, array_filter(array_keys($document['components']['schemas']), fn ($name) => $name === 'Success'));
    }
}
