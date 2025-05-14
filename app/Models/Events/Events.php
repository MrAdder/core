<?php

namespace App\Models\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Events extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'start_time', 'end_time'];

    public function rosters()
    {
        return $this->hasMany(EventsRoster::class);
    }
}
