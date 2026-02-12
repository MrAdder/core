@extends('layout')

@section('content')
    @php($calendarMonth = isset($month) ? $month : now()->startOfMonth())
    @php($calendarDaysInMonth = isset($daysInMonth) ? $daysInMonth : collect(range(1, $calendarMonth->daysInMonth))->map(fn (int $day) => $calendarMonth->copy()->day($day)))
    @php($calendarSlotsByDate = isset($slotsByDate) ? $slotsByDate : collect())
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-ukblue">
                <div class="panel-heading">
                    <i class="glyphicon glyphicon-calendar"></i> &thinsp; Mentor &amp; Examiner Session Calendar
                </div>
                <div class="panel-body">
                    <p>
                        Public calendar for available mentoring sessions, exams, and open training slots.
                        Mentors and examiners can pick up unassigned sessions directly from this page.
                    </p>

                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    @if ($errors->has('pickup'))
                        <div class="alert alert-danger">{{ $errors->first('pickup') }}</div>
                    @endif

                    <div class="row" style="margin-bottom: 20px;">
                        <div class="col-sm-4">
                            <a class="btn btn-default" href="{{ route('site.atc.bookings.calendar', ['month' => $calendarMonth->copy()->subMonth()->format('Y-m')]) }}">
                                &larr; Previous Month
                            </a>
                        </div>
                        <div class="col-sm-4 text-center">
                            <strong>{{ $calendarMonth->format('F Y') }}</strong>
                        </div>
                        <div class="col-sm-4 text-right">
                            <a class="btn btn-default" href="{{ route('site.atc.bookings.calendar', ['month' => $calendarMonth->copy()->addMonth()->format('Y-m')]) }}">
                                Next Month &rarr;
                            </a>
                        </div>
                    </div>

                    @foreach ($calendarDaysInMonth as $date)
                        @php($slots = $calendarSlotsByDate->get($date->toDateString(), collect()))
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <strong>{{ $date->format('D j M') }}</strong>
                                @if ($slots->isEmpty())
                                    <span class="text-muted">&ndash; No sessions published</span>
                                @endif
                            </div>
                            @if ($slots->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-striped" style="margin-bottom: 0;">
                                        <thead>
                                            <tr>
                                                <th>Time (UTC)</th>
                                                <th>Type</th>
                                                <th>Session</th>
                                                <th>Role Restriction</th>
                                                <th>Status</th>
                                                <th style="width: 320px;">Pick up</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($slots as $slot)
                                                <tr>
                                                    <td>
                                                        {{ $slot->scheduled_for->format('H:i') }}
                                                        &ndash;
                                                        {{ $slot->scheduled_for->copy()->addMinutes($slot->duration_minutes)->format('H:i') }}
                                                    </td>
                                                    <td>{{ $slot->isExam() ? 'Exam' : ($slot->isMentorSession() ? 'Mentor Session' : 'Open Slot') }}</td>
                                                    <td>
                                                        <strong>{{ $slot->title }}</strong>
                                                        @if ($slot->notes)
                                                            <br><small class="text-muted">{{ $slot->notes }}</small>
                                                        @endif
                                                    </td>
                                                    <td>{{ $slot->roleRestrictionLabel() }}</td>
                                                    <td>
                                                        @if ($slot->isPickedUp())
                                                            <span class="label label-success">
                                                                Booked by {{ $slot->publicBookedByLabel() }}
                                                            </span>
                                                        @else
                                                            <span class="label label-warning">Open</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($slot->isPickedUp())
                                                            <span class="text-muted">Already assigned</span>
                                                        @else
                                                            <form method="POST" action="{{ route('site.atc.bookings.pickup', $slot) }}">
                                                                @csrf
                                                                <div class="form-group" style="margin-bottom: 6px;">
                                                                    <input type="text" name="picked_up_by_name" class="form-control input-sm" placeholder="Your name" required>
                                                                </div>
                                                                <div class="form-group" style="margin-bottom: 6px;">
                                                                    <input type="email" name="picked_up_by_email" class="form-control input-sm" placeholder="Your email" required>
                                                                </div>

                                                                @if ($slot->isOpenSlot())
                                                                    <div class="form-group" style="margin-bottom: 6px;"> 
                                                                        <input type="text" name="picked_up_by_cid" class="form-control input-sm" placeholder="Your CID" inputmode="numeric" pattern="[0-9]{6,10}" required>
                                                                    </div>
                                                                @endif
                                                                <div class="form-group" style="margin-bottom: 6px;">
                                                                    <select name="picked_up_role" class="form-control input-sm" required>
                                                                        <option value="">Select role</option>
                                                                        @if (! $slot->isExam())
                                                                            <option value="mentor">Mentor</option>
                                                                        @endif
                                                                        @if (! $slot->isMentorSession())
                                                                            <option value="examiner">Examiner</option>
                                                                        @endif
                                                                    </select>
                                                                </div>
                                                                <button type="submit" class="btn btn-primary btn-sm">Pick up session</button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@stop
