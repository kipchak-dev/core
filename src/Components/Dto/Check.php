<?php

declare(strict_types=1);

namespace Kipchak\Core\Components\Dto;

use Attribute;
use CuyZ\Valinor\Mapper\Tree\Message\MessageBuilder;
use LogicException;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * A rule an attribute argument cannot state, such as one that depends on the date. Names a static method that takes the
 * value and returns what is wrong with it, or null. Like Field, it applies only through Input.
 *
 * The OpenAPI document cannot state such a rule: say what it does in the property's description.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Check
{
    /**
     * @param array{class-string, string} $method
     */
    public function __construct(private array $method) {}

    /**
     * A value that is not of the type the method takes passes through to be reported by the type check. See Field::map()
     * for why this runs before $next.
     *
     * @template T
     * @param callable(mixed): T $next
     * @return T
     */
    public function map(mixed $value, callable $next): mixed
    {
        if ($value !== null && $this->accepts($value)) {
            $problem = ($this->method)($value);
            if ($problem !== null) {
                throw MessageBuilder::newError('{problem}')->withParameter('problem', $problem)->build();
            }
        }

        return $next($value);
    }

    private function accepts(mixed $value): bool
    {
        $method = new ReflectionMethod(...$this->method);
        if (!$method->isStatic()) {
            throw new LogicException("{$method->class}::{$method->name}() must be static to be used by Check.");
        }
        $type = $method->getParameters()[0]?->getType();

        return !$type instanceof ReflectionNamedType || $type->getName() === 'mixed' || get_debug_type($value) === $type->getName();
    }
}
