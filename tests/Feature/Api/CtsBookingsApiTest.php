<?php

namespace Tests\Feature\Api;

use App\Models\Cts\Booking;
use App\Models\Cts\Member;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CtsBookingsApiTest extends TestCase
{
    #[Test]
    public function it_excludes_member_information_and_returns_public_booked_by_for_standard_bookings()
    {
        $member = Member::factory()->create([
            'cid' => 1398426,
            'name' => 'Reece Brown',
        ]);

        Booking::factory()->create([
            'date' => $this->knownDate->toDateString(),
            'from' => '10:00',
            'to' => '13:00',
            'position' => 'EGCC_TWR',
            'member_id' => $member->id,
            'type' => 'BK',
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
        $member = Member::factory()->create([
            'cid' => 1398426,
            'name' => 'Reece Brown',
        ]);

        Booking::factory()->create([
            'date' => $this->knownDate->toDateString(),
            'from' => '10:00',
            'to' => '13:00',
            'position' => 'EGCC_TWR',
            'member_id' => $member->id,
            'type' => 'ME',
        ]);

        $response = $this->getJson(route('api.cts.bookings', ['date' => $this->knownDate->toDateString()]));

        $response->assertOk()
            ->assertJsonPath('bookings.0.booked_by', 'Hidden')
            ->assertJsonMissingPath('bookings.0.member');
    }
}
