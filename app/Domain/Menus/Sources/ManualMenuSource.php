<?php

namespace App\Domain\Menus\Sources;

use App\Domain\Menus\MenuResult;
use App\Domain\Menus\MenuSource;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;

class ManualMenuSource implements MenuSource
{
    public function fetch(Restaurant $restaurant, CarbonImmutable $date): ?MenuResult
    {
        return null;
    }
}
