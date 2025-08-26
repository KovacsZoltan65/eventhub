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
        Schema::create('events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->dateTime('starts_at')->index();
            $t->string('location')->index();
            $t->unsignedInteger('capacity');
            //$t->unsignedInteger('remaining_seats')->virtualAs('capacity - (select coalesce(sum(quantity),0) from bookings b where b.event_id = events.id and b.status="confirmed")');
            //$t->unsignedInteger('remaining_seats');
            $t->string('category')->nullable()->index();
            $t->enum('status', ['draft','published','cancelled'])->default('draft')->index();
            $t->timestamps();
            
            $t->index(['status','category','location','starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
