<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_booking_slots', function (Blueprint $table): void {
            $table->id();
            $table->string('session_type'); // exam | mentor_session | open_slot
            $table->string('title');
            $table->dateTime('scheduled_for');
            $table->unsignedSmallInteger('duration_minutes')->default(90);
            $table->text('notes')->nullable();
            $table->string('picked_up_by_name')->nullable();
            $table->string('picked_up_by_email')->nullable();
            $table->unsignedBigInteger('picked_up_by_cid')->nullable();
            $table->string('picked_up_role')->nullable(); // mentor | examiner
            $table->dateTime('picked_up_at')->nullable();
            $table->timestamps();

            $table->index('scheduled_for');
            $table->index(['session_type', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_booking_slots');
    }
};
