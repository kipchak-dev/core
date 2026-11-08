<?php

declare(strict_types=1);

namespace Kipchak\Core\Tests\Unit\Dto;

use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\MapperBuilder;
use Kipchak\Core\Components\Dto\Input;
use Kipchak\Core\Components\Dto\InvalidInput;
use Kipchak\Core\OpenApi\Envelope;
use Kipchak\Core\Tests\Fixtures\Dto\Volume;
use OpenApi\Generator;
use OpenApi\Utils\Pipeline;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

final class InputTest extends TestCase
{
    private function request(string $body): \Psr\Http\Message\ServerRequestInterface
    {
        return new ServerRequestFactory()->createServerRequest('POST', '/')
            ->withBody(new StreamFactory()->createStream($body));
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, list<string>>
     */
    private function errors(array $body): array
    {
        try {
            Input::from($this->request(json_encode($body, JSON_THROW_ON_ERROR)), Volume::class, ['id' => 0]);
        } catch (InvalidInput $invalid) {
            return $invalid->errors;
        }

        return [];
    }

    public function testEveryFieldIsReportedAtOnceWithRulesAndTypesTogether(): void
    {
        $errors = $this->errors(['title' => '', 'year' => 1449, 'isbn' => 5]);

        self::assertSame(['Must be at least 1 character.'], $errors['title']);
        self::assertSame(['This field is required.'], $errors['author']);
        self::assertSame(['Must be at least 1450.'], $errors['year']);
        self::assertArrayHasKey('isbn', $errors);
    }

    public function testTheMessageNamesTheSameFieldsAsTheErrors(): void
    {
        try {
            Input::from($this->request('{"title":""}'), Volume::class, ['id' => 0]);
            self::fail('The body is not valid.');
        } catch (InvalidInput $invalid) {
            self::assertSame(
                'The volume is not valid: title: Must be at least 1 character. author: This field is required.',
                $invalid->getMessage(),
            );
            self::assertSame(400, $invalid->getCode());
            self::assertSame(['title', 'author'], array_keys($invalid->errors));
        }
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function brokenRules(): array
    {
        $book = ['title' => 'T', 'author' => 'A'];

        return [
            'a long title' => [['title' => str_repeat('a', 201), 'author' => 'A'], 'title', 'Must be at most 200 characters.'],
            'a year after this one' => [$book + ['year' => (int) date('Y') + 1], 'year', 'Must not be in the future.'],
            'a pattern not matched' => [$book + ['isbn' => 'abc'], 'isbn', 'Must match the pattern ^[0-9-]{10,17}$.'],
            'a value not in the enum' => [$book + ['binding' => 'scroll'], 'binding', 'Must be one of: hardback, paperback.'],
        ];
    }

    /**
     * @param array<string, mixed> $body
     */
    #[DataProvider('brokenRules')]
    public function testEachRuleIsEnforced(array $body, string $field, string $message): void
    {
        self::assertSame([$field => [$message]], $this->errors($body));
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function validBodies(): array
    {
        $book = ['title' => 'T', 'author' => 'A'];

        return [
            'the fewest fields' => [$book],
            'a null year' => [$book + ['year' => null]],
            'the first year' => [$book + ['year' => 1450]],
            'this year' => [$book + ['year' => (int) date('Y')]],
            '200 characters of two bytes' => [['title' => str_repeat('é', 200), 'author' => 'A']],
            'a matching pattern' => [$book + ['isbn' => '978-0-14-143954-8']],
            'a value in the enum' => [$book + ['binding' => 'paperback']],
            'a null enum' => [$book + ['binding' => null]],
        ];
    }

    /**
     * @param array<string, mixed> $body
     */
    #[DataProvider('validBodies')]
    public function testControlAValidBodyIsMapped(array $body): void
    {
        self::assertSame([], $this->errors($body));
    }

    public function testNestedAndListPathsAreReported(): void
    {
        $errors = $this->errors(['title' => 'T', 'author' => 'A', 'notes' => [['text' => 'fine'], ['text' => 'x']]]);

        self::assertSame(['notes.1.text' => ['Must be at least 2 characters.']], $errors);
    }

    public function testTheServerSetsWhatTheClientMayNot(): void
    {
        $volume = Input::from($this->request('{"id":500,"title":"T","author":"A"}'), Volume::class, ['id' => 7]);

        self::assertSame(7, $volume->id);
    }

    public function testABodyThatIsNotJsonIsInvalidInput(): void
    {
        $this->expectException(InvalidInput::class);
        $this->expectExceptionMessage('The body is not valid JSON.');

        Input::from($this->request('{not json'), Volume::class);
    }

    public function testABodyThatIsNotAnObjectIsInvalidInput(): void
    {
        $this->expectExceptionMessage('The body must be a JSON object.');

        Input::from($this->request('5'), Volume::class);
    }

    /**
     * Tightening a rule must not make an existing row unreadable.
     */
    public function testAnOldRowThatBreaksARuleIsReadableAndRefusedAsInput(): void
    {
        $row = ['id' => 1, 'title' => str_repeat('a', 300), 'author' => 'A', 'year' => 1000, 'isbn' => 'abc', 'binding' => 'scroll'];

        $read = (new MapperBuilder())->mapper()->map(Volume::class, Source::array($row));
        self::assertSame(1000, $read->year);
        self::assertSame('scroll', $read->binding);

        $errors = $this->errors($row);
        self::assertSame(['title', 'year', 'isbn', 'binding'], array_keys($errors));
    }

    public function testControlAnOpenApiPropertyIsNotEnforced(): void
    {
        self::assertSame([], $this->errors(['title' => 'T', 'author' => 'A', 'documentedOnly' => 'longer than three']));
    }

    public function testControlCheckIsNotCalledWithAValueOfAnotherType(): void
    {
        $errors = $this->errors(['title' => 'T', 'author' => 'A', 'year' => 'soon']);

        self::assertArrayHasKey('year', $errors);
        self::assertStringNotContainsString('future', $errors['year'][0]);
    }

    public function testTheRulesAppearInTheDocumentAsTheyAreWritten(): void
    {
        $document = json_decode(
            new Generator(new NullLogger())
                ->withProcessorPipeline(fn (Pipeline $pipeline) => $pipeline->add(new Envelope(errors: true)))
                ->generate([dirname(__DIR__, 2) . '/Fixtures/Dto/Volume.php', dirname(__DIR__, 2) . '/Fixtures/Dto/VolumeNote.php'], validate: false)
                ->toJson(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $properties = $document['components']['schemas']['Volume']['properties'];

        self::assertSame(1, $properties['title']['minLength']);
        self::assertSame(200, $properties['title']['maxLength']);
        self::assertSame('Middlemarch', $properties['title']['example']);
        self::assertSame(1450, $properties['year']['minimum']);
        self::assertTrue($properties['year']['nullable']);
        self::assertSame('^[0-9-]{10,17}$', $properties['isbn']['pattern']);
        self::assertSame(['hardback', 'paperback'], $properties['binding']['enum']);
        self::assertSame('integer', $properties['year']['type']);
        self::assertArrayNotHasKey('method', $properties['year']);
    }
}
