<?php

namespace App\Models\Atc;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory;

    protected $table = 'atc_bookings';

    protected $guarded = [];
}
