<?php

namespace App\Http\Controllers\Api;

use App\Models\Atc\Booking;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

class CtsController
{
    public function getBookings(Request $request)
    {
        $rateLimitKey = 'get-bookings:'.$request->ip();
        $maxAttempts = 30;

        if (app()->environment() !== 'development' && RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'message' => 'Too many requests. Please try again after '.$seconds.' seconds.',
            ], Response::HTTP_TOO_MANY_REQUESTS)
                ->header('Retry-After', $seconds)
                ->header('X-RateLimit-Reset', now()->addSeconds($seconds)->getTimestamp());
        }

        RateLimiter::hit($rateLimitKey, 300); // 5 minutes

        $date = Carbon::now()->startOfDay();
        $requestedDate = $request->get('date');

        if ($requestedDate) {
            try {
                $date = Carbon::parse($requestedDate);

                if ($date->isPast() && $date->diffInDays(Carbon::now()) > 30) {
                    return response()->json([
                        'message' => 'Date is too far in the past. Please use a date within the last 30 days. Oldest date allowed: '.Carbon::now()->subDays(30)->toDateString(),
                    ], Response::HTTP_BAD_REQUEST);
                }
            } catch (InvalidFormatException $e) {
                return response()->json([
                    'message' => 'Invalid date format. Please use YYYY-MM-DD.',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $bookings = Booking::query()
            ->whereDate('date', $date->toDateString())
            ->orderBy('from')
            ->get();

        return response()->json([
            'bookings' => $bookings->map(function (Booking $booking) {
                return [
                    'id' => $booking->id,
                    'date' => Carbon::parse($booking->date)->toDateString(),
                    'from' => Carbon::parse($booking->from)->format('H:i'),
                    'to' => Carbon::parse($booking->to)->format('H:i'),
                    'position' => $booking->position,
                    'type' => $booking->type,
                    'booked_by' => $this->buildPublicBookedBy($booking),
                ];
            }),
            'date' => $date->toDateString(),
            'count' => $bookings->count(),
            'next_page_url' => $this->generateNextPageUrl($date),
            'previous_page_url' => $this->generatePreviousPageUrl($date),
        ]);
    }

    private function buildPublicBookedBy(Booking $booking): string
    {
        if ($booking->type !== 'BK') {
            return 'Hidden';
        }

        if (! $booking->booked_by_name || ! $booking->booked_by_cid) {
            return 'Hidden';
        }

        return $this->formatPublicBookingName($booking->booked_by_name).' '.$booking->booked_by_cid;
    }

    private function formatPublicBookingName(string $bookedByName): string
    {
        $nameParts = preg_split('/\s+/', trim($bookedByName)) ?: [];

        if (count($nameParts) < 2) {
            return $bookedByName;
        }

        $firstName = $nameParts[0];
        $surnameInitial = strtoupper(substr(end($nameParts), 0, 1));

        return sprintf('%s %s.', $firstName, $surnameInitial);
    }

    private function generateNextPageUrl(Carbon $date): string
    {
        return route('api.cts.bookings').'?date='.$date->copy()->addDay()->toDateString();
    }

    private function generatePreviousPageUrl(Carbon $date): ?string
    {
        $previousDate = $date->copy()->subDay();

        if ($previousDate->diffInDays(Carbon::now()) > 30 && $previousDate->isPast()) {
            return null;
        }

        return route('api.cts.bookings').'?date='.$previousDate->toDateString();
    }
}
