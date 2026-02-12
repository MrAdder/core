<?php

namespace App\Http\Controllers\Site;

use App\Models\Cts\Booking;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ATCPagesController extends \App\Http\Controllers\BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->addBreadcrumb('ATC', route('site.atc.landing'));
    }

    public function viewLanding()
    {
        $this->setTitle('ATC Training');

        return $this->viewMake('site.atc.landing');
    }

    public function viewNewController()
    {
        $this->setTitle('New Controller');
        $this->addBreadcrumb('New Controller', route('site.atc.newController'));

        return $this->viewMake('site.atc.newcontroller');
    }

    public function viewEndorsements()
    {
        $this->setTitle('ATC Endorsements');
        $this->addBreadcrumb('Endorsements', route('site.atc.endorsements'));

        return $this->viewMake('site.atc.endorsements');
    }

    public function viewHeathrow()
    {
        $this->setTitle('Heathrow Endorsements');
        $this->addBreadcrumb('Heathrow Endorsements', route('site.atc.heathrow'));

        return $this->viewMake('site.atc.heathrow');
    }

    public function viewBecomingAMentor()
    {
        $this->setTitle('Becoming a Mentor');
        $this->addBreadcrumb('Becoming a Mentor', route('site.atc.mentor'));

        return $this->viewMake('site.atc.mentor');
    }

    public function viewBookings()
    {
        $this->setTitle('Bookings');
        $this->addBreadcrumb('Bookings', route('site.atc.bookings'));

        return $this->viewMake('site.atc.bookings');
    }

    public function storeBooking(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'position' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]{4}_[A-Z0-9_]{3,15}$/'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.Carbon::now()->addDays(90)->toDateString()],
            'from' => ['required', 'date_format:H:i'],
            'to' => ['required', 'date_format:H:i', 'after:from'],
            'type' => ['required', Rule::in(['BK', 'ME'])],
        ]);

        $member = $request->user()->member;

        if (! $member) {
            return back()->withErrors(['booking' => 'Your account is not linked to CTS, so a booking could not be created.'])->withInput();
        }

        $overlappingBooking = Booking::query()
            ->where('date', $validated['date'])
            ->where('position', $validated['position'])
            ->where('from', '<', $validated['to'])
            ->where('to', '>', $validated['from'])
            ->exists();

        if ($overlappingBooking) {
            return back()->withErrors(['booking' => 'That slot has already been booked for this position.'])->withInput();
        }

        Booking::query()->create([
            'date' => $validated['date'],
            'from' => $validated['from'],
            'to' => $validated['to'],
            'position' => $validated['position'],
            'member_id' => $member->id,
            'type' => $validated['type'],
            'type_id' => 0,
        ]);

        return redirect()->route('site.atc.bookings')->with('booking_status', 'Booking created successfully.');
    }
}
