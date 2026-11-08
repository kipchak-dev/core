<?php

declare(strict_types=1);

namespace Kipchak\Core\Tests\Fixtures\Dto;

use Kipchak\Core\Components\Dto\Check;
use Kipchak\Core\Components\Dto\Field;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Volume', required: ['title', 'author'])]
final readonly class Volume
{
    /**
     * @param list<VolumeNote> $notes
     */
    public function __construct(
        #[OA\Property(readOnly: true)]
        public int $id,

        #[Field(maxLength: 200, minLength: 1, example: 'Middlemarch')]
        public string $title,

        #[Field(maxLength: 100, minLength: 1)]
        public string $author,

        #[Field(description: 'From 1450 to the current year.', minimum: 1450, nullable: true), Check([self::class, 'notInTheFuture'])]
        public ?int $year = null,

        #[Field(pattern: '^[0-9-]{10,17}$', nullable: true)]
        public ?string $isbn = null,

        #[Field(enum: ['hardback', 'paperback'], nullable: true)]
        public ?string $binding = null,

        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/VolumeNote'))]
        public array $notes = [],

        #[OA\Property(maxLength: 3, nullable: true)]
        public ?string $documentedOnly = null,
    ) {}

    public static function notInTheFuture(int $year): ?string
    {
        return $year > (int) date('Y') ? 'Must not be in the future.' : null;
    }
}
