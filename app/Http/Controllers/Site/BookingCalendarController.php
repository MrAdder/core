<?php

namespace App\Http\Controllers\Site;

use App\Models\Training\SessionBookingSlot;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingCalendarController extends \App\Http\Controllers\BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->addBreadcrumb('ATC', route('site.atc.landing'));
        $this->addBreadcrumb('Bookings', route('site.atc.bookings'));
        $this->addBreadcrumb('Mentor & Examiner Calendar', route('site.atc.bookings.calendar'));
    }

    public function index(Request $request)
    {
        $month = $this->resolveMonth($request->string('month')->toString());

        $slots = SessionBookingSlot::query()
            ->whereBetween('scheduled_for', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->orderBy('scheduled_for')
            ->get()
            ->reject(fn (SessionBookingSlot $slot): bool => $slot->hasEndedForPublic())
            ->groupBy(fn (SessionBookingSlot $slot): string => $slot->scheduled_for->toDateString());

        $daysInMonth = collect(range(1, $month->daysInMonth))
            ->map(fn (int $day): Carbon => $month->copy()->day($day));

        $this->setTitle('Mentor & Examiner Booking Calendar');

        return $this->viewMake('site.atc.bookings-calendar', [
            'month' => $month,
            'slotsByDate' => $slots,
            'daysInMonth' => $daysInMonth,
            'firstDayOffset' => $month->copy()->startOfMonth()->dayOfWeekIso - 1,
        ]);
    }

    public function pickup(Request $request, SessionBookingSlot $sessionBookingSlot): RedirectResponse
    {
        $validated = $request->validate([
            'picked_up_by_name' => ['nullable', 'string', 'max:100'],
            'picked_up_by_email' => ['nullable', 'email', 'max:255'],
            'picked_up_role' => ['required', 'in:mentor,examiner'],
            'picked_up_by_cid' => [
                Rule::requiredIf(fn (): bool => $sessionBookingSlot->isOpenSlot()),
                'nullable',
                'digits_between:6,10',
            ],
        ]);

        if ($sessionBookingSlot->hasEndedForPublic()) {
            return back()->withErrors(['pickup' => 'This slot has already ended (Zulu/UTC) and can no longer be booked.']);
        }

        if ($sessionBookingSlot->isPickedUp()) {
            return back()->withErrors(['pickup' => 'This session has already been picked up.']);
        }

        if (! $sessionBookingSlot->canBePickedUpBy($validated['picked_up_role'])) {
            return back()->withErrors(['pickup' => 'This session can only be picked up by: '.$sessionBookingSlot->roleRestrictionLabel().'.']);
        }

        $account = auth()->user();

        if ($account) {
            $validated['picked_up_by_name'] = trim(($account->name_first ?? '').' '.($account->name_last ?? ''));
            $validated['picked_up_by_email'] = $account->email;

            if ($sessionBookingSlot->isOpenSlot()) {
                $validated['picked_up_by_cid'] = (string) $account->id;
            }
        }

        $sessionBookingSlot->update([
            ...$validated,
            'picked_up_at' => now(),
        ]);

        return back()->with('status', 'Session picked up successfully.');
    }

    private function resolveMonth(string $month): Carbon
    {
        if ($month === '') {
            return now()->startOfMonth();
        }

        try {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (InvalidFormatException) {
            return now()->startOfMonth();
        }
    }
}
