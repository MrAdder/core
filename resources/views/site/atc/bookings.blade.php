@extends('layout')

@section('content')
    <div class="row">

        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-ukblue">
                <div class="panel-heading"><i class="glyphicon glyphicon-calendar"></i> &thinsp; Bookings</div>
                <div class="panel-body">
                    @if(session('booking_status'))
                        <div class="alert alert-success">{{ session('booking_status') }}</div>
                    @endif

                    @if($errors->has('booking'))
                        <div class="alert alert-danger">{{ $errors->first('booking') }}</div>
                    @endif

                    <h2>ATC Slot Calendar</h2>
                    <p>
                        The calendar below shows ATC bookings for a 7-day window. Bookings on the current day are
                        automatically hidden once their end time has passed.
                    </p>

                    <div class="row" style="margin-bottom: 15px;">
                        <div class="col-sm-12 text-center">
                            <button class="btn btn-default" id="booking-calendar-prev">&laquo; Previous week</button>
                            <span id="booking-calendar-range" style="display:inline-block; margin: 0 15px; font-weight: 600;"></span>
                            <button class="btn btn-default" id="booking-calendar-next">Next week &raquo;</button>
                        </div>
                    </div>

                    <div class="row" id="booking-calendar-grid"></div>

                    @auth
                        <hr>
                        <h3>Create a booking</h3>
                        <form action="{{ route('site.atc.bookings.store') }}" method="POST" class="form-horizontal">
                            @csrf
                            <div class="row">
                                <div class="col-sm-3 form-group">
                                    <label for="position">Position</label>
                                    <input id="position" name="position" class="form-control" placeholder="EGCC_TWR" value="{{ old('position') }}" required>
                                </div>
                                <div class="col-sm-2 form-group">
                                    <label for="date">Date</label>
                                    <input id="date" type="date" name="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" required>
                                </div>
                                <div class="col-sm-2 form-group">
                                    <label for="from">From</label>
                                    <input id="from" type="time" name="from" class="form-control" value="{{ old('from', '10:00') }}" required>
                                </div>
                                <div class="col-sm-2 form-group">
                                    <label for="to">To</label>
                                    <input id="to" type="time" name="to" class="form-control" value="{{ old('to', '13:00') }}" required>
                                </div>
                                <div class="col-sm-2 form-group">
                                    <label for="type">Type</label>
                                    <select id="type" name="type" class="form-control">
                                        <option value="BK" @selected(old('type', 'BK') === 'BK')>Position booking</option>
                                        <option value="ME" @selected(old('type', 'BK') === 'ME')>Mentoring</option>
                                    </select>
                                </div>
                                <div class="col-sm-1 form-group" style="padding-top: 24px;">
                                    <button class="btn btn-primary btn-block" type="submit">Book</button>
                                </div>
                            </div>
                            @if($errors->any())
                                <p class="text-danger">Please review the booking form fields and try again.</p>
                            @endif
                        </form>
                    @else
                        <p><a href="{{ route('login') }}">Log in</a> to create ATC slot bookings.</p>
                    @endauth

                    <hr>

                    <p>
                        Below are outlined some of the additional rules regarding Controller Bookings. These rules are
                        not designed to prevent you from controlling, but instead to give everyone the opportunity to
                        log on and control. Above all, please remain considerate of others when making controller
                        bookings and ensure that you arrive and control for any bookings you make.
                    </p>

                    <h2>
                        General
                    </h2>

                    <ul>
                        <li>
                            A booking on the CT System shall reserve a position on the VATSIM network during the time
                            period specified. Bookings may synchronise to external sources, however must appear on the
                            CT System in order to be considered valid;
                        </li>
                        <li>
                            A controller may only make a booking if they are currently allowed to control that position
                            and will be allowed to do so at the time booked;
                        </li>
                        <li>
                            A member may make a maximum of six advance bookings up to 90 days in advance at any one time
                            and no more than two of these bookings may be on Gatwick Ground (EGKK_GND) or Gatwick Delivery
                            (EGKK_DEL) in any combination.
                        </li>
                        <li>
                            Members may not book less than two hours in advance unless they are currently controlling that
                            position and are booking to extend their current controlling session.
                        </li>
                        <li>
                            Bookings shall not be excessive in duration (note that an excessive duration at an aerodrome
                            that is more in demand by controllers will be different than at an aerodrome that has
                            lower controller demand);
                        </li>
                        <li>
                            A controller must vacate a position if a member with a valid booking arrives to take over;
                        </li>
                        <li>
                            Members should honour their bookings (including allocated controlling during events) and not
                            book positions they are unlikely to be available for.
                        </li>
                    </ul>

                    <h2>
                        Validity
                    </h2>

                    <ul>
                        <li>
                            A booking ceases to be valid if the member is more than 15 minutes late logging onto the
                            position or voluntarily vacates the position unless specified otherwise below;
                        </li>
                        <li>
                            If a student does not arrive in adequate time for their mentoring session, the mentor may
                            choose to assume the booked time for their own controlling, or nullify the booking.
                            Mentoring session bookings ceases to be valid if the student or mentor has not logged in to
                            control within 30 minutes of the session start time;
                        </li>
                        <li>
                            Bookings&nbsp;may&nbsp;be overridden by mentoring sessions. This includes bookings for
                            underlying splits, for example a GND booking for a TWR mentoring session;
                        </li>
                        <li>
                            Active exam or endorsement bookings remain valid until completed. Such bookings override
                            other controller bookings, including when the session continues beyond the booked time;
                        </li>
                    </ul>

                    <h2>
                        Events
                    </h2>

                    <ul>
                        <li>
                            When approved by the Marketing Director, controllers may be allocated positions to control.
                            Allocated controlling shall take priority over controller bookings.
                        </li>
                    </ul>

                    <h2>
                        Split Positions
                    </h2>

                    <ul>
                        <li>
                            When a position split is opened, the first controller to book may choose the split position
                            they will control, regardless of the positions booked on the CT System, with the exception
                            of:
                            <ul>
                                <li>
                                    Controllers wishing to bandbox two or more primary area sectors should book a
                                    single sector and then log on to the bandboxed position. Other members may then
                                    log on to or book the other Primary or Secondary sectors without the need for
                                    obtaining the first controller&rsquo;s preference;
                                </li>
                                <li>
                                    If a controller does choose to book a position bandboxing the Primary area
                                    sectors, other controllers may choose to log on to or book a contained Primary or
                                    Secondary sector without obtaining the first controller&rsquo;s preference. This
                                    action must leave the bandbox controller with a minimum of one Primary or Secondary
                                    sector to control.
                                </li>
                            </ul>
                        </li>
                    </ul>

                    <p>
                        Primary and Secondary Sectors are defined on the <a href="{{ route('site.operations.sectors') }}">Area Sectors</a> page.
                    </p>

                </div>
            </div>
        </div>

    </div>
@stop

@push('scripts')
<script>
    (function () {
        const calendarGrid = document.getElementById('booking-calendar-grid');
        const rangeLabel = document.getElementById('booking-calendar-range');
        const prevButton = document.getElementById('booking-calendar-prev');
        const nextButton = document.getElementById('booking-calendar-next');

        if (!calendarGrid || !rangeLabel || !prevButton || !nextButton) {
            return;
        }

        const today = new Date();
        let weekOffset = 0;

        function toDateString(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }

        function buildTooltip(booking, dateString) {
            const bookingTypeMap = {
                BK: 'Position booking',
                ME: 'Mentoring',
                EV: 'Event booking',
                EX: 'Exam booking'
            };

            return [
                'Booking Information',
                `Type: ${bookingTypeMap[booking.type] || booking.type}`,
                `Position: ${booking.position}`,
                `Date: ${dateString}`,
                `Book Time: ${booking.from} - ${booking.to}`,
                `Booked By: ${booking.booked_by || 'Hidden'}`
            ].join('\n');
        }

        function renderDay(dayDate, bookings) {
            const dateString = toDateString(dayDate);
            const nowString = `${String(today.getHours()).padStart(2, '0')}:${String(today.getMinutes()).padStart(2, '0')}`;
            const isToday = dateString === toDateString(today);

            const visibleBookings = bookings.filter((booking) => !(isToday && booking.to <= nowString));
            const bookingHtml = visibleBookings.length === 0
                ? '<p class="text-muted">No active bookings.</p>'
                : `<ul class="list-unstyled">${visibleBookings.map((booking) => {
                    const tooltip = buildTooltip(booking, dayDate.toDateString()).replace(/"/g, '&quot;');

                    return `<li style="margin-bottom: 8px;"><span class="label label-primary" title="${tooltip}">${booking.position}<br>${booking.from} - ${booking.to}</span></li>`;
                }).join('')}</ul>`;

            return `
                <div class="col-sm-6 col-md-3" style="margin-bottom: 15px;">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>${dayDate.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' })}</strong></div>
                        <div class="panel-body">${bookingHtml}</div>
                    </div>
                </div>
            `;
        }

        async function fetchBookingsForDate(dateString) {
            const response = await fetch(`/api/cts/bookings?date=${dateString}`);
            if (!response.ok) {
                throw new Error(`Request failed for ${dateString}`);
            }

            const body = await response.json();

            return body.bookings || [];
        }

        async function renderWeek() {
            calendarGrid.innerHTML = '<div class="col-sm-12"><p>Loading bookings…</p></div>';

            const start = new Date(today);
            start.setDate(today.getDate() + (weekOffset * 7));
            start.setHours(0, 0, 0, 0);

            const days = Array.from({ length: 7 }, (_, index) => {
                const day = new Date(start);
                day.setDate(start.getDate() + index);

                return day;
            });

            rangeLabel.textContent = `${days[0].toLocaleDateString()} - ${days[6].toLocaleDateString()}`;

            try {
                const results = await Promise.all(days.map((day) => fetchBookingsForDate(toDateString(day))));
                calendarGrid.innerHTML = days.map((day, index) => renderDay(day, results[index])).join('');
            } catch (error) {
                calendarGrid.innerHTML = '<div class="col-sm-12"><p class="text-danger">Unable to load booking data right now. Please refresh and try again.</p></div>';
            }
        }

        prevButton.addEventListener('click', function () {
            weekOffset -= 1;
            renderWeek();
        });

        nextButton.addEventListener('click', function () {
            weekOffset += 1;
            renderWeek();
        });

        renderWeek();
    })();
</script>
@endpush
