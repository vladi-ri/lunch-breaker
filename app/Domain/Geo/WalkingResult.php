<?php

namespace App\Domain\Geo;

readonly class WalkingResult
{
    public function __construct(
        public int $meters,
        public int $seconds,
    ) {}
}
