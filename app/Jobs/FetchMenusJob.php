<?php

namespace App\Jobs;

use App\Domain\Menus\MenuSourceResolver;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchMenusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        protected Restaurant $restaurant,
        protected ?CarbonImmutable $date = null,
    ) {}

    public function handle(MenuSourceResolver $resolver): void
    {
        $date = $this->date ?? CarbonImmutable::today();

        $source = $resolver->resolve($this->restaurant);

        if ($source === null) {
            return;
        }

        try {
            $result = $source->fetch($this->restaurant, $date);
        } catch (\Throwable $e) {
            Log::error('Menu fetch failed', [
                'restaurant_id' => $this->restaurant->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($result === null || empty($result->items)) {
            // Leave any existing menu for this date untouched rather than overwriting with nothing.
            return;
        }

        $menu = $this->restaurant->menus()->updateOrCreate(
            ['date' => $date->toDateString()],
            [
                'source_type' => 'scraped',
                'fetched_at' => now(),
                'raw_text' => $result->rawText,
            ],
        );

        $menu->items()->delete();

        foreach ($result->items as $index => $item) {
            $menu->items()->create([
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'price' => $item['price'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }
}
