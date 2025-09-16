<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('minute_of_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_meeting_id')
                ->constrained('bookingmeetings')
                ->onDelete('cascade');
            $table->longText('details'); // isi MOM (pakai Quill)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minute_of_meetings');
    }
};
