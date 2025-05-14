<?php

namespace App\Models\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventsCalendar extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

}
