<?php

namespace Tests\Feature\Site;

use App\Models\Mship\Account;
use App\Models\Training\SessionBookingSlot;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_page_lists_slots(): void
    {
        SessionBookingSlot::create([
            'session_type' => SessionBookingSlot::TYPE_EXAM,
            'title' => 'EGLL S2 Exam',
            'scheduled_for' => Carbon::create(2026, 2, 16, 19, 0, 0),
            'duration_minutes' => 120,
        ]);

        $response = $this->get(route('site.atc.bookings.calendar', ['month' => '2026-02']));

        $response->assertOk();
        $response->assertSee('EGLL S2 Exam');
    }

    public function test_exam_can_be_picked_up_by_examiner(): void
    {
        $slot = SessionBookingSlot::create([
            'session_type' => SessionBookingSlot::TYPE_EXAM,
            'title' => 'Manchester Exam',
            'scheduled_for' => now()->addDay(),
            'duration_minutes' => 90,
        ]);

        $response = $this->post(route('site.atc.bookings.pickup', $slot), [
            'picked_up_by_name' => 'Alex Examiner',
            'picked_up_by_email' => 'alex@example.com',
            'picked_up_role' => 'examiner',
            'picked_up_by_cid' => '1669680',
        ]);

        $response->assertRedirect();
        $this->assertNotNull($slot->fresh()->picked_up_at);
        $this->assertSame('Alex Examiner', $slot->fresh()->picked_up_by_name);
    }

    public function test_exam_cannot_be_picked_up_by_mentor(): void
    {
        $slot = SessionBookingSlot::create([
            'session_type' => SessionBookingSlot::TYPE_EXAM,
            'title' => 'Bristol Exam',
            'scheduled_for' => now()->addDay(),
            'duration_minutes' => 90,
        ]);

        $response = $this->from(route('site.atc.bookings.calendar'))->post(route('site.atc.bookings.pickup', $slot), [
            'picked_up_by_name' => 'Mia Mentor',
            'picked_up_by_email' => 'mia@example.com',
            'picked_up_role' => 'mentor',
            'picked_up_by_cid' => '1669679',
        ]);

        $response->assertRedirect(route('site.atc.bookings.calendar'));
        $response->assertSessionHasErrors('pickup');
        $this->assertNull($slot->fresh()->picked_up_at);
    }

    public function test_open_slot_can_be_picked_up_by_mentor_or_examiner(): void
    {
        $slot = SessionBookingSlot::create([
            'session_type' => SessionBookingSlot::TYPE_OPEN_SLOT,
            'title' => 'Open Evening Slot',
            'scheduled_for' => now()->addDay(),
            'duration_minutes' => 60,
        ]);

        $mentorResponse = $this->post(route('site.atc.bookings.pickup', $slot), [
            'picked_up_by_name' => 'Taylor Mentor',
            'picked_up_by_email' => 'taylor@example.com',
            'picked_up_role' => 'mentor',
            'picked_up_by_cid' => '1669679',
        ]);

        $mentorResponse->assertRedirect();
        $this->assertSame('mentor', $slot->fresh()->picked_up_role);

        $secondSlot = SessionBookingSlot::create([
            'session_type' => SessionBookingSlot::TYPE_OPEN_SLOT,
            'title' => 'Open Backup Slot',
            'scheduled_for' => now()->addDays(2),
            'duration_minutes' => 60,
        ]);

        $examinerResponse = $this->post(route('site.atc.bookings.pickup', $secondSlot), [
            'picked_up_by_name' => 'Jordan Examiner',
            'picked_up_by_email' => 'jordan@example.com',
            'picked_up_role' => 'examiner',
            'picked_up_by_cid' => '1669680',
        ]);

        $examinerResponse->assertRedirect();
        $this->assertSame('examiner', $secondSlot->fresh()->picked_up_role);
    }

    public function test_exam_and_mentor_slots_render_booked_by_hidden(): void
    {
        SessionBookingSlot::create([
            'session_type' => SessionBookingSlot::TYPE_EXAM,
            'title' => 'Hidden Exam Slot',
            'scheduled_for' => Carbon::create(2026, 2, 14, 10, 30, 0),
            'duration_minutes' => 90,
            'picked_up_by_name' => 'Tristan Shaw',
            'picked_up_by_cid' => 1669679,
            'picked_up_role' => 'examiner',
            'picked_up_at' => now(),
        ]);

        SessionBookingSlot::create([
            'session_type' => SessionBookingSlot::TYPE_MENTOR_SESSION,
            'title' => 'Hidden Mentor Slot',
            'scheduled_for' => Carbon::create(2026, 2, 14, 13, 0, 0),
            'duration_minutes' => 90,
            'picked_up_by_name' => 'Mentor Person',
            'picked_up_by_cid' => 1234567,
            'picked_up_role' => 'mentor',
            'picked_up_at' => now(),
        ]);

        $response = $this->get(route('site.atc.bookings.calendar', ['month' => '2026-02']));

        $response->assertOk();
        $response->assertSee('Booked by HIDDEN');
    }

    public function test_open_slot_renders_short_name_and_cid_when_booked(): void
    {
        SessionBookingSlot::create([
            'session_type' => SessionBookingSlot::TYPE_OPEN_SLOT,
            'title' => 'Open Visibility Slot',
            'scheduled_for' => Carbon::create(2026, 2, 15, 11, 0, 0),
            'duration_minutes' => 90,
            'picked_up_by_name' => 'Tristan Shaw',
            'picked_up_by_cid' => 1669679,
            'picked_up_role' => 'mentor',
            'picked_up_at' => now(),
        ]);

        $response = $this->get(route('site.atc.bookings.calendar', ['month' => '2026-02']));

        $response->assertOk();
        $response->assertSee('Booked by Tristan S (1669679)');
    }


    public function test_open_slot_pickup_requires_cid(): void
    {
        $slot = SessionBookingSlot::create([
            'session_type' => SessionBookingSlot::TYPE_OPEN_SLOT,
            'title' => 'CID required slot',
            'scheduled_for' => now()->addDay(),
            'duration_minutes' => 60,
        ]);

        $response = $this->from(route('site.atc.bookings.calendar'))->post(route('site.atc.bookings.pickup', $slot), [
            'picked_up_by_name' => 'Taylor Mentor',
            'picked_up_by_email' => 'taylor@example.com',
            'picked_up_role' => 'mentor',
        ]);

        $response->assertRedirect(route('site.atc.bookings.calendar'));
        $response->assertSessionHasErrors('picked_up_by_cid');
    }


    public function test_logged_in_user_can_book_directly_from_calendar(): void
    {
        $account = Account::factory()->create([
            'id' => 1669679,
            'name_first' => 'Tristan',
            'name_last' => 'Shaw',
            'email' => 'tristan@example.com',
        ]);

        $slot = SessionBookingSlot::create([
            'session_type' => SessionBookingSlot::TYPE_OPEN_SLOT,
            'title' => 'Direct booking slot',
            'scheduled_for' => now()->addDay(),
            'duration_minutes' => 60,
        ]);

        $response = $this->actingAs($account)->post(route('site.atc.bookings.pickup', $slot), [
            'picked_up_role' => 'mentor',
        ]);

        $response->assertRedirect();
        $slot = $slot->fresh();
        $this->assertSame('Tristan Shaw', $slot->picked_up_by_name);
        $this->assertSame('tristan@example.com', $slot->picked_up_by_email);
        $this->assertSame('1669679', (string) $slot->picked_up_by_cid);
    }

}
