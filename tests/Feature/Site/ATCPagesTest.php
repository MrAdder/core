<?php

namespace Tests\Feature\Site;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ATCPagesTest extends TestCase
{
    #[Test]
    public function test_it_loads_the_landing_page()
    {
        $this->get(route('site.atc.landing'))->assertOk();
    }

    #[Test]
    public function test_it_loads_the_new_controller_page()
    {
        $this->get(route('site.atc.newController'))->assertOk();
    }

    #[Test]
    public function test_it_loads_the_endorsements_page()
    {
        $this->get(route('site.atc.endorsements'))->assertOk();
    }

    #[Test]
    public function test_it_loads_the_heathrow_page()
    {
        $this->get(route('site.atc.heathrow'))->assertOk();
    }

    #[Test]
    public function test_it_loads_the_becoming_a_mentor_page()
    {
        $this->get(route('site.atc.mentor'))->assertOk();
    }

    #[Test]
    public function test_it_loads_the_bookings_page()
    {
        $this->get(route('site.atc.bookings'))->assertOk();
    }

    #[Test]
    public function guests_are_prompted_to_log_in_on_the_bookings_page()
    {
        $this->get(route('site.atc.bookings'))
            ->assertOk()
            ->assertSee('Log in')
            ->assertDontSee('Create a booking');
    }

    #[Test]
    public function logged_in_users_can_see_the_booking_form_on_the_public_page()
    {
        $this->actingAs($this->user)
            ->get(route('site.atc.bookings'))
            ->assertOk()
            ->assertSee('Create a booking')
            ->assertSee(route('site.atc.bookings.store'));
    }

    #[Test]
    public function bookings_page_renders_calendar_script_block()
    {
        $this->get(route('site.atc.bookings'))
            ->assertOk()
            ->assertSee('const bookingsApiEndpoint', false);
    }
}
