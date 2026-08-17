<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RsvpOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_rsvping_to_a_restaurant_outside_the_active_office_is_forbidden(): void
    {
        $owner       = User::factory()->create();
        $office      = Office::factory()->for($owner)->create();
        $restaurant  = Restaurant::factory()->for($office, 'office')->create();

        $otherUser   = User::factory()->create();
        $otherOffice = Office::factory()->for($otherUser)->create();
        $otherUser->update(['office_id' => $otherOffice->id]);

        $this->actingAs($otherUser)->post('/rsvps', [
            'restaurant_id' => $restaurant->id,
            'date'          => now()->toDateString(),
        ])->assertForbidden();
    }

    public function test_removing_an_rsvp_outside_the_active_office_is_forbidden(): void
    {
        $owner      = User::factory()->create();
        $office     = Office::factory()->for($owner)->create();
        $restaurant = Restaurant::factory()->for($office, 'office')->create();

        $otherUser   = User::factory()->create();
        $otherOffice = Office::factory()->for($otherUser)->create();
        $otherUser->update(['office_id' => $otherOffice->id]);

        $this->actingAs($otherUser)->delete("/rsvps/{$restaurant->id}", [
            'date' => now()->toDateString(),
        ])->assertForbidden();
    }
}
