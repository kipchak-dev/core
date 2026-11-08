<?php

declare(strict_types=1);

namespace Kipchak\Core\Tests\Fixtures\Dto;

use Kipchak\Core\Components\Dto\Field;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'VolumeNote')]
final readonly class VolumeNote
{
    public function __construct(
        #[Field(minLength: 2)]
        public string $text,
    ) {}
}
