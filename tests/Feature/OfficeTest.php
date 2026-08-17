<?php

namespace Tests\Feature;

use App\Domain\Geo\GeocodesAddresses;
use App\Domain\Geo\GeocodeResult;
use App\Jobs\DiscoverRestaurantsJob;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OfficeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(
            GeocodesAddresses::class,
            new class implements GeocodesAddresses {
                public function geocode(string $address): ?GeocodeResult
                {
                    return new GeocodeResult(50.0, 10.0, $address);
                }
            }
        );
    }

    public function test_first_office_auto_activates(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post('/settings/offices', [
            'name'                => 'Home Office',
            'address'             => 'Musterstraße 1',
            'distance_unit'       => 'meters',
        ])->assertRedirect();

        $office = Office::first();

        $this->assertSame($user->id, $office->user_id);
        $this->assertSame($office->id, $user->fresh()->office_id);

        Queue::assertPushed(DiscoverRestaurantsJob::class);
    }

    public function test_second_office_does_not_steal_activation(): void
    {
        Queue::fake();

        $user  = User::factory()->create();
        $first = Office::factory()->for($user)->create();
        $user->update(['office_id' => $first->id]);

        $this->actingAs($user)->post('/settings/offices', [
            'name'                => 'Second Office',
            'address'             => 'Musterstraße 2',
            'distance_unit'       => 'meters',
        ])->assertRedirect();

        $this->assertSame($first->id, $user->fresh()->office_id);
    }

    public function test_activate_switches_active_office(): void
    {
        $user   = User::factory()->create();
        $first  = Office::factory()->for($user)->create();
        $second = Office::factory()->for($user)->create();
        $user->update(['office_id' => $first->id]);

        $this->actingAs($user)
            ->post("/settings/offices/{$second->id}/activate")
            ->assertRedirect(route('offices.index'));

        $this->assertSame($second->id, $user->fresh()->office_id);
    }

    public function test_user_cannot_manage_another_users_office(): void
    {
        Queue::fake();

        $owner        = User::factory()->create();
        $office       = Office::factory()->for($owner)->create();
        $intruder     = User::factory()->create();

        $this->actingAs($intruder)->get("/settings/offices/{$office->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->put("/settings/offices/{$office->id}", [
            'name' => 'Hijacked', 'address' => 'Nope', 'distance_unit' => 'meters',
        ])->assertForbidden();
        $this->actingAs($intruder)->post("/settings/offices/{$office->id}/activate")->assertForbidden();
        $this->actingAs($intruder)->post("/settings/offices/{$office->id}/rediscover")->assertForbidden();
        $this->actingAs($intruder)->delete("/settings/offices/{$office->id}")->assertForbidden();
    }

    public function test_destroy_active_office_falls_back_to_another_owned_office(): void
    {
        $user     = User::factory()->create();
        $active   = Office::factory()->for($user)->create();
        $fallback = Office::factory()->for($user)->create();
        $user->update(['office_id' => $active->id]);

        $this->actingAs($user)->delete("/settings/offices/{$active->id}")->assertRedirect(route('offices.index'));

        $this->assertSame($fallback->id, $user->fresh()->office_id);
        $this->assertNull(Office::find($active->id));
    }

    public function test_dashboard_redirects_to_create_when_user_has_no_offices(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('offices.create'));
    }

    public function test_dashboard_redirects_to_index_when_user_owns_offices_but_none_active(): void
    {
        $user = User::factory()->create();
        Office::factory()->for($user)->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('offices.index'));
    }

    public function test_reverting_address_still_triggers_discovery(): void
    {
        Queue::fake();

        $user   = User::factory()->create();
        $office = Office::factory()->for($user)->create(['address' => 'Original Address']);
        $user->update(['office_id' => $office->id]);

        $this->actingAs($user)->put("/settings/offices/{$office->id}", [
            'name' => $office->name, 'address' => 'A Different Address', 'distance_unit' => 'meters',
        ]);

        $this->actingAs($user)->put("/settings/offices/{$office->id}", [
            'name' => $office->name, 'address' => 'Original Address', 'distance_unit' => 'meters',
        ]);

        Queue::assertPushed(DiscoverRestaurantsJob::class, 2);
    }
}
