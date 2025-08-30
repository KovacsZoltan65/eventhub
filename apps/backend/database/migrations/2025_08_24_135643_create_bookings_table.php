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
        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $t->unsignedInteger('quantity');
            $t->enum('status',['pending','confirmed','cancelled'])->default('confirmed')->index();
            $t->unsignedInteger('unit_price')->default(0); // ha később lesz ár
            $t->timestamps();

            $t->index(['event_id','status']);
            $t->index(['user_id','event_id']);
            $t->index(['user_id']);
            $t->index(['event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
