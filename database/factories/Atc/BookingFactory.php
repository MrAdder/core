<?php

namespace Database\Factories\Atc;

use App\Models\Atc\Booking;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $from = $this->faker->time('H:i:s');

        return [
            'date' => $this->faker->dateTimeInInterval('+7 days')->format('Y-m-d'),
            'from' => $from,
            'to' => Carbon::createFromTimeString($from)->addHours(rand(1, 4))->format('H:i:s'),
            'position' => $this->faker->randomElement(['EGKK_APP', 'EGCC_APP', 'LON_SC_CTR', 'EGGP_GND']),
            'type' => $this->faker->randomElement(['BK', 'ME', 'EV', 'EX']),
            'booked_by_cid' => $this->faker->numberBetween(100000, 9999999),
            'booked_by_name' => $this->faker->name,
        ];
    }
}
