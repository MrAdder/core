@extends('layout')

@section('content')
    @php($calendarMonth = isset($month) ? $month : now()->startOfMonth())
    @php($calendarDaysInMonth = isset($daysInMonth) ? $daysInMonth : collect(range(1, $calendarMonth->daysInMonth))->map(fn (int $day) => $calendarMonth->copy()->day($day)))
    @php($calendarSlotsByDate = isset($slotsByDate) ? $slotsByDate : collect())
    @php($calendarFirstDayOffset = isset($firstDayOffset) ? $firstDayOffset : ($calendarMonth->copy()->startOfMonth()->dayOfWeekIso - 1))

    <style>
        .booking-calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 12px;
        }

        .booking-calendar-day-header,
        .booking-calendar-day {
            border: 1px solid #d8d8d8;
            border-radius: 4px;
            background: #fff;
        }

        .booking-calendar-day-header {
            font-weight: 700;
            text-align: center;
            padding: 8px 6px;
            background: #f5f5f5;
        }

        .booking-calendar-day {
            min-height: 180px;
            padding: 8px;
        }

        .booking-calendar-day.muted {
            background: #fafafa;
            border-style: dashed;
        }

        .booking-calendar-date {
            font-weight: 700;
            margin-bottom: 8px;
        }

        .booking-calendar-slot {
            border: 1px solid #e2e2e2;
            border-radius: 4px;
            padding: 6px;
            margin-bottom: 8px;
            background: #fcfcfc;
            font-size: 12px;
        }

        .booking-calendar-slot-title {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .booking-calendar-actions form {
            display: inline-block;
            margin-top: 6px;
        }
    </style>

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-ukblue">
                <div class="panel-heading">
                    <i class="glyphicon glyphicon-calendar"></i> &thinsp; Mentor &amp; Examiner Session Calendar
                </div>
                <div class="panel-body">
                    <p>
                        Public calendar for available mentoring sessions, exams, and open training slots.
                        @auth
                            You are signed in, so you can book eligible open slots directly from this page.
                        @else
                            Sign in to one-click book; guests can still submit booking details manually.
                        @endauth
                    </p>

                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="row" style="margin-bottom: 16px;">
                        <div class="col-sm-4">
                            <a class="btn btn-default" href="{{ route('site.atc.bookings.calendar', ['month' => $calendarMonth->copy()->subMonth()->format('Y-m')]) }}">
                                &larr; Previous Month
                            </a>
                        </div>
                        <div class="col-sm-4 text-center" style="padding-top: 6px;">
                            <strong>{{ $calendarMonth->format('F Y') }}</strong>
                        </div>
                        <div class="col-sm-4 text-right">
                            <a class="btn btn-default" href="{{ route('site.atc.bookings.calendar', ['month' => $calendarMonth->copy()->addMonth()->format('Y-m')]) }}">
                                Next Month &rarr;
                            </a>
                        </div>
                    </div>

                    <div class="booking-calendar-grid">
                        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dow)
                            <div class="booking-calendar-day-header">{{ $dow }}</div>
                        @endforeach

                        @for ($i = 0; $i < $calendarFirstDayOffset; $i++)
                            <div class="booking-calendar-day muted"></div>
                        @endfor

                        @foreach ($calendarDaysInMonth as $date)
                            @php($slots = $calendarSlotsByDate->get($date->toDateString(), collect()))
                            <div class="booking-calendar-day">
                                <div class="booking-calendar-date">{{ $date->format('j M') }}</div>

                                @forelse ($slots as $slot)
                                    <div class="booking-calendar-slot">
                                        <div class="booking-calendar-slot-title">{{ $slot->title }}</div>
                                        <div>{{ $slot->scheduled_for->format('H:i') }}&ndash;{{ $slot->scheduled_for->copy()->addMinutes($slot->duration_minutes)->format('H:i') }} UTC</div>
                                        <div>{{ $slot->isExam() ? 'Exam' : ($slot->isMentorSession() ? 'Mentor Session' : 'Open Slot') }}</div>
                                        <div><small class="text-muted">{{ $slot->roleRestrictionLabel() }}</small></div>

                                        @if ($slot->isPickedUp())
                                            <div><span class="label label-success">Booked by {{ $slot->publicBookedByLabel() }}</span></div>
                                        @else
                                            <div><span class="label label-warning">Open</span></div>
                                            <div class="booking-calendar-actions">
                                                @auth
                                                    @if (! $slot->isExam())
                                                        <form method="POST" action="{{ route('site.atc.bookings.pickup', $slot) }}">
                                                            @csrf
                                                            <input type="hidden" name="picked_up_role" value="mentor">
                                                            <button type="submit" class="btn btn-xs btn-primary">Book as Mentor</button>
                                                        </form>
                                                    @endif

                                                    @if (! $slot->isMentorSession())
                                                        <form method="POST" action="{{ route('site.atc.bookings.pickup', $slot) }}">
                                                            @csrf
                                                            <input type="hidden" name="picked_up_role" value="examiner">
                                                            <button type="submit" class="btn btn-xs btn-info">Book as Examiner</button>
                                                        </form>
                                                    @endif
                                                @else
                                                    <form method="POST" action="{{ route('site.atc.bookings.pickup', $slot) }}" style="margin-top: 6px;">
                                                        @csrf
                                                        <input type="text" name="picked_up_by_name" class="form-control input-sm" placeholder="Your name" required style="margin-bottom: 4px;">
                                                        <input type="email" name="picked_up_by_email" class="form-control input-sm" placeholder="Your email" required style="margin-bottom: 4px;">
                                                        @if ($slot->isOpenSlot())
                                                            <input type="text" name="picked_up_by_cid" class="form-control input-sm" placeholder="Your CID" inputmode="numeric" pattern="[0-9]{6,10}" required style="margin-bottom: 4px;">
                                                        @endif
                                                        <select name="picked_up_role" class="form-control input-sm" required style="margin-bottom: 4px;">
                                                            <option value="">Select role</option>
                                                            @if (! $slot->isExam())
                                                                <option value="mentor">Mentor</option>
                                                            @endif
                                                            @if (! $slot->isMentorSession())
                                                                <option value="examiner">Examiner</option>
                                                            @endif
                                                        </select>
                                                        <button type="submit" class="btn btn-xs btn-primary">Book slot</button>
                                                    </form>
                                                @endauth
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <small class="text-muted">No sessions</small>
                                @endforelse
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
