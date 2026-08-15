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

/**
 * FetchMenusJob is responsible for fetching menus for a specific restaurant and date.
 * 
 * @implements ShouldQueue
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class FetchMenusJob implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * The number of times the job may be attempted.
     * 
     * @var    int
     * @access public
     */
    public int $tries = 2;

    /**
     * Create a new job instance.
     * 
     * @param Restaurant          $restaurant The restaurant for which to fetch menus.
     * @param CarbonImmutable|null $date       The date for which to fetch the menu. Defaults to today if not provided.
     * 
     * @access public
     * @return void
     */
    public function __construct(
        protected Restaurant $restaurant,
        protected ?CarbonImmutable $date = null
    ) {}

    /**
     * Execute the job.
     * 
     * @param MenuSourceResolver $resolver Object that can resolve the appropriate menu source for a restaurant.
     * 
     * @access public
     * @return void
     */
    public function handle(MenuSourceResolver $resolver) : void {
        $date   = $this->date ?? CarbonImmutable::today();
        $source = $resolver->resolve($this->restaurant);

        // If no source is found for the restaurant, we cannot fetch the menu, so we exit early.
        if ($source === null) {
            return;
        }

        try {
            $result = $source->fetch($this->restaurant, $date);
        } catch (\Throwable $e) {
            Log::error(
                'Menu fetch failed', [
                    'restaurant_id' => $this->restaurant->id,
                    'error'         => $e->getMessage()
                ]
            );

            return;
        }

        if ($result === null || (empty($result->items) && empty($result->rawText))) {
            // Leave any existing menu for this date untouched rather than overwriting with nothing.
            return;
        }

        $menu   = $this->restaurant->menus()->updateOrCreate(
            ['date' => $date->toDateString()],
            [
                'source_type' => 'scraped',
                'fetched_at'  => now(),
                'raw_text'    => $result->rawText
            ],
        );

        $menu->items()->delete();

        foreach ($result->items as $index => $item) {
            $menu->items()->create([
                'name'        => $item['name'],
                'description' => $item['description'] ?? null,
                'price'       => $item['price'] ?? null,
                'sort_order'  => $index
            ]);
        }
    }
}
