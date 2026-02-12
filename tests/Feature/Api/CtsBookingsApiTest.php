<?php

namespace Tests\Feature\Api;

use App\Models\Atc\Booking;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CtsBookingsApiTest extends TestCase
{
    #[Test]
    public function it_returns_public_booked_by_for_standard_bookings_from_core_storage()
    {
        Booking::factory()->create([
            'date' => $this->knownDate->toDateString(),
            'from' => '10:00:00',
            'to' => '13:00:00',
            'position' => 'EGCC_TWR',
            'type' => 'BK',
            'booked_by_cid' => 1398426,
            'booked_by_name' => 'Reece Brown',
        ]);

        $response = $this->getJson(route('api.cts.bookings', ['date' => $this->knownDate->toDateString()]));

        $response->assertOk()
            ->assertJsonPath('bookings.0.position', 'EGCC_TWR')
            ->assertJsonPath('bookings.0.booked_by', 'Reece B. 1398426')
            ->assertJsonMissingPath('bookings.0.member');
    }

    #[Test]
    public function it_returns_hidden_booked_by_for_non_standard_bookings()
    {
        Booking::factory()->create([
            'date' => $this->knownDate->toDateString(),
            'from' => '10:00:00',
            'to' => '13:00:00',
            'position' => 'EGCC_TWR',
            'type' => 'ME',
            'booked_by_cid' => 1398426,
            'booked_by_name' => 'Reece Brown',
        ]);

        $response = $this->getJson(route('api.cts.bookings', ['date' => $this->knownDate->toDateString()]));

        $response->assertOk()
            ->assertJsonPath('bookings.0.booked_by', 'Hidden')
            ->assertJsonMissingPath('bookings.0.member');
    }
}
