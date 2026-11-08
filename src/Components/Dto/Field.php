<?php

declare(strict_types=1);

namespace Kipchak\Core\Components\Dto;

use Attribute;
use CuyZ\Valinor\Mapper\Tree\Message\MessageBuilder;
use LogicException;
use OpenApi\Attributes as OA;
use OpenApi\Generator;

/**
 * A DTO property whose rules are written once. It takes everything OA\Property takes, so its minLength, maxLength,
 * minimum, maximum, pattern and enum describe the property in the OpenAPI document, and Input enforces those same
 * values on a request body.
 *
 * It applies only through Input. A plain Valinor mapper ignores it, so a stored row that breaks a rule added later is
 * still readable.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class Field extends OA\Property
{
    /**
     * The rules run before $next because, on a nullable property, what $next runs is each branch of the union, and
     * Valinor replaces a message raised there with its own. A value of the wrong type passes through to be reported by
     * the type check.
     *
     * @template T
     * @param callable(mixed): T $next
     * @return T
     */
    public function map(mixed $value, callable $next): mixed
    {
        $problem = $this->problem($value);
        if ($problem !== null) {
            throw MessageBuilder::newError('{problem}')->withParameter('problem', $problem)->build();
        }

        return $next($value);
    }

    private function problem(mixed $value): ?string
    {
        if (is_array($this->enum) && is_scalar($value) && !in_array($value, $this->enum, true)) {
            return 'Must be one of: ' . implode(', ', $this->enum) . '.';
        }
        if (is_string($value)) {
            return $this->stringProblem($value);
        }
        if (is_int($value) || is_float($value)) {
            return $this->numberProblem($value);
        }

        return null;
    }

    private function stringProblem(string $value): ?string
    {
        $length = mb_strlen($value);
        if (!Generator::isDefault($this->minLength) && $length < $this->minLength) {
            return "Must be at least {$this->minLength} " . self::characters($this->minLength) . '.';
        }
        if (!Generator::isDefault($this->maxLength) && $length > $this->maxLength) {
            return "Must be at most {$this->maxLength} " . self::characters($this->maxLength) . '.';
        }
        if (!Generator::isDefault($this->pattern)) {
            // OpenAPI writes a pattern with no delimiters.
            $matched = @preg_match('/' . str_replace('/', '\/', $this->pattern) . '/u', $value);
            if ($matched === false) {
                throw new LogicException("The pattern {$this->pattern} is not a valid regular expression.");
            }
            if ($matched === 0) {
                return "Must match the pattern {$this->pattern}.";
            }
        }

        return null;
    }

    private function numberProblem(int|float $value): ?string
    {
        if (!Generator::isDefault($this->minimum) && $value < $this->minimum) {
            return "Must be at least {$this->minimum}.";
        }
        if (!Generator::isDefault($this->maximum) && $value > $this->maximum) {
            return "Must be at most {$this->maximum}.";
        }

        return null;
    }

    private static function characters(int $count): string
    {
        return $count === 1 ? 'character' : 'characters';
    }
}
