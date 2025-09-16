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
    Schema::create('bookingmeetings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
        $table->foreignId('meeting_with')->constrained('users');
        $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
        $table->string('name');         
        $table->string('position');      
        $table->string('company');       
        $table->date('date');             
        $table->time('start_time');
        $table->time('end_time')->nullable();    
        $table->integer('duration');    
        $table->string('purpose')->nullable();
        $table->string('referral_code', 6)->unique();
        $table->enum('status', ['open', 'in_progress', 'closed', 'cancelled'])->default('open'); 
        $table->timestamp('checked_out_at')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookingmeetings');
    }
};
