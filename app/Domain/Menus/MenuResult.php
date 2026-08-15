<?php

namespace App\Domain\Menus;

readonly class MenuResult
{
    /**
     * @param  array<int, array{name: string, description: ?string, price: ?float}>  $items
     */
    public function __construct(
        public array $items,
        public ?string $rawText = null,
    ) {}
}
