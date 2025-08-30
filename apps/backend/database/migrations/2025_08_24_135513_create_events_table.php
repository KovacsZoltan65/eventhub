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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at')->index();
            $table->string('location')->index();
            $table->unsignedInteger('capacity');
            //$t->unsignedInteger('remaining_seats')->virtualAs('capacity - (select coalesce(sum(quantity),0) from bookings b where b.event_id = events.id and b.status="confirmed")');
            //$t->unsignedInteger('remaining_seats');
            $table->string('category')->nullable()->index();
            $table->enum('status', ['draft','published','cancelled'])->default('draft')->index();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['status','category','location','starts_at']);
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
