<?php

declare(strict_types=1);

namespace Kipchak\Core\Tests\Fixtures\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Order')]
final readonly class Order
{
    public function __construct(
        #[OA\Property(description: 'The order ID.', example: 7)]
        public int $id,
    ) {
    }
}
