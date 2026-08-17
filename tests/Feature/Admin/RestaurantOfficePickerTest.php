<?php

namespace Tests\Feature\Admin;

use App\Domain\Geo\GeocodesAddresses;
use App\Domain\Geo\GeocodeResult;
use App\Jobs\RefreshWalkingDistanceJob;
use App\Models\Office;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RestaurantOfficePickerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(
            GeocodesAddresses::class,
            new class implements GeocodesAddresses {
                public function geocode(string $address) : ?GeocodeResult {
                    return new GeocodeResult(50.0, 10.0, $address);
                }
            }
        );
    }

    public function test_office_id_is_required_when_creating_a_restaurant() : void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post('/admin/restaurants', [
            'name'     => 'New Place',
            'address'  => 'Somewhere 1',
            'category' => 'restaurant'
        ])->assertSessionHasErrors('office_id');
    }

    public function test_reassigning_office_dispatches_refresh_walking_distance_job() : void
    {
        Queue::fake();

        $admin          = User::factory()->create(['is_admin' => true]);
        $originalOwner  = User::factory()->create();
        $newOwner       = User::factory()->create();
        $originalOffice = Office::factory()->for($originalOwner)->create();
        $newOffice      = Office::factory()->for($newOwner)->create();
        $restaurant     = Restaurant::factory()->for($originalOffice, 'office')->create();

        $this->actingAs($admin)->put("/admin/restaurants/{$restaurant->id}", [
            'name'      => $restaurant->name,
            'address'   => $restaurant->address,
            'category'  => $restaurant->category,
            'office_id' => $newOffice->id
        ])->assertRedirect(route('admin.restaurants.index'));

        $this->assertSame($newOffice->id, $restaurant->fresh()->office_id);

        Queue::assertPushed(RefreshWalkingDistanceJob::class);
    }
}
