<?php

namespace App\Domain\Menus;

use App\Models\Restaurant;
use Carbon\CarbonImmutable;

interface MenuSource
{
    public function fetch(Restaurant $restaurant, CarbonImmutable $date): ?MenuResult;
}
