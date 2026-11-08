<?php

declare(strict_types=1);

namespace Kipchak\Core\Components\Dto;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;
use JsonException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Maps a request's JSON body into a DTO, enforcing its Field and Check attributes, and reports every problem at once.
 *
 * Only this mapper registers those attributes. Reading stored rows with a plain Valinor mapper does not enforce them,
 * so tightening a rule never makes an existing row unreadable.
 */
final class Input
{
    /** Holds no request data, so one instance is safe for every request a worker serves. */
    private static ?TreeMapper $mapper = null;

    /**
     * @template T of object
     * @param class-string<T> $dto
     * @param array<string, mixed> $set values the server sets, such as the id, which replace any the client sent
     * @return T
     * @throws InvalidInput
     */
    public static function from(ServerRequestInterface $request, string $dto, array $set = []): object
    {
        try {
            $body = json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidInput($request, 'The body is not valid JSON.');
        }
        if (!is_array($body)) {
            throw new InvalidInput($request, 'The body must be a JSON object.');
        }

        try {
            return self::mapper()->map($dto, Source::array($set + $body));
        } catch (MappingError $error) {
            throw self::invalid($request, $dto, $error);
        }
    }

    /**
     * @param class-string $dto
     */
    private static function invalid(ServerRequestInterface $request, string $dto, MappingError $error): InvalidInput
    {
        $errors = [];
        $sentences = [];
        foreach ($error->messages()->errors() as $message) {
            $text = $message->sourceValue() === '*missing*' ? 'This field is required.' : (string) $message;
            $errors[$message->path()][] = $text;
            $sentences[] = "{$message->path()}: {$text}";
        }

        $short = substr((string) strrchr('\\' . $dto, '\\'), 1);
        $name = strtolower((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $short));

        return new InvalidInput($request, "The {$name} is not valid: " . implode(' ', $sentences), $errors);
    }

    private static function mapper(): TreeMapper
    {
        return self::$mapper ??= (new MapperBuilder())
            ->registerConverter(Field::class)
            ->registerConverter(Check::class)
            ->mapper();
    }
}
