<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atc_bookings', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('from');
            $table->time('to');
            $table->string('position', 20);
            $table->string('type', 2);
            $table->unsignedBigInteger('booked_by_cid')->nullable();
            $table->string('booked_by_name')->nullable();
            $table->timestamps();

            $table->index(['date', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atc_bookings');
    }
};
