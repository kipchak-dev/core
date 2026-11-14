<?php

namespace Kipchak\Core\Tests\Unit;

use Kipchak\Core\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function Kipchak\Core\env;

require_once __DIR__ . '/../../src/Functions.php';

class EnvTest extends TestCase
{
    private const string NAME = 'KIPCHAK_CORE_ENV_TEST';

    protected function tearDown(): void
    {
        putenv(self::NAME);
    }

    public function testUnsetGivesTheDefault(): void
    {
        putenv(self::NAME);
        $this->assertSame('fallback', env(self::NAME, 'fallback'));
    }

    public function testEmptyGivesTheDefault(): void
    {
        putenv(self::NAME . '=');
        $this->assertSame('fallback', env(self::NAME, 'fallback'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function values(): array
    {
        return [
            'zero' => ['0'],
            'false' => ['false'],
            'a word' => ['valkey'],
            'a number' => ['6379'],
        ];
    }

    #[DataProvider('values')]
    public function testASetValueIsReturned(string $value): void
    {
        putenv(self::NAME . '=' . $value);
        $this->assertSame($value, env(self::NAME, '1'));
    }
}
