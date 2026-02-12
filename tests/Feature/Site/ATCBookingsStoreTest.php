<?php

namespace Tests\Feature\Site;

use App\Models\Atc\Booking;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ATCBookingsStoreTest extends TestCase
{
    #[Test]
    public function a_logged_in_member_can_create_an_atc_booking()
    {
        $this->withoutMiddleware();

        $user = $this->user;

        $response = $this->actingAs($user)->post(route('site.atc.bookings.store'), [
            'position' => 'EGCC_TWR',
            'date' => $this->knownDate->copy()->addDay()->toDateString(),
            'from' => '10:00',
            'to' => '13:00',
            'type' => 'BK',
        ]);

        $response->assertRedirect(route('site.atc.bookings'));

        $this->assertDatabaseHas('atc_bookings', [
            'position' => 'EGCC_TWR',
            'type' => 'BK',
            'booked_by_cid' => $user->id,
        ]);
    }

    #[Test]
    public function it_rejects_overlapping_bookings_for_the_same_position()
    {
        $this->withoutMiddleware();

        $user = $this->user;

        Booking::factory()->create([
            'date' => $this->knownDate->copy()->addDay()->toDateString(),
            'position' => 'EGCC_TWR',
            'from' => '10:00:00',
            'to' => '12:00:00',
            'type' => 'BK',
        ]);

        $response = $this->actingAs($user)->from(route('site.atc.bookings'))->post(route('site.atc.bookings.store'), [
            'position' => 'EGCC_TWR',
            'date' => $this->knownDate->copy()->addDay()->toDateString(),
            'from' => '11:00',
            'to' => '13:00',
            'type' => 'BK',
        ]);

        $response->assertRedirect(route('site.atc.bookings'));
        $response->assertSessionHasErrors('booking');
    }
}
