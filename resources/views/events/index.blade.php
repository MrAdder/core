@extends('layout')

@section('content')
    <h1>Events</h1>
    <a href="{{ route('events_roster.create') }}">Create Event</a>
    <ul>
        @foreach ($rosters as $roster)
            <li>
                <a href="{{ route('events_roster.show', $event) }}">{{ $event->title }}</a>
                <form action="{{ route('events_roster.destroy', $event) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit">Delete</button>
                </form>
            </li>
        @endforeach
    </ul>
@endsection